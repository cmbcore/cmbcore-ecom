<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\Api\SearchController;

Route::prefix('api/storefront/search')
    ->middleware(['storefront_api'])
    ->group(function (): void {
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
    });

Route::prefix('api/admin/search')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/analytics', [SearchController::class, 'analytics']);
    });
