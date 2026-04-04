<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Core\Localization\LocalizationManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LocalizationController extends Controller
{
    public function __construct(
        private readonly LocalizationManager $localizationManager,
    ) {
    }

    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless($this->localizationManager->isSupported($locale), 404);

        $this->localizationManager->apply($locale);

        return redirect()
            ->back()
            ->withCookie(Cookie::forever($this->localizationManager->cookieName(), $locale));
    }
}
