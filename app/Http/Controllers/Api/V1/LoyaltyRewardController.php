<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Loyalty\LoyaltyRewardResource;
use App\Services\Portfolio\RewardCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @group EcoPuntos
 *
 * APIs de consulta del catálogo de recompensas canjeables con EcoPuntos.
 */
class LoyaltyRewardController extends Controller
{
    public function __construct(
        private readonly RewardCatalogService $rewardCatalogService
    ) {}

    /**
     * Listar recompensas.
     *
     * @subgroup Consultas
     *
     * @queryParam available boolean Mostrar solo recompensas disponibles. Example: true
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Fertilización orgánica de jardín",
     *       "pointsCost": 100,
     *       "active": true,
     *       "service": {
     *         "id": 2,
     *         "name": "Fertilización"
     *       }
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $rewards = $request->boolean('available')
            ? $this->rewardCatalogService->available()
            : $this->rewardCatalogService->all();

        return response()->json([
            'data' => LoyaltyRewardResource::collection($rewards)->resolve(),
        ]);
    }

    /**
     * Ver una recompensa.
     *
     * @subgroup Consultas
     *
     * @urlParam loyalty_reward integer required ID de la recompensa. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "name": "Fertilización orgánica de jardín",
     *   "pointsCost": 100,
     *   "active": true,
     *   "service": {
     *     "id": 2,
     *     "name": "Fertilización"
     *   }
     * }
     * @response status=404 {
     *   "message": "Recompensa no encontrada."
     * }
     */
    public function show(int $loyalty_reward): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        $reward = $this->rewardCatalogService->getById($loyalty_reward);

        return response()->json(new LoyaltyRewardResource($reward));
    }
}
