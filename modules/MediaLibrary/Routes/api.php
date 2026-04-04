<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\MediaLibrary\Http\Controllers\Api\MediaLibraryController;

Route::prefix('api/admin/media-library')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [MediaLibraryController::class, 'index']);
        Route::post('/upload', [MediaLibraryController::class, 'upload']);
        Route::delete('/{id}', [MediaLibraryController::class, 'destroy']);
    });
