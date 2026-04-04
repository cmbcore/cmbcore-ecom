<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\Frontend\CartController;

Route::middleware('web')->group(function (): void {
    Route::get('/gio-hang', [CartController::class, 'index'])->name('storefront.cart.index');
    Route::post('/gio-hang/items', [CartController::class, 'store'])->name('storefront.cart.store');
    Route::post('/gio-hang/items/{id}', [CartController::class, 'update'])->name('storefront.cart.update');
    Route::post('/gio-hang/items/{id}/xoa', [CartController::class, 'destroy'])->name('storefront.cart.destroy');
});
