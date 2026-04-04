<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Frontend\CheckoutController;

Route::middleware('web')->group(function (): void {
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('storefront.checkout.index');
    Route::post('/thanh-toan/mua-ngay', [CheckoutController::class, 'buyNow'])->name('storefront.checkout.buy_now');
    Route::post('/thanh-toan', [CheckoutController::class, 'placeOrder'])->name('storefront.checkout.place_order');
    Route::get('/don-hang/dat-thanh-cong/{orderNumber}', [CheckoutController::class, 'success'])->name('storefront.orders.success');
});
