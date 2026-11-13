<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CountryConfig;
use App\Models\Quote;
use App\Models\SubscriptionPlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    /**
     * Listar y filtrar cotizaciones con paginación.
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = Quote::query()
            ->with(['customer', 'employee', 'appliedPlan'])
            ->withCount('items');

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(quote_number) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->whereRaw('LOWER(legal_name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['applied_plan_id'])) {
            $query->where('applied_plan_id', $filters['applied_plan_id']);
        }

        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }

        $allowedSorts = ['id', 'quote_number', 'status', 'total', 'created_at'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true) ? $filters['sort_by'] : 'id';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($limit);
    }

    /**
     * Obtener una cotización por ID con sus relaciones.
     */
    public function getById(int $id): Quote
    {
        return Quote::with(['customer', 'employee', 'appliedPlan', 'items'])->findOrFail($id);
    }

    /**
     * Crear una cotización y sus ítems calculando los montos.
     */
    public function create(array $data, int $employeeId): Quote
    {
        return DB::transaction(function () use ($data, $employeeId) {
            $plan = SubscriptionPlan::findOrFail($data['applied_plan_id']);
            $includesVat = (bool) ($data['includes_vat'] ?? false);
            $pointsUsed = (int) ($data['loyalty_points_used'] ?? 0);

            $items = $this->buildItems($data['items']);
            $this->assertLoyaltyPoints($items, $pointsUsed);
            $totals = $this->calculateTotals($items, $plan, $includesVat);

            $quote = Quote::create([
                'quote_number' => $this->generateQuoteNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'employee_id' => $employeeId,
                'applied_plan_id' => $plan->id,
                'base_price' => $totals['base_price'],
                'discounted_subtotal' => $totals['discounted_subtotal'],
                'includes_vat' => $includesVat,
                'loyalty_points_used' => $pointsUsed,
                'loyalty_points_earned' => $totals['loyalty_points_earned'],
                'total' => $totals['total'],
                'status' => $data['status'] ?? 'pending',
            ]);

            $quote->items()->createMany($items);

            $this->logAudit('insert', 'quotes', $quote->id, null, $quote->toArray());

            return $quote->load(['customer', 'employee', 'appliedPlan', 'items']);
        });
    }

    /**
     * Actualizar una cotización. Si se envían ítems, reemplaza el detalle completo.
     */
    public function update(int $id, array $data): Quote
    {
        return DB::transaction(function () use ($id, $data) {
            $quote = Quote::with('items')->findOrFail($id);
            $oldValues = $quote->toArray();

            $plan = array_key_exists('applied_plan_id', $data)
                ? SubscriptionPlan::findOrFail($data['applied_plan_id'])
                : $quote->appliedPlan;

            $includesVat = array_key_exists('includes_vat', $data)
                ? (bool) $data['includes_vat']
                : (bool) $quote->includes_vat;

            $pointsUsed = array_key_exists('loyalty_points_used', $data)
                ? (int) $data['loyalty_points_used']
                : (int) $quote->loyalty_points_used;

            $hasNewItems = array_key_exists('items', $data);
            $items = $hasNewItems
                ? $this->buildItems($data['items'])
                : $this->itemsToArray($quote->items);

            $this->assertLoyaltyPoints($items, $pointsUsed);
            $totals = $this->calculateTotals($items, $plan, $includesVat);

            $quote->fill([
                'customer_id' => array_key_exists('customer_id', $data) ? $data['customer_id'] : $quote->customer_id,
                'applied_plan_id' => $plan->id,
                'base_price' => $totals['base_price'],
                'discounted_subtotal' => $totals['discounted_subtotal'],
                'includes_vat' => $includesVat,
                'loyalty_points_used' => $pointsUsed,
                'loyalty_points_earned' => $totals['loyalty_points_earned'],
                'total' => $totals['total'],
                'status' => $data['status'] ?? $quote->status,
            ]);
            $quote->save();

            if ($hasNewItems) {
                $quote->items()->delete();
                $quote->items()->createMany($items);
            }

            $this->logAudit('update', 'quotes', $quote->id, $oldValues, $quote->fresh()->toArray());

            return $quote->load(['customer', 'employee', 'appliedPlan', 'items']);
        });
    }

    /**
     * Cambiar el estado de una cotización.
     */
    public function updateStatus(int $id, string $status): Quote
    {
        return DB::transaction(function () use ($id, $status) {
            $quote = Quote::findOrFail($id);
            $oldValues = ['status' => $quote->status];

            $quote->status = $status;
            $quote->save();

            $this->logAudit('update', 'quotes', $quote->id, $oldValues, ['status' => $status]);

            return $quote->load(['customer', 'employee', 'appliedPlan', 'items']);
        });
    }

    /**
     * Eliminar cotización (borrado lógico).
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $quote = Quote::findOrFail($id);
            $oldValues = $quote->toArray();

            $quote->delete();

            $this->logAudit('delete', 'quotes', $quote->id, $oldValues, []);
        });
    }

    /**
     * Normalizar ítems entrantes y calcular su total monetario.
     */
    private function buildItems(array $items): array
    {
        return array_map(function (array $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $type = $item['item_type'];

            return [
                'item_type' => $type,
                'service_id' => $type === 'service' ? ($item['service_id'] ?? null) : null,
                'plant_id' => $type === 'plant' ? ($item['plant_id'] ?? null) : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }, $items);
    }

    /**
     * Convertir los ítems persistidos a la estructura usada por los cálculos.
     */
    private function itemsToArray(iterable $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = [
                'item_type' => $item->item_type,
                'service_id' => $item->service_id,
                'plant_id' => $item->plant_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ];
        }

        return $result;
    }

    /**
     * Calcular base, subtotal con descuento del plan, IVA, total y puntos ganados.
     */
    private function calculateTotals(array $items, SubscriptionPlan $plan, bool $includesVat): array
    {
        $basePrice = round(array_sum(array_column($items, 'total')), 2);

        $discountPercentage = (float) ($plan->discount_percentage ?? 0);
        $discountedSubtotal = round($basePrice * (1 - ($discountPercentage / 100)), 2);

        $taxPercentage = $includesVat ? 0.0 : $this->taxPercentage();
        $total = round($discountedSubtotal * (1 + ($taxPercentage / 100)), 2);

        $multiplier = (float) ($plan->loyalty_points_multiplier ?? 0);
        $earned = (int) floor($discountedSubtotal * $multiplier);

        return [
            'base_price' => $basePrice,
            'discounted_subtotal' => $discountedSubtotal,
            'total' => $total,
            'loyalty_points_earned' => $earned,
        ];
    }

    /**
     * Los puntos de lealtad solo aplican si la cotización incluye un servicio completo.
     */
    private function assertLoyaltyPoints(array $items, int $pointsUsed): void
    {
        if ($pointsUsed <= 0) {
            return;
        }

        $hasService = collect($items)->contains(
            fn (array $item) => ($item['item_type'] ?? null) === 'service'
        );

        if (! $hasService) {
            throw ValidationException::withMessages([
                'loyalty_points_used' => ['Los puntos de lealtad solo pueden usarse para cubrir un servicio completo.'],
            ]);
        }
    }

    /**
     * Porcentaje de IVA configurado para El Salvador.
     */
    private function taxPercentage(): float
    {
        $config = CountryConfig::where('country', 'El Salvador')->first();

        return $config ? (float) $config->tax_percentage : 13.00;
    }

    /**
     * Generar un número de cotización único con formato COT-YYYYMMDD-0001.
     */
    private function generateQuoteNumber(): string
    {
        $prefix = 'COT-'.now()->format('Ymd').'-';

        $lastNumber = Quote::withTrashed()
            ->where('quote_number', 'like', $prefix.'%')
            ->orderByDesc('quote_number')
            ->lockForUpdate()
            ->value('quote_number');

        $sequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registrar en la tabla audit_log con el ID de empleado autenticado.
     */
    private function logAudit(string $action, string $tableName, int $recordId, ?array $oldValues, ?array $newValues): void
    {
        $employeeId = request()->user()?->employee_id ?? auth()->user()?->employee_id;

        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $tableName,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => ! empty($oldValues) ? $oldValues : null,
            'new_data' => ! empty($newValues) ? $newValues : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
