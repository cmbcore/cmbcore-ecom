<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\Api\ReportsController;

Route::prefix('api/admin/reports')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [ReportsController::class, 'index']);
    });
