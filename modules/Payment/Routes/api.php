<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Api\PaymentAdminController;

Route::prefix('api/admin/payments')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [PaymentAdminController::class, 'index']);
        Route::post('/{id}/confirm', [PaymentAdminController::class, 'confirm']);
    });
