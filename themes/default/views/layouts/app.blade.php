<!DOCTYPE html>
<html lang="{{ theme_locale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', theme_context('page.meta_title', theme_context('page.title', theme_site_name())))</title>
    @if (theme_has_context('page.meta_description'))
        <meta name="description" content="{{ theme_context('page.meta_description') }}">
    @endif
    @if (theme_has_context('seo.og'))
        @foreach ((array) theme_context('seo.og', []) as $property => $value)
            @if (filled($value))
                <meta property="{{ $property }}" content="{{ $value }}">
            @endif
        @endforeach
    @endif
    @if (theme_has_context('seo.schema'))
        <script type="application/ld+json">@json(theme_context('seo.schema'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</script>
    @endif
    @if (theme_has_vite_assets())
        {!! theme_vite_assets() !!}
    @endif
    <style>
        :root {
            --sf-primary: {{ theme_setting('primary_color', '#0d9488') }};
            --sf-primary-hover: {{ theme_setting('primary_hover', '#0f766e') }};
            --sf-accent-soft: {{ theme_setting('accent_soft', '#ccfbf1') }};
            --sf-secondary: {{ theme_setting('secondary_color', '#0f172a') }};
            --sf-surface-alt: {{ theme_setting('surface_alt', '#f8fafc') }};
        }
    </style>
    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
    @hook('theme.head')
</head>
<body class="sf-body {{ request()->routeIs('storefront.home') ? 'sf-body--home' : 'sf-body--inner' }}">
    <div class="sf-shell">
        @include(theme_view('layouts.header'))
        <main class="sf-main">
            @yield('content')
        </main>
        @include(theme_view('layouts.footer'))
        @include(theme_view('layouts.mobile-toolbar'))
    </div>
    <script src="{{ theme_asset('js/theme.js') }}" defer></script>
    @hook('theme.footer')
</body>
</html>
