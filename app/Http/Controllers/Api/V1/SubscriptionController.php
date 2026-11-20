<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\ShowPortfolioRequest;
use App\Http\Resources\Subscriptions\SubscriptionResource;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\Portfolio\SubscriptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @group Suscripciones
 *
 * APIs de consulta de las suscripciones de clientes.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Listar las suscripciones de un cliente.
     *
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customerId": 15,
     *       "plan": {
     *         "id": 2,
     *         "planName": "Plan Residencial Plus",
     *         "planCode": "RES-PLUS"
     *       },
     *       "status": "active",
     *       "startDate": "2026-01-01",
     *       "endDate": "2026-12-31",
     *       "paymentDate": "2026-01-01"
     *     }
     *   ]
     * }
     * @response status=404 {
     *   "message": "Cliente no encontrado",
     *   "codigo": 404
     * }
     */
    public function index(ShowPortfolioRequest $request, int $customer): JsonResponse
    {
        try {
            $subscriptions = $this->subscriptionService->forCustomerId($customer);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions)->resolve(),
        ]);
    }

    /**
     * Ver el detalle de una suscripción.
     *
     * @subgroup Consultas
     *
     * @urlParam subscription integer required ID de la suscripción. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "customerId": 15,
     *   "plan": {
     *     "id": 2,
     *     "planName": "Plan Residencial Plus",
     *     "planCode": "RES-PLUS"
     *   },
     *   "status": "active",
     *   "startDate": "2026-01-01",
     *   "endDate": "2026-12-31",
     *   "paymentDate": "2026-01-01",
     *   "payments": [],
     *   "consumptions": []
     * }
     * @response status=404 {
     *   "message": "Suscripción no encontrada."
     * }
     */
    public function show(int $subscription): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $model = $this->subscriptionService->getById($subscription);

        return response()->json(new SubscriptionResource($model));
    }

    /**
     * Consultar la suscripción activa actual del cliente.
     *
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "customerId": 15,
     *     "plan": {
     *       "id": 2,
     *       "planName": "Plan Residencial Plus",
     *       "planCode": "RES-PLUS"
     *     },
     *     "status": "active",
     *     "startDate": "2026-01-01",
     *     "endDate": "2026-12-31"
     *   }
     * }
     * @response status=404 {
     *   "message": "Cliente no encontrado."
     * }
     */
    public function current(Request $request, int $customer): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $subscription = $this->subscriptionService->currentForCustomerId($customer);

        return response()->json([
            'data' => $subscription ? (new SubscriptionResource($subscription))->resolve() : null,
        ]);
    }
}
