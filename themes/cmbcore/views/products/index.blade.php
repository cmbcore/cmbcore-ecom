@php
    $products = theme_context('products', []);
    $categories = collect(theme_context('categories', []));
    $selectedCategory = theme_context('selected_category');
    $catalogTitle = (string) ($selectedCategory['name'] ?? theme_setting('products_list_title', theme_text('products.list_title')));
    $currentPage = (int) theme_context('pagination.current_page', 1);
    $lastPage = (int) theme_context('pagination.last_page', 1);
    $prevUrl = theme_context('pagination.prev_url');
    $nextUrl = theme_context('pagination.next_url');
    $currentSort = theme_context('filters.sort', 'featured');
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $catalogTitle))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            {{-- Category quick-filter chips --}}
            @if ($categories->isNotEmpty())
                <div class="cmbcore-cat-chips">
                    <a class="cmbcore-cat-chip {{ empty($selectedCategory) ? 'is-active' : '' }}"
                       href="{{ theme_route_url('storefront.products.index') }}">
                        Tất cả
                    </a>
                    @foreach ($categories as $category)
                        <a class="cmbcore-cat-chip {{ ($selectedCategory['slug'] ?? null) === $category['slug'] ? 'is-active' : '' }}"
                           href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $category['slug']]) }}">
                            {{ $category['name'] }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Catalog header: title + sort --}}
            <div class="cmbcore-catalog-header">
                <h1 class="cmbcore-catalog-header__title">{{ $catalogTitle }}</h1>
                <form class="cmbcore-catalog-header__sort" method="get"
                      action="{{ !empty($selectedCategory['slug']) ? theme_route_url('storefront.product-categories.show', ['slug' => $selectedCategory['slug']]) : theme_route_url('storefront.products.index') }}">
                    <label for="sort-select">Sắp xếp:</label>
                    <select id="sort-select" name="sort" onchange="this.form.submit()">
                        <option value="best_selling" @selected($currentSort === 'best_selling')>Sản phẩm bán chạy</option>
                        <option value="featured"     @selected($currentSort === 'featured')>Nổi bật</option>
                        <option value="newest"       @selected($currentSort === 'newest')>Mới nhất</option>
                        <option value="price_asc"    @selected($currentSort === 'price_asc')>Giá tăng dần</option>
                        <option value="price_desc"   @selected($currentSort === 'price_desc')>Giá giảm dần</option>
                        <option value="rating"       @selected($currentSort === 'rating')>Đánh giá cao</option>
                    </select>
                </form>
            </div>

            {{-- Product grid --}}
            @if ($products === [])
                <div class="cmbcore-empty-state">
                    <h2>{{ theme_text('products.empty_title') }}</h2>
                    <p>{{ theme_text('products.empty_description') }}</p>
                </div>
            @else
                <div class="cmbcore-product-grid cmbcore-product-grid--catalog">
                    @foreach ($products as $product)
                        @include(theme_view('partials.product-card'), ['product' => $product])
                    @endforeach
                </div>
            @endif

            {{-- Numbered pagination --}}
            @if ($lastPage > 1)
                <nav class="cmbcore-pagination-numbered" aria-label="Phân trang">
                    @if ($prevUrl)
                        <a class="cmbcore-page-btn" href="{{ $prevUrl }}" aria-label="Trang trước">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </a>
                    @else
                        <span class="cmbcore-page-btn is-disabled" aria-disabled="true">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </span>
                    @endif

                    @php
                        // Hiển thị tối đa 5 số xung quanh trang hiện tại
                        $range = 2;
                        $start = max(1, $currentPage - $range);
                        $end   = min($lastPage, $currentPage + $range);
                    @endphp

                    @if ($start > 1)
                        <a class="cmbcore-page-btn" href="{{ $prevUrl ? str_replace('page=' . ($currentPage - 1), 'page=1', $prevUrl) : '#' }}?page=1">1</a>
                        @if ($start > 2)
                            <span class="cmbcore-page-ellipsis">…</span>
                        @endif
                    @endif

                    @for ($p = $start; $p <= $end; $p++)
                        @php
                            $pageUrl = $p === 1
                                ? (!empty($selectedCategory['slug'])
                                    ? theme_route_url('storefront.product-categories.show', ['slug' => $selectedCategory['slug']])
                                    : theme_route_url('storefront.products.index'))
                                : ((!empty($selectedCategory['slug'])
                                    ? theme_route_url('storefront.product-categories.show', ['slug' => $selectedCategory['slug']])
                                    : theme_route_url('storefront.products.index')) . '?page=' . $p);
                        @endphp
                        @if ($p === $currentPage)
                            <span class="cmbcore-page-btn is-current" aria-current="page">{{ $p }}</span>
                        @else
                            <a class="cmbcore-page-btn" href="{{ $pageUrl }}">{{ $p }}</a>
                        @endif
                    @endfor

                    @if ($end < $lastPage)
                        @if ($end < $lastPage - 1)
                            <span class="cmbcore-page-ellipsis">…</span>
                        @endif
                        @php
                            $lastUrl = (!empty($selectedCategory['slug'])
                                ? theme_route_url('storefront.product-categories.show', ['slug' => $selectedCategory['slug']])
                                : theme_route_url('storefront.products.index')) . '?page=' . $lastPage;
                        @endphp
                        <a class="cmbcore-page-btn" href="{{ $lastUrl }}">{{ $lastPage }}</a>
                    @endif

                    @if ($nextUrl)
                        <a class="cmbcore-page-btn" href="{{ $nextUrl }}" aria-label="Trang tiếp">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </a>
                    @else
                        <span class="cmbcore-page-btn is-disabled" aria-disabled="true">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </span>
                    @endif
                </nav>
            @endif

        </div>
    </section>
@endsection
