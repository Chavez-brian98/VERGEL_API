<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\CreatePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\Customers\CustomerListResource;
use App\Http\Resources\Plans\SubscriptionPlanListResource;
use App\Http\Resources\Plans\SubscriptionPlanResource;
use App\Services\Plans\SubscriptionPlanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        private SubscriptionPlanService $subscriptionPlanService
    ) {}

    /**
     * Listar y filtrar planes de suscripción.
     *
     * Obtiene una lista paginada de planes. Por defecto solo devuelve planes activos.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Consultas
     *
     * @queryParam active boolean Filtrar por estado (true/activo o false/inactivo). Por defecto solo activos. Example: true
     * @queryParam search string Busca por código o nombre del plan. Example: premium
     * @queryParam frequency_unit string Filtrar por unidad de frecuencia. Example: days
     * @queryParam limit integer Cantidad de resultados por página. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "planCode": "PLAN-BASIC",
     *       "planName": "Mantenimiento Mensual Básico",
     *       "frequencyValue": 30,
     *       "frequencyUnit": "days",
     *       "visitCount": 1,
     *       "maintenanceType": "Jardinería",
     *       "discountPercentage": 5,
     *       "loyaltyPointsMultiplier": 1,
     *       "active": true,
     *       "activeSubscribersCount": 3,
     *       "createdAt": "2026-09-23T06:00:00+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/subscription-plans?page=1",
     *     "last": "http://localhost/api/v1/subscription-plans?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/subscription-plans",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $plans = $this->subscriptionPlanService->search($request->all(), $limit);

        return SubscriptionPlanListResource::collection($plans);
    }

    /**
     * Crear un plan de suscripción.
     *
     * Registra un nuevo plan en el catálogo. El estado por defecto es activo.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Escritura
     *
     * @bodyParam plan_code string required Código único del plan. Example: PLAN-BASIC
     * @bodyParam plan_name string required Nombre del plan. Example: Mantenimiento Mensual Básico
     * @bodyParam frequency_value integer required Valor de la frecuencia. Example: 30
     * @bodyParam frequency_unit string required Unidad de frecuencia (days, weeks, months). Example: days
     * @bodyParam visit_count integer required Cantidad de visitas por ciclo. Example: 1
     * @bodyParam maintenance_type string Tipo de mantenimiento. Example: Jardinería
     * @bodyParam discount_percentage numeric Porcentaje de descuento (0-100). Example: 5
     * @bodyParam loyalty_points_multiplier numeric Multiplicador de puntos de lealtad (mínimo 1). Example: 1
     * @bodyParam active boolean Estado del plan. Example: true
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "planCode": "PLAN-BASIC",
     *     "planName": "Mantenimiento Mensual Básico",
     *     "frequencyValue": 30,
     *     "frequencyUnit": "days",
     *     "visitCount": 1,
     *     "maintenanceType": "Jardinería",
     *     "discountPercentage": 5,
     *     "loyaltyPointsMultiplier": 1,
     *     "active": true,
     *     "activeSubscribersCount": 0,
     *     "createdAt": "2026-09-23T06:00:00+00:00",
     *     "updatedAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "The plan code has already been taken.",
     *   "errors": {
     *     "plan_code": ["The plan code has already been taken."]
     *   }
     * }
     */
    public function store(CreatePlanRequest $request)
    {
        try {
            $plan = $this->subscriptionPlanService->create($request->validated());

            return response()->json(new SubscriptionPlanResource($plan), 201);
        } catch (\Exception $e) {
            Log::error('Error creating subscription plan: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al crear el plan'], 500);
        }
    }

    /**
     * Obtener detalle de un plan de suscripción.
     *
     * Devuelve los datos del plan junto con la cantidad de clientes activos suscritos.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Consultas
     *
     * @urlParam subscription_plan integer required ID del plan. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "planCode": "PLAN-BASIC",
     *     "planName": "Mantenimiento Mensual Básico",
     *     "frequencyValue": 30,
     *     "frequencyUnit": "days",
     *     "visitCount": 1,
     *     "maintenanceType": "Jardinería",
     *     "discountPercentage": 5,
     *     "loyaltyPointsMultiplier": 1,
     *     "active": true,
     *     "activeSubscribersCount": 3,
     *     "createdAt": "2026-09-23T06:00:00+00:00",
     *     "updatedAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "No query results for model [App\\Models\\SubscriptionPlan]"
     * }
     */
    public function show($id)
    {
        $plan = $this->subscriptionPlanService->getById($id);

        return new SubscriptionPlanResource($plan);
    }

    /**
     * Actualizar un plan de suscripción.
     *
     * Actualiza de forma parcial los datos editables del plan. Registra la operación en auditoría.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Escritura
     *
     * @urlParam subscription_plan integer required ID del plan. Example: 1
     *
     * @bodyParam plan_code string Código único del plan. Example: PLAN-BASIC
     * @bodyParam plan_name string Nombre del plan. Example: Mantenimiento Mensual Básico v2
     * @bodyParam frequency_value integer Valor de la frecuencia. Example: 30
     * @bodyParam frequency_unit string Unidad de frecuencia (days, weeks, months). Example: days
     * @bodyParam visit_count integer Cantidad de visitas por ciclo. Example: 3
     * @bodyParam maintenance_type string Tipo de mantenimiento. Example: Jardinería
     * @bodyParam discount_percentage numeric Porcentaje de descuento (0-100). Example: 12.5
     * @bodyParam loyalty_points_multiplier numeric Multiplicador de puntos de lealtad (mínimo 1). Example: 1
     * @bodyParam active boolean Estado del plan. Example: true
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "planCode": "PLAN-BASIC",
     *     "planName": "Mantenimiento Mensual Básico v2",
     *     "frequencyValue": 30,
     *     "frequencyUnit": "days",
     *     "visitCount": 3,
     *     "maintenanceType": "Jardinería",
     *     "discountPercentage": 12.5,
     *     "loyaltyPointsMultiplier": 1,
     *     "active": true,
     *     "activeSubscribersCount": 0,
     *     "createdAt": "2026-09-23T06:00:00+00:00",
     *     "updatedAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Plan no encontrado"
     * }
     * @response status=422 {
     *   "message": "The plan code has already been taken.",
     *   "errors": {
     *     "plan_code": ["The plan code has already been taken."]
     *   }
     * }
     */
    public function update(UpdatePlanRequest $request, $id)
    {
        try {
            $plan = $this->subscriptionPlanService->update($id, $request->validated());

            return response()->json(new SubscriptionPlanResource($plan), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating subscription plan: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el plan'], 500);
        }
    }

    /**
     * Activar o desactivar un plan.
     *
     * Cambia el estado (activo/inactivo) de un plan existente.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Escritura
     *
     * @urlParam plan integer required ID del plan. Example: 1
     *
     * @bodyParam active boolean required Nuevo estado del plan. Example: false
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "planCode": "PLAN-BASIC",
     *     "planName": "Mantenimiento Mensual Básico",
     *     "frequencyValue": 30,
     *     "frequencyUnit": "days",
     *     "visitCount": 1,
     *     "maintenanceType": "Jardinería",
     *     "discountPercentage": 5,
     *     "loyaltyPointsMultiplier": 1,
     *     "active": false,
     *     "activeSubscribersCount": 0,
     *     "createdAt": "2026-09-23T06:00:00+00:00",
     *     "updatedAt": "2026-09-23T06:00:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Plan no encontrado"
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
            $plan = $this->subscriptionPlanService->updateStatus($id, $request->boolean('active'));

            return response()->json(new SubscriptionPlanResource($plan), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating subscription plan status: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al cambiar el estado'], 500);
        }
    }

    /**
     * Eliminar un plan de suscripción (soft delete).
     *
     * Marca el plan como eliminado sin borrarlo físicamente. No se permite si el plan
     * tiene clientes activos suscritos.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Escritura
     *
     * @urlParam subscription_plan integer required ID del plan. Example: 1
     *
     * @response status=204
     * @response status=409 {
     *   "message": "No se puede eliminar el plan porque tiene 2 cliente(s) activo(s) suscrito(s). Migre dichos clientes a otro plan primero.",
     *   "errors": {
     *     "plan": ["No se puede eliminar el plan porque tiene 2 cliente(s) activo(s) suscrito(s). Migre dichos clientes a otro plan primero."]
     *   }
     * }
     * @response status=404 {
     *   "message": "Plan no encontrado"
     * }
     */
    public function destroy($id)
    {
        try {
            $this->subscriptionPlanService->delete($id);

            return response()->json(null, 204);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 409);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting subscription plan: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al eliminar el plan'], 500);
        }
    }

    /**
     * Listar los clientes suscritos a un plan.
     *
     * Devuelve los clientes cuyo plan actual es el indicado.
     *
     * @group Planes de Suscripción
     *
     * @subgroup Consultas
     *
     * @urlParam plan integer required ID del plan. Example: 1
     *
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
     *       "currentPlanName": "Mantenimiento Mensual Básico",
     *       "cashBalance": 0,
     *       "loyaltyPoints": 0,
     *       "createdAt": "2026-09-23T06:00:00+00:00"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/subscription-plans/1/subscribers?page=1",
     *     "last": "http://localhost/api/v1/subscription-plans/1/subscribers?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/v1/subscription-plans/1/subscribers",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response status=404 {
     *   "message": "No query results for model [App\\Models\\SubscriptionPlan]"
     * }
     */
    public function subscribers(Request $request, $id)
    {
        $limit = $request->input('limit', 15);
        $customers = $this->subscriptionPlanService->subscribers($id, $limit);

        return CustomerListResource::collection($customers);
    }
}
