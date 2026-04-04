<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FlashSale\Http\Controllers\Api\FlashSaleController;

Route::prefix('api/admin/flash-sales')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [FlashSaleController::class, 'index']);
        Route::get('/sku-options', [FlashSaleController::class, 'skuOptions']);
        Route::post('/', [FlashSaleController::class, 'store']);
        Route::delete('/{id}', [FlashSaleController::class, 'destroy']);
    });
