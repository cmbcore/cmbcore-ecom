<!DOCTYPE html>
<html lang="{{ theme_locale() }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', theme_context('page.meta_title', theme_context('page.title', theme_site_name())))</title>
    @if (theme_has_context('page.meta_description'))
        <meta name="description" content="{{ theme_context('page.meta_description') }}">
    @endif
    @if (theme_has_vite_assets())
        {!! theme_vite_assets() !!}
    @endif
    <style>
        :root {
            --cmbcore-primary: {{ theme_setting('primary_color', '#ed2524') }};
            --cmbcore-secondary: {{ theme_setting('secondary_color', '#fb4908') }};
            --cmbcore-dark: {{ theme_setting('surface_dark', '#000000') }};
            --cmbcore-shell: #0f0f0f;
            --cmbcore-surface: #ffffff;
            --cmbcore-muted: #767676;
            --cmbcore-border: #e8e8e8;
            --cmbcore-star: #f9d637;
            --cmbcore-container: min(1310px, calc(100% - 32px));
        }
    </style>
    <link rel="stylesheet" href="{{ theme_asset('css/theme.css') }}">
    @hook('theme.head')
    @stack('head')
</head>
<body class="cmbcore-shell {{ request()->routeIs('storefront.home') ? 'is-home' : 'is-inner' }}">
<div class="cmbcore-site">
    @include(theme_view('layouts.header'))
    <main class="cmbcore-main">
        @yield('content')
    </main>
    @include(theme_view('layouts.footer'))
    @include(theme_view('layouts.mobile-toolbar'))
</div>
<script src="{{ theme_asset('js/theme.js') }}" defer></script>
@stack('scripts')
@hook('theme.footer')
</body>
</html>

