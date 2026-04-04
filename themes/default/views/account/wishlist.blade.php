@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.wishlist_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.wishlist_kicker') }}</span>
                                <h1>{{ theme_text('account.wishlist_title') }}</h1>
                                <p>{{ theme_text('account.wishlist_description') }}</p>
                            </div>
                            <span class="sf-pill">{{ $wishlist_count ?? count($products ?? []) }} {{ theme_text('account.wishlist_count') }}</span>
                        </div>
                    </div>

                    @if (empty($products))
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('account.wishlist_empty_title') }}</h2>
                            <p>{{ theme_text('account.wishlist_empty_description') }}</p>
                            <a class="sf-button sf-button--primary" href="{{ route('storefront.products.index') }}">{{ theme_text('products.browse_all') }}</a>
                        </div>
                    @else
                        <div class="sf-product-grid">
                            @foreach ($products as $product)
                                @include(theme_view('partials.product-card'), ['product' => $product])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
