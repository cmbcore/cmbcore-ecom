<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Core\Localization\LocalizationManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocalizationController extends Controller
{
    public function __construct(
        private readonly LocalizationManager $localizationManager,
    ) {
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->localizationManager->adminPayload(),
            'message' => __('admin.locale.messages.loaded'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'locale' => [
                'required',
                'string',
                Rule::in(array_column($this->localizationManager->supportedLocales(), 'code')),
            ],
        ]);

        $locale = $this->localizationManager->apply((string) $payload['locale']);

        return response()
            ->json([
                'success' => true,
                'data' => $this->localizationManager->adminPayload($locale),
                'message' => __('admin.locale.messages.updated'),
            ])
            ->cookie(Cookie::forever($this->localizationManager->cookieName(), $locale));
    }
}
