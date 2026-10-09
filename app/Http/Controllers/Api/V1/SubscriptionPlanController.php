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
     * Consultar y Filtrar Catálogo de Planes.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $plans = $this->subscriptionPlanService->search($request->all(), $limit);

        return SubscriptionPlanListResource::collection($plans);
    }

    /**
     * Crear Plan de Suscripción.
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
     * Obtener detalle de un plan con sus estadísticas de uso.
     */
    public function show($id)
    {
        $plan = $this->subscriptionPlanService->getById($id);

        return new SubscriptionPlanResource($plan);
    }

    /**
     * HU-03: Actualizar Configuración de un Plan.
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
     * Activar o desactivar un plan (PATCH /status).
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
     * HU-04: Eliminación lógica (Soft Delete).
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
     * HU-05: Consultar Clientes Suscritos por Plan.
     */
    public function subscribers(Request $request, $id)
    {
        $limit = $request->input('limit', 15);
        $customers = $this->subscriptionPlanService->subscribers($id, $limit);

        return CustomerListResource::collection($customers);
    }
}
