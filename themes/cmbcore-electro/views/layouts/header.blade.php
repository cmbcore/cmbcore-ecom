@php
    $menuItems = theme_menu_items('main_menu');
    $searchAction = theme_route_url('storefront.products.index');
    $searchValue = trim((string) request()->query('search', ''));
    $logoImage = theme_media_url((string) theme_setting('logo_image', theme_asset('images/logo.png')), theme_asset('images/logo.png'));
    $logoAlt = (string) theme_setting('logo_alt', theme_site_name());
    $accountUrl = auth()->check() && auth()->user()?->role === 'customer'
        ? route('storefront.account.dashboard')
        : route('storefront.account.login');
    $cartUrl = route('storefront.cart.index');
    $wishlistUrl = route('storefront.wishlist.index');
    $footerContact = theme_setting_json('footer_contact', []);
@endphp

<header class="electro-header">
    {{-- TOP HEADER --}}
    <div class="electro-top-header">
        <div class="electro-container">
            <ul class="electro-header-links electro-pull-left">
                @if (!empty($footerContact['phone']))
                    <li><a href="tel:{{ preg_replace('/\D+/', '', (string) $footerContact['phone']) }}"><i class="fa fa-phone"></i> {{ $footerContact['phone'] }}</a></li>
                @endif
                @if (!empty($footerContact['email']))
                    <li><a href="mailto:{{ $footerContact['email'] }}"><i class="fa fa-envelope-o"></i> {{ $footerContact['email'] }}</a></li>
                @endif
                @if (!empty($footerContact['address']))
                    <li><a href="#"><i class="fa fa-map-marker"></i> {{ $footerContact['address'] }}</a></li>
                @endif
            </ul>
            <ul class="electro-header-links electro-pull-right">
                <li><a href="{{ $accountUrl }}"><i class="fa fa-user-o"></i> {{ theme_text('navigation.account') }}</a></li>
            </ul>
        </div>
    </div>

    {{-- MAIN HEADER --}}
    <div class="electro-main-header">
        <div class="electro-container">
            <div class="electro-row">
                {{-- LOGO --}}
                <div class="electro-col electro-col-logo">
                    <div class="electro-header-logo">
                        <a href="{{ theme_home_url() }}" class="electro-logo">
                            <img src="{{ $logoImage }}" alt="{{ $logoAlt }}">
                        </a>
                    </div>
                </div>

                {{-- SEARCH BAR --}}
                <div class="electro-col electro-col-search">
                    <div class="electro-header-search">
                        <form action="{{ $searchAction }}" method="get">
                            <input class="electro-input" name="search" value="{{ $searchValue }}" placeholder="{{ theme_text('navigation.search_placeholder') }}">
                            <button class="electro-search-btn" type="submit">{{ theme_text('navigation.search') }}</button>
                        </form>
                    </div>
                </div>

                {{-- ACCOUNT --}}
                <div class="electro-col electro-col-actions">
                    <div class="electro-header-ctn">
                        {{-- Wishlist --}}
                        <div>
                            <a href="{{ $wishlistUrl }}">
                                <i class="fa fa-heart-o"></i>
                                <span>{{ theme_text('navigation.wishlist') }}</span>
                            </a>
                        </div>

                        {{-- Cart --}}
                        <div>
                            <a href="{{ $cartUrl }}">
                                <i class="fa fa-shopping-cart"></i>
                                <span>{{ theme_text('navigation.cart') }}</span>
                            </a>
                        </div>

                        {{-- Menu Toggle (mobile) --}}
                        <div class="electro-menu-toggle">
                            <a href="#" data-electro-menu-toggle>
                                <i class="fa fa-bars"></i>
                                <span>Menu</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- NAVIGATION --}}
    <nav class="electro-navigation">
        <div class="electro-container">
            <div class="electro-responsive-nav" id="electro-responsive-nav">
                <ul class="electro-main-nav">
                    @foreach ($menuItems as $item)
                        @php
                            $itemPath = trim((string) parse_url(theme_menu_url($item), PHP_URL_PATH), '/');
                            $isActive = request()->path() === ($itemPath ?: '/') || ($itemPath !== '' && request()->is($itemPath . '/*'));
                        @endphp
                        <li class="{{ $isActive ? 'active' : '' }}">
                            <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                {{ theme_menu_label($item) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>
</header>
