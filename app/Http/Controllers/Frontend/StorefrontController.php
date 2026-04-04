<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\StorefrontHomeService;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly StorefrontHomeService $homeService,
    ) {
    }

    public function index(): View
    {
        return theme_manager()->view('home', $this->homeService->payload());
    }
}
