<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\ContactForm\Http\Controllers\ContactFormAdminController;

Route::prefix('api/admin/contact-forms')
    ->middleware(['api', 'auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/list', [ContactFormAdminController::class, 'index']);
        Route::post('/', [ContactFormAdminController::class, 'store']);
        Route::get('/{contactForm}', [ContactFormAdminController::class, 'show']);
        Route::put('/{contactForm}', [ContactFormAdminController::class, 'update']);
        Route::delete('/{contactForm}', [ContactFormAdminController::class, 'destroy']);
        Route::get('/{contactForm}/submissions', [ContactFormAdminController::class, 'submissions']);
        Route::patch('/submissions/{submissionId}/read', [ContactFormAdminController::class, 'markRead']);
    });
