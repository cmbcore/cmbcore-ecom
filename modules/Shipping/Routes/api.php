<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shipping\Http\Controllers\Api\ShippingAdminController;

Route::prefix('api/admin/shipping')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ShippingAdminController::class, 'index']);
        Route::post('/zones', [ShippingAdminController::class, 'saveZone']);
        Route::delete('/zones/{id}', [ShippingAdminController::class, 'deleteZone']);
        Route::post('/methods', [ShippingAdminController::class, 'saveMethod']);
        Route::delete('/methods/{id}', [ShippingAdminController::class, 'deleteMethod']);
    });
