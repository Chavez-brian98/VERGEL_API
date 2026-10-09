<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\CreateEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\Employees\EmployeeListResource;
use App\Http\Resources\Employees\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Listar y filtrar empleados.
     *
     * Obtiene una lista paginada de empleados con opciones de búsqueda y filtros.
     *
     * @group Empleados
     *
     * @subgroup Consultas
     *
     * @queryParam search string Busca por nombre completo, DUI o teléfono. Example: Carlos
     * @queryParam active boolean Filtrar por estado (true/activo o false/inactivo). Example: true
     * @queryParam role_id integer Filtrar por ID de rol. Example: 1
     * @queryParam limit integer Cantidad de resultados por página. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "fullName": "Carlos Mendoza",
     *       "dui": "01234567-8",
     *       "phone": "7000-0000",
     *       "active": true,
     *       "roleName": "Administrador",
     *       "createdAt": "2026-10-08T22:58:33+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/employees?page=1",
     *     "last": "http://localhost/api/v1/employees?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/employees",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $employees = $this->employeeService->search($request->all(), $limit);

        return EmployeeListResource::collection($employees);
    }

    /**
     * Registrar un nuevo empleado.
     *
     * Crea un empleado asociado a un rol existente. El estado por defecto es activo.
     *
     * @group Empleados
     *
     * @subgroup Escritura
     *
     * @bodyParam full_name string required Nombre completo del empleado. Example: Carlos Mendoza
     * @bodyParam role_id integer required ID del rol asignado. Example: 1
     * @bodyParam dui string DUI del empleado (único). Example: 01234567-8
     * @bodyParam address string Dirección. Example: Colonia Centro, San Salvador
     * @bodyParam municipality string Municipio. Example: San Salvador
     * @bodyParam department string Departamento. Example: San Salvador
     * @bodyParam birth_date date Fecha de nacimiento (YYYY-MM-DD). Example: 1990-01-15
     * @bodyParam hire_date date Fecha de contratación (YYYY-MM-DD). Example: 2024-03-01
     * @bodyParam phone string Teléfono. Example: 7000-0000
     * @bodyParam active boolean Estado del empleado. Example: true
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "fullName": "Carlos Mendoza",
     *     "dui": "01234567-8",
     *     "address": "Colonia Centro, San Salvador",
     *     "municipality": "San Salvador",
     *     "department": "San Salvador",
     *     "birthDate": "1990-01-15",
     *     "hireDate": "2024-03-01",
     *     "phone": "7000-0000",
     *     "active": true,
     *     "role": {
     *       "id": 1,
     *       "roleName": "Administrador"
     *     },
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "The full name field is required.",
     *   "errors": {
     *     "full_name": ["The full name field is required."]
     *   }
     * }
     */
    public function store(CreateEmployeeRequest $request)
    {
        try {
            $employee = $this->employeeService->create($request->validated());

            return (new EmployeeResource($employee))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Error creating employee: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al crear el empleado'], 500);
        }
    }

    /**
     * Obtener detalle de un empleado.
     *
     * Devuelve los datos completos del empleado junto con su rol.
     *
     * @group Empleados
     *
     * @subgroup Consultas
     *
     * @urlParam employee integer required ID del empleado. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "fullName": "Carlos Mendoza",
     *     "dui": "01234567-8",
     *     "address": "Colonia Centro, San Salvador",
     *     "municipality": "San Salvador",
     *     "department": "San Salvador",
     *     "birthDate": "1990-01-15",
     *     "hireDate": "2024-03-01",
     *     "phone": "7000-0000",
     *     "active": true,
     *     "role": {
     *       "id": 1,
     *       "roleName": "Administrador"
     *     },
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "No query results for model [App\\Models\\Employee]"
     * }
     */
    public function show($id)
    {
        $employee = $this->employeeService->getById($id);

        return new EmployeeResource($employee);
    }

    /**
     * Actualizar un empleado.
     *
     * Actualiza de forma parcial los datos editables del empleado. Registra la operación en auditoría.
     *
     * @group Empleados
     *
     * @subgroup Escritura
     *
     * @urlParam employee integer required ID del empleado. Example: 1
     *
     * @bodyParam full_name string Nombre completo del empleado. Example: Carlos Mendoza
     * @bodyParam role_id integer ID del rol asignado. Example: 1
     * @bodyParam dui string DUI del empleado (único). Example: 01234567-8
     * @bodyParam address string Dirección. Example: Colonia Centro, San Salvador
     * @bodyParam municipality string Municipio. Example: San Salvador
     * @bodyParam department string Departamento. Example: San Salvador
     * @bodyParam birth_date date Fecha de nacimiento (YYYY-MM-DD). Example: 1990-01-15
     * @bodyParam hire_date date Fecha de contratación (YYYY-MM-DD). Example: 2024-03-01
     * @bodyParam phone string Teléfono. Example: 7000-0000
     * @bodyParam active boolean Estado del empleado. Example: true
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "fullName": "Carlos Mendoza Actualizado",
     *     "dui": "01234567-8",
     *     "active": true,
     *     "createdAt": "2026-10-08T22:58:33+00:00",
     *     "updatedAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Empleado no encontrado"
     * }
     * @response status=422 {
     *   "message": "The dui has already been taken.",
     *   "errors": {
     *     "dui": ["The dui has already been taken."]
     *   }
     * }
     */
    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            $employee = $this->employeeService->update($id, $request->validated());

            return (new EmployeeResource($employee))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating employee: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el empleado'], 500);
        }
    }

    /**
     * Activar o desactivar un empleado.
     *
     * Cambia el estado (activo/inactivo) de un empleado existente.
     *
     * @group Empleados
     *
     * @subgroup Escritura
     *
     * @urlParam employee integer required ID del empleado. Example: 1
     *
     * @bodyParam active boolean required Nuevo estado del empleado. Example: false
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "fullName": "Carlos Mendoza",
     *     "active": false,
     *     "createdAt": "2026-10-08T22:58:33+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Empleado no encontrado"
     * }
     * @response status=422 {
     *   "message": "The active field is required.",
     *   "errors": {
     *     "active": ["The active field is required."]
     *   }
     * }
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'active' => 'required|boolean',
        ]);

        try {
            $employee = $this->employeeService->updateStatus($id, $request->boolean('active'));

            return (new EmployeeResource($employee))->response();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating employee status: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al cambiar el estado'], 500);
        }
    }

    /**
     * Eliminar un empleado (soft delete).
     *
     * Marca al empleado como eliminado sin borrarlo físicamente de la base de datos.
     *
     * @group Empleados
     *
     * @subgroup Escritura
     *
     * @urlParam employee integer required ID del empleado. Example: 1
     *
     * @response status=204
     * @response status=404 {
     *   "message": "Empleado no encontrado"
     * }
     */
    public function destroy($id)
    {
        try {
            $this->employeeService->delete($id);

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting employee: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al eliminar el empleado'], 500);
        }
    }
}
