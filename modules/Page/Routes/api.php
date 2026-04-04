<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Api\PageController;

Route::prefix('api/admin/pages')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/templates', [PageController::class, 'templates']);
        Route::get('/', [PageController::class, 'index']);
        Route::post('/', [PageController::class, 'store']);
        Route::get('/{page}', [PageController::class, 'show']);
        Route::post('/{page}', [PageController::class, 'update']);
        Route::put('/{page}', [PageController::class, 'update']);
        Route::patch('/{page}', [PageController::class, 'update']);
        Route::delete('/{page}', [PageController::class, 'destroy']);
    });
