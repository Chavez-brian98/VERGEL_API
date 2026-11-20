<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\IndexSupplierRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierStatusRequest;
use App\Http\Resources\Suppliers\SupplierListResource;
use App\Http\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @group Proveedores
 *
 * APIs para la gestión de proveedores, compras y suministros de El Vergel.
 */
class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * Listar y filtrar proveedores.
     *
     * Permite consultar la lista paginada de proveedores con filtros por texto, estado, giro económico, teléfono o rango de fechas.
     *
     * @subgroup Consultas
     *
     * @queryParam search string Búsqueda por nombre, nombre comercial, email, teléfono, tax_id (NIT/DUI), NRC o contacto. Example: Vivero
     * @queryParam status string Filtrar por estado (`active` o `inactive`). Example: active
     * @queryParam economic_activity string Filtrar por giro o actividad económica. Example: Jardinería
     * @queryParam phone string Filtrar por número de teléfono. Example: 78901234
     * @queryParam desde string Filtrar proveedores creados desde una fecha (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam hasta string Filtrar proveedores creados hasta una fecha (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam sort_by string Campo de ordenamiento (`id`, `name`, `trade_name`, `status`, `created_at`). Default: `id`. Example: name
     * @queryParam sort_direction string Dirección (`asc` o `desc`). Default: `desc`. Example: asc
     * @queryParam limit integer Cantidad de registros por página (1-100). Default: 15. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Distribuidora Verde S.A. de C.V.",
     *       "tradeName": "Vivero El Roble",
     *       "phone": "22223333",
     *       "email": "ventas@elroble.com",
     *       "status": "active",
     *       "economicActivity": "Venta de plantas y abonos",
     *       "contactName": "Carlos Henríquez",
     *       "createdAt": "2026-11-14T10:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/suppliers?page=1",
     *     "last": "http://localhost/api/v1/suppliers?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response status=401 {
     *   "message": "Unauthenticated."
     * }
     * @response status=403 {
     *   "message": "No tiene permisos para realizar esta acción."
     * }
     */
    public function index(IndexSupplierRequest $request): AnonymousResourceCollection
    {
        $limit = (int) ($request->validated('limit') ?? $request->validated('per_page') ?? 15);
        $suppliers = $this->supplierService->search($request->validated(), $limit);

        return SupplierListResource::collection($suppliers);
    }

    /**
     * Registrar un nuevo proveedor.
     *
     * @subgroup Escritura
     *
     * @bodyParam name string required Razón social o nombre del proveedor. Example: Distribuidora Verde S.A. de C.V.
     * @bodyParam trade_name string Nombre comercial del proveedor. Example: Vivero El Roble
     * @bodyParam tax_id string NIT o DUI del proveedor. Example: 0614-010190-101-1
     * @bodyParam nrc string Número de Registro de Contribuyente (NRC). Example: 123456-7
     * @bodyParam economic_activity string Giro comercial. Example: Venta de plantas y abonos
     * @bodyParam phone string required Teléfono principal de contacto. Example: 22223333
     * @bodyParam email string Correo electrónico. Example: ventas@elroble.com
     * @bodyParam address string Dirección física. Example: Calle Principal #123, San Salvador
     * @bodyParam contact_name string Nombre del contacto / vendedor. Example: Carlos Henríquez
     * @bodyParam contact_phone string Teléfono del contacto. Example: 77778888
     * @bodyParam status string Estado inicial (`active` o `inactive`). Default: active. Example: active
     * @bodyParam notes string Notas u observaciones. Example: Proveedor mayorista de tierra y sustratos
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "name": "Distribuidora Verde S.A. de C.V.",
     *     "tradeName": "Vivero El Roble",
     *     "taxId": "0614-010190-101-1",
     *     "nrc": "123456-7",
     *     "economicActivity": "Venta de plantas y abonos",
     *     "phone": "22223333",
     *     "email": "ventas@elroble.com",
     *     "address": "Calle Principal #123, San Salvador",
     *     "contactName": "Carlos Henríquez",
     *     "contactPhone": "77778888",
     *     "status": "active",
     *     "notes": "Proveedor mayorista de tierra y sustratos",
     *     "createdAt": "2026-11-14T10:00:00.000000Z",
     *     "updatedAt": "2026-11-14T10:00:00.000000Z"
     *   }
     * }
     * @response status=422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "phone": ["El campo phone ya ha sido registrado."]
     *   }
     * }
     * @response status=403 {
     *   "message": "No tiene permisos para realizar esta acción."
     * }
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consultar detalle de un proveedor.
     *
     * @subgroup Consultas
     *
     * @urlParam id integer required ID del proveedor. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Distribuidora Verde S.A. de C.V.",
     *     "tradeName": "Vivero El Roble",
     *     "taxId": "0614-010190-101-1",
     *     "nrc": "123456-7",
     *     "economicActivity": "Venta de plantas y abonos",
     *     "phone": "22223333",
     *     "email": "ventas@elroble.com",
     *     "address": "Calle Principal #123, San Salvador",
     *     "contactName": "Carlos Henríquez",
     *     "contactPhone": "77778888",
     *     "status": "active",
     *     "notes": "Proveedor mayorista de tierra y sustratos",
     *     "createdAt": "2026-11-14T10:00:00.000000Z",
     *     "updatedAt": "2026-11-14T10:00:00.000000Z"
     *   }
     * }
     * @response status=404 {
     *   "message": "Proveedor no encontrado."
     * }
     */
    public function show(int $id): SupplierResource
    {
        Gate::authorize('view', Supplier::class);

        $supplier = $this->supplierService->getById($id);

        return new SupplierResource($supplier);
    }

    /**
     * Actualizar información de un proveedor.
     *
     * @subgroup Escritura
     *
     * @urlParam id integer required ID del proveedor. Example: 1
     *
     * @bodyParam name string required Razón social o nombre del proveedor. Example: Distribuidora Verde S.A. de C.V.
     * @bodyParam trade_name string Nombre comercial. Example: Vivero El Roble Central
     * @bodyParam phone string required Teléfono principal. Example: 22223333
     * @bodyParam email string Correo electrónico. Example: info@elroble.com
     * @bodyParam status string Estado (`active` o `inactive`). Example: active
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Distribuidora Verde S.A. de C.V.",
     *     "tradeName": "Vivero El Roble Central",
     *     "phone": "22223333",
     *     "email": "info@elroble.com",
     *     "status": "active",
     *     "updatedAt": "2026-11-14T11:00:00.000000Z"
     *   }
     * }
     * @response status=404 {
     *   "message": "Proveedor no encontrado."
     * }
     */
    public function update(UpdateSupplierRequest $request, int $id): SupplierResource
    {
        $supplier = $this->supplierService->update($id, $request->validated());

        return new SupplierResource($supplier);
    }

    /**
     * Cambiar estado de un proveedor.
     *
     * @subgroup Escritura
     *
     * @urlParam id integer required ID del proveedor. Example: 1
     *
     * @bodyParam status string required Nuevo estado (`active` o `inactive`). Example: inactive
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Distribuidora Verde S.A. de C.V.",
     *     "status": "inactive",
     *     "updatedAt": "2026-11-14T12:00:00.000000Z"
     *   }
     * }
     * @response status=404 {
     *   "message": "Proveedor no encontrado."
     * }
     */
    public function updateStatus(UpdateSupplierStatusRequest $request, int $id): SupplierResource
    {
        $supplier = $this->supplierService->updateStatus($id, $request->validated('status'));

        return new SupplierResource($supplier);
    }

    /**
     * Eliminar proveedor (Borrado lógico).
     *
     * @subgroup Escritura
     *
     * @urlParam id integer required ID del proveedor. Example: 1
     *
     * @response {
     *   "message": "Proveedor eliminado correctamente."
     * }
     * @response status=404 {
     *   "message": "Proveedor no encontrado."
     * }
     * @response status=403 {
     *   "message": "No tiene permisos para realizar esta acción."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('delete', Supplier::class);

        $this->supplierService->delete($id);

        return response()->json([
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }
}
