<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ActivityLog\Http\Controllers\Api\ActivityLogController;

Route::prefix('api/admin/activity-logs')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ActivityLogController::class, 'index']);
    });
