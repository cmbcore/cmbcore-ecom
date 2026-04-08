<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\LocalizationController;
use App\Http\Controllers\Api\Admin\ModuleController;
use App\Http\Controllers\Api\Admin\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::get('/localization', [LocalizationController::class, 'show']);
    Route::put('/localization', [LocalizationController::class, 'update']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class);
        Route::get('/modules', ModuleController::class);

        // Profile management
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'changePassword']);
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    });
});
