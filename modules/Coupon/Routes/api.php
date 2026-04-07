<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Coupon\Http\Controllers\Api\CouponAdminController;
use Modules\Coupon\Http\Controllers\Api\CouponPreviewController;

// ── Admin routes ───────────────────────────────────────────────────────────
Route::prefix('api/admin/coupons')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [CouponAdminController::class, 'index']);
        Route::post('/', [CouponAdminController::class, 'store']);
        Route::delete('/{id}', [CouponAdminController::class, 'destroy']);
    });

// ── Storefront: coupon preview (public, no auth required) ──────────────────
Route::post('api/storefront/coupon/preview', CouponPreviewController::class)
    ->middleware(['api']);
