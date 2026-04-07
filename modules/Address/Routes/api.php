<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\Api\AddressController;

/*
 * Public API – không yêu cầu xác thực.
 * URL: /api/address/provinces  &  /api/address/provinces/{code}/communes
 */
Route::prefix('api/address')
    ->middleware(['api'])
    ->group(function (): void {
        Route::get('provinces', [AddressController::class, 'provinces']);
        Route::get('provinces/{code}/communes', [AddressController::class, 'communes']);
    });
