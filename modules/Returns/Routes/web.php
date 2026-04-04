<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Frontend\ReturnRequestController;

Route::middleware(['web', 'auth', 'customer'])->group(function (): void {
    Route::get('/tai-khoan/doi-tra', [ReturnRequestController::class, 'index'])->name('storefront.account.returns');
    Route::post('/tai-khoan/doi-tra/{orderNumber}', [ReturnRequestController::class, 'store'])->name('storefront.account.returns.store');
});
