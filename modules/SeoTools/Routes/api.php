<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SeoTools\Http\Controllers\Api\SeoToolsController;

Route::prefix('api/admin/seo-tools')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [SeoToolsController::class, 'index']);
    });
