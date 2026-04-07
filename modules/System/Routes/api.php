<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Api\SettingsController;

Route::prefix('api/admin/system')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail']);
    });
