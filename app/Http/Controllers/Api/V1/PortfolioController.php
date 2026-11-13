<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portfolio\IndexPortfolioMovementRequest;
use App\Http\Requests\Portfolio\IndexPortfolioRequest;
use App\Http\Requests\Portfolio\ShowPortfolioRequest;
use App\Http\Requests\Portfolio\StoreLoyaltyTransactionRequest;
use App\Http\Requests\Portfolio\StoreWalletTransactionRequest;
use App\Http\Resources\Portfolio\CustomerMovementResource;
use App\Http\Resources\Portfolio\CustomerPortfolioResource;
use App\Http\Resources\Portfolio\CustomerPortfolioSummaryResource;
use App\Services\PortfolioService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * @group Cartera
 *
 * APIs para consultar y gestionar la billetera, EcoPuntos y movimientos del cliente.
 */
class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioService $portfolioService
    ) {}

    /**
     * Listar los resúmenes básicos de cartera de todos los clientes.
     *
     * @subgroup Consultas
     *
     * @queryParam limit integer Cantidad de resultados por página (1-100). Default: 15.
     */
    public function index(IndexPortfolioRequest $request)
    {
        $limit = $request->validated('limit', 15);
        $summaries = $this->portfolioService->getSummaries($limit);

        return CustomerPortfolioSummaryResource::collection($summaries);
    }

    /**
     * Consultar movimientos de cartera de todos los clientes.
     *
     * @subgroup Consultas
     *
     * @queryParam type string Filtrar por origen: all, wallet o loyalty. Default: all.
     * @queryParam page integer Página solicitada. Default: 1.
     * @queryParam limit integer Cantidad de registros por página (1-100). Default: 15.
     */
    public function allMovements(IndexPortfolioMovementRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $paginator = $this->portfolioService->getAllMovements(
            (string) ($filters['type'] ?? 'all'),
            (int) ($filters['page'] ?? 1),
            (int) ($filters['limit'] ?? 15)
        );

        $items = collect($paginator->items())
            ->map(fn (array $movement) => (new CustomerMovementResource($movement))->resolve())
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Obtener el resumen consolidado de cartera del cliente.
     *
     * Devuelve los datos generales del cliente, plan actual e historial, saldos
     * de billetera, EcoPuntos, deuda, suscripciones, citas y actividad reciente.
     *
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @response {
     *   "customerId": 15,
     *   "legalName": "Comercializadora San JosǸ S.A. de C.V.",
     *   "tradeName": "San JosǸ",
     *   "taxId": "0614-260290-101-1",
     *   "nrc": "123456-7",
     *   "economicActivity": "Venta de servicios",
     *   "address": "Colonia Centro, San Salvador",
     *   "department": "San Salvador",
     *   "municipality": "San Salvador",
     *   "phone": "+50378901234",
     *   "email": "cliente@example.com",
     *   "status": "active",
     *   "visitFrequency": { "value": 30, "unit": "days" },
     *   "memberSince": "2026-01-15T10:00:00+00:00",
     *   "hasPlan": true,
     *   "currentPlan": {
     *     "id": 2,
     *     "planCode": "PREMIUM",
     *     "planName": "Plan Premium",
     *     "frequencyValue": 30,
     *     "frequencyUnit": "days",
     *     "visitCount": 2,
     *     "maintenanceType": "Mensual",
     *     "discountPercentage": 10.00,
     *     "loyaltyPointsMultiplier": 1.50,
     *     "active": true,
     *     "startDate": "2026-10-01",
     *     "endDate": null
     *   },
     *   "planHistory": [
     *     {
     *       "id": 5,
     *       "planId": 2,
     *       "planCode": "PREMIUM",
     *       "planName": "Plan Premium",
     *       "startDate": "2026-10-01",
     *       "endDate": null
     *     }
     *   ],
     *   "wallet": {
     *     "cashBalance": 150.00,
     *     "reservedSubscriptionBalance": 50.00,
     *     "totalAvailable": 200.00
     *   },
     *   "loyalty": { "pointsBalance": 320 },
     *   "totalBalance": 200.00,
     *   "totalPoints": 320,
     *   "debt": {
     *     "pendingQuotesCount": 1,
     *     "totalPendingAmount": 85.50,
     *     "quotes": [
     *       {
     *         "id": 1,
     *         "quoteNumber": "COT-2026-0001",
     *         "total": 100.00,
     *         "amountPaid": 14.50,
     *         "pendingAmount": 85.50,
     *         "createdAt": "2026-10-20T09:00:00+00:00"
     *       }
     *     ]
     *   },
     *   "subscriptions": {
     *     "activeCount": 1,
     *     "current": {
     *       "id": 1,
     *       "planId": 2,
     *       "planCode": "PREMIUM",
     *       "planName": "Plan Premium",
     *       "paymentDate": "2026-10-01",
     *       "amountPaid": 100.00,
     *       "reservedBalance": 50.00,
     *       "status": "active"
     *     }
     *   },
     *   "appointments": {
     *     "upcomingCount": 1,
     *     "next": {
     *       "id": 10,
     *       "appointmentDate": "2026-11-10",
     *       "appointmentTime": "09:00",
     *       "status": "confirmed",
     *       "origin": "automatic",
     *       "serviceId": 3,
     *       "service": { "id": 3, "name": "Mantenimiento", "price": 25.00 },
     *       "employeeId": 4,
     *       "employee": { "id": 4, "fullName": "TǸcnico Campo" }
     *     },
     *     "lastCompletedDate": "2026-10-10"
     *   },
     *   "recentQuotes": [
     *     {
     *       "id": 1,
     *       "quoteNumber": "COT-2026-0001",
     *       "total": 100.00,
     *       "status": "pending",
     *       "loyaltyPointsUsed": 0,
     *       "loyaltyPointsEarned": 10,
     *       "createdAt": "2026-10-20T09:00:00+00:00"
     *     }
     *   ],
     *   "recentPayments": [
     *     {
     *       "id": 1,
     *       "quoteId": 1,
     *       "quoteNumber": "COT-2026-0001",
     *       "amountPaid": 14.50,
     *       "paymentMethod": "cash",
     *       "paymentDate": "2026-10-21T11:00:00+00:00"
     *     }
     *   ]
     * }
     */
    public function summary(ShowPortfolioRequest $request, $customer): JsonResponse
    {
        try {
            $summary = $this->portfolioService->getSummary($customer);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        return response()->json((new CustomerPortfolioResource($summary))->resolve());
    }

    /**
     * Consultar el historial unificado de movimientos del cliente.
     *
     * Combina transacciones de dinero y EcoPuntos, ordenadas por fecha descendente.
     *
     * @subgroup Consultas
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @queryParam type string Filtro por origen del movimiento: all, wallet o loyalty. Default: all. Example: all
     * @queryParam page integer Página solicitada. Default: 1. Example: 1
     * @queryParam limit integer Cantidad de registros por página (1-100). Default: 15. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 102,
     *       "source": "loyalty",
     *       "transactionType": "earned_purchase",
     *       "amount": null,
     *       "points": 50,
     *       "description": "Puntos ganados por Cotización #COT-2026-004",
     *       "paymentMethod": null,
     *       "createdAt": "2026-03-01T10:15:00+00:00"
     *     }
     *   ],
     *   "meta": {
     *     "currentPage": 1,
     *     "totalPages": 1,
     *     "totalItems": 1
     *   }
     * }
     */
    public function movements(IndexPortfolioMovementRequest $request, $customer): JsonResponse
    {
        $filters = $request->validated();

        try {
            $paginator = $this->portfolioService->getMovements(
                $customer,
                (string) ($filters['type'] ?? 'all'),
                (int) ($filters['page'] ?? 1),
                (int) ($filters['limit'] ?? 15)
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        $items = collect($paginator->items())
            ->map(fn (array $movement) => (new CustomerMovementResource($movement))->resolve())
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Registrar una transacci��n de billetera.
     *
     * Tipos permitidos: top_up, annual_fee, maintenance_deduction, adjustment,
     * reserve_funds y spend_reserved_funds.
     * - reserve_funds: mueve saldo de cash a reservado para suscripción.
     * - spend_reserved_funds: descuenta del saldo reservado.
     * Las transacciones son inmutables; las correcciones se registran como ajustes.
     *
     * @subgroup Gesti��n
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @bodyParam transaction_type string required Tipo de transacci��n. Example: top_up
     * @bodyParam amount numeric required Monto de la transacci��n. Para adjustment puede ser negativo. Example: 100.00
     * @bodyParam payment_method string MǸtodo de pago. Example: transfer
     * @bodyParam is_annual_payment boolean Indica si corresponde a pago anual. Example: false
     *
     * @response status=201 {
     *   "data": {
     *     "id": 45,
     *     "transactionType": "top_up",
     *     "amount": 100.00,
     *     "paymentMethod": "transfer",
     *     "isAnnualPayment": false,
     *     "cashBalance": 250.00,
     *     "reservedSubscriptionBalance": 50.00,
     *     "totalAvailable": 300.00,
     *     "createdAt": "2026-02-28T16:00:00+00:00"
     *   }
     * }
     */
    public function storeWalletTransaction(StoreWalletTransactionRequest $request, $customer): JsonResponse
    {
        try {
            $transaction = $this->portfolioService->createWalletTransaction(
                $customer,
                $request->validated()
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        $cash = (float) $transaction->wallet->cash_balance;
        $reserved = (float) $transaction->wallet->reserved_subscription_balance;

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'transactionType' => $transaction->transaction_type,
                'amount' => (float) $transaction->amount,
                'paymentMethod' => $transaction->payment_method,
                'isAnnualPayment' => (bool) $transaction->is_annual_payment,
                'cashBalance' => $cash,
                'reservedSubscriptionBalance' => $reserved,
                'totalAvailable' => round($cash + $reserved, 2),
                'createdAt' => $transaction->created_at
                    ? Carbon::parse($transaction->created_at)->toIso8601String()
                    : null,
            ],
        ], 201);
    }

    /**
     * Registrar una transacción de EcoPuntos.
     *
     * Tipos permitidos: earned_purchase, redeemed, referral, promotional y special_bonus.
     * El balance de puntos nunca puede quedar en negativo.
     *
     * @subgroup Gestión
     *
     * @urlParam customer integer required ID del cliente. Example: 15
     *
     * @bodyParam transaction_type string required Tipo de transacción de puntos. Example: earned_purchase
     * @bodyParam points integer required Cantidad de puntos. Example: 50
     * @bodyParam description string Descripción del movimiento. Example: Puntos ganados por compra
     * @bodyParam quote_id integer ID de cotización asociada, si aplica. Example: 4
     *
     * @response status=201 {
     *   "data": {
     *     "id": 102,
     *     "transactionType": "earned_purchase",
     *     "points": 50,
     *     "description": "Puntos ganados por compra",
     *     "pointsBalance": 370,
     *     "createdAt": "2026-03-01T10:15:00+00:00"
     *   }
     * }
     */
    public function storeLoyaltyTransaction(StoreLoyaltyTransactionRequest $request, $customer): JsonResponse
    {
        try {
            $transaction = $this->portfolioService->createLoyaltyTransaction(
                $customer,
                $request->validated()
            );
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cliente no encontrado', 'codigo' => 404], 404);
        }

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'transactionType' => $transaction->transaction_type,
                'points' => (int) $transaction->points,
                'description' => $transaction->description,
                'pointsBalance' => (int) $transaction->loyaltyPoints->points_balance,
                'createdAt' => $transaction->created_at
                    ? Carbon::parse($transaction->created_at)->toIso8601String()
                    : null,
            ],
        ], 201);
    }
}
