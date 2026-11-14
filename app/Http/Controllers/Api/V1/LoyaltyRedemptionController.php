<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\ShowPortfolioRequest;
use App\Http\Resources\Loyalty\LoyaltyRedemptionResource;
use App\Models\Customer;
use App\Services\Portfolio\RedemptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * @group EcoPuntos
 *
 * APIs de consulta de canjes de recompensas de clientes.
 */
class LoyaltyRedemptionController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemptionService
    ) {}

    /**
     * Listar los canjes de un cliente.
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

        $redemptions = $this->redemptionService->forCustomer($customerModel);

        return response()->json([
            'data' => LoyaltyRedemptionResource::collection($redemptions)->resolve(),
        ]);
    }
}
