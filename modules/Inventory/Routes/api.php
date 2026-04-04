<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Api\InventoryAdminController;

Route::prefix('api/admin/inventory')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [InventoryAdminController::class, 'index']);
        Route::post('/bulk-update', [InventoryAdminController::class, 'bulkUpdate']);
    });
