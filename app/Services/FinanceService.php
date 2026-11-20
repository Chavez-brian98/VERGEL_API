<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plant;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultas de solo lectura del módulo de Finanzas.
 *
 * Reutiliza las tablas existentes (quotes, payments, purchases) sin escribir
 * en ellas: los abonos y compras se registran en Movimientos / Cotizaciones.
 */
class FinanceService
{
    /**
     * Calcula los indicadores financieros sobre el rango de fechas filtrado.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function summary(array $filters = []): array
    {
        $from = $this->dateFilter($filters, 'from');
        $to = $this->dateFilter($filters, 'to');

        $totalProduction = (float) Quote::query()
            ->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->sum('total');

        $paymentsQuery = Payment::query()
            ->when($from, fn (Builder $query) => $query->whereDate('payment_date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('payment_date', '<=', $to));

        $totalPaid = (float) (clone $paymentsQuery)->sum('amount_paid');

        // Abonos que corresponden a cotizaciones liquidadas parcialmente.
        $paidExpression = $this->paidSumExpression();
        $partialPaymentsTotal = (float) (clone $paymentsQuery)
            ->whereHas('quote', function (Builder $query) use ($paidExpression) {
                $query->whereRaw("{$paidExpression} > 0")
                    ->whereRaw("{$paidExpression} < quotes.total");
            })
            ->sum('amount_paid');

        $totalPurchases = (float) (Purchase::query()
            ->when($from, fn (Builder $query) => $query->whereDate('issue_date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('issue_date', '<=', $to))
            ->selectRaw('COALESCE(SUM(taxed_amount + exempt_amount + non_taxable_amount + vat_amount), 0) AS purchases_total')
            ->first()?->purchases_total ?? 0);

        return [
            'totalProduction' => round($totalProduction, 2),
            'totalPaid' => round($totalPaid, 2),
            'totalPending' => round($totalProduction - $totalPaid, 2),
            'partialPaymentsTotal' => round($partialPaymentsTotal, 2),
            'totalPurchases' => round($totalPurchases, 2),
            'profitability' => round($totalPaid - $totalPurchases, 2),
        ];
    }

    /**
     * Lista las cotizaciones con el estado de pago calculado dinámicamente.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listQuotes(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $from = $this->dateFilter($filters, 'from');
        $to = $this->dateFilter($filters, 'to');

        $query = Quote::query()
            ->with('customer')
            ->withSum('payments', 'amount_paid')
            ->when(! empty($filters['customer_id']), fn (Builder $q) => $q->where('customer_id', $filters['customer_id']))
            ->when(! empty($filters['quote_number']), fn (Builder $q) => $q->where('quote_number', 'like', '%'.$filters['quote_number'].'%'))
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));

        $paidExpression = $this->paidSumExpression();

        match ($filters['payment_status'] ?? 'all') {
            'paid' => $query->whereRaw("{$paidExpression} >= quotes.total"),
            'partial' => $query->whereRaw("{$paidExpression} > 0")->whereRaw("{$paidExpression} < quotes.total"),
            'pending' => $query->whereRaw("{$paidExpression} <= 0"),
            default => null,
        };

        return $query->orderByDesc('quotes.created_at')
            ->orderByDesc('quotes.id')
            ->paginate($perPage);
    }

    /**
     * Devuelve la cotización y el historial de abonos asociado.
     *
     * @return array{quote: Quote, payments: Collection<int, Payment>}
     */
    public function getQuotePayments(int $quoteId): array
    {
        $quote = Quote::withSum('payments', 'amount_paid')->findOrFail($quoteId);

        $payments = $quote->payments()
            ->with('employee')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return [
            'quote' => $quote,
            'payments' => $payments,
        ];
    }

    /**
     * Devuelve la planta, su historial de ventas y los totales agregados.
     *
     * @param  array<string, mixed>  $filters
     * @return array{plant: Plant, sales: LengthAwarePaginator, totals: array<string, float|int>}
     */
    public function plantSales(int $plantId, array $filters = [], int $perPage = 15): array
    {
        $plant = Plant::findOrFail($plantId);

        $sales = $this->plantSalesQuery($plantId, $filters)
            ->select('quote_items.*')
            ->with([
                'quote' => fn ($query) => $query->withSum('payments', 'amount_paid'),
                'quote.customer',
            ])
            ->orderByDesc('quotes.created_at')
            ->orderByDesc('quotes.id')
            ->orderByDesc('quote_items.id')
            ->paginate($perPage);

        $totals = $this->plantSalesQuery($plantId, $filters)
            ->selectRaw('COALESCE(SUM(quote_items.quantity), 0) AS units_sold, COALESCE(SUM(quote_items.total), 0) AS sales_total, COUNT(DISTINCT quotes.id) AS quotes_count')
            ->first();

        return [
            'plant' => $plant,
            'sales' => $sales,
            'totals' => [
                'unitsSold' => round((float) ($totals->units_sold ?? 0), 2),
                'salesTotal' => round((float) ($totals->sales_total ?? 0), 2),
                'quotesCount' => (int) ($totals->quotes_count ?? 0),
            ],
        ];
    }

    /**
     * Lista las compras registradas con filtros de fecha y proveedor.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPurchases(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $from = $this->dateFilter($filters, 'from');
        $to = $this->dateFilter($filters, 'to');

        return Purchase::query()
            ->with('employee')
            ->when(! empty($filters['supplier']), fn (Builder $q) => $q->where('supplier_name', 'like', '%'.$filters['supplier'].'%'))
            ->when(! empty($filters['supplier_tax_id']), fn (Builder $q) => $q->where('supplier_tax_id', 'like', '%'.$filters['supplier_tax_id'].'%'))
            ->when(! empty($filters['document_number']), fn (Builder $q) => $q->where('document_number', 'like', '%'.$filters['document_number'].'%'))
            ->when($from, fn (Builder $q) => $q->whereDate('issue_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('issue_date', '<=', $to))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Consulta base de los ítems de planta vendidos, ya filtrada por fecha.
     *
     * @param  array<string, mixed>  $filters
     */
    private function plantSalesQuery(int $plantId, array $filters): Builder
    {
        $from = $this->dateFilter($filters, 'from');
        $to = $this->dateFilter($filters, 'to');

        return QuoteItem::query()
            ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
            ->where('quote_items.plant_id', $plantId)
            ->whereNull('quotes.deleted_at')
            ->when($from, fn (Builder $query) => $query->whereDate('quotes.created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('quotes.created_at', '<=', $to));
    }

    /**
     * Suma total de abonos de una cotización como expresión SQL correlacionada.
     */
    private function paidSumExpression(): string
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
