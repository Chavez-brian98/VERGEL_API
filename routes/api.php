<?php

use App\Http\Controllers\Api\V1\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::patch('customers/{customer}/status', [CustomerController::class, 'updateStatus']);
    Route::get('customers/{customer}/plan-history', [CustomerController::class, 'planHistory']);
    Route::post('customers/{customer}/plan', [CustomerController::class, 'updatePlan']);
});