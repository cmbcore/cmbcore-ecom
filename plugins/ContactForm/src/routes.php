<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\ContactForm\Http\Controllers\ContactFormFrontendController;

Route::middleware('web')->group(function (): void {
    Route::post('/contact-form/submit', [ContactFormFrontendController::class, 'store'])
        ->name('contact-form.store');
});
