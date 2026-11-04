<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLog\IndexAuditLogRequest;
use App\Http\Requests\AuditLog\ShowAuditLogRequest;
use App\Http\Resources\AuditLogs\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    /**
     * Listar y filtrar bitácoras de auditoría.
     *
     * Obtiene una lista paginada de los registros y movimientos de auditoría del sistema (creación, edición, eliminación y accesos denegados). Requiere autenticación con Sanctum y está restringido exclusivamente a colaboradores activos con rol de Administrador.
     *
     * @group Auditoría
     *
     * @subgroup Consultas
     *
     * @queryParam user_id integer Filtrar por el identificador del usuario del sistema que realizó la acción. Example: 1
     * @queryParam employee_id integer Filtrar por el identificador del colaborador asociado. Example: 1
     * @queryParam action string Filtrar por el tipo de acción registrada (insert, update, delete, restore, denied). Example: update
     * @queryParam table_name string Filtrar por el nombre de la tabla afectada en la base de datos. Example: plants
     * @queryParam affected_table string Alias alternativo para el parámetro table_name. Example: plants
     * @queryParam affected_record_id integer Filtrar por el identificador del registro afectado. Example: 12
     * @queryParam start_date date Filtrar por fecha de registro inicial (formato YYYY-MM-DD). Example: 2026-01-01
     * @queryParam end_date date Filtrar por fecha de registro final (formato YYYY-MM-DD). Example: 2026-12-31
     * @queryParam desde date Alias alternativo para start_date (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam hasta date Alias alternativo para end_date (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam limit integer Cantidad de registros por página (1 a 100). Por defecto 15. Example: 15
     * @queryParam per_page integer Alias alternativo para limit. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "action": "update",
     *       "affectedTable": "plants",
     *       "affectedRecordId": 12,
     *       "oldData": {
     *         "id": 12,
     *         "name": "Monstera Deliciosa",
     *         "price": "24.50"
     *       },
     *       "newData": {
     *         "id": 12,
     *         "name": "Monstera Deliciosa Variegata",
     *         "price": "35.00"
     *       },
     *       "ipAddress": "192.168.1.15",
     *       "createdAt": "2026-10-24T18:30:00+00:00",
     *       "actor": {
     *         "employeeId": 1,
     *         "fullName": "Carlos Administrador",
     *         "role": "Administrador",
     *         "userId": 1,
     *         "email": "admin@vergel.com"
     *       }
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/audit-logs?page=1",
     *     "last": "http://localhost/api/v1/audit-logs?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/audit-logs",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response status=401 {
     *   "message": "Unauthenticated."
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=422 {
     *   "message": "La acción seleccionada no es válida.",
     *   "errors": {
     *     "action": [
     *       "La acción debe ser una de las siguientes: insert, update, delete, restore o denied."
     *     ]
     *   }
     * }
     */
    public function index(IndexAuditLogRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $logs = $this->auditLogService->listLogs($filters);

        return AuditLogResource::collection($logs);
    }

    /**
     * Obtener el detalle de una bitácora de auditoría.
     *
     * Devuelve la información detallada de un registro individual de auditoría mediante su ID, incluyendo el actor que ejecutó la acción y el estado anterior y nuevo de los datos afectados.
     * Endpoint restringido exclusivamente a colaboradores activos con rol de Administrador.
     *
     * @group Auditoría
     *
     * @subgroup Consultas
     *
     * @urlParam id integer required ID único del registro de auditoría. Example: 1
     *
     * @response status=200 {
     *   "data": {
     *     "id": 1,
     *     "action": "update",
     *     "affectedTable": "plants",
     *     "affectedRecordId": 12,
     *     "oldData": {
     *       "id": 12,
     *       "name": "Monstera Deliciosa",
     *       "price": "24.50"
     *     },
     *     "newData": {
     *       "id": 12,
     *       "name": "Monstera Deliciosa Variegata",
     *       "price": "35.00"
     *     },
     *     "ipAddress": "192.168.1.15",
     *     "createdAt": "2026-10-24T18:30:00+00:00",
     *     "actor": {
     *       "employeeId": 1,
     *       "fullName": "Carlos Administrador",
     *       "role": "Administrador",
     *       "userId": 1,
     *       "email": "admin@vergel.com"
     *     }
     *   }
     * }
     * @response status=401 {
     *   "message": "Unauthenticated."
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=404 {
     *   "message": "Registro de auditoría no encontrado",
     *   "codigo": 404
     * }
     * @response status=500 {
     *   "message": "Error interno al obtener el registro de auditoría",
     *   "codigo": 500
     * }
     */
    public function show(ShowAuditLogRequest $request, int|string $id): JsonResponse|AuditLogResource
    {
        try {
            $log = $this->auditLogService->getById((int) $id);

            return new AuditLogResource($log);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Registro de auditoría no encontrado',
                'codigo' => 404,
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Error showing audit log: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al obtener el registro de auditoría',
                'codigo' => 500,
            ], 500);
        }
    }
}
