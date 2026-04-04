@php
    $product = theme_context('product', []);
    $skus = collect($product['skus'] ?? [])->values();
    $media = collect($product['media'] ?? [])->values();
    $initialSku = $skus->first() ?? [];
    $initialPrice = $initialSku['price'] ?? ($product['min_price'] ?? 0);
    $initialComparePrice = $initialSku['compare_price'] ?? null;
    $initialMedia = $media->first() ?? null;
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $product['name'] ?? theme_site_name()))

@section('content')
    <section class="sf-product">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-product__layout">
                <div class="sf-product__gallery" data-sf-gallery>
                    <div class="sf-product__main-media" data-sf-gallery-main>
                        @if ($initialMedia)
                            @if (($initialMedia['type'] ?? 'image') === 'video')
                                <video controls preload="metadata" src="{{ $initialMedia['url'] }}"></video>
                            @else
                                <img src="{{ $initialMedia['url'] }}" alt="{{ $initialMedia['alt_text'] ?? ($product['name'] ?? '') }}">
                            @endif
                        @else
                            <div class="sf-product-card__placeholder">
                                <i class="fa-regular fa-image" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>

                    @if ($media->isNotEmpty())
                        <div class="sf-product__thumbs">
                            @foreach ($media as $item)
                                @php
                                    $thumbHtml = ($item['type'] ?? 'image') === 'video'
                                        ? '<video controls preload="metadata" src="' . e($item['url']) . '"></video>'
                                        : '<img src="' . e($item['url']) . '" alt="' . e($item['alt_text'] ?? ($product['name'] ?? '')) . '">';
                                @endphp
                                <button type="button" class="sf-product__thumb {{ $loop->first ? 'is-active' : '' }}" data-sf-gallery-thumb data-html="{{ e($thumbHtml) }}">
                                    @if (($item['type'] ?? 'image') === 'video')
                                        <video preload="metadata" src="{{ $item['url'] }}"></video>
                                    @else
                                        <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] ?? ($product['name'] ?? '') }}">
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sf-product__summary" data-sf-product-purchase>
                    <div class="sf-product__topline">
                        @if (!empty($product['category']['name']))
                            <a class="sf-pill" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                                {{ $product['category']['name'] }}
                            </a>
                        @endif
                        <span class="sf-pill">{{ theme_text('products.sold_label', ['count' => $product['sold_count'] ?? 0]) }}</span>
                    </div>

                    <h1>{{ $product['name'] ?? '' }}</h1>

                    <div class="sf-product__meta">
                        <span class="sf-rating">
                            <span class="sf-rating__stars"><span style="width: {{ max(0, min(100, (((float) ($product['rating_value'] ?? 0)) / 5) * 100)) }}%"></span></span>
                            <span>{{ number_format((float) ($product['rating_value'] ?? 0), 1) }}</span>
                        </span>
                        <span>{{ theme_text('products.review_suffix', ['count' => $product['review_count'] ?? 0]) }}</span>
                    </div>

                    @if (!empty($product['short_description']))
                        <p>{{ strip_tags((string) $product['short_description']) }}</p>
                    @endif

                    <div class="sf-product__price">
                        <strong data-sf-price>{{ theme_money($initialPrice) }}</strong>
                        <del data-sf-compare-price @if (!is_numeric($initialComparePrice) || (float) $initialComparePrice <= (float) $initialPrice) hidden @endif>
                            {{ is_numeric($initialComparePrice) && (float) $initialComparePrice > (float) $initialPrice ? theme_money($initialComparePrice) : '' }}
                        </del>
                    </div>

                    <div class="sf-product__countdown" data-sf-countdown data-title="{{ data_get($initialSku, 'flash_sale.title', '') }}" data-end-at="{{ data_get($initialSku, 'flash_sale.ends_at', data_get(theme_context('flash_sale', []), 'ends_at', '')) }}" @if (empty(data_get($initialSku, 'flash_sale.ends_at')) && empty(data_get(theme_context('flash_sale', []), 'ends_at'))) hidden @endif></div>

                    <div class="sf-product__facts">
                        <div class="sf-product__fact">
                            <span>{{ theme_text('products.detail_fields.brand') }}</span>
                            <strong>{{ $product['brand'] ?: theme_text('products.not_available') }}</strong>
                        </div>
                        <div class="sf-product__fact">
                            <span>{{ theme_text('products.detail_fields.stock') }}</span>
                            <strong data-sf-stock>{{ theme_text('products.stock_label', ['count' => $initialSku['stock_quantity'] ?? ($product['total_stock'] ?? 0)]) }}</strong>
                        </div>
                        <div class="sf-product__fact">
                            <span>{{ theme_text('products.detail_fields.skus') }}</span>
                            <strong><span data-sf-sku-name>{{ $initialSku['name'] ?? ($initialSku['sku_code'] ?? '-') }}</span> · <span data-sf-sku-code>{{ $initialSku['sku_code'] ?? '-' }}</span></strong>
                        </div>
                    </div>

                    @if ($skus->isNotEmpty())
                        <div>
                            <span class="sf-kicker">{{ theme_text('products.sku_title') }}</span>
                            <div class="sf-product__variant-list">
                                @foreach ($skus as $sku)
                                    <button
                                        type="button"
                                        class="sf-chip {{ $loop->first ? 'is-active' : '' }}"
                                        data-sf-variant-option
                                        data-sku-id="{{ $sku['id'] }}"
                                        data-price="{{ theme_money($sku['price'] ?? 0) }}"
                                        data-compare-price="{{ is_numeric($sku['compare_price'] ?? null) && (float) $sku['compare_price'] > (float) ($sku['price'] ?? 0) ? theme_money($sku['compare_price']) : '' }}"
                                        data-stock="{{ theme_text('products.stock_label', ['count' => $sku['stock_quantity'] ?? 0]) }}"
                                        data-name="{{ $sku['name'] ?: $sku['sku_code'] }}"
                                        data-code="{{ $sku['sku_code'] }}"
                                        data-sale-title="{{ data_get($sku, 'flash_sale.title', '') }}"
                                        data-ends-at="{{ data_get($sku, 'flash_sale.ends_at', '') }}"
                                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    >
                                        {{ $sku['name'] ?: $sku['sku_code'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="sf-product__purchase-actions">
                        <div class="sf-qty" data-sf-qty>
                            <button type="button" data-sf-qty-step="-1" aria-label="{{ theme_text('common.decrease') }}">-</button>
                            <input type="number" value="1" min="1" max="99" data-sf-qty-input>
                            <button type="button" data-sf-qty-step="1" aria-label="{{ theme_text('common.increase') }}">+</button>
                        </div>

                        <form method="post" action="{{ route('storefront.cart.store') }}">
                            @csrf
                            <input type="hidden" name="product_sku_id" value="{{ $initialSku['id'] ?? '' }}" data-sf-sku-input>
                            <input type="hidden" name="quantity" value="1" data-sf-qty-mirror>
                            <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('products.add_to_cart') }}</button>
                        </form>

                        <form method="post" action="{{ route('storefront.checkout.buy_now') }}">
                            @csrf
                            <input type="hidden" name="product_sku_id" value="{{ $initialSku['id'] ?? '' }}" data-sf-sku-input>
                            <input type="hidden" name="quantity" value="1" data-sf-qty-mirror>
                            <button type="submit" class="sf-button sf-button--primary">{{ theme_text('products.buy_now') }}</button>
                        </form>
                    </div>

                    @if (auth()->check() && auth()->user()?->role === 'customer')
                        <form method="post" action="{{ route('storefront.wishlist.toggle', ['slug' => $product['slug']]) }}">
                            @csrf
                            <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('navigation.wishlist') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            <section class="sf-product__section">
                <div class="sf-product__section-body">
                    <div class="sf-section-heading">
                        <div>
                            <span class="sf-kicker">{{ theme_text('products.description_tab') }}</span>
                            <h2>{{ theme_text('products.promo_title') }}</h2>
                            <p>{{ theme_text('products.promo_copy') }}</p>
                        </div>
                    </div>
                    <div class="sf-page__content">
                        {!! $product['description_html'] ?? $product['description'] ?? '' !!}
                    </div>
                </div>
            </section>

            <section class="sf-product__section">
                <div class="sf-product__section-body">
                    <div class="sf-section-heading">
                        <div>
                            <span class="sf-kicker">{{ theme_text('products.procedure_kicker') }}</span>
                            <h2>{{ theme_text('products.sku_title') }}</h2>
                            <p>{{ theme_text('products.sku_description') }}</p>
                        </div>
                    </div>
                    <div class="sf-product__variant-list">
                        @foreach ($skus as $sku)
                            <div class="sf-chip">
                                {{ $sku['sku_code'] }} · {{ theme_money($sku['price'] ?? 0) }} · {{ theme_text('products.stock_label', ['count' => $sku['stock_quantity'] ?? 0]) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="sf-product__section">
                <div class="sf-product__section-body">
                    <div class="sf-section-heading">
                        <div>
                            <span class="sf-kicker">{{ theme_text('products.reviews_kicker') }}</span>
                            <h2>{{ theme_text('products.reviews_title') }}</h2>
                        </div>
                    </div>

                    <div class="sf-review-list">
                        @forelse (theme_context('reviews', []) as $review)
                            <article class="sf-review-card">
                                <div class="sf-product__review-head">
                                    <strong>{{ $review['title'] }}</strong>
                                    <span class="sf-rating">
                                        <span class="sf-rating__stars"><span style="width: {{ max(0, min(100, (((float) ($review['rating'] ?? 0)) / 5) * 100)) }}%"></span></span>
                                        <span>{{ number_format((float) ($review['rating'] ?? 0), 1) }}</span>
                                    </span>
                                </div>
                                <small>{{ $review['author_name'] ?? theme_text('common.customer') }} · {{ !empty($review['created_at']) ? \Illuminate\Support\Carbon::parse($review['created_at'])->translatedFormat('d/m/Y') : '' }}</small>
                                <p>{{ $review['content'] }}</p>
                                @if (!empty($review['admin_reply']))
                                    <div class="sf-alert is-success">{{ $review['admin_reply'] }}</div>
                                @endif
                            </article>
                        @empty
                            <div class="sf-empty-state">
                                <h3>{{ theme_text('products.reviews_empty_title') }}</h3>
                                <p>{{ theme_text('products.reviews_empty_description') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="sf-product__section" style="margin-top: 1.5rem;">
                        <div class="sf-product__section-body">
                            <h2>{{ theme_text('products.reviews_form_title') }}</h2>
                            @if (theme_context('can_review'))
                                <form method="post" action="{{ route('storefront.products.reviews.store', ['slug' => $product['slug']]) }}" class="sf-form-grid sf-form-grid--2">
                                    @csrf
                                    <label class="sf-field">
                                        <span>{{ theme_text('products.reviews_fields.rating') }}</span>
                                        <select name="rating" required>
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                <option value="{{ $rating }}">{{ $rating }}/5</option>
                                            @endfor
                                        </select>
                                    </label>
                                    <label class="sf-field">
                                        <span>{{ theme_text('products.reviews_fields.title') }}</span>
                                        <input type="text" name="title" required>
                                    </label>
                                    <label class="sf-field is-full">
                                        <span>{{ theme_text('products.reviews_fields.content') }}</span>
                                        <textarea name="content" required></textarea>
                                    </label>
                                    <div>
                                        <button type="submit" class="sf-button sf-button--primary">{{ theme_text('products.reviews_submit') }}</button>
                                    </div>
                                </form>
                            @else
                                <div class="sf-alert is-error">
                                    <strong>{{ theme_text('products.reviews_locked_title') }}</strong>
                                    <span>{{ theme_text('products.reviews_locked_description') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            @if (!empty(theme_context('related_products', [])))
                <section class="sf-product__section">
                    <div class="sf-section-heading">
                        <div>
                            <span class="sf-kicker">{{ theme_text('products.related_kicker') }}</span>
                            <h2>{{ theme_text('products.related_title') }}</h2>
                            <p>{{ theme_text('products.related_description') }}</p>
                        </div>
                    </div>
                    <div class="sf-product-grid">
                        @foreach (theme_context('related_products', []) as $relatedProduct)
                            @include(theme_view('partials.product-card'), ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const root = document.querySelector('[data-sf-product-purchase]');
            const qty = root?.querySelector('[data-sf-qty-input]');
            const mirrors = root ? Array.from(root.querySelectorAll('[data-sf-qty-mirror]')) : [];

            const syncQty = () => {
                if (!(qty instanceof HTMLInputElement)) {
                    return;
                }

                mirrors.forEach((input) => {
                    if (input instanceof HTMLInputElement) {
                        input.value = qty.value || '1';
                    }
                });
            };

            qty?.addEventListener('change', syncQty);
            syncQty();
        })();
    </script>
@endsection
