<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\Api\ReviewAdminController;

Route::prefix('api/admin/reviews')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ReviewAdminController::class, 'index']);
        Route::post('/', [ReviewAdminController::class, 'store']);
        Route::put('/{id}', [ReviewAdminController::class, 'update']);
    });
