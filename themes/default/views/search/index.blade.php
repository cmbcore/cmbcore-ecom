@extends(theme_layout('app'))

@section('title', theme_text('search.title'))

@section('content')
    <section class="sf-search">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('search.title')],
                ],
            ])

            <div class="sf-catalog__hero">
                <div>
                    <span class="sf-kicker">{{ theme_text('search.kicker') }}</span>
                    <h1>{{ theme_text('search.title') }}</h1>
                    <p>{{ theme_text('search.description') }}</p>
                </div>

                <form class="sf-catalog__search" action="{{ theme_route_url('storefront.search.index') }}" method="get">
                    <input type="search" name="search" value="{{ theme_context('filters.search', '') }}" placeholder="{{ theme_text('search.placeholder') }}">
                    <select name="category">
                        <option value="">{{ theme_text('products.all_categories') }}</option>
                        @foreach (theme_context('categories', []) as $category)
                            <option value="{{ $category['slug'] }}" @selected(theme_context('filters.category') === $category['slug'])>{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="price_min" min="0" step="1000" value="{{ theme_context('filters.price_min', '') }}" placeholder="{{ theme_text('products.filters.min_price') }}">
                    <input type="number" name="price_max" min="0" step="1000" value="{{ theme_context('filters.price_max', '') }}" placeholder="{{ theme_text('products.filters.max_price') }}">
                    <button class="sf-button sf-button--primary" type="submit">{{ theme_text('search.submit') }}</button>
                </form>
            </div>

            <div class="sf-section-heading">
                <div>
                    <span class="sf-kicker">{{ theme_text('search.results_kicker') }}</span>
                    <h2>{{ theme_text('search.query', ['query' => theme_context('filters.search', theme_text('search.all_products'))]) }}</h2>
                </div>
                <span class="sf-pill">{{ count(theme_context('products', [])) }} {{ theme_text('search.results_count') }}</span>
            </div>

            @if (theme_context('products', []) === [])
                <div class="sf-empty-state">
                    <h2>{{ theme_text('search.empty_title') }}</h2>
                    <p>{{ theme_text('search.empty_description') }}</p>
                    <a class="sf-button sf-button--primary" href="{{ theme_route_url('storefront.products.index') }}">
                        {{ theme_text('products.browse_all') }}
                    </a>
                </div>
            @else
                <div class="sf-product-grid">
                    @foreach (theme_context('products', []) as $product)
                        @include(theme_view('partials.product-card'), ['product' => $product])
                    @endforeach
                </div>

                @if (theme_context('pagination.last_page', 1) > 1)
                    <div class="sf-pagination">
                        @if (theme_context('pagination.prev_url'))
                            <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.prev_url') }}">{{ theme_text('products.pagination.previous') }}</a>
                        @endif
                        <span class="sf-pill">
                            {{ theme_text('products.pagination.status', [
                                'current' => theme_context('pagination.current_page', 1),
                                'last' => theme_context('pagination.last_page', 1),
                            ]) }}
                        </span>
                        @if (theme_context('pagination.next_url'))
                            <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.next_url') }}">{{ theme_text('products.pagination.next') }}</a>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
