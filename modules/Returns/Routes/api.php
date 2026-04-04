<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Api\ReturnRequestController;

Route::prefix('api/storefront/orders')
    ->middleware(['storefront_api', 'auth:sanctum', 'customer'])
    ->group(function (): void {
        Route::post('/{orderNumber}/returns', [ReturnRequestController::class, 'store']);
    });

Route::prefix('api/admin/returns')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ReturnRequestController::class, 'index']);
        Route::put('/{id}', [ReturnRequestController::class, 'update']);
    });
