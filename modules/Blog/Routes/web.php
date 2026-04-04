<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Frontend\BlogController;

Route::middleware('web')
    ->group(function (): void {
        Route::get('/bai-viet', [BlogController::class, 'index'])->name('storefront.blog.index');
        Route::get('/category/{slug}', [BlogController::class, 'category'])->name('storefront.blog.categories.show');
        Route::get('/bai-viet/{slug}', [BlogController::class, 'show'])->name('storefront.blog.show');
    });
