<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Frontend\AccountController;

Route::middleware('web')->group(function (): void {
    Route::get('/tai-khoan/dang-nhap', [AccountController::class, 'showLogin'])->name('storefront.account.login');
    Route::post('/tai-khoan/dang-nhap', [AccountController::class, 'login'])->name('storefront.account.login.submit');
    Route::get('/tai-khoan/dang-ky', [AccountController::class, 'showRegister'])->name('storefront.account.register');
    Route::post('/tai-khoan/dang-ky', [AccountController::class, 'register'])->name('storefront.account.register.submit');
    Route::post('/tai-khoan/dang-xuat', [AccountController::class, 'logout'])->middleware('customer')->name('storefront.account.logout');
    Route::get('/tai-khoan', [AccountController::class, 'dashboard'])->middleware('customer')->name('storefront.account.dashboard');
    Route::get('/tai-khoan/ho-so', [AccountController::class, 'showProfile'])->middleware('customer')->name('storefront.account.profile');
    Route::post('/tai-khoan/ho-so', [AccountController::class, 'updateProfile'])->middleware('customer')->name('storefront.account.profile.update');
    Route::post('/tai-khoan/doi-mat-khau', [AccountController::class, 'changePassword'])->middleware('customer')->name('storefront.account.password.change');
    Route::get('/tai-khoan/don-hang', [AccountController::class, 'orders'])->middleware('customer')->name('storefront.account.orders');
    Route::get('/tai-khoan/don-hang/{orderNumber}', [AccountController::class, 'orderDetail'])->middleware('customer')->name('storefront.account.orders.show');
    Route::get('/tai-khoan/dia-chi', [AccountController::class, 'addresses'])->middleware('customer')->name('storefront.account.addresses');
    Route::post('/tai-khoan/dia-chi', [AccountController::class, 'storeAddress'])->middleware('customer')->name('storefront.account.addresses.store');
    Route::post('/tai-khoan/dia-chi/{id}', [AccountController::class, 'updateAddress'])->middleware('customer')->name('storefront.account.addresses.update');
    Route::post('/tai-khoan/dia-chi/{id}/xoa', [AccountController::class, 'destroyAddress'])->middleware('customer')->name('storefront.account.addresses.destroy');
    Route::post('/tai-khoan/dia-chi/{id}/mac-dinh', [AccountController::class, 'setDefaultAddress'])->middleware('customer')->name('storefront.account.addresses.default');
});
