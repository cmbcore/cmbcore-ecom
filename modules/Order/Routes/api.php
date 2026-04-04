<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Api\CheckoutController;
use Modules\Order\Http\Controllers\Api\CustomerOrderController;
use Modules\Order\Http\Controllers\Api\OrderAdminController;

Route::prefix('api/storefront')
    ->middleware(['storefront_api'])
    ->group(function (): void {
        Route::post('/checkout/preview', [CheckoutController::class, 'preview']);
        Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder']);
        Route::post('/buy-now/preview', [CheckoutController::class, 'buyNowPreview']);

        Route::middleware(['auth:sanctum', 'customer'])->group(function (): void {
            Route::get('/orders', [CustomerOrderController::class, 'index']);
            Route::get('/orders/{orderNumber}', [CustomerOrderController::class, 'show']);
        });
    });

Route::prefix('api/admin/orders')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [OrderAdminController::class, 'index']);
        Route::get('/{id}', [OrderAdminController::class, 'show']);
        Route::put('/{id}', [OrderAdminController::class, 'update']);
    });
