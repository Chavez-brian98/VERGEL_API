<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ChangePlanRequest;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\Customers\CustomerListResource;
use App\Http\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * Listar y filtrar clientes.
     *
     * Obtiene una lista paginada de clientes con opciones de búsqueda y filtros.
     *
     * @group Clientes
     * @subgroup Consultas
     *
     * @queryParam search string Busca por nombre legal, nombre comercial, correo, teléfono, NIT o NRC.
     * @queryParam status string Filtrar por estado. Example: active
     * @queryParam phone string Filtrar por teléfono (coincidencia parcial).
     * @queryParam desde date Filtrar por fecha de creación desde (YYYY-MM-DD).
     * @queryParam hasta date Filtrar por fecha de creación hasta (YYYY-MM-DD).
     * @queryParam limit integer Cantidad de resultados por página. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "legalName": "Cliente Ejemplo S.A. de C.V.",
     *       "tradeName": "Cliente Ejemplo",
     *       "taxId": "0614-260290-101-1",
     *       "nrc": "123456-7",
     *       "phone": "7000-0000",
     *       "status": "active",
     *       "currentPlanName": "Plan Básico",
     *       "cashBalance": 250.5,
     *       "loyaltyPoints": 120,
     *       "createdAt": "2026-09-23T06:00:00+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/customers?page=1",
     *     "last": "http://localhost/api/v1/customers?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/customers",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $customers = $this->customerService->search($request->all(), $limit);

        return CustomerListResource::collection($customers);
    }

    /**
     * Registrar un nuevo cliente.
     *
     * Crea un cliente y, como parte del proceso, inicializa su wallet y sus puntos de lealtad.
     *
     * @group Clientes
     * @subgroup Escritura
     *
     * @bodyParam legal_name string required Nombre legal del cliente. Example: Cliente Ejemplo S.A. de C.V.
     * @bodyParam trade_name string Nombre comercial. Example: Cliente Ejemplo
     * @bodyParam tax_id string NIT del cliente (único). Example: 0614-260290-101-1
     * @bodyParam nrc string NRC del cliente (único). Example: 123456-7
     * @bodyParam economic_activity string Actividad económica. Example: Venta de servicios
     * @bodyParam address string required Dirección. Example: Colonia Centro, San Salvador
     * @bodyParam department string Departamento. Example: San Salvador
     * @bodyParam municipality string required Municipio. Example: San Salvador
     * @bodyParam phone string required Teléfono (único). Example: 7000-0000
     * @bodyParam email string Correo electrónico. Example: cliente@example.com
     * @bodyParam visit_frequency_value integer Frecuencia de visita (valor numérico). Example: 30
     * @bodyParam visit_frequency_unit string Unidad de frecuencia de visita. Example: days
     * @bodyParam current_plan_id integer ID del plan de suscripción inicial. Example: 1
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "legalName": "Cliente Ejemplo S.A. de C.V.",
     *     "tradeName": "Cliente Ejemplo",
     *     "taxId": "0614-260290-101-1",
     *     "nrc": "123456-7",
     *     "economicActivity": "Venta de servicios",
     *     "address": "Colonia Centro, San Salvador",
     *     "municipality": "San Salvador",
     *     "department": "San Salvador",
     *     "phone": "7000-0000",
     *     "email": "cliente@example.com",
     *     "status": "active",
     *     "visitFrequency": null,
     *     "currentPlan": null,
     *     "wallet": {
     *       "cashBalance": 0,
     *       "reservedSubscriptionBalance": 0
     *     },
     *     "loyaltyPoints": null,
     *     "createdAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     *
     * @response status=422 {
     *   "message": "The legal name field is required.",
     *   "errors": {
     *     "legal_name": ["The legal name field is required."]
     *   }
     * }
     */
    public function store(CreateCustomerRequest $request)
    {
        try {
            $customer = $this->customerService->create($request->validated());

            return response()->json(new CustomerResource($customer), 201);
        } catch (\Exception $e) {
            Log::error('Error creating customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al crear el cliente'], 500);
        }
    }

    /**
     * Obtener detalle completo de un cliente.
     *
     * Incluye el plan actual, la wallet y los puntos de lealtad del cliente.
     *
     * @group Clientes
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "legalName": "Cliente Ejemplo S.A. de C.V.",
     *     "tradeName": "Cliente Ejemplo",
     *     "taxId": "0614-260290-101-1",
     *     "nrc": "123456-7",
     *     "economicActivity": "Venta de servicios",
     *     "address": "Colonia Centro, San Salvador",
     *     "municipality": "San Salvador",
     *     "department": "San Salvador",
     *     "phone": "7000-0000",
     *     "email": "cliente@example.com",
     *     "status": "active",
     *     "visitFrequency": {
     *       "value": 30,
     *       "unit": "days"
     *     },
     *     "currentPlan": {
     *       "id": 1,
     *       "planCode": "BASICO",
     *       "planName": "Plan Básico"
     *     },
     *     "wallet": {
     *       "cashBalance": 250.5,
     *       "reservedSubscriptionBalance": 0
     *     },
     *     "loyaltyPoints": {
     *       "pointsBalance": 120
     *     },
     *     "createdAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     *
     * @response status=404 {
     *   "message": "No query results for model [App\\Models\\Customer]"
     * }
     */
    public function show($id)
    {
        $customer = $this->customerService->getById($id);

        return new CustomerResource($customer);
    }

    /**
     * Actualizar información del cliente.
     *
     * Actualiza los datos editables de un cliente existente.
     *
     * @group Clientes
     * @subgroup Escritura
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @bodyParam legal_name string required Nombre legal del cliente. Example: Cliente Actualizado S.A. de C.V.
     * @bodyParam trade_name string Nombre comercial. Example: Cliente Actualizado
     * @bodyParam tax_id string NIT del cliente (único). Example: 0614-260290-101-1
     * @bodyParam nrc string NRC del cliente (único). Example: 123456-7
     * @bodyParam economic_activity string Actividad económica. Example: Venta de servicios
     * @bodyParam address string required Dirección. Example: Colonia Centro, San Salvador
     * @bodyParam department string Departamento. Example: San Salvador
     * @bodyParam municipality string required Municipio. Example: San Salvador
     * @bodyParam phone string required Teléfono (único). Example: 7000-0000
     * @bodyParam email string Correo electrónico. Example: cliente@example.com
     * @bodyParam visit_frequency_value integer Frecuencia de visita (valor numérico). Example: 30
     * @bodyParam visit_frequency_unit string Unidad de frecuencia de visita. Example: days
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "legalName": "Cliente Actualizado S.A. de C.V.",
     *     "status": "active",
     *     "createdAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     *
     * @response status=422 {
     *   "message": "The phone has already been taken.",
     *   "errors": {
     *     "phone": ["The phone has already been taken."]
     *   }
     * }
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        try {
            $customer = $this->customerService->update($id, $request->validated());

            return response()->json(new CustomerResource($customer), 200);
        } catch (\Exception $e) {
            Log::error('Error updating customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el cliente'], 500);
        }
    }

    /**
     * Inactivar / cambiar estado de un cliente.
     *
     * Actualiza el estado de un cliente (activo o inactivo) mediante PATCH.
     *
     * @group Clientes
     * @subgroup Escritura
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @bodyParam status string required Nuevo estado del cliente. Example: inactive
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "legalName": "Cliente Ejemplo S.A. de C.V.",
     *     "status": "inactive",
     *     "createdAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     *
     * @response status=422 {
     *   "message": "The selected status is invalid.",
     *   "errors": {
     *     "status": ["The selected status is invalid."]
     *   }
     * }
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $customer = $this->customerService->updateStatus($id, $request->input('status'));

            return response()->json(new CustomerResource($customer));
        } catch (\Exception $e) {
            Log::error('Error updating customer status: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al cambiar el estado'], 500);
        }
    }

    /**
     * Eliminar un cliente (soft delete).
     *
     * Marca al cliente como eliminado. No se elimina físicamente de la base de datos.
     *
     * @group Clientes
     * @subgroup Escritura
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @response status=204
     */
    public function destroy($id)
    {
        try {
            $this->customerService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al eliminar el cliente'], 500);
        }
    }

    /**
     * Cambiar el plan de suscripción de un cliente.
     *
     * Asigna un nuevo plan al cliente, cerrando el historial del plan anterior.
     *
     * @group Clientes
     * @subgroup Escritura
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @bodyParam plan_id integer required ID del plan de suscripción a asignar. Example: 2
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "legalName": "Cliente Ejemplo S.A. de C.V.",
     *     "status": "active",
     *     "currentPlan": {
     *       "id": 2,
     *       "planCode": "PREMIUM",
     *       "planName": "Plan Premium"
     *     },
     *     "createdAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     *
     * @response status=400 {
     *   "message": "El cliente ya tiene asignado este plan"
     * }
     *
     * @response status=422 {
     *   "message": "The selected plan id is invalid.",
     *   "errors": {
     *     "plan_id": ["The selected plan id is invalid."]
     *   }
     * }
     */
    public function updatePlan(ChangePlanRequest $request, $id)
    {
        try {
            $customer = $this->customerService->updatePlan($id, $request->input('plan_id'));

            return response()->json(new CustomerResource($customer));
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Error updating customer plan: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el plan'], 500);
        }
    }

    /**
     * Obtener el historial de planes de un cliente.
     *
     * Devuelve los planes que ha tenido el cliente, ordenados por fecha de inicio (descendente).
     *
     * @group Clientes
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 1
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 2,
     *       "customer_id": 1,
     *       "plan_id": 2,
     *       "start_date": "2026-10-01",
     *       "end_date": null,
     *       "plan": {
     *         "id": 2,
     *         "plan_code": "PREMIUM",
     *         "plan_name": "Plan Premium"
     *       }
     *     }
     *   ]
     * }
     *
     * @response status=404 {
     *   "message": "No query results for model [App\\Models\\Customer]"
     * }
     */
    public function planHistory($id)
    {
        $customer = Customer::findOrFail($id);
        $history = $customer->planHistories()->with('plan')->orderBy('start_date', 'desc')->get();

        return response()->json(['data' => $history]);
    }
}