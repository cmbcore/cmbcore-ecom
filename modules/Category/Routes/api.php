<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Category\Http\Controllers\Api\CategoryController;

Route::prefix('api/admin/categories')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/tree', [CategoryController::class, 'tree']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::post('/{category}', [CategoryController::class, 'update']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::patch('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });
