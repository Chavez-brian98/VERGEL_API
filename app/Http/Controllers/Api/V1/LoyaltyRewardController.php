<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Loyalty\LoyaltyRewardResource;
use App\Models\LoyaltyReward;
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
     * @urlParam reward integer required ID de la recompensa. Example: 1
     */
    public function show(int $loyalty_reward): JsonResponse
    {
        Gate::authorize('viewPortfolio');

        return response()->json(new LoyaltyRewardResource(LoyaltyReward::with('service')->findOrFail($loyalty_reward)));
    }
}
