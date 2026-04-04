<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Api\BlogCategoryController;
use Modules\Blog\Http\Controllers\Api\BlogPostController;

Route::prefix('api/admin/blog/posts')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [BlogPostController::class, 'index']);
        Route::post('/', [BlogPostController::class, 'store']);
        Route::get('/{post}', [BlogPostController::class, 'show']);
        Route::post('/{post}', [BlogPostController::class, 'update']);
        Route::put('/{post}', [BlogPostController::class, 'update']);
        Route::patch('/{post}', [BlogPostController::class, 'update']);
        Route::delete('/{post}', [BlogPostController::class, 'destroy']);
    });

Route::prefix('api/admin/blog/categories')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/', [BlogCategoryController::class, 'index']);
        Route::post('/', [BlogCategoryController::class, 'store']);
        Route::get('/{category}', [BlogCategoryController::class, 'show']);
        Route::post('/{category}', [BlogCategoryController::class, 'update']);
        Route::put('/{category}', [BlogCategoryController::class, 'update']);
        Route::patch('/{category}', [BlogCategoryController::class, 'update']);
        Route::delete('/{category}', [BlogCategoryController::class, 'destroy']);
    });
