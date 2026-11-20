<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultas agregadas de solo lectura del módulo de Reportes.
 *
 * Las agrupaciones se resuelven con Eloquent/Query Builder para mantener
 * la compatibilidad entre SQLite (pruebas) y MySQL (producción).
 */
class ReportService
{
    /**
     * Ventas agrupadas por mes.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array<string, float|int>}
     */
    public function sales(array $filters = []): array
    {
        $rows = $this->salesQuery($filters)
            ->selectRaw('SUBSTR(quotes.created_at, 1, 7) AS period')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return ['rows' => $rows, 'summary' => $this->salesSummary($filters)];
    }

    /**
     * Ranking de clientes por facturación.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, summary: array<string, float|int>}
     */
    public function customers(array $filters = [], int $perPage = 15): array
    {
        $query = $this->quotesByCustomerQuery($filters)
            ->groupBy('customers.id', 'customers.legal_name', 'customers.trade_name')
            ->orderByDesc('total_billed')
            ->orderByDesc('customers.id');

        return [
            'rows' => $query->paginate($perPage),
            'summary' => $this->customersSummary($filters),
        ];
    }

    /**
     * Ranking de plantas vendidas.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, summary: array<string, float|int>}
     */
    public function plants(array $filters = [], int $perPage = 15): array
    {
        $query = $this->plantsQuery($filters)
            ->groupBy('plants.id', 'plants.name', 'plants.category_type')
            ->orderByDesc('sales_total')
            ->orderByDesc('plants.id');

        return [
            'rows' => $query->paginate($perPage),
            'summary' => $this->plantsSummary($filters),
        ];
    }

    /**
     * Cobros agrupados por empleado, con desglose por método de pago.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, summary: array<string, float|int>}
     */
    public function collections(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->collectionsQuery($filters)
            ->groupBy('employees.id', 'employees.full_name')
            ->orderByDesc('total_collected')
            ->paginate($perPage);

        $methods = Payment::query()
            ->select('payments.employee_id', 'payments.payment_method')
            ->selectRaw('COUNT(payments.id) AS payments_count')
            ->selectRaw('COALESCE(SUM(payments.amount_paid), 0) AS total_collected')
            ->tap(fn (Builder $query) => $this->applyPaymentFilters($query, $filters))
            ->groupBy('payments.employee_id', 'payments.payment_method')
            ->get()
            ->groupBy(fn ($row) => (string) $row->employee_id);

        foreach ($paginator->items() as $row) {
            $row->methods = $methods->get((string) $row->employee_id, collect())
                ->map(fn ($method) => [
                    'key' => $method->payment_method,
                    'paymentsCount' => (int) $method->payments_count,
                    'totalCollected' => round((float) $method->total_collected, 2),
                ])
                ->values()
                ->all();
        }

        return [
            'rows' => $paginator,
            'summary' => $this->collectionsSummary($filters),
        ];
    }

    /**
     * Distribución de cotizaciones según su estado de pago.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array<string, float|int>}
     */
    public function quotesStatus(array $filters = []): array
    {
        $paidExpression = $this->paidExpression();

        $grouped = Quote::query()
            ->selectRaw("CASE WHEN {$paidExpression} <= 0 THEN 'pending' WHEN {$paidExpression} < quotes.total THEN 'partial' ELSE 'paid' END AS payment_status")
            ->selectRaw('COUNT(*) AS quotes_count')
            ->selectRaw('COALESCE(SUM(quotes.total), 0) AS total_billed')
            ->selectRaw("COALESCE(SUM({$paidExpression}), 0) AS total_paid")
            ->tap(fn (Builder $query) => $this->applyQuoteFilters($query, $filters))
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $rows = collect(['paid', 'partial', 'pending'])->map(function (string $key) use ($grouped) {
            $row = $grouped->get($key);

            return (object) [
                'payment_status' => $key,
                'quotes_count' => (int) ($row->quotes_count ?? 0),
                'total_billed' => (float) ($row->total_billed ?? 0),
                'total_paid' => (float) ($row->total_paid ?? 0),
            ];
        });

        return ['rows' => $rows, 'summary' => $this->quotesStatusSummary($filters)];
    }

    /**
     * Compras agrupadas por proveedor.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, summary: array<string, float|int>}
     */
    public function purchases(array $filters = [], int $perPage = 15): array
    {
        $query = $this->purchasesQuery($filters)
            ->groupBy('purchases.supplier_name', 'purchases.supplier_tax_id', 'purchases.supplier_nrc')
            ->orderByDesc('total')
            ->orderByDesc('purchases.id');

        return [
            'rows' => $query->paginate($perPage),
            'summary' => $this->purchasesSummary($filters),
        ];
    }

    /**
     * Rentabilidad mensual a partir del libro de movimientos.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array<string, float|int>}
     */
    public function profitability(array $filters = []): array
    {
        $rows = $this->profitabilityQuery($filters)
            ->selectRaw('SUBSTR(movements.movement_date, 1, 7) AS period')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($row) {
                $row->utility = round((float) $row->sales - (float) $row->purchases - (float) $row->expenses, 2);
                $row->margin_percentage = (float) $row->sales > 0
                    ? round($row->utility / (float) $row->sales * 100, 2)
                    : 0.0;

                return $row;
            });

        $sales = round((float) $rows->sum('sales'), 2);
        $purchases = round((float) $rows->sum('purchases'), 2);
        $expenses = round((float) $rows->sum('expenses'), 2);
        $utility = round($sales - $purchases - $expenses, 2);

        return [
            'rows' => $rows,
            'summary' => [
                'totalSales' => $sales,
                'totalPurchases' => $purchases,
                'totalExpenses' => $expenses,
                'totalUtility' => $utility,
                'profitMarginPercentage' => $sales > 0 ? round($utility / $sales * 100, 2) : 0.0,
            ],
        ];
    }

    /**
     * Suscripciones agrupadas por plan.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, summary: array<string, float|int>}
     */
    public function subscriptions(array $filters = [], int $perPage = 15): array
    {
        $query = $this->subscriptionsQuery($filters)
            ->groupBy('subscription_plans.id', 'subscription_plans.plan_code', 'subscription_plans.plan_name')
            ->orderByDesc('subscriptions_count')
            ->orderByDesc('subscription_plans.id');

        return [
            'rows' => $query->paginate($perPage),
            'summary' => $this->subscriptionsSummary($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function salesQuery(array $filters): Builder
    {
        $paidExpression = $this->paidExpression();

        return Quote::query()
            ->selectRaw('COUNT(*) AS quotes_count')
            ->selectRaw('COALESCE(SUM(quotes.total), 0) AS total_sales')
            ->selectRaw("COALESCE(SUM({$paidExpression}), 0) AS total_paid")
            ->tap(fn (Builder $query) => $this->applyQuoteFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function salesSummary(array $filters): array
    {
        $totals = $this->salesQuery($filters)->first();
        $sales = round((float) ($totals->total_sales ?? 0), 2);
        $paid = round((float) ($totals->total_paid ?? 0), 2);
        $count = (int) ($totals->quotes_count ?? 0);

        return [
            'totalQuotes' => $count,
            'totalSales' => $sales,
            'totalPaid' => $paid,
            'totalPending' => round($sales - $paid, 2),
            'averageTicket' => $count > 0 ? round($sales / $count, 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function quotesByCustomerQuery(array $filters): Builder
    {
        $paidExpression = $this->paidExpression();

        return Quote::query()
            ->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->whereNull('customers.deleted_at')
            ->select('customers.id AS customer_id', 'customers.legal_name', 'customers.trade_name')
            ->selectRaw('COUNT(quotes.id) AS quotes_count')
            ->selectRaw('COALESCE(SUM(quotes.total), 0) AS total_billed')
            ->selectRaw("COALESCE(SUM({$paidExpression}), 0) AS total_paid")
            ->selectRaw('MAX(quotes.created_at) AS last_sale_date')
            ->tap(fn (Builder $query) => $this->applyQuoteFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function customersSummary(array $filters): array
    {
        $paidExpression = $this->paidExpression();

        $totals = Quote::query()
            ->join('customers', 'customers.id', '=', 'quotes.customer_id')
            ->whereNull('customers.deleted_at')
            ->selectRaw('COUNT(DISTINCT customers.id) AS customers_count')
            ->selectRaw('COALESCE(SUM(quotes.total), 0) AS total_billed')
            ->selectRaw("COALESCE(SUM({$paidExpression}), 0) AS total_paid")
            ->tap(fn (Builder $query) => $this->applyQuoteFilters($query, $filters))
            ->first();

        $billed = round((float) ($totals->total_billed ?? 0), 2);
        $paid = round((float) ($totals->total_paid ?? 0), 2);

        return [
            'customersCount' => (int) ($totals->customers_count ?? 0),
            'totalBilled' => $billed,
            'totalPaid' => $paid,
            'totalPending' => round($billed - $paid, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function plantsQuery(array $filters): Builder
    {
        return QuoteItem::query()
            ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
            ->join('plants', 'plants.id', '=', 'quote_items.plant_id')
            ->where('quote_items.item_type', 'plant')
            ->whereNull('quotes.deleted_at')
            ->whereNull('plants.deleted_at')
            ->select('plants.id AS plant_id', 'plants.name', 'plants.category_type')
            ->selectRaw('COALESCE(SUM(quote_items.quantity), 0) AS units_sold')
            ->selectRaw('COALESCE(SUM(quote_items.total), 0) AS sales_total')
            ->selectRaw('COUNT(DISTINCT quotes.id) AS quotes_count')
            ->selectRaw('COALESCE(AVG(quote_items.unit_price), 0) AS average_unit_price')
            ->tap(fn (Builder $query) => $this->applyQuoteItemFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function plantsSummary(array $filters): array
    {
        $totals = QuoteItem::query()
            ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
            ->join('plants', 'plants.id', '=', 'quote_items.plant_id')
            ->where('quote_items.item_type', 'plant')
            ->whereNull('quotes.deleted_at')
            ->whereNull('plants.deleted_at')
            ->selectRaw('COUNT(DISTINCT plants.id) AS plants_count')
            ->selectRaw('COALESCE(SUM(quote_items.quantity), 0) AS units_sold')
            ->selectRaw('COALESCE(SUM(quote_items.total), 0) AS sales_total')
            ->tap(fn (Builder $query) => $this->applyQuoteItemFilters($query, $filters))
            ->first();

        return [
            'plantsCount' => (int) ($totals->plants_count ?? 0),
            'unitsSold' => round((float) ($totals->units_sold ?? 0), 2),
            'salesTotal' => round((float) ($totals->sales_total ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function collectionsQuery(array $filters): Builder
    {
        return Payment::query()
            ->leftJoin('employees', 'employees.id', '=', 'payments.employee_id')
            ->select('employees.id AS employee_id', 'employees.full_name AS employee_name')
            ->selectRaw('COUNT(payments.id) AS payments_count')
            ->selectRaw('COALESCE(SUM(payments.amount_paid), 0) AS total_collected')
            ->tap(fn (Builder $query) => $this->applyPaymentFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function collectionsSummary(array $filters): array
    {
        $totals = Payment::query()
            ->selectRaw('COUNT(payments.id) AS payments_count')
            ->selectRaw('COALESCE(SUM(payments.amount_paid), 0) AS total_collected')
            ->tap(fn (Builder $query) => $this->applyPaymentFilters($query, $filters))
            ->first();

        return [
            'paymentsCount' => (int) ($totals->payments_count ?? 0),
            'totalCollected' => round((float) ($totals->total_collected ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function quotesStatusSummary(array $filters): array
    {
        $paidExpression = $this->paidExpression();

        $totals = Quote::query()
            ->selectRaw('COUNT(*) AS quotes_count')
            ->selectRaw('COALESCE(SUM(quotes.total), 0) AS total_billed')
            ->selectRaw("COALESCE(SUM({$paidExpression}), 0) AS total_paid")
            ->tap(fn (Builder $query) => $this->applyQuoteFilters($query, $filters))
            ->first();

        $billed = round((float) ($totals->total_billed ?? 0), 2);
        $paid = round((float) ($totals->total_paid ?? 0), 2);

        return [
            'totalQuotes' => (int) ($totals->quotes_count ?? 0),
            'totalBilled' => $billed,
            'totalPaid' => $paid,
            'totalPending' => round($billed - $paid, 2),
            'paidPercentage' => $billed > 0 ? round($paid / $billed * 100, 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function purchasesQuery(array $filters): Builder
    {
        return Purchase::query()
            ->select('purchases.supplier_name', 'purchases.supplier_tax_id', 'purchases.supplier_nrc')
            ->selectRaw('COUNT(purchases.id) AS documents_count')
            ->selectRaw('COALESCE(SUM(purchases.taxed_amount), 0) AS taxed')
            ->selectRaw('COALESCE(SUM(purchases.vat_amount), 0) AS vat')
            ->selectRaw('COALESCE(SUM(purchases.taxed_amount + purchases.exempt_amount + purchases.non_taxable_amount + purchases.vat_amount), 0) AS total')
            ->tap(fn (Builder $query) => $this->applyPurchaseFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function purchasesSummary(array $filters): array
    {
        $totals = Purchase::query()
            ->selectRaw('COUNT(purchases.id) AS documents_count')
            ->selectRaw('COALESCE(SUM(purchases.vat_amount), 0) AS vat')
            ->selectRaw('COALESCE(SUM(purchases.taxed_amount + purchases.exempt_amount + purchases.non_taxable_amount + purchases.vat_amount), 0) AS total')
            ->tap(fn (Builder $query) => $this->applyPurchaseFilters($query, $filters))
            ->first();

        return [
            'documentsCount' => (int) ($totals->documents_count ?? 0),
            'totalVat' => round((float) ($totals->vat ?? 0), 2),
            'totalPurchases' => round((float) ($totals->total ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function profitabilityQuery(array $filters): Builder
    {
        return Movement::query()
            ->where('movements.status', 'completed')
            ->selectRaw("COALESCE(SUM(CASE WHEN movements.movement_type = 'sale' THEN movements.total ELSE 0 END), 0) AS sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN movements.movement_type = 'purchase' THEN movements.total ELSE 0 END), 0) AS purchases")
            ->selectRaw("COALESCE(SUM(CASE WHEN movements.movement_type = 'expense' THEN movements.total ELSE 0 END), 0) AS expenses")
            ->tap(fn (Builder $query) => $this->applyMovementFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function subscriptionsQuery(array $filters): Builder
    {
        return Subscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.plan_id')
            ->whereNull('subscription_plans.deleted_at')
            ->select('subscription_plans.id AS plan_id', 'subscription_plans.plan_code', 'subscription_plans.plan_name')
            ->selectRaw('COUNT(subscriptions.id) AS subscriptions_count')
            ->selectRaw("SUM(CASE WHEN subscriptions.status = 'active' THEN 1 ELSE 0 END) AS active_count")
            ->selectRaw("SUM(CASE WHEN subscriptions.status = 'pending_payment' THEN 1 ELSE 0 END) AS pending_payment_count")
            ->selectRaw("SUM(CASE WHEN subscriptions.status = 'completed' THEN 1 ELSE 0 END) AS completed_count")
            ->selectRaw("SUM(CASE WHEN subscriptions.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count")
            ->selectRaw('COALESCE(SUM(subscriptions.amount_paid), 0) AS total_paid')
            ->selectRaw('COALESCE(SUM(subscriptions.reserved_balance), 0) AS reserved_balance')
            ->tap(fn (Builder $query) => $this->applySubscriptionFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function subscriptionsSummary(array $filters): array
    {
        $totals = Subscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.plan_id')
            ->whereNull('subscription_plans.deleted_at')
            ->selectRaw('COUNT(subscriptions.id) AS subscriptions_count')
            ->selectRaw("SUM(CASE WHEN subscriptions.status = 'active' THEN 1 ELSE 0 END) AS active_count")
            ->selectRaw('COALESCE(SUM(subscriptions.amount_paid), 0) AS total_paid')
            ->selectRaw('COALESCE(SUM(subscriptions.reserved_balance), 0) AS reserved_balance')
            ->tap(fn (Builder $query) => $this->applySubscriptionFilters($query, $filters))
            ->first();

        return [
            'subscriptionsCount' => (int) ($totals->subscriptions_count ?? 0),
            'activeSubscriptions' => (int) ($totals->active_count ?? 0),
            'totalPaid' => round((float) ($totals->total_paid ?? 0), 2),
            'reservedBalance' => round((float) ($totals->reserved_balance ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyQuoteFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['customer_id']), fn (Builder $q) => $q->where('quotes.customer_id', $filters['customer_id']))
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('quotes.created_at', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('quotes.created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyQuoteItemFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['plant_id']), fn (Builder $q) => $q->where('quote_items.plant_id', $filters['plant_id']))
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('quotes.created_at', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('quotes.created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyPaymentFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['employee_id']), fn (Builder $q) => $q->where('payments.employee_id', $filters['employee_id']))
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('payments.payment_date', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('payments.payment_date', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyPurchaseFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['supplier']), fn (Builder $q) => $q->where('purchases.supplier_name', 'like', '%'.$filters['supplier'].'%'))
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('purchases.issue_date', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('purchases.issue_date', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyMovementFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('movements.movement_date', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('movements.movement_date', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySubscriptionFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['plan_id']), fn (Builder $q) => $q->where('subscriptions.plan_id', $filters['plan_id']))
            ->when($this->dateFilter($filters, 'from'), fn (Builder $q, ?string $from) => $q->whereDate('subscriptions.payment_date', '>=', $from))
            ->when($this->dateFilter($filters, 'to'), fn (Builder $q, ?string $to) => $q->whereDate('subscriptions.payment_date', '<=', $to));
    }

    /**
     * Suma de abonos de una cotización como expresión SQL correlacionada.
     */
    private function paidExpression(): string
    {
        return 'COALESCE((SELECT SUM(p.amount_paid) FROM payments AS p WHERE p.quote_id = quotes.id), 0)';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function dateFilter(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
