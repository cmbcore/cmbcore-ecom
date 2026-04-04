<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Frontend\ProductCatalogController;

Route::middleware('web')
    ->group(function (): void {
        Route::get('/san-pham', [ProductCatalogController::class, 'index'])->name('storefront.products.index');
        Route::get('/danh-muc-san-pham/{slug}', [ProductCatalogController::class, 'category'])->name('storefront.product-categories.show');
        Route::get('/san-pham/{slug}', [ProductCatalogController::class, 'show'])->name('storefront.products.show');
    });
