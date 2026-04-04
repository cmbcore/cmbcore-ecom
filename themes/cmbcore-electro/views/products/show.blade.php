@php
    $product = theme_context('product', []);
    $skus = collect($product['skus'] ?? [])->values();
    $media = collect($product['media'] ?? [])->values();
    $initialSku = $skus->first() ?? [];
    $initialPrice = $initialSku['price'] ?? ($product['min_price'] ?? 0);
    $initialComparePrice = $initialSku['compare_price'] ?? null;
    $initialMedia = $media->first() ?? null;
    $reviews = theme_context('reviews', []);
    $relatedProducts = theme_context('related_products', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $product['name'] ?? theme_site_name()))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => $product['name'] ?? '',
        'breadcrumbs' => [
            ['label' => theme_text('navigation.products'), 'url' => theme_route_url('storefront.products.index')],
            ...(!empty($product['category']) ? [['label' => $product['category']['name'], 'url' => theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']])]] : []),
            ['label' => $product['name'] ?? ''],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            {{-- Product Detail --}}
            <div class="electro-product-detail">
                {{-- Gallery --}}
                <div class="electro-product-gallery">
                    <div class="electro-product-gallery__main">
                        @if ($initialMedia)
                            <img src="{{ $initialMedia['url'] }}" alt="{{ $initialMedia['alt_text'] ?? ($product['name'] ?? '') }}">
                        @else
                            <div class="electro-product-card__placeholder">
                                <i class="fa fa-image" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>
                    @if ($media->count() > 1)
                        <div class="electro-product-gallery__thumbs">
                            @foreach ($media as $item)
                                <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] ?? ($product['name'] ?? '') }}" class="{{ $loop->first ? 'active' : '' }}">
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Product Info --}}
                <div class="electro-product-info">
                    @if (!empty($product['category']['name']))
                        <a class="electro-product-card__category" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                            {{ $product['category']['name'] }}
                        </a>
                    @endif

                    <h1 class="electro-product-name">{{ $product['name'] ?? '' }}</h1>

                    <div class="electro-product-rating-inline">
                        <span class="electro-stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa {{ $i <= round((float) ($product['rating_value'] ?? 0)) ? 'fa-star' : 'fa-star-o' }}"></i>
                            @endfor
                        </span>
                        <span class="electro-review-link">{{ theme_text('products.review_suffix', ['count' => $product['review_count'] ?? 0]) }}</span>
                    </div>

                    <div class="electro-product-price-block">
                        <h3 data-electro-price>{{ theme_money($initialPrice) }}</h3>
                        @if (is_numeric($initialComparePrice) && (float) $initialComparePrice > (float) $initialPrice)
                            <del data-electro-compare-price>{{ theme_money($initialComparePrice) }}</del>
                        @endif
                        <span class="electro-product-available">
                            {{ theme_text('products.stock_label', ['count' => $initialSku['stock_quantity'] ?? ($product['total_stock'] ?? 0)]) }}
                        </span>
                    </div>

                    @if (!empty($product['short_description']))
                        <p style="color: var(--electro-grey); margin: 15px 0;">{{ strip_tags((string) $product['short_description']) }}</p>
                    @endif

                    {{-- SKU variants --}}
                    @if ($skus->count() > 1)
                        <div class="electro-product-options">
                            <label>
                                {{ theme_text('products.sku_title') }}
                                <select class="electro-input-select" data-electro-sku-select>
                                    @foreach ($skus as $sku)
                                        <option value="{{ $sku['id'] }}" data-price="{{ theme_money($sku['price'] ?? 0) }}" data-stock="{{ $sku['stock_quantity'] ?? 0 }}">
                                            {{ $sku['name'] ?: $sku['sku_code'] }} — {{ theme_money($sku['price'] ?? 0) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    @endif

                    {{-- Add to Cart --}}
                    <div class="electro-product-add-to-cart">
                        <div class="electro-input-number">
                            <input type="number" id="electro-qty-input" value="1" min="1" max="99">
                            <span class="electro-qty-up">+</span>
                            <span class="electro-qty-down">-</span>
                        </div>

                        <form method="post" action="{{ route('storefront.cart.store') }}">
                            @csrf
                            <input type="hidden" name="product_sku_id" value="{{ $initialSku['id'] ?? '' }}" data-electro-sku-input>
                            <input type="hidden" name="quantity" value="1" data-electro-qty-mirror>
                            <button type="submit" class="electro-add-to-cart-btn">
                                <i class="fa fa-shopping-cart"></i> {{ theme_text('products.add_to_cart') }}
                            </button>
                        </form>
                    </div>

                    {{-- Wishlist --}}
                    @if (auth()->check() && auth()->user()?->role === 'customer')
                        <form method="post" action="{{ route('storefront.wishlist.toggle', ['slug' => $product['slug']]) }}" style="margin-top:10px;">
                            @csrf
                            <button type="submit" class="electro-primary-btn" style="background:transparent; color:var(--electro-heading); border:1px solid var(--electro-border);">
                                <i class="fa fa-heart-o"></i> {{ theme_text('navigation.wishlist') }}
                            </button>
                        </form>
                    @endif

                    {{-- Product links --}}
                    <ul class="electro-product-links">
                        @if (!empty($product['brand']))
                            <li>{{ theme_text('products.detail_fields.brand') }}: <strong>{{ $product['brand'] }}</strong></li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- Product Tabs --}}
            <div class="electro-product-tab">
                <ul class="electro-tab-nav">
                    <li class="active"><a href="#" data-tab="electro-tab-desc">{{ theme_text('products.description_tab') }}</a></li>
                    <li><a href="#" data-tab="electro-tab-reviews">{{ theme_text('products.reviews_title') }} ({{ count($reviews) }})</a></li>
                </ul>

                {{-- Description Tab --}}
                <div class="electro-tab-pane active" id="electro-tab-desc">
                    <div class="electro-product-description">
                        {!! $product['description_html'] ?? $product['description'] ?? '' !!}
                    </div>
                </div>

                {{-- Reviews Tab --}}
                <div class="electro-tab-pane" id="electro-tab-reviews">
                    @if (!empty($reviews))
                        <ul class="electro-reviews-list">
                            @foreach ($reviews as $review)
                                <li>
                                    <div class="electro-review-heading">
                                        <span class="electro-name">{{ $review['author_name'] ?? theme_text('common.customer') }}</span>
                                        <span class="electro-date">{{ !empty($review['created_at']) ? \Illuminate\Support\Carbon::parse($review['created_at'])->translatedFormat('d/m/Y') : '' }}</span>
                                    </div>
                                    <div class="electro-review-rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa {{ $i <= (int) ($review['rating'] ?? 0) ? 'fa-star' : 'fa-star-o' }}"></i>
                                        @endfor
                                    </div>
                                    @if (!empty($review['title']))
                                        <strong>{{ $review['title'] }}</strong>
                                    @endif
                                    <div class="electro-review-body">
                                        <p>{{ $review['content'] }}</p>
                                    </div>
                                    @if (!empty($review['admin_reply']))
                                        <div class="electro-alert electro-alert--success" style="margin-top:8px;">
                                            {{ $review['admin_reply'] }}
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ theme_text('products.reviews_empty_description') }}</p>
                    @endif

                    {{-- Review Form --}}
                    <div style="margin-top: 30px;">
                        <h3>{{ theme_text('products.reviews_form_title') }}</h3>
                        @if (theme_context('can_review'))
                            <form method="post" action="{{ route('storefront.products.reviews.store', ['slug' => $product['slug']]) }}" class="electro-review-form">
                                @csrf
                                <div class="electro-input-rating">
                                    <label>{{ theme_text('products.reviews_fields.rating') }}</label>
                                    <select name="rating" class="electro-input-select" required>
                                        @for ($r = 5; $r >= 1; $r--)
                                            <option value="{{ $r }}">{{ $r }}/5</option>
                                        @endfor
                                    </select>
                                </div>
                                <input class="electro-input" type="text" name="title" placeholder="{{ theme_text('products.reviews_fields.title') }}" required>
                                <textarea class="electro-input" name="content" placeholder="{{ theme_text('products.reviews_fields.content') }}" required></textarea>
                                <button type="submit" class="electro-primary-btn" style="margin-top:10px;">{{ theme_text('products.reviews_submit') }}</button>
                            </form>
                        @else
                            <div class="electro-alert electro-alert--error">
                                <strong>{{ theme_text('products.reviews_locked_title') }}</strong>
                                <p>{{ theme_text('products.reviews_locked_description') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Related Products --}}
            @if (!empty($relatedProducts))
                <div class="electro-section" style="padding-top:0;">
                    <div class="electro-section-title">
                        <h3 class="electro-title">{{ theme_text('products.related_title') }}</h3>
                    </div>
                    <div class="electro-product-grid">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include(theme_view('partials.product-card'), ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (() => {
            const qtyInput = document.getElementById('electro-qty-input');
            const mirrors = document.querySelectorAll('[data-electro-qty-mirror]');
            const syncQty = () => {
                if (qtyInput) {
                    mirrors.forEach(m => { m.value = qtyInput.value || '1'; });
                }
            };
            if (qtyInput) {
                qtyInput.addEventListener('change', syncQty);
                syncQty();
            }
        })();
    </script>
@endsection
