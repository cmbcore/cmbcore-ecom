<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\ImageOptimizer\Http\Controllers\ImageOptimizerController;

Route::prefix('api/admin/plugins/image-optimizer')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/settings', [ImageOptimizerController::class, 'settings']);
        Route::post('/preview', [ImageOptimizerController::class, 'preview']);
        Route::post('/test-convert', [ImageOptimizerController::class, 'testConvert']);
    });
