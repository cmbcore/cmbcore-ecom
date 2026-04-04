<!DOCTYPE html>
<html lang="{{ theme_locale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', theme_context('page.meta_title', theme_context('page.title', theme_site_name())))</title>
    @if (theme_has_context('page.meta_description'))
        <meta name="description" content="{{ theme_context('page.meta_description') }}">
    @endif
    @if (theme_has_vite_assets())
        {!! theme_vite_assets() !!}
    @endif
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --electro-primary: {{ theme_setting('primary_color', '#D10024') }};
            --electro-dark: {{ theme_setting('dark_bg', '#15161D') }};
            --electro-top-header: {{ theme_setting('top_header_bg', '#1E1F29') }};
            --electro-heading: #2B2D42;
            --electro-body: #333;
            --electro-grey: #8D99AE;
            --electro-border: #E4E7ED;
            --electro-surface: #FBFBFC;
            --electro-white: #ffffff;
            --electro-star: #EF233C;
            --electro-star-empty: #E4E7ED;
            --electro-container: min(1170px, calc(100% - 30px));
        }
    </style>
    <link rel="stylesheet" href="{{ theme_asset('css/electro.css') }}">
    @hook('theme.head')
</head>
<body class="electro-body {{ request()->routeIs('storefront.home') ? 'is-home' : 'is-inner' }}">
<div class="electro-site">
    @include(theme_view('layouts.header'))
    <main class="electro-main">
        @yield('content')
    </main>
    @include(theme_view('layouts.footer'))
    @include(theme_view('layouts.mobile-toolbar'))
</div>
<script src="{{ theme_asset('js/electro.js') }}" defer></script>
@hook('theme.footer')
</body>
</html>
