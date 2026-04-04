<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? __('admin.meta.panel_title') }}</title>
    @php($hasViteAssets = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
    @if ($hasViteAssets)
        @viteReactRefresh
        @vite(['resources/js/admin/main.jsx', 'resources/scss/app.scss'])
    @endif
    <style>
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: #f6f8fb; }
        .admin-fallback { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .admin-fallback__card {
            width: min(560px, 100%);
            padding: 32px;
            border-radius: 24px;
            background: #ffffff;
            border: 1px solid #dbe4ee;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        }
        .admin-fallback__eyebrow { font-size: 12px; text-transform: uppercase; letter-spacing: 0.16em; color: #0f766e; }
        .admin-fallback__title { margin: 14px 0 10px; font-size: 32px; }
        .admin-fallback__copy { margin: 0; color: #475569; line-height: 1.7; }
        .admin-fallback__actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
        .admin-fallback__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            background: #0f766e;
            color: #ffffff;
        }
        .admin-fallback__button--ghost {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #dbe4ee;
        }
    </style>
</head>
<body>
    <div id="admin-root" data-entry-mode="{{ $entryMode ?? 'app' }}">
        <div class="admin-fallback">
            <div class="admin-fallback__card">
                <div class="admin-fallback__eyebrow">{{ __('admin.system.fallback.eyebrow') }}</div>
                <h1 class="admin-fallback__title">{{ __('admin.system.fallback.title') }}</h1>
                <p class="admin-fallback__copy">{{ __('admin.system.fallback.description') }}</p>
                <div class="admin-fallback__actions">
                    <a class="admin-fallback__button" href="{{ route('admin.login') }}">{{ __('admin.system.fallback.login') }}</a>
                    <a class="admin-fallback__button admin-fallback__button--ghost" href="{{ route('storefront.home') }}">{{ __('admin.system.fallback.storefront') }}</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.__CMBCORE__ = @json($appPayload ?? []);
    </script>
</body>
</html>
