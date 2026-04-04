<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Localization\LocalizationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private readonly LocalizationManager $localizationManager,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localizationManager->resolveLocale($request);
        $this->localizationManager->apply($locale);

        return $next($request);
    }
}
