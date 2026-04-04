<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\Frontend\ReviewController;

Route::middleware(['web', 'auth', 'customer'])->group(function (): void {
    Route::get('/tai-khoan/danh-gia', [ReviewController::class, 'myReviews'])->name('storefront.account.reviews');
    Route::post('/san-pham/{slug}/reviews', [ReviewController::class, 'store'])->name('storefront.products.reviews.store');
});
