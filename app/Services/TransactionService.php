<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * Consulta y filtra el historial de transacciones (trabajos ya efectuados y cancelados/declinados).
     * Excluye estrictamente los trabajos en curso o cotizaciones abiertas en estado pending.
     *
     * @param  array<string, mixed>  $filters
     * @return array{paginator: LengthAwarePaginator, summary: array<string, mixed>}
     */
    public function listTransactions(array $filters = []): array
    {
        $query = Quote::withTrashed()
            ->with([
                'customer',
                'employee',
                'plan',
                'items.service',
                'items.plant',
                'payments',
            ]);

        // Regla de Negocio 5.6: Solo transacciones pagadas (efectuadas) o canceladas/declinadas (eliminadas)
        $query->where(function ($q) {
            $q->where('status', 'paid')
                ->orWhereNotNull('deleted_at');
        });

        // Filtro por tipo de estado histórico (opcional si el cliente desea segregar vistas)
        if (! empty($filters['status_type'])) {
            if ($filters['status_type'] === 'performed') {
                $query->where('status', 'paid')->whereNull('deleted_at');
            } elseif ($filters['status_type'] === 'declined') {
                $query->whereNotNull('deleted_at');
            }
        }

        // Buscador Principal General: ID de cliente, número de cotización o nombre (legal/comercial)
        $query->when(! empty($filters['search']), function ($q) use ($filters) {
            $term = $filters['search'];
            $q->where(function ($sub) use ($term) {
                $sub->where('quote_number', 'like', "%{$term}%")
                    ->when(is_numeric($term), function ($numQ) use ($term) {
                        $numQ->orWhere('customer_id', (int) $term);
                    })
                    ->orWhereHas('customer', function ($custQ) use ($term) {
                        $custQ->where('legal_name', 'like', "%{$term}%")
                            ->orWhere('trade_name', 'like', "%{$term}%");
                    });
            });
        });

        // Filtro puntual por cliente
        $query->when(! empty($filters['customer_id']), function ($q) use ($filters) {
            $q->where('customer_id', $filters['customer_id']);
        });

        // Filtro por rango de fechas de registro
        $query->when(! empty($filters['start_date']), function ($q) use ($filters) {
            $q->whereDate('created_at', '>=', $filters['start_date']);
        });

        $query->when(! empty($filters['end_date']), function ($q) use ($filters) {
            $q->whereDate('created_at', '<=', $filters['end_date']);
        });

        // Cálculo del Resumen por Cliente / conjunto filtrado antes de la paginación
        $summaryQuery = clone $query;

        $acceptedCount = (clone $summaryQuery)
            ->where('status', 'paid')
            ->whereNull('deleted_at')
            ->count();

        $declinedCount = (clone $summaryQuery)
            ->whereNotNull('deleted_at')
            ->count();

        $totalAcceptedAmount = (float) ((clone $summaryQuery)
            ->where('status', 'paid')
            ->whereNull('deleted_at')
            ->sum('total'));

        $totalDeclinedAmount = (float) ((clone $summaryQuery)
            ->whereNotNull('deleted_at')
            ->sum('total'));

        $summary = [
            'acceptedCount' => $acceptedCount,
            'declinedCount' => $declinedCount,
            'totalCount' => $acceptedCount + $declinedCount,
            'totalAcceptedAmount' => round($totalAcceptedAmount, 2),
            'totalDeclinedAmount' => round($totalDeclinedAmount, 2),
        ];

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Paginación
        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 15);
        $paginator = $query->paginate($perPage);

        return [
            'paginator' => $paginator,
            'summary' => $summary,
        ];
    }

    /**
     * Obtiene el detalle individual de una transacción histórica mediante su ID.
     * Garantiza que únicamente se pueda consultar si es un trabajo realizado o cancelado.
     *
     * @throws ModelNotFoundException
     */
    public function getTransactionById(int $id): Quote
    {
        return Quote::withTrashed()
            ->with([
                'customer',
                'employee',
                'plan',
                'items.service',
                'items.plant',
                'payments',
            ])
            ->where(function ($q) {
                $q->where('status', 'paid')
                    ->orWhereNotNull('deleted_at');
            })
            ->findOrFail($id);
    }

    /**
     * Registra una cotización existente como una transacción realizada (pagada) en el historial.
     * Respeta el principio de responsabilidad única (SRP): no crea cotizaciones ni ítems,
     * únicamente formaliza el registro de la transacción y sus pagos asociados.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createTransaction(array $data): Quote
    {
        $validator = Validator::make($data, [
            'quote_id' => ['required', 'integer', Rule::exists('quotes', 'id')->withoutTrashed()],
            'amount_paid' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'quote_id.required' => 'El identificador de la cotización es obligatorio para registrar la transacción.',
            'quote_id.exists' => 'La cotización especificada no existe o ha sido eliminada.',
            'amount_paid.numeric' => 'El monto abonado debe ser un valor numérico.',
            'amount_paid.min' => 'El monto abonado debe ser mayor a cero.',
        ]);

        $validated = $validator->validate();

        return DB::transaction(function () use ($validated) {
            $quote = Quote::findOrFail($validated['quote_id']);
            $oldData = $quote->toArray();

            // Si se suministró información de pago, registrar el cobro en la tabla payments
            if (! empty($validated['amount_paid'])) {
                Payment::create([
                    'quote_id' => $quote->id,
                    'employee_id' => auth()->user()?->employee_id ?? $quote->employee_id,
                    'payment_date' => $validated['payment_date'] ?? now(),
                    'amount_paid' => $validated['amount_paid'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                ]);
            }

            // Marcar la cotización como pagada para integrarla al Historial de Transacciones
            $quote->update(['status' => 'paid']);

            $this->logAudit('insert', 'quotes', $quote->id, $oldData, $quote->fresh()->toArray());

            return $quote->fresh([
                'customer',
                'employee',
                'plan',
                'items.service',
                'items.plant',
                'payments',
            ]);
        });
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
