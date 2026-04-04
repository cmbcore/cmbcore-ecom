<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ThemeManager\Http\Controllers\Api\ThemeController;
use Modules\ThemeManager\Http\Controllers\Api\ThemePreviewController;

Route::prefix('api/admin/themes')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ThemeController::class, 'index']);
        Route::post('/install', [ThemeController::class, 'install']);
        Route::get('/{alias}/settings', [ThemeController::class, 'settings']);
        Route::put('/{alias}/settings', [ThemeController::class, 'updateSettings']);
        Route::put('/{alias}/activate', [ThemeController::class, 'activate']);
        Route::delete('/{alias}', [ThemeController::class, 'destroy']);
        // Preview session
        Route::post('/{alias}/preview-session', [ThemePreviewController::class, 'store']);
        Route::delete('/{alias}/preview-session', [ThemePreviewController::class, 'destroy']);
    });
