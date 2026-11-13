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
     */
    public function index(ShowPortfolioRequest $request, int $customer): JsonResponse
    {
        try {
            $customerModel = Customer::findOrFail($customer);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        $subscriptions = $this->subscriptionService->forCustomer($customerModel);

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
     */
    public function show(int $subscription): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $model = Subscription::with(['plan', 'payments', 'consumptions'])->findOrFail($subscription);

        return response()->json(new SubscriptionResource($model));
    }

    /**
     * Consultar la suscripción activa actual del cliente.
     *
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     */
    public function current(Request $request, int $customer): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $customerModel = Customer::findOrFail($customer);
        $subscription = $this->subscriptionService->currentForCustomer($customerModel);

        return response()->json([
            'data' => $subscription ? (new SubscriptionResource($subscription))->resolve() : null,
        ]);
    }
}
