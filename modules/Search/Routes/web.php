<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\Frontend\SearchController;

Route::middleware('web')->group(function (): void {
    Route::get('/tim-kiem', [SearchController::class, 'index'])->name('storefront.search.index');
});
