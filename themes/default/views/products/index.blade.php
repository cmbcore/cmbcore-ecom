@php
    $filters = theme_context('filters', []);
    $products = theme_context('products', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('products.list_title')))

@section('content')
    <section class="sf-catalog">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-catalog__hero">
                <div>
                    <span class="sf-kicker">{{ theme_text('products.eyebrow') }}</span>
                    <h1>{{ theme_context('selected_category.name', theme_text('products.list_title')) }}</h1>
                    <p>{{ theme_context('page.meta_description', theme_text('products.list_description')) }}</p>
                </div>

                <form class="sf-catalog__search" action="{{ theme_route_url('storefront.products.index') }}" method="get">
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ theme_text('products.search_placeholder') }}">
                    <select name="category">
                        <option value="">{{ theme_text('products.all_categories') }}</option>
                        @foreach (theme_context('categories', []) as $category)
                            <option value="{{ $category['slug'] }}" @selected(($filters['category'] ?? '') === $category['slug'])>{{ $category['name'] }}</option>
                            @foreach (($category['children'] ?? []) as $child)
                                <option value="{{ $child['slug'] }}" @selected(($filters['category'] ?? '') === $child['slug'])>-- {{ $child['name'] }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    <input type="number" name="price_min" min="0" step="1000" value="{{ $filters['price_min'] ?? '' }}" placeholder="{{ theme_text('products.filters.min_price') }}">
                    <input type="number" name="price_max" min="0" step="1000" value="{{ $filters['price_max'] ?? '' }}" placeholder="{{ theme_text('products.filters.max_price') }}">
                    <button type="submit" class="sf-button sf-button--primary">{{ theme_text('products.search_action') }}</button>
                </form>

                <div class="sf-header__actions">
                    <span class="sf-pill">{{ theme_text('products.filters.sort_label') }}: {{ theme_text('products.sort.' . ($filters['sort'] ?? 'featured')) }}</span>
                    @if (!empty($filters['search']))
                        <span class="sf-pill">{{ theme_text('search.query', ['query' => $filters['search']]) }}</span>
                    @endif
                </div>
            </div>

            <div class="sf-catalog__layout">
                <aside class="sf-sidebar__stack">
                    <section class="sf-sidebar__card">
                        <h3>{{ theme_text('products.category_sidebar_title') }}</h3>
                        <div class="sf-sidebar__list">
                            <a href="{{ theme_route_url('storefront.products.index') }}">{{ theme_text('products.all_categories') }}</a>
                            @foreach (theme_context('categories', []) as $category)
                                <a href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $category['slug']]) }}">{{ $category['name'] }}</a>
                                @foreach (($category['children'] ?? []) as $child)
                                    <a href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $child['slug']]) }}">-- {{ $child['name'] }}</a>
                                @endforeach
                            @endforeach
                        </div>
                    </section>
                </aside>

                <div>
                    @if ($products === [])
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('products.empty_title') }}</h2>
                            <p>{{ theme_text('products.empty_description') }}</p>
                            <a class="sf-button sf-button--primary" href="{{ theme_route_url('storefront.products.index') }}">
                                {{ theme_text('products.browse_all') }}
                            </a>
                        </div>
                    @else
                        <div class="sf-product-grid">
                            @foreach ($products as $product)
                                @include(theme_view('partials.product-card'), ['product' => $product])
                            @endforeach
                        </div>

                        @if (theme_context('pagination.last_page', 1) > 1)
                            <div class="sf-pagination">
                                @if (theme_context('pagination.prev_url'))
                                    <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.prev_url') }}">
                                        {{ theme_text('products.pagination.previous') }}
                                    </a>
                                @endif
                                <span class="sf-pill">
                                    {{ theme_text('products.pagination.status', [
                                        'current' => theme_context('pagination.current_page', 1),
                                        'last' => theme_context('pagination.last_page', 1),
                                    ]) }}
                                </span>
                                @if (theme_context('pagination.next_url'))
                                    <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.next_url') }}">
                                        {{ theme_text('products.pagination.next') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
