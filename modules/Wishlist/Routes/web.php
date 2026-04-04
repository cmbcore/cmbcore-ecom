<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wishlist\Http\Controllers\Frontend\WishlistController;

Route::middleware(['web', 'auth', 'customer'])->group(function (): void {
    Route::get('/yeu-thich', [WishlistController::class, 'index'])->name('storefront.wishlist.index');
    Route::post('/yeu-thich/{slug}/toggle', [WishlistController::class, 'toggle'])->name('storefront.wishlist.toggle');
});
