<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\ShowTransactionRequest;
use App\Http\Resources\Transactions\TransactionCollection;
use App\Http\Resources\Transactions\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Historial de Transacciones
 *
 * Endpoints para consultar el registro histórico de trabajos de jardinería efectuados (pagados)
 * y trabajos declinados o cancelados por los clientes, excluyendo trabajos en curso.
 */
class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    /**
     * Listar y filtrar historial de transacciones.
     *
     * Devuelve una lista paginada de transacciones históricas que ya fueron realizadas (pagadas)
     * o que fueron canceladas/declinadas porque el cliente no aceptó la cotización.
     * Excluye estrictamente los trabajos en curso o cotizaciones abiertas en estado pendiente.
     *
     * Incluye un buscador principal unificado (por ID de cliente, número de referencia/cotización
     * o nombre del cliente), filtro específico por cliente y la sección "Resumen por cliente".
     *
     * @subgroup Consultas
     *
     * @queryParam search string Término de búsqueda general que filtra por ID de cliente, N° de cotización o nombre legal/comercial. Example: Juan
     * @queryParam customer_id integer ID del cliente para consultar su historial específico. Example: 1
     * @queryParam client_id integer Alias directo de customer_id. Example: 1
     * @queryParam status_type string Filtrar por clasificación histórica (all, performed, declined). Por defecto all. Example: performed
     * @queryParam start_date string Fecha inicial en formato AAAA-MM-DD. Example: 2026-10-01
     * @queryParam end_date string Fecha final en formato AAAA-MM-DD. Example: 2026-10-31
     * @queryParam sort_by string Campo de ordenamiento (id, quote_number, total, created_at). Por defecto created_at. Example: created_at
     * @queryParam sort_direction string Dirección de ordenamiento (asc o desc). Por defecto desc. Example: desc
     * @queryParam per_page integer Cantidad de transacciones por página (1 a 100). Por defecto 15. Example: 15
     * @queryParam limit integer Alias alternativo para per_page. Example: 15
     *
     * @response status=200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "quoteNumber": "COT-2026-0001",
     *       "transactionReference": "COT-2026-0001",
     *       "customerId": 1,
     *       "customer": {
     *         "id": 1,
     *         "legalName": "Juan Carlos Pérez",
     *         "tradeName": null,
     *         "taxId": "0614-260290-101-1",
     *         "phone": "7890-1234",
     *         "email": "juan.perez@example.com",
     *         "municipality": "Antiguo Cuscatlán",
     *         "department": "La Libertad",
     *         "address": "Residencial Cumbres de Cuscatlán, Pol. D #14"
     *       },
     *       "employeeId": 1,
     *       "employee": {
     *         "id": 1,
     *         "fullName": "Carlos Administrador"
     *       },
     *       "plan": {
     *         "id": 1,
     *         "name": "Plan Estándar",
     *         "code": "STD"
     *       },
     *       "amounts": {
     *         "basePrice": 120.0,
     *         "discountedSubtotal": 108.0,
     *         "includesVat": false,
     *         "total": 108.0
     *       },
     *       "loyaltyPoints": {
     *         "used": 0,
     *         "earned": 20
     *       },
     *       "status": {
     *         "raw": "paid",
     *         "state": "performed",
     *         "label": "Trabajo Efectuado (Pagado)",
     *         "isCancelled": false,
     *         "isPaid": true,
     *         "badge": "success"
     *       },
     *       "items": [
     *         {
     *           "id": 1,
     *           "itemType": "service",
     *           "quantity": 1.0,
     *           "unitPrice": 120.0,
     *           "total": 120.0,
     *           "service": {
     *             "id": 1,
     *             "name": "Poda y Mantenimiento de Césped"
     *           },
     *           "plant": null
     *         }
     *       ],
     *       "payments": [
     *         {
     *           "id": 1,
     *           "amountPaid": 108.0,
     *           "paymentMethod": "transfer",
     *           "paymentDate": "2026-10-15T14:30:00.000000Z"
     *         }
     *       ],
     *       "createdAt": "2026-10-10T10:00:00.000000Z",
     *       "declinedAt": null
     *     }
     *   ],
     *   "meta": {
     *     "summary": {
     *       "acceptedCount": 12,
     *       "declinedCount": 2,
     *       "totalCount": 14,
     *       "totalAcceptedAmount": 2450.0,
     *       "totalDeclinedAmount": 380.0
     *     }
     *   },
     *   "message": "Historial de transacciones obtenido exitosamente."
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=500 {
     *   "message": "Error interno al consultar el historial de transacciones.",
     *   "codigo": 500
     * }
     */
    public function index(IndexTransactionRequest $request): TransactionCollection|JsonResponse
    {
        try {
            $result = $this->transactionService->listTransactions($request->validated());

            return new TransactionCollection($result['paginator'], $result['summary']);
        } catch (\Throwable $e) {
            Log::error('Error en TransactionController@index: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Error interno al consultar el historial de transacciones.',
                'codigo' => 500,
            ], 500);
        }
    }

    /**
     * Obtener el detalle de una transacción histórica.
     *
     * Devuelve la información completa y desglosada de una transacción individual (trabajo efectuado o declinado)
     * mediante su identificador único (ID). Si la transacción está en curso o pendiente, no se encontrará en este historial.
     *
     * @subgroup Consultas
     *
     * @urlParam id integer required ID de la transacción histórica (cotización). Example: 1
     *
     * @response status=200 {
     *   "data": {
     *     "id": 1,
     *     "quoteNumber": "COT-2026-0001",
     *     "transactionReference": "COT-2026-0001",
     *     "customerId": 1,
     *     "customer": {
     *       "id": 1,
     *       "legalName": "Juan Carlos Pérez",
     *       "tradeName": null,
     *       "taxId": "0614-260290-101-1",
     *       "phone": "7890-1234",
     *       "email": "juan.perez@example.com",
     *       "municipality": "Antiguo Cuscatlán",
     *       "department": "La Libertad",
     *       "address": "Residencial Cumbres de Cuscatlán, Pol. D #14"
     *     },
     *     "employeeId": 1,
     *     "employee": {
     *       "id": 1,
     *       "fullName": "Carlos Administrador"
     *     },
     *     "amounts": {
     *       "basePrice": 120.0,
     *       "discountedSubtotal": 108.0,
     *       "includesVat": false,
     *       "total": 108.0
     *     },
     *     "status": {
     *       "raw": "paid",
     *       "state": "performed",
     *       "label": "Trabajo Efectuado (Pagado)",
     *       "isCancelled": false,
     *       "isPaid": true,
     *       "badge": "success"
     *     },
     *     "items": [
     *       {
     *         "id": 1,
     *         "itemType": "service",
     *         "quantity": 1.0,
     *         "unitPrice": 120.0,
     *         "total": 120.0,
     *         "service": {
     *           "id": 1,
     *           "name": "Poda y Mantenimiento de Césped"
     *         },
     *         "plant": null
     *       }
     *     ],
     *     "payments": [
     *       {
     *         "id": 1,
     *         "amountPaid": 108.0,
     *         "paymentMethod": "transfer",
     *         "paymentDate": "2026-10-15T14:30:00.000000Z"
     *       }
     *     ],
     *     "createdAt": "2026-10-10T10:00:00.000000Z",
     *     "declinedAt": null
     *   }
     * }
     * @response status=404 {
     *   "message": "Transacción no encontrada en el registro histórico.",
     *   "codigo": 404
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=500 {
     *   "message": "Error interno al consultar el detalle de la transacción.",
     *   "codigo": 500
     * }
     */
    public function show(ShowTransactionRequest $request, int|string $id): JsonResponse
    {
        try {
            $transaction = $this->transactionService->getTransactionById((int) $id);

            return response()->json([
                'data' => new TransactionResource($transaction),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Transacción no encontrada en el registro histórico.',
                'codigo' => 404,
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Error en TransactionController@show: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);

            return response()->json([
                'message' => 'Error interno al consultar el detalle de la transacción.',
                'codigo' => 500,
            ], 500);
        }
    }
}
