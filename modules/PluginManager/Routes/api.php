<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PluginManager\Http\Controllers\Api\PluginController;

Route::prefix('api/admin/plugins')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [PluginController::class, 'index']);
        Route::post('/install', [PluginController::class, 'install']);
        Route::get('/{alias}/settings', [PluginController::class, 'settings']);
        Route::put('/{alias}/settings', [PluginController::class, 'updateSettings']);
        Route::put('/{alias}/enable', [PluginController::class, 'enable']);
        Route::put('/{alias}/disable', [PluginController::class, 'disable']);
        Route::delete('/{alias}', [PluginController::class, 'destroy']);
    });
