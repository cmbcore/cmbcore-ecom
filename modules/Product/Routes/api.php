<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\ProductController;
use Modules\Product\Http\Controllers\Api\ProductMediaController;
use Modules\Product\Http\Controllers\Api\ProductSkuController;

Route::prefix('api/admin/products')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::post('/{product}', [ProductController::class, 'update']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::patch('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
        Route::get('/{product}/skus', [ProductSkuController::class, 'index']);
        Route::get('/{product}/media', [ProductMediaController::class, 'index']);
    });
