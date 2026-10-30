<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\PlantController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SubscriptionPlanController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('v1')->group(function () {
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

        Route::apiResource('suppliers', SupplierController::class);
        Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'updateStatus']);

        Route::get('plants', [PlantController::class, 'index']);
        Route::post('plants', [PlantController::class, 'store']);
        Route::get('plants/{id}', [PlantController::class, 'show']);
        Route::match(['put', 'patch', 'post'], 'plants/{id}', [PlantController::class, 'update']);
        Route::delete('plants/{id}', [PlantController::class, 'destroy']);
    });
});
