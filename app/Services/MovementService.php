<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ExpenseTicket;
use App\Models\Movement;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MovementService
{
    /**
     * Consulta y filtra el libro general de movimientos (Ventas, Compras y Gastos).
     * Calcula en tiempo real las métricas financieras (Ingresos, Egresos y Utilidad neta).
     *
     * @param  array<string, mixed>  $filters
     * @return array{paginator: LengthAwarePaginator, summary: array<string, mixed>}
     */
    public function listMovements(array $filters = []): array
    {
        $query = Movement::query();

        // Filtro por tipo de movimiento (sale, purchase, expense)
        if (! empty($filters['movement_type']) && $filters['movement_type'] !== 'all') {
            $type = $filters['movement_type'] === 'ticket' ? 'expense' : $filters['movement_type'];
            $query->where('movement_type', $type);
        }

        // Filtro por rango de fechas contables
        if (! empty($filters['start_date'])) {
            $query->whereDate('movement_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('movement_date', '<=', $filters['end_date']);
        }

        // Buscador unificado (Cliente / Proveedor / N° documento / NIT)
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function ($sub) use ($term) {
                $sub->where('entity_name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%")
                    ->orWhere('entity_tax_id', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        // Filtro puntual por nombre de entidad (cliente o proveedor)
        if (! empty($filters['entity_name'])) {
            $query->where('entity_name', 'like', '%'.trim($filters['entity_name']).'%');
        }

        // Filtro por estado del movimiento (completed, cancelled, all)
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        } else {
            // Por defecto solo movimientos vigentes / completados para el cálculo contable
            if (empty($filters['status'])) {
                $query->where('status', 'completed');
            }
        }

        // Cálculo de totales y utilidad en tiempo real sobre el conjunto filtrado (pre-paginación)
        $summaryQuery = clone $query;
        $totals = $summaryQuery
            ->selectRaw("
                COALESCE(SUM(CASE WHEN movement_type = 'sale' THEN total ELSE 0 END), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN movement_type = 'purchase' THEN total ELSE 0 END), 0) AS total_purchases,
                COALESCE(SUM(CASE WHEN movement_type = 'expense' THEN total ELSE 0 END), 0) AS total_expenses,
                COUNT(*) AS total_count
            ")
            ->first();

        $totalSales = round((float) ($totals->total_sales ?? 0), 2);
        $totalPurchases = round((float) ($totals->total_purchases ?? 0), 2);
        $totalExpenses = round((float) ($totals->total_expenses ?? 0), 2);
        $totalIncome = $totalSales;
        $totalOutflow = round($totalPurchases + $totalExpenses, 2);
        $utility = round($totalIncome - $totalOutflow, 2);
        $profitMargin = $totalIncome > 0 ? round(($utility / $totalIncome) * 100, 2) : 0.0;

        $summary = [
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
            'totalExpenses' => $totalExpenses,
            'totalIncome' => $totalIncome,
            'totalOutflow' => $totalOutflow,
            'utility' => $utility,
            'profitMarginPercentage' => $profitMargin,
            'totalCount' => (int) ($totals->total_count ?? 0),
        ];

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'movement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection)->orderBy('id', 'desc');

        // Paginación
        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 15);
        $paginator = $query->with(['employee'])->paginate($perPage);

        return [
            'paginator' => $paginator,
            'summary' => $summary,
        ];
    }

    /**
     * Registra automáticamente una venta en el libro general cuando una cotización es pagada.
     * Garantiza idempotencia estricta para evitar duplicidad de registros contables.
     */
    public function recordSaleFromQuote(Quote $quote, ?Payment $payment = null): ?Movement
    {
        // Control de Idempotencia: Verificar que no haya sido registrada previamente
        $existing = Movement::where('movementable_type', Quote::class)
            ->where('movementable_id', $quote->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $customer = $quote->customer;
        $entityName = $customer?->legal_name ?? $customer?->trade_name ?? 'Cliente General';
        $entityTaxId = $customer?->tax_id ?? $customer?->nrc;

        $total = (float) $quote->total;

        // Desglose fiscal según si incluye IVA
        if ($quote->includes_vat) {
            $amount = round($total / 1.13, 2);
            $vatAmount = round($total - $amount, 2);
        } else {
            $amount = (float) ($quote->discounted_subtotal ?? $quote->base_price ?? $total);
            $vatAmount = 0.00;
        }

        $movementDate = $payment?->payment_date
            ? date('Y-m-d', strtotime((string) $payment->payment_date))
            : now()->toDateString();

        $employeeId = auth()->user()?->employee_id ?? $quote->employee_id ?? Employee::first()?->id;

        $movement = Movement::create([
            'movement_type' => 'sale',
            'movementable_type' => Quote::class,
            'movementable_id' => $quote->id,
            'document_type' => 'Cotización Pagada',
            'document_number' => $quote->quote_number,
            'entity_name' => $entityName,
            'entity_tax_id' => $entityTaxId,
            'movement_date' => $movementDate,
            'amount' => $amount,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'status' => 'completed',
            'notes' => 'Venta originada automáticamente por trabajo efectuado (Cotización '.$quote->quote_number.')',
            'employee_id' => $employeeId,
        ]);

        $this->logAudit('insert', 'movements', $movement->id, null, $movement->toArray());

        return $movement;
    }

    /**
     * Asienta contablemente una compra ya creada en el libro general de movimientos.
     * Método helper invocado por el módulo de Compras tras registrar una compra.
     */
    public function recordPurchase(Purchase $purchase): Movement
    {
        $existing = Movement::where('movementable_type', Purchase::class)
            ->where('movementable_id', $purchase->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $subtotal = round(
            (float) $purchase->taxed_amount +
            (float) $purchase->exempt_amount +
            (float) $purchase->non_taxable_amount,
            2
        );
        $vatAmount = (float) $purchase->vat_amount;
        $total = round($subtotal + $vatAmount, 2);

        $movement = Movement::create([
            'movement_type' => 'purchase',
            'movementable_type' => Purchase::class,
            'movementable_id' => $purchase->id,
            'document_type' => $purchase->document_type,
            'document_number' => $purchase->document_number,
            'entity_name' => $purchase->supplier_name,
            'entity_tax_id' => $purchase->supplier_tax_id,
            'movement_date' => $purchase->issue_date,
            'amount' => $subtotal,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'status' => 'completed',
            'notes' => $purchase->notes,
            'employee_id' => $purchase->employee_id ?? auth()->user()?->employee_id,
        ]);

        $this->logAudit('insert', 'movements', $movement->id, null, $movement->toArray());

        return $movement;
    }

    /**
     * Asienta contablemente un gasto operativo / ticket ya creado en el libro general de movimientos.
     * Método helper invocado por el módulo de Gastos tras registrar un ticket.
     */
    public function recordExpense(ExpenseTicket $ticket): Movement
    {
        $existing = Movement::where('movementable_type', ExpenseTicket::class)
            ->where('movementable_id', $ticket->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $documentNumber = 'TCK-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
        $amount = (float) $ticket->amount;

        $movement = Movement::create([
            'movement_type' => 'expense',
            'movementable_type' => ExpenseTicket::class,
            'movementable_id' => $ticket->id,
            'document_type' => 'Ticket Gasto',
            'document_number' => $documentNumber,
            'entity_name' => $ticket->category,
            'entity_tax_id' => null,
            'movement_date' => $ticket->expense_date,
            'amount' => $amount,
            'vat_amount' => 0.00,
            'total' => $amount,
            'status' => 'completed',
            'notes' => $ticket->description,
            'employee_id' => $ticket->employee_id ?? auth()->user()?->employee_id,
        ]);

        $this->logAudit('insert', 'movements', $movement->id, null, $movement->toArray());

        return $movement;
    }

    /**
     * Registra un evento en la bitácora de auditoría (audit_log).
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employeeId = auth()->user()?->employee_id ?? Employee::first()?->id;
        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
