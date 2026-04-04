@php
    $menuItems = theme_menu_items('main_menu');
    $logoImage = theme_media_url((string) theme_setting('logo_image', ''), '');
    $logoText = (string) theme_setting('logo_text', theme_site_name());
    $announcementText = trim((string) theme_setting('announcement_text', ''));
    $announcementLinkLabel = trim((string) theme_setting('announcement_link_label', ''));
    $announcementLinkUrl = trim((string) theme_setting('announcement_link_url', ''));
    $searchAction = theme_route_url('storefront.search.index');
    $searchValue = trim((string) request()->query('search', ''));
    $accountUrl = auth()->check() && auth()->user()?->role === 'customer'
        ? route('storefront.account.dashboard')
        : route('storefront.account.login');
@endphp

<header class="sf-header">
    @if ($announcementText !== '')
        <div class="sf-header__announce">
            <div class="sf-container sf-header__announce-inner">
                <span>{{ $announcementText }}</span>
                @if ($announcementLinkLabel !== '' && $announcementLinkUrl !== '')
                    <a href="{{ theme_url($announcementLinkUrl) }}">{{ $announcementLinkLabel }}</a>
                @endif
            </div>
        </div>
    @endif

    <div class="sf-header__bar">
        <div class="sf-container sf-header__bar-inner">
            <button class="sf-header__toggle" type="button" data-sf-drawer-toggle aria-controls="sf-drawer" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a class="sf-brand" href="{{ theme_home_url() }}" aria-label="{{ $logoText }}">
                @if ($logoImage !== '')
                    <img src="{{ $logoImage }}" alt="{{ $logoText }}">
                @else
                    <span class="sf-brand__mark">C</span>
                @endif
                <span class="sf-brand__copy">
                    <strong>{{ $logoText }}</strong>
                    <small>{{ theme_site_name() }}</small>
                </span>
            </a>

            <nav class="sf-nav" aria-label="{{ theme_text('navigation.main_menu') }}">
                @foreach ($menuItems as $item)
                    @php
                        $itemUrl = theme_menu_url($item);
                        $path = trim((string) parse_url($itemUrl, PHP_URL_PATH), '/');
                        $isActive = $path !== '' && (request()->path() === $path || request()->is($path . '/*'));
                    @endphp
                    <a class="sf-nav__link {{ $isActive ? 'is-active' : '' }}" href="{{ $itemUrl }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                        {!! theme_menu_icon($item, 'sf-nav__icon') !!}
                        <span>{{ theme_menu_label($item) }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="sf-header__search" action="{{ $searchAction }}" method="get">
                <i class="fa-solid fa-magnifying-glass sf-header__search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="{{ theme_text('navigation.search_placeholder') }}"
                    aria-label="{{ theme_text('navigation.search_placeholder') }}"
                >
            </form>

            <div class="sf-header__actions">
                <a class="sf-header__action" href="{{ theme_route_url('storefront.search.index') }}" aria-label="{{ theme_text('navigation.search') }}">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </a>
                <a class="sf-header__action" href="{{ route('storefront.wishlist.index') }}" aria-label="{{ theme_text('navigation.wishlist') }}">
                    <i class="fa-regular fa-heart" aria-hidden="true"></i>
                </a>
                <a class="sf-header__action" href="{{ $accountUrl }}" aria-label="{{ theme_text('navigation.account') }}">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </a>
                <a class="sf-header__action is-solid" href="{{ route('storefront.cart.index') }}" aria-label="{{ theme_text('navigation.cart') }}">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="sf-drawer-backdrop" data-sf-drawer-close></div>
    <aside class="sf-drawer" id="sf-drawer" aria-hidden="true">
        <div class="sf-drawer__header">
            <a class="sf-brand" href="{{ theme_home_url() }}">
                @if ($logoImage !== '')
                    <img src="{{ $logoImage }}" alt="{{ $logoText }}">
                @else
                    <span class="sf-brand__mark">C</span>
                @endif
                <span class="sf-brand__copy">
                    <strong>{{ $logoText }}</strong>
                    <small>{{ theme_site_name() }}</small>
                </span>
            </a>
            <button class="sf-drawer__close" type="button" data-sf-drawer-close aria-label="{{ theme_text('common.close') }}">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <form class="sf-header__search sf-header__search--drawer" action="{{ $searchAction }}" method="get">
            <i class="fa-solid fa-magnifying-glass sf-header__search-icon" aria-hidden="true"></i>
            <input
                type="search"
                name="search"
                value="{{ $searchValue }}"
                placeholder="{{ theme_text('navigation.search_placeholder') }}"
                aria-label="{{ theme_text('navigation.search_placeholder') }}"
            >
        </form>

        <nav class="sf-drawer__nav" aria-label="{{ theme_text('navigation.main_menu') }}">
            @foreach ($menuItems as $item)
                <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                    {!! theme_menu_icon($item, 'sf-drawer__icon') !!}
                    <span>{{ theme_menu_label($item) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sf-drawer__shortcuts">
            <a href="{{ route('storefront.cart.index') }}">{{ theme_text('navigation.cart') }}</a>
            <a href="{{ route('storefront.wishlist.index') }}">{{ theme_text('navigation.wishlist') }}</a>
            <a href="{{ $accountUrl }}">{{ theme_text('navigation.account') }}</a>
            <a href="{{ route('storefront.blog.index') }}">{{ theme_text('navigation.blog') }}</a>
        </div>
    </aside>
</header>
