<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\CountryConfigController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LoyaltyRedemptionController;
use App\Http\Controllers\Api\V1\LoyaltyRewardController;
use App\Http\Controllers\Api\V1\MovementController;
use App\Http\Controllers\Api\V1\PlantController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\InspectionController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SubscriptionPlanController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('v1')->group(function () {
        Route::get('customers/portfolio/movements', [PortfolioController::class, 'allMovements']);
        Route::get('customers/portfolio', [PortfolioController::class, 'index']);
        Route::get('customers/{customer}/portfolio', [PortfolioController::class, 'summary']);
        Route::get('customers/{customer}/portfolio/movements', [PortfolioController::class, 'movements']);
        Route::post('customers/{customer}/wallet/transactions', [PortfolioController::class, 'storeWalletTransaction']);
        Route::post('customers/{customer}/loyalty-points/transactions', [PortfolioController::class, 'storeLoyaltyTransaction']);

        Route::get('customers/{customer}/subscriptions/current', [SubscriptionController::class, 'current']);
        Route::get('customers/{customer}/subscriptions', [SubscriptionController::class, 'index']);
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show']);

        Route::get('customers/{customer}/loyalty-redemptions', [LoyaltyRedemptionController::class, 'index']);

        Route::get('loyalty-rewards', [LoyaltyRewardController::class, 'index']);
        Route::get('loyalty-rewards/{loyalty_reward}', [LoyaltyRewardController::class, 'show']);

        Route::apiResource('customers', CustomerController::class);
        Route::patch('customers/{customer}/status', [CustomerController::class, 'updateStatus']);
        Route::get('customers/{customer}/plan-history', [CustomerController::class, 'planHistory']);
        Route::post('customers/{customer}/plan', [CustomerController::class, 'updatePlan']);

        Route::apiResource('subscription-plans', SubscriptionPlanController::class);
        Route::patch('subscription-plans/{plan}/status', [SubscriptionPlanController::class, 'updateStatus']);
        Route::get('subscription-plans/{plan}/subscribers', [SubscriptionPlanController::class, 'subscribers']);

        Route::apiResource('employees', EmployeeController::class);
        Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus']);

        Route::apiResource('roles', RoleController::class);

        Route::apiResource('country-configs', CountryConfigController::class);

        Route::apiResource('suppliers', SupplierController::class);
        Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'updateStatus']);

        Route::apiResource('quotes', QuoteController::class);
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'updateStatus']);

        Route::apiResource('inspections', InspectionController::class);
        Route::patch('inspections/{inspection}/status', [InspectionController::class, 'updateStatus']);
        Route::post('inspections/{inspection}/photos', [InspectionController::class, 'uploadPhotos']);
        Route::delete('inspections/{inspection}/photos/{photo}', [InspectionController::class, 'deletePhoto']);

        Route::get('plants', [PlantController::class, 'index']);
        Route::post('plants', [PlantController::class, 'store']);
        Route::get('plants/{id}', [PlantController::class, 'show']);
        Route::match(['put', 'patch', 'post'], 'plants/{id}', [PlantController::class, 'update']);
        Route::delete('plants/{id}', [PlantController::class, 'destroy']);

        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::match(['put', 'patch'], 'appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::post('quotes/{quote}/appointments', [AppointmentController::class, 'storeFromQuote']);

        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/{id}', [AuditLogController::class, 'show']);

        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{id}', [TransactionController::class, 'show']);

        Route::get('movements', [MovementController::class, 'index']);
    });
});
