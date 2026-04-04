<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Api\AddressController;
use Modules\Customer\Http\Controllers\Api\AuthController;
use Modules\Customer\Http\Controllers\Api\CustomerAdminController;

Route::prefix('api/storefront')
    ->middleware(['storefront_api'])
    ->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'customer'])->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/addresses', [AddressController::class, 'index']);
            Route::post('/addresses', [AddressController::class, 'store']);
            Route::put('/addresses/{id}', [AddressController::class, 'update']);
            Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
            Route::post('/addresses/{id}/default', [AddressController::class, 'setDefault']);
        });
    });

Route::prefix('api/admin/customers')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [CustomerAdminController::class, 'index']);
        Route::get('/{id}', [CustomerAdminController::class, 'show']);
        Route::put('/{id}', [CustomerAdminController::class, 'update']);
        Route::delete('/{id}', [CustomerAdminController::class, 'destroy']);
    });
