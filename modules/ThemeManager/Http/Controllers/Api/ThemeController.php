<?php

declare(strict_types=1);

namespace Modules\ThemeManager\Http\Controllers\Api;

use App\Core\Localization\LocalizationManager;
use App\Core\Theme\ThemeManager;
use App\Http\Controllers\Controller;
use App\Services\ThemeSettingMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly LocalizationManager $localizationManager,
        private readonly ThemeSettingMediaService $themeSettingMediaService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->themeManager->payloads(),
            'message' => __('admin.themes.messages.list_loaded'),
        ]);
    }

    public function settings(string $alias): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->resolveThemeConfiguration($alias),
            'message' => __('admin.themes.messages.settings_loaded'),
        ]);
    }

    public function install(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'],
            'force' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->themeManager->installFromArchive(
                $payload['package'],
                (bool) ($payload['force'] ?? false),
            ),
            'message' => __('admin.themes.messages.installed'),
        ], 201);
    }

    public function updateSettings(Request $request, string $alias): JsonResponse
    {
        $configuration = $this->resolveThemeConfiguration($alias);
        $settingsSchema = (array) ($configuration['settings_schema'] ?? []);

        $supportedLocales = array_column($this->localizationManager->supportedLocales(), 'code');

        $menus = $this->decodeArrayInput($request->input('menus', []));
        $settings = $this->decodeArrayInput($request->input('settings', []));

        Log::info('[ThemeController] updateSettings incoming', [
            'alias'    => $alias,
            'logo_alt' => $settings['logo_alt'] ?? 'KEY_MISSING',
            'company'  => $settings['footer_contact']['company'] ?? 'KEY_MISSING',
            'raw_settings_type' => gettype($request->input('settings')),
            'raw_settings_len'  => strlen((string) $request->input('settings', '')),
        ]);

        $payload = $request->validate([
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['file', 'image', 'max:5120'],
        ]);

        validator(['menus' => $menus], [
            'menus' => ['nullable', 'array'],
            'menus.*.alias' => ['required_with:menus', 'string'],
            'menus.*.items' => ['nullable', 'array'],
            'menus.*.items.*.label' => ['required_with:menus.*.items', 'max:255'],
            'menus.*.items.*.url' => ['required_with:menus.*.items', 'string', 'max:500'],
            'menus.*.items.*.icon' => ['nullable', 'string', 'max:255'],
            'menus.*.items.*.target' => ['nullable', Rule::in(['_self', '_blank'])],
        ])->validate();

        $settings = $this->themeSettingMediaService->resolve(
            $alias,
            $settingsSchema,
            is_array($settings) ? $settings : [],
            (array) $request->file('uploads', []),
        );

        return response()->json([
            'success' => true,
            'data' => $this->themeManager->updateConfiguration(
                $alias,
                $settings,
                is_array($menus) ? $menus : [],
            ),
            'message' => __('admin.themes.messages.updated'),
        ]);
    }

    public function activate(string $alias): JsonResponse
    {
        $theme = $this->themeManager->find($alias);

        abort_if($theme === null, 404);

        $this->themeManager->activate($alias);

        return response()->json([
            'success' => true,
            'data' => $this->themeManager->configuration($alias),
            'message' => __('admin.themes.messages.activated'),
        ]);
    }

    public function destroy(string $alias): JsonResponse
    {
        $theme = $this->themeManager->find($alias);

        abort_if($theme === null, 404);

        $this->themeManager->delete($alias);

        return response()->json([
            'success' => true,
            'message' => 'Da xoa theme.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveThemeConfiguration(string $alias): array
    {
        $theme = $this->themeManager->find($alias);

        abort_if($theme === null, 404);

        return $this->themeManager->configuration($alias);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeArrayInput(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
