<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Frontend\LocalizationController;
use App\Http\Controllers\Frontend\StorefrontController;
use App\Http\Controllers\Frontend\StorefrontContentFallbackController;
use App\Http\Controllers\Frontend\StorefrontPreviewController;
use App\Http\Controllers\Frontend\ThemeAssetController;
use App\Http\Middleware\ThemePreviewSessionMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('storefront.home');
Route::get('/ngon-ngu/{locale}', LocalizationController::class)->name('locale.switch');
Route::get('/theme-assets/{theme}/{path}', ThemeAssetController::class)
    ->where('path', '.*')
    ->name('theme.assets');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');
});

Route::get('/admin/{any?}', [AdminController::class, 'index'])
    ->where('any', '.*')
    ->name('admin');

// ── Live preview route (loaded in admin iframe) ──────────────────────
// Token in `_pt` query param grants access; no session/auth needed.
Route::get('/preview-theme/{alias}', StorefrontPreviewController::class)
    ->middleware(ThemePreviewSessionMiddleware::class)
    ->name('theme.preview');

Route::fallback(StorefrontContentFallbackController::class);
