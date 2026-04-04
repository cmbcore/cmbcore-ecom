<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\Api\CartController;

Route::prefix('api/storefront')
    ->middleware(['storefront_api'])
    ->group(function (): void {
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'store']);
        Route::put('/cart/items/{id}', [CartController::class, 'update']);
        Route::delete('/cart/items/{id}', [CartController::class, 'destroy']);

        Route::middleware(['auth:sanctum', 'customer'])->group(function (): void {
            Route::post('/cart/merge', [CartController::class, 'merge']);
        });
    });
