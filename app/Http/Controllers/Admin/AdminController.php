<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Localization\LocalizationManager;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function __construct(
        private readonly LocalizationManager $localizationManager,
    ) {
    }

    public function index(): View
    {
        return view('admin.app', [
            'pageTitle' => __('admin.meta.panel_title'),
            'appPayload' => [
                'localization' => $this->localizationManager->adminPayload(),
            ],
            'entryMode' => 'app',
        ]);
    }

    public function login(): View
    {
        return view('admin.app', [
            'pageTitle' => __('admin.meta.login_title'),
            'appPayload' => [
                'localization' => $this->localizationManager->adminPayload(),
            ],
            'entryMode' => 'login',
        ]);
    }
}
