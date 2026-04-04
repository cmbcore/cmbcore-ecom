<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SeoTools\Http\Controllers\Frontend\SitemapController;

Route::middleware('web')->group(function (): void {
    Route::get('/sitemap.xml', SitemapController::class)->name('storefront.sitemap');
});
