<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceSummaryRequest;
use App\Http\Requests\Finance\IndexFinanceMovementsRequest;
use App\Http\Requests\Finance\IndexFinancePurchasesRequest;
use App\Http\Requests\Finance\IndexFinanceQuotesRequest;
use App\Http\Requests\Finance\ShowPlantSalesRequest;
use App\Http\Requests\Finance\ShowQuotePaymentsRequest;
use App\Http\Resources\Finance\FinancePaymentResource;
use App\Http\Resources\Finance\FinancePlantSaleResource;
use App\Http\Resources\Finance\FinancePurchaseResource;
use App\Http\Resources\Finance\FinanceQuoteResource;
use App\Http\Resources\Finance\FinanceSummaryResource;
use App\Http\Resources\Movements\MovementCollection;
use App\Services\FinanceService;
use App\Services\MovementService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Finanzas
 *
 * Consultas de solo lectura sobre producción, cobros y compras. Acceso exclusivo para Admin / Administrador.
 */
class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceService $financeService,
        private readonly MovementService $movementService
    ) {}

    /**
     * Consultar indicadores financieros.
     *
     * Calcula producción total, cobrado, pendiente, abonos parciales y rentabilidad para el rango indicado.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     */
    public function summary(FinanceSummaryRequest $request): FinanceSummaryResource
    {
        return new FinanceSummaryResource($this->financeService->summary($request->validated()));
    }

    /**
     * Listar cotizaciones con su estado de pago.
     *
     * El estado se calcula comparando la suma de abonos contra el total de la cotización.
     *
     * @subgroup Consultas
     *
     * @queryParam customer_id integer Filtrar por cliente. Example: 1
     * @queryParam quote_number string Filtrar por número de cotización. Example: COT-2026
     * @queryParam payment_status string Estado de pago (all, paid, partial, pending). Example: partial
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     *
     * @response status=200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "quoteNumber": "COT-2026-0001",
     *       "customerName": "Juan Pérez",
     *       "total": 180.00,
     *       "paidAmount": 80.00,
     *       "pendingAmount": 100.00,
     *       "paymentStatus": {"key": "partial", "label": "Parcial", "badge": "warning"}
     *     }
     *   ]
     * }
     */
    public function index(IndexFinanceQuotesRequest $request): AnonymousResourceCollection
    {
        $limit = (int) ($request->validated('per_page') ?? $request->validated('limit') ?? 15);

        return FinanceQuoteResource::collection($this->financeService->listQuotes($request->validated(), $limit));
    }

    /**
     * Ver el historial de abonos de una cotización.
     *
     * @subgroup Consultas
     *
     * @urlParam quote integer required ID de la cotización. Example: 1
     *
     * @response status=404 {"message": "No query results for model [App\\Models\\Quote] 99"}
     */
    public function payments(ShowQuotePaymentsRequest $request, int $quote): AnonymousResourceCollection
    {
        $result = $this->financeService->getQuotePayments($quote);

        return FinancePaymentResource::collection($result['payments'])->additional([
            'meta' => [
                'quote' => (new FinanceQuoteResource($result['quote']))->resolve(),
            ],
            'message' => 'Abonos de la cotización obtenidos exitosamente.',
        ]);
    }

    /**
     * Listar todos los movimientos de compra, venta y gasto.
     *
     * Reutiliza el libro general de movimientos con el permiso exclusivo de Finanzas.
     *
     * @subgroup Consultas
     *
     * @queryParam movement_type string Tipo de movimiento (all, sale, purchase, expense). Example: purchase
     * @queryParam type string Alias de movement_type. Example: venta
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam search string Buscar por cliente, proveedor, documento o NIT. Example: Vivero
     * @queryParam status string Estado (all, completed, cancelled). Example: completed
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     *
     * @response status=200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "type": "sale",
     *       "typeLabel": "Venta",
     *       "documentNumber": "COT-2026-0001",
     *       "entity": {"name": "Juan Pérez", "role": "customer"},
     *       "date": "2026-11-05",
     *       "amounts": {"total": 108.00}
     *     }
     *   ],
     *   "summary": {"totalSales": 108.00, "totalPurchases": 50.00, "utility": 58.00}
     * }
     */
    public function movements(IndexFinanceMovementsRequest $request): MovementCollection
    {
        $result = $this->movementService->listMovements($request->validated());

        return new MovementCollection($result['paginator'], $result['summary']);
    }

    /**
     * Ver el historial de ventas de una planta.
     *
     * Devuelve los datos de la planta, las cotizaciones donde aparece y totales de unidades y monto vendido.
     *
     * @subgroup Consultas
     *
     * @urlParam plant integer required ID de la planta. Example: 1
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     *
     * @response status=200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "quoteId": 3,
     *       "quoteNumber": "COT-2026-0003",
     *       "customerName": "Juan Pérez",
     *       "saleDate": "2026-11-02T10:00:00.000000Z",
     *       "quantity": 2,
     *       "unitPrice": 25.00,
     *       "total": 50.00,
     *       "paymentStatus": {"key": "partial", "label": "Parcial", "badge": "warning"}
     *     }
     *   ],
     *   "meta": {
     *     "plant": {"id": 1, "name": "Palma", "price": 25.00, "categoryType": "ornamental", "active": true},
     *     "totals": {"unitsSold": 2, "salesTotal": 50.00, "quotesCount": 1}
     *   }
     * }
     * @response status=404 {"message": "No query results for model [App\\Models\\Plant] 99"}
     */
    public function plantSales(ShowPlantSalesRequest $request, int $plant): AnonymousResourceCollection
    {
        $limit = (int) ($request->validated('per_page') ?? $request->validated('limit') ?? 15);

        $result = $this->financeService->plantSales($plant, $request->validated(), $limit);

        return FinancePlantSaleResource::collection($result['sales'])->additional([
            'meta' => [
                'plant' => [
                    'id' => $result['plant']->id,
                    'name' => $result['plant']->name,
                    'price' => (float) $result['plant']->price,
                    'categoryType' => $result['plant']->category_type,
                    'active' => (bool) $result['plant']->active,
                ],
                'totals' => $result['totals'],
            ],
            'message' => 'Historial de ventas de la planta obtenido exitosamente.',
        ]);
    }

    /**
     * Listar compras registradas.
     *
     * @subgroup Consultas
     *
     * @queryParam from string Fecha inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam to string Fecha final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam supplier string Filtrar por nombre del proveedor. Example: Vivero
     * @queryParam supplier_tax_id string Filtrar por NIT del proveedor. Example: 0614-150885-102-3
     * @queryParam per_page integer Registros por página (1-100). Example: 15
     */
    public function purchases(IndexFinancePurchasesRequest $request): AnonymousResourceCollection
    {
        $limit = (int) ($request->validated('per_page') ?? $request->validated('limit') ?? 15);

        return FinancePurchaseResource::collection($this->financeService->listPurchases($request->validated(), $limit));
    }
}
