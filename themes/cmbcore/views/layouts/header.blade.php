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
    $aboutUrl = theme_url('/gioi-thieu/');
    $blogUrl = theme_route_url('storefront.blog.categories.show', ['slug' => 'tin-tuc']);
    $storefrontReadiness = app(\App\Services\StorefrontDataReadiness::class);
    $categoryMenus = $storefrontReadiness->hasProducts()
        ? \Modules\Category\Models\Category::query()
            ->roots()
            ->active()
            ->ordered()
            ->get()
            ->mapWithKeys(function (\Modules\Category\Models\Category $category): array {
                $products = \Modules\Product\Models\Product::query()
                    ->active()
                    ->with(['category', 'skus.attributes', 'media'])
                    ->withCount(['skus as sku_count', 'media as media_count'])
                    ->withSum('skus as total_stock', 'stock_quantity')
                    ->withMin('skus as min_price', 'price')
                    ->withMax('skus as max_price', 'price')
                    ->withMin('skus as min_compare_price', 'compare_price')
                    ->withMax('skus as max_compare_price', 'compare_price')
                    ->where('category_id', $category->id)
                    ->orderByDesc('is_featured')
                    ->ordered()
                    ->limit(6)
                    ->get();

                return [
                    $category->slug => [
                        'category' => $category,
                        'products' => \Modules\Product\Http\Resources\ProductResource::collection($products)->resolve(),
                    ],
                ];
            })
        : collect();
@endphp

<header class="cmbcore-header">
    <div class="cmbcore-header__main">
        <div class="cmbcore-container cmbcore-header__main-inner">
            <button class="cmbcore-menu-toggle" type="button" data-cmbcore-drawer-toggle aria-controls="cmbcore-drawer" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a class="cmbcore-logo" href="{{ theme_home_url() }}" aria-label="{{ $logoAlt }}">
                <img src="{{ $logoImage }}" alt="{{ $logoAlt }}">
            </a>

            <form class="cmbcore-search" action="{{ $searchAction }}" method="get">
                <span class="cmbcore-search__icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input
                    type="search"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="{{ theme_text('navigation.search_placeholder') }}"
                    aria-label="{{ theme_text('navigation.search_placeholder') }}"
                >
            </form>

            <div class="cmbcore-header__actions">
                <a class="cmbcore-header__action" href="{{ $accountUrl }}" aria-label="Tài khoản">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </a>
                <a class="cmbcore-header__action" href="{{ $cartUrl }}" aria-label="Giỏ hàng">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="cmbcore-header__mobile-search">
        <div class="cmbcore-container">
            <form class="cmbcore-search cmbcore-search--mobile" action="{{ $searchAction }}" method="get">
                <span class="cmbcore-search__icon">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input
                    type="search"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="{{ theme_text('navigation.search_placeholder') }}"
                    aria-label="{{ theme_text('navigation.search_placeholder') }}"
                >
            </form>
        </div>
    </div>

    <div class="cmbcore-header__nav">
        <div class="cmbcore-container">
            <nav class="cmbcore-menu" aria-label="{{ theme_text('navigation.main_menu') }}">
                @foreach ($menuItems as $item)
                    @php
                        $itemPath = trim((string) parse_url(theme_menu_url($item), PHP_URL_PATH), '/');
                        $slug = $itemPath !== '' ? basename($itemPath) : null;
                        $isActive = $itemPath !== '' && (request()->path() === $itemPath || request()->is($itemPath . '/*'));
                        $submenu = $slug !== null ? ($categoryMenus[$slug] ?? null) : null;
                    @endphp
                    <div class="cmbcore-menu__item {{ $isActive ? 'is-active' : '' }} {{ $submenu ? 'has-submenu' : '' }}">
                        <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                            {{ theme_menu_label($item) }}
                        </a>

                        @if ($submenu)
                            <div class="cmbcore-submenu">
                                <div class="cmbcore-submenu__header">
                                    <div>
                                        <p>{{ $submenu['category']->name }}</p>
                                        <span>{{ $submenu['category']->description }}</span>
                                    </div>
                                    <a href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $submenu['category']->slug]) }}">
                                        {{ theme_text('products.view_all') }}
                                    </a>
                                </div>
                                <div class="cmbcore-submenu__products">
                                    @foreach ($submenu['products'] as $product)
                                        <a class="cmbcore-submenu__product" href="{{ theme_route_url('storefront.products.show', ['slug' => $product['slug']]) }}">
                                            <span class="cmbcore-submenu__thumb">
                                                @if (!empty($product['primary_media_url']))
                                                    <img src="{{ $product['primary_media_url'] }}" alt="{{ $product['name'] }}">
                                                @endif
                                            </span>
                                            <span class="cmbcore-submenu__copy">
                                                <strong>{{ $product['name'] }}</strong>
                                                <small>{{ theme_money($product['min_price']) }}</small>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>
        </div>
    </div>

    <div class="cmbcore-drawer-backdrop" data-cmbcore-drawer-close></div>
    <aside class="cmbcore-drawer" id="cmbcore-drawer" aria-hidden="true">
        <div class="cmbcore-drawer__header">
            <a class="cmbcore-drawer__brand" href="{{ theme_home_url() }}">
                <img src="{{ $logoImage }}" alt="{{ $logoAlt }}">
            </a>
            <button type="button" class="cmbcore-drawer__close" data-cmbcore-drawer-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <form class="cmbcore-search cmbcore-search--drawer" action="{{ $searchAction }}" method="get">
            <span class="cmbcore-search__icon">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </span>
            <input
                type="search"
                name="search"
                value="{{ $searchValue }}"
                placeholder="{{ theme_text('navigation.search_placeholder') }}"
                aria-label="{{ theme_text('navigation.search_placeholder') }}"
            >
        </form>
        <nav class="cmbcore-drawer__menu" aria-label="{{ theme_text('navigation.main_menu') }}">
            @foreach ($menuItems as $item)
                @php
                    $itemPath = trim((string) parse_url(theme_menu_url($item), PHP_URL_PATH), '/');
                    $slug = $itemPath !== '' ? basename($itemPath) : null;
                    $submenu = $slug !== null ? ($categoryMenus[$slug] ?? null) : null;
                @endphp
                <div class="cmbcore-drawer__item {{ $submenu ? 'has-children' : '' }}">
                    <div class="cmbcore-drawer__item-head">
                        <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                            {{ theme_menu_label($item) }}
                        </a>
                        @if ($submenu)
                            <button type="button" class="cmbcore-drawer__toggle" data-cmbcore-drawer-section aria-expanded="false" aria-label="Mở danh mục {{ theme_menu_label($item) }}">
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                    @if ($submenu)
                        <div class="cmbcore-drawer__children">
                            @foreach ($submenu['products'] as $product)
                                <a href="{{ theme_route_url('storefront.products.show', ['slug' => $product['slug']]) }}">
                                    {{ $product['name'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
        <div class="cmbcore-drawer__actions">
            <a href="{{ $blogUrl }}">{{ theme_setting('header_blog_label', theme_text('navigation.blog')) }}</a>
            <a href="{{ $aboutUrl }}">{{ theme_setting('header_about_label', theme_text('navigation.about')) }}</a>
            <a href="{{ $accountUrl }}">Tài khoản</a>
            <a href="{{ $cartUrl }}">Giỏ hàng</a>
        </div>
    </aside>
</header>

