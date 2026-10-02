<?php

use App\Http\Controllers\Api\V1\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::patch('customers/{customer}/status', [CustomerController::class, 'updateStatus']);
    Route::get('customers/{customer}/plan-history', [CustomerController::class, 'planHistory']);
    Route::post('customers/{customer}/plan', [CustomerController::class, 'updatePlan']);
});
