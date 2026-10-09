<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\Roles\RoleListResource;
use App\Http\Resources\Roles\RoleResource;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    /**
     * Listar y filtrar roles.
     *
     * Obtiene una lista paginada de roles junto con la cantidad de empleados asociados.
     *
     * @group Roles
     *
     * @subgroup Consultas
     *
     * @queryParam search string Busca por nombre o descripción del rol. Example: admin
     * @queryParam limit integer Cantidad de resultados por página. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "roleName": "Administrador",
     *       "description": "Colaborador administrativo con acceso total al sistema",
     *       "employeesCount": 2,
     *       "createdAt": "2026-10-08T22:58:33+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/roles?page=1",
     *     "last": "http://localhost/api/v1/roles?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/roles",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $roles = $this->roleService->search($request->all(), $limit);

        return RoleListResource::collection($roles);
    }

    /**
     * Registrar un nuevo rol.
     *
     * Crea un rol en el catálogo. El nombre del rol debe ser único.
     *
     * @group Roles
     *
     * @subgroup Escritura
     *
     * @bodyParam role_name string required Nombre único del rol. Example: Administrador
     * @bodyParam description string Descripción del rol. Example: Colaborador administrativo con acceso total al sistema
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "roleName": "Administrador",
     *     "description": "Colaborador administrativo con acceso total al sistema",
     *     "employeesCount": 0,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "The role name has already been taken.",
     *   "errors": {
     *     "role_name": ["The role name has already been taken."]
     *   }
     * }
     */
    public function store(CreateRoleRequest $request)
    {
        try {
            $role = $this->roleService->create($request->validated());

            return (new RoleResource($role))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Error creating role: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al crear el rol',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Obtener detalle de un rol.
     *
     * Devuelve los datos del rol junto con la cantidad de empleados asociados.
     *
     * @group Roles
     *
     * @subgroup Consultas
     *
     * @urlParam role integer required ID del rol. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "roleName": "Administrador",
     *     "description": "Colaborador administrativo con acceso total al sistema",
     *     "employeesCount": 2,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Rol no encontrado",
     *   "codigo": 404
     * }
     */
    public function show($id)
    {
        try {
            $role = $this->roleService->getById($id);

            return new RoleResource($role);
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Rol no encontrado',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error showing role: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al obtener el rol',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Actualizar un rol.
     *
     * Actualiza de forma parcial los datos editables del rol. Registra la operación en auditoría.
     *
     * @group Roles
     *
     * @subgroup Escritura
     *
     * @urlParam role integer required ID del rol. Example: 1
     *
     * @bodyParam role_name string Nombre único del rol. Example: Administrador General
     * @bodyParam description string Descripción del rol. Example: Acceso total al sistema
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "roleName": "Administrador General",
     *     "description": "Acceso total al sistema",
     *     "employeesCount": 2,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Rol no encontrado",
     *   "codigo": 404
     * }
     * @response status=422 {
     *   "message": "The role name has already been taken.",
     *   "errors": {
     *     "role_name": ["The role name has already been taken."]
     *   }
     * }
     */
    public function update(UpdateRoleRequest $request, $id)
    {
        try {
            $role = $this->roleService->update($id, $request->validated());

            return (new RoleResource($role))->response();
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Rol no encontrado',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error updating role: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al actualizar el rol',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }

    /**
     * Eliminar un rol.
     *
     * Elimina un rol del catálogo. No se permite si el rol tiene empleados asociados.
     *
     * @group Roles
     *
     * @subgroup Escritura
     *
     * @urlParam role integer required ID del rol. Example: 1
     *
     * @response status=204
     * @response status=404 {
     *   "message": "Rol no encontrado",
     *   "codigo": 404
     * }
     * @response status=409 {
     *   "message": "No se puede eliminar el rol porque tiene 2 empleado(s) asociado(s). Reasigne o elimine dichos empleados primero.",
     *   "codigo": 409,
     *   "errors": {
     *     "role": ["No se puede eliminar el rol porque tiene 2 empleado(s) asociado(s). Reasigne o elimine dichos empleados primero."]
     *   }
     * }
     */
    public function destroy($id)
    {
        try {
            $this->roleService->delete($id);

            return response()->json(null, 204);
        } catch (ValidationException $e) {
            $data = [
                'message' => $e->getMessage(),
                'codigo' => 409,
                'errors' => $e->errors(),
            ];

            return response()->json($data, 409);
        } catch (ModelNotFoundException $e) {
            $data = [
                'message' => 'Rol no encontrado',
                'codigo' => 404,
            ];

            return response()->json($data, 404);
        } catch (\Exception $e) {
            Log::error('Error deleting role: '.$e->getMessage());

            $data = [
                'message' => 'Error interno al eliminar el rol',
                'codigo' => 500,
            ];

            return response()->json($data, 500);
        }
    }
}
