@php
    $products = theme_context('products', []);
    $searchQuery = request()->query('search', '');
    $pagination = theme_context('pagination', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_text('search.query', ['query' => $searchQuery]) . ' - ' . theme_site_name())

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('search.title'),
        'breadcrumbs' => [
            ['label' => theme_text('search.title')],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <h2>{{ theme_text('search.query', ['query' => $searchQuery]) }}</h2>

            @if (empty($products))
                <div class="electro-text-center" style="padding:60px 0;">
                    <h3>{{ theme_text('search.empty_title') }}</h3>
                    <p>{{ theme_text('search.empty_description') }}</p>
                    <a href="{{ theme_route_url('storefront.products.index') }}" class="electro-primary-btn">{{ theme_text('products.browse_all') }}</a>
                </div>
            @else
                <div class="electro-product-grid">
                    @foreach ($products as $product)
                        @include(theme_view('partials.product-card'), ['product' => $product])
                    @endforeach
                </div>

                @if (!empty($pagination['last_page']) && $pagination['last_page'] > 1)
                    <ul class="electro-pagination">
                        @for ($p = 1; $p <= $pagination['last_page']; $p++)
                            <li class="{{ $p === ($pagination['current_page'] ?? 1) ? 'active' : '' }}">
                                @if ($p === ($pagination['current_page'] ?? 1))
                                    <span>{{ $p }}</span>
                                @else
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                                @endif
                            </li>
                        @endfor
                    </ul>
                @endif
            @endif
        </div>
    </div>
@endsection
