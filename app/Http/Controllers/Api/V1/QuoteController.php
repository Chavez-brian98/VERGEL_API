<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quote\IndexQuoteRequest;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteStatusRequest;
use App\Http\Resources\Quotes\QuoteListResource;
use App\Http\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @group Cotizaciones
 *
 * APIs para la gestión de cotizaciones, ítems de servicio/planta y cálculo automático de montos.
 */
class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService
    ) {}

    /**
     * Listar y filtrar cotizaciones.
     *
     * Devuelve las cotizaciones paginadas con filtros por texto, estado, cliente, empleado, plan y rango de fechas.
     *
     * @subgroup Consultas
     *
     * @queryParam search string Búsqueda por número de cotización o nombre del cliente. Example: COT-20261111
     * @queryParam status string Filtrar por estado (`pending` o `paid`). Example: pending
     * @queryParam customer_id integer Filtrar por cliente. Example: 1
     * @queryParam employee_id integer Filtrar por empleado que registró la cotización. Example: 1
     * @queryParam applied_plan_id integer Filtrar por plan de suscripción aplicado. Example: 1
     * @queryParam desde string Filtrar cotizaciones creadas desde una fecha (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam hasta string Filtrar cotizaciones creadas hasta una fecha (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam sort_by string Campo de ordenamiento (`id`, `quote_number`, `status`, `total`, `created_at`). Default: `id`. Example: created_at
     * @queryParam sort_direction string Dirección (`asc` o `desc`). Default: `desc`. Example: desc
     * @queryParam limit integer Cantidad de registros por página (1-100). Default: 15. Example: 15
     */
    public function index(IndexQuoteRequest $request): AnonymousResourceCollection
    {
        $limit = (int) ($request->validated('limit') ?? $request->validated('per_page') ?? 15);
        $quotes = $this->quoteService->search($request->validated(), $limit);

        return QuoteListResource::collection($quotes);
    }

    /**
     * Registrar una nueva cotización.
     *
     * El backend calcula el total de cada ítem (`quantity * unit_price`), el subtotal con descuento del plan,
     * el IVA (13% de El Salvador cuando `includes_vat` es false) y los puntos de lealtad ganados.
     * El empleado se toma del usuario autenticado.
     *
     * @subgroup Gestión
     *
     * @bodyParam customer_id integer ID del cliente asociado. Example: 1
     * @bodyParam applied_plan_id integer required ID del plan de suscripción aplicado. Example: 1
     * @bodyParam includes_vat boolean Indica si los precios ya incluyen IVA. Default: false. Example: false
     * @bodyParam loyalty_points_used integer Puntos de lealtad usados (solo si la cotización incluye un servicio). Example: 0
     * @bodyParam status string Estado inicial (`pending` o `paid`). Default: pending. Example: pending
     * @bodyParam items object[] required Lista de ítems de la cotización.
     * @bodyParam items[].item_type string required Tipo de ítem (`service` o `plant`). Example: service
     * @bodyParam items[].service_id integer required ID del servicio cuando `item_type` es `service`. Example: 1
     * @bodyParam items[].plant_id integer required ID de la planta cuando `item_type` es `plant`. Example: 1
     * @bodyParam items[].quantity number required Cantidad. Example: 2
     * @bodyParam items[].unit_price number required Precio unitario. Example: 25.50
     */
    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $employeeId = (int) $request->user()->employee_id;

        $quote = $this->quoteService->create($request->validated(), $employeeId);

        return (new QuoteResource($quote))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consultar el detalle de una cotización.
     *
     * Incluye cliente, empleado, plan aplicado e ítems.
     *
     * @subgroup Consultas
     *
     * @urlParam quote integer required ID de la cotización. Example: 1
     */
    public function show(int $id): QuoteResource
    {
        Gate::authorize('view', Quote::class);

        $quote = $this->quoteService->getById($id);

        return new QuoteResource($quote);
    }

    /**
     * Actualizar una cotización.
     *
     * Actualiza datos generales y, si se envía `items`, reemplaza el detalle completo y recalcula los montos.
     *
     * @subgroup Gestión
     *
     * @urlParam quote integer required ID de la cotización. Example: 1
     *
     * @bodyParam customer_id integer ID del cliente asociado. Example: 1
     * @bodyParam applied_plan_id integer ID del plan de suscripción aplicado. Example: 1
     * @bodyParam includes_vat boolean Indica si los precios ya incluyen IVA. Example: true
     * @bodyParam loyalty_points_used integer Puntos de lealtad usados (solo si la cotización incluye un servicio). Example: 0
     * @bodyParam status string Estado (`pending` o `paid`). Example: pending
     * @bodyParam items object[] Lista de ítems; si se envía, reemplaza los existentes.
     * @bodyParam items[].item_type string required Tipo de ítem (`service` o `plant`). Example: service
     * @bodyParam items[].service_id integer required ID del servicio cuando `item_type` es `service`. Example: 1
     * @bodyParam items[].plant_id integer required ID de la planta cuando `item_type` es `plant`. Example: 1
     * @bodyParam items[].quantity number required Cantidad. Example: 2
     * @bodyParam items[].unit_price number required Precio unitario. Example: 25.50
     */
    public function update(UpdateQuoteRequest $request, int $id): QuoteResource
    {
        $quote = $this->quoteService->update($id, $request->validated());

        return new QuoteResource($quote);
    }

    /**
     * Cambiar el estado de una cotización.
     *
     * @subgroup Gestión
     *
     * @urlParam quote integer required ID de la cotización. Example: 1
     *
     * @bodyParam status string required Nuevo estado (`pending` o `paid`). Example: paid
     */
    public function updateStatus(UpdateQuoteStatusRequest $request, int $id): QuoteResource
    {
        $quote = $this->quoteService->updateStatus($id, $request->validated('status'));

        return new QuoteResource($quote);
    }

    /**
     * Eliminar una cotización (Borrado lógico).
     *
     * @subgroup Gestión
     *
     * @urlParam quote integer required ID de la cotización. Example: 1
     */
    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('delete', Quote::class);

        $this->quoteService->delete($id);

        return response()->json([
            'message' => 'Cotización eliminada correctamente.',
        ]);
    }
}
