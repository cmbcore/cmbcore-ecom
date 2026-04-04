<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wishlist\Http\Controllers\Api\WishlistController;

Route::prefix('api/storefront/wishlist')
    ->middleware(['storefront_api', 'auth:sanctum', 'customer'])
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/toggle/{productId}', [WishlistController::class, 'toggle']);
    });

Route::prefix('api/admin/wishlist')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'stats']);
    });
