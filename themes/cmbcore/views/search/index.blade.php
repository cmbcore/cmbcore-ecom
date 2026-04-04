@php
    $products = theme_context('products', []);
    $categories = collect(theme_context('categories', []));
    $selectedCategory = theme_context('selected_category');
@endphp

@extends(theme_layout('app'))

@section('title', theme_text('search.title'))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('search.title')],
                ],
            ])

            <header class="cmbcore-archive-header">
                <span class="cmbcore-kicker">{{ theme_text('search.kicker') }}</span>
                <h1>{{ theme_text('search.title') }}</h1>
                <p>{{ theme_text('search.description') }}</p>
            </header>

            <div class="cmbcore-storefront-layout">
                <aside class="cmbcore-sidebar cmbcore-sidebar--catalog">
                    <div class="cmbcore-sidebar__widget">
                        <h3>{{ theme_text('products.category_sidebar_title') }}</h3>
                        <ul class="cmbcore-sidebar__links">
                            <li>
                                <a class="{{ empty($selectedCategory) ? 'is-active' : '' }}" href="{{ theme_route_url('storefront.search.index') }}">{{ theme_text('products.all_categories') }}</a>
                            </li>
                            @foreach ($categories as $category)
                                <li>
                                    <a class="{{ ($selectedCategory['slug'] ?? null) === $category['slug'] ? 'is-active' : '' }}" href="{{ theme_route_url('storefront.search.index', ['category' => $category['slug']]) }}">{{ $category['name'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                <div class="cmbcore-storefront-layout__main">
                    <div class="cmbcore-catalog-controls">
                        <form class="cmbcore-search cmbcore-search--catalog" action="{{ theme_route_url('storefront.search.index') }}" method="get">
                            @if (!empty($selectedCategory['slug']))
                                <input type="hidden" name="category" value="{{ $selectedCategory['slug'] }}">
                            @endif
                            <span class="cmbcore-search__icon">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            </span>
                            <input type="search" name="search" value="{{ theme_context('filters.search', '') }}" placeholder="{{ theme_text('search.placeholder') }}">
                            <select name="sort">
                                <option value="featured" @selected(theme_context('filters.sort') === 'featured')>Noi bat</option>
                                <option value="newest" @selected(theme_context('filters.sort') === 'newest')>Moi nhat</option>
                                <option value="price_asc" @selected(theme_context('filters.sort') === 'price_asc')>Gia tang dan</option>
                                <option value="price_desc" @selected(theme_context('filters.sort') === 'price_desc')>Gia giam dan</option>
                                <option value="best_selling" @selected(theme_context('filters.sort') === 'best_selling')>Ban chay</option>
                                <option value="rating" @selected(theme_context('filters.sort') === 'rating')>Danh gia cao</option>
                            </select>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="in_stock" value="1" @checked(theme_context('filters.in_stock'))>
                                <span>Con hang</span>
                            </label>
                            <button type="submit" class="cmbcore-button is-secondary">{{ theme_text('search.submit') }}</button>
                        </form>
                    </div>

                    @if ($products === [])
                        <div class="cmbcore-empty-state">
                            <h2>{{ theme_text('search.empty_title') }}</h2>
                            <p>{{ theme_text('search.empty_description') }}</p>
                        </div>
                    @else
                        <div class="cmbcore-product-grid">
                            @foreach ($products as $product)
                                @include(theme_view('partials.product-card'), ['product' => $product])
                            @endforeach
                        </div>
                    @endif

                    @if (theme_context('pagination.last_page', 1) > 1)
                        <div class="cmbcore-pagination">
                            @if (theme_context('pagination.prev_url'))
                                <a class="cmbcore-button is-secondary" href="{{ theme_context('pagination.prev_url') }}">{{ theme_text('products.pagination.previous') }}</a>
                            @endif
                            <span>{{ theme_text('products.pagination.status', ['current' => theme_context('pagination.current_page', 1), 'last' => theme_context('pagination.last_page', 1)]) }}</span>
                            @if (theme_context('pagination.next_url'))
                                <a class="cmbcore-button is-secondary" href="{{ theme_context('pagination.next_url') }}">{{ theme_text('products.pagination.next') }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
