<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Frontend\PageController;

Route::middleware('web')
    ->group(function (): void {
        Route::get('/trang/{slug}', [PageController::class, 'show'])->name('storefront.pages.show');
    });
