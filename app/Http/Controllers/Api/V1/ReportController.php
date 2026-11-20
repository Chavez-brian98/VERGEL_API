<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CollectionsReportRequest;
use App\Http\Requests\Reports\CustomersReportRequest;
use App\Http\Requests\Reports\PlantsReportRequest;
use App\Http\Requests\Reports\ProfitabilityReportRequest;
use App\Http\Requests\Reports\PurchasesReportRequest;
use App\Http\Requests\Reports\QuotesStatusReportRequest;
use App\Http\Requests\Reports\SalesReportRequest;
use App\Http\Requests\Reports\SubscriptionsReportRequest;
use App\Http\Resources\Reports\CollectionReportResource;
use App\Http\Resources\Reports\CustomerRankingResource;
use App\Http\Resources\Reports\PlantRankingResource;
use App\Http\Resources\Reports\ProfitabilityReportResource;
use App\Http\Resources\Reports\PurchaseReportResource;
use App\Http\Resources\Reports\QuoteStatusResource;
use App\Http\Resources\Reports\SalesReportResource;
use App\Http\Resources\Reports\SubscriptionReportResource;
use App\Services\ReportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Reportes
 *
 * Reportes de solo lectura sobre ventas, cobros, compras y suscripciones. Acceso exclusivo para Admin / Administrador.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * Reporte de ventas mensuales.
     *
     * Agrupa las cotizaciones por mes con facturación, cobrado, pendiente y ticket promedio.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam customer_id integer Filtrar por cliente. Example: 1
     *
     * @response status=200 {"data":[{"period":"2026-11","quotesCount":2,"totalSales":300,"totalPaid":200,"totalPending":100,"averageTicket":150}],"meta":{"summary":{"totalQuotes":2,"totalSales":300,"totalPaid":200,"totalPending":100,"averageTicket":150}},"message":"Reporte de ventas generado exitosamente."}
     */
    public function sales(SalesReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->sales($request->validated());

        return SalesReportResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de ventas generado exitosamente.',
        ]);
    }

    /**
     * Ranking de clientes por facturación.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam customer_id integer Filtrar por cliente. Example: 1
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     *
     * @response status=200 {"data":[{"customerId":1,"legalName":"Juan Pérez","quotesCount":2,"totalBilled":300,"totalPaid":200,"totalPending":100}],"meta":{"summary":{"customersCount":1,"totalBilled":300,"totalPaid":200,"totalPending":100}}}
     */
    public function customers(CustomersReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->customers($request->validated(), $this->perPage($request->validated()));

        return CustomerRankingResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de clientes generado exitosamente.',
        ]);
    }

    /**
     * Ranking de plantas vendidas.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam plant_id integer Filtrar por planta. Example: 1
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     */
    public function plants(PlantsReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->plants($request->validated(), $this->perPage($request->validated()));

        return PlantRankingResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de plantas generado exitosamente.',
        ]);
    }

    /**
     * Cobros agrupados por empleado.
     *
     * Incluye el desglose de cada método de pago dentro de `methods`.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam employee_id integer Filtrar por empleado. Example: 1
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     */
    public function collections(CollectionsReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->collections($request->validated(), $this->perPage($request->validated()));

        return CollectionReportResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de cobros generado exitosamente.',
        ]);
    }

    /**
     * Estado de pago de las cotizaciones.
     *
     * Devuelve siempre las tres categorías: pagado, parcial y pendiente.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam customer_id integer Filtrar por cliente. Example: 1
     */
    public function quotesStatus(QuotesStatusReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->quotesStatus($request->validated());

        return QuoteStatusResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de estado de cotizaciones generado exitosamente.',
        ]);
    }

    /**
     * Compras agrupadas por proveedor.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam supplier string Filtrar por nombre del proveedor. Example: Vivero
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     */
    public function purchases(PurchasesReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->purchases($request->validated(), $this->perPage($request->validated()));

        return PurchaseReportResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de compras generado exitosamente.',
        ]);
    }

    /**
     * Rentabilidad mensual por ventas, compras y gastos.
     *
     * Utiliza los movimientos contables con estado completado.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     */
    public function profitability(ProfitabilityReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->profitability($request->validated());

        return ProfitabilityReportResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de rentabilidad generado exitosamente.',
        ]);
    }

    /**
     * Suscripciones agrupadas por plan.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam plan_id integer Filtrar por plan. Example: 1
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     */
    public function subscriptions(SubscriptionsReportRequest $request): AnonymousResourceCollection
    {
        $result = $this->reportService->subscriptions($request->validated(), $this->perPage($request->validated()));

        return SubscriptionReportResource::collection($result['rows'])->additional([
            'meta' => ['summary' => $result['summary']],
            'message' => 'Reporte de suscripciones generado exitosamente.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function perPage(array $validated): int
    {
        return (int) ($validated['per_page'] ?? $validated['limit'] ?? 15);
    }
}
