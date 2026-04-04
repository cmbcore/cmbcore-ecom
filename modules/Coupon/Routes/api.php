<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Coupon\Http\Controllers\Api\CouponAdminController;

Route::prefix('api/admin/coupons')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [CouponAdminController::class, 'index']);
        Route::post('/', [CouponAdminController::class, 'store']);
        Route::delete('/{id}', [CouponAdminController::class, 'destroy']);
    });
