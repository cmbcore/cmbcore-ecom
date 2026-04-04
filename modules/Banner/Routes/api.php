<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Banner\Http\Controllers\Api\BannerController;

Route::prefix('api/admin/banners')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [BannerController::class, 'index']);
        Route::post('/', [BannerController::class, 'store']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
    });
