<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/customers', [CustomerController::class, 'index']);

Route::middleware('auth')->group(function () {

    // Customer routes
Route::post('/clientes/crear', [CustomerController::class, 'create'])->name('clientes.create');
Route::get('/clientes/', [CustomerController::class, 'search'])->name('clientes.search');

});




