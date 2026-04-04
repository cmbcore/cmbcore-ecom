<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tax\Http\Controllers\Api\TaxRateController;

Route::prefix('api/admin/tax-rates')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [TaxRateController::class, 'index']);
        Route::post('/', [TaxRateController::class, 'store']);
        Route::delete('/{id}', [TaxRateController::class, 'destroy']);
    });
