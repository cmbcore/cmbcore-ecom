<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\Api\NotificationTemplateController;

Route::prefix('api/admin/notifications')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [NotificationTemplateController::class, 'index']);
        Route::post('/', [NotificationTemplateController::class, 'store']);
    });
