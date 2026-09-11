<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/customers', [CustomerController::class, 'index']);

Route::get('/user', function (Request $request) {return $request->user(); })->middleware('auth:sanctum');





    Route::middleware('auth')->group(function () {

    // Customer routes
    Route::post('/clientes', [CustomerController::class, 'create'])->name('clientes.store');
    //Route::get('/clientes/buscar', [CustomerController::class, 'search'])->name('clientes.search');
    // Route::post('/clientes/crear', [CustomerController::class, 'create'])->name('clientes.create');
    });


    // rutas sin autenticación para crear clientes de pruebas para pruebas de integración
    Route::post('/clientes/crear', [CustomerController::class, 'create'])->name('clientes.create');
    Route::get('/clientes/buscar', [CustomerController::class, 'search'])->name('clientes.search');


    


