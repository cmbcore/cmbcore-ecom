@php
    $products = theme_context('products', []);
    $categories = theme_context('categories', []);
    $currentCategory = theme_context('category');
    $pagination = theme_context('pagination', []);
    $currentSort = request()->query('sort', 'featured');
    $currentSearch = request()->query('search', '');
    $pageTitle = $currentCategory ? $currentCategory['name'] : theme_text('products.list_title');
@endphp

@extends(theme_layout('app'))

@section('title', $pageTitle . ' - ' . theme_site_name())

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => $pageTitle,
        'breadcrumbs' => [
            ['label' => theme_text('navigation.products'), 'url' => theme_route_url('storefront.products.index')],
            ...($currentCategory ? [['label' => $currentCategory['name']]] : []),
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <div class="electro-store-layout">
                {{-- SIDEBAR --}}
                <div class="electro-store-aside">
                    {{-- Categories --}}
                    <div class="electro-aside-widget">
                        <h3 class="electro-aside-title">{{ theme_text('products.category_sidebar_title') }}</h3>
                        <ul class="electro-footer-links">
                            <li>
                                <a href="{{ theme_route_url('storefront.products.index') }}" class="{{ !$currentCategory ? 'active' : '' }}">
                                    {{ theme_text('products.all_categories') }}
                                </a>
                            </li>
                            @foreach ($categories as $cat)
                                <li>
                                    <a href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $cat['slug']]) }}" class="{{ $currentCategory && $currentCategory['slug'] === $cat['slug'] ? 'active' : '' }}">
                                        {{ $cat['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Price filter --}}
                    <div class="electro-aside-widget">
                        <h3 class="electro-aside-title">{{ theme_text('products.filters.sort_label') }}</h3>
                        <form action="" method="get">
                            @if ($currentSearch)
                                <input type="hidden" name="search" value="{{ $currentSearch }}">
                            @endif
                            <div class="electro-form-group">
                                <label>{{ theme_text('products.filters.min_price') }}</label>
                                <input class="electro-input" type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0">
                            </div>
                            <div class="electro-form-group">
                                <label>{{ theme_text('products.filters.max_price') }}</label>
                                <input class="electro-input" type="number" name="max_price" value="{{ request('max_price') }}" placeholder="999999">
                            </div>
                            <button type="submit" class="electro-primary-btn" style="width:100%; margin-top:10px;">{{ theme_text('products.search_action') }}</button>
                        </form>
                    </div>
                </div>

                {{-- MAIN STORE --}}
                <div class="electro-store-main">
                    {{-- Sort filter --}}
                    <div class="electro-store-filter">
                        <div class="electro-store-sort">
                            <label>
                                {{ theme_text('products.filters.sort_label') }}:
                                <select class="electro-input-select" onchange="window.location.href=this.value">
                                    @foreach (['featured', 'latest', 'best_selling', 'price_asc', 'price_desc'] as $sortKey)
                                        @php
                                            $sortUrl = request()->fullUrlWithQuery(['sort' => $sortKey]);
                                        @endphp
                                        <option value="{{ $sortUrl }}" {{ $currentSort === $sortKey ? 'selected' : '' }}>
                                            {{ theme_text('products.sort.' . $sortKey) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    {{-- Products --}}
                    @if (empty($products))
                        <div class="electro-text-center" style="padding: 60px 0;">
                            <h3>{{ theme_text('products.empty_title') }}</h3>
                            <p>{{ theme_text('products.empty_description') }}</p>
                            <a href="{{ theme_route_url('storefront.products.index') }}" class="electro-primary-btn">
                                {{ theme_text('products.browse_all') }}
                            </a>
                        </div>
                    @else
                        <div class="electro-product-grid" style="grid-template-columns: repeat(3, 1fr);">
                            @foreach ($products as $product)
                                @include(theme_view('partials.product-card'), ['product' => $product])
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        @if (!empty($pagination['last_page']) && $pagination['last_page'] > 1)
                            <ul class="electro-pagination">
                                @if ($pagination['current_page'] > 1)
                                    <li><a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">{{ theme_text('products.pagination.previous') }}</a></li>
                                @endif
                                @for ($p = 1; $p <= $pagination['last_page']; $p++)
                                    <li class="{{ $p === $pagination['current_page'] ? 'active' : '' }}">
                                        @if ($p === $pagination['current_page'])
                                            <span>{{ $p }}</span>
                                        @else
                                            <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                                        @endif
                                    </li>
                                @endfor
                                @if ($pagination['current_page'] < $pagination['last_page'])
                                    <li><a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">{{ theme_text('products.pagination.next') }}</a></li>
                                @endif
                            </ul>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (theme_setting('newsletter_enabled', '1') === '1')
        @include(theme_view('partials.newsletter'))
    @endif
@endsection
