<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\IndexMovementRequest;
use App\Http\Resources\Movements\MovementCollection;
use App\Services\MovementService;

/**
 * @group Movimientos (Compras, Ventas y Gastos)
 *
 * Endpoints del libro general dinámico del sistema. Centraliza toda la actividad económica
 * (ventas por trabajos efectuados, compras a proveedores y gastos operativos tipo tickets),
 * permitiendo filtrar por fechas, tipo y entidad, calculando en tiempo real la Utilidad (Ingresos - Egresos).
 */
class MovementController extends Controller
{
    public function __construct(
        private readonly MovementService $movementService
    ) {}

    /**
     * Listar y filtrar movimientos del libro general.
     *
     * Devuelve una lista paginada de todos los movimientos económicos registrados en el sistema,
     * calculando en tiempo real el resumen financiero de ingresos, compras, gastos y utilidad neta.
     *
     * @subgroup Consultas
     *
     * @queryParam movement_type string Filtrar por tipo (all, sale, purchase, expense, ticket). Por defecto all. Example: all
     * @queryParam type string Alias directo de movement_type. Example: sale
     * @queryParam start_date string Fecha contable inicial (AAAA-MM-DD). Example: 2026-11-01
     * @queryParam end_date string Fecha contable final (AAAA-MM-DD). Example: 2026-11-30
     * @queryParam search string Término unificado de búsqueda (nombre de cliente/proveedor, documento o NIT). Example: Vivero
     * @queryParam entity_name string Filtro específico por nombre de cliente o proveedor. Example: Juan Pérez
     * @queryParam status string Estado contable (completed, cancelled, all). Por defecto completed. Example: completed
     * @queryParam sort_by string Campo de ordenamiento (id, movement_date, total, amount, created_at, document_number). Por defecto movement_date. Example: movement_date
     * @queryParam sort_direction string Dirección de ordenamiento (asc o desc). Por defecto desc. Example: desc
     * @queryParam per_page integer Cantidad de registros por página (1 a 100). Por defecto 15. Example: 15
     * @queryParam limit integer Alias alternativo para per_page. Example: 15
     *
     * @response status=200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "type": "sale",
     *       "typeLabel": "Venta",
     *       "documentType": "Cotización Pagada",
     *       "documentNumber": "COT-2026-0001",
     *       "entity": {
     *         "name": "Juan Carlos Pérez",
     *         "taxId": "0614-260290-101-1",
     *         "role": "customer"
     *       },
     *       "date": "2026-11-05",
     *       "amounts": {
     *         "amount": 108.00,
     *         "vatAmount": 0.00,
     *         "total": 108.00
     *       },
     *       "status": {
     *         "raw": "completed",
     *         "label": "Completado",
     *         "isCancelled": false,
     *         "badge": "success"
     *       },
     *       "notes": "Venta originada automáticamente por trabajo efectuado (Cotización COT-2026-0001)",
     *       "employee": {
     *         "id": 1,
     *         "fullName": "Carlos Administrador"
     *       },
     *       "origin": {
     *         "type": "Quote",
     *         "id": 1
     *       },
     *       "createdAt": "2026-11-05T14:30:00.000000Z"
     *     }
     *   ],
     *   "summary": {
     *     "totalSales": 108.00,
     *     "totalPurchases": 50.00,
     *     "totalExpenses": 20.00,
     *     "totalIncome": 108.00,
     *     "totalOutflow": 70.00,
     *     "utility": 38.00,
     *     "profitMarginPercentage": 35.19,
     *     "totalCount": 3
     *   },
     *   "meta": {
     *     "summary": {
     *       "totalSales": 108.00,
     *       "totalPurchases": 50.00,
     *       "totalExpenses": 20.00,
     *       "totalIncome": 108.00,
     *       "totalOutflow": 70.00,
     *       "utility": 38.00,
     *       "profitMarginPercentage": 35.19,
     *       "totalCount": 3
     *     }
     *   },
     *   "message": "Movimientos contables obtenidos exitosamente."
     * }
     */
    public function index(IndexMovementRequest $request): MovementCollection
    {
        $result = $this->movementService->listMovements($request->validated());

        return new MovementCollection($result['paginator'], $result['summary']);
    }
}
