<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ImportExport\Http\Controllers\Api\ImportExportController;

Route::prefix('api/admin/import-export')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/products/export', [ImportExportController::class, 'export']);
        Route::post('/products/import', [ImportExportController::class, 'import']);
    });
