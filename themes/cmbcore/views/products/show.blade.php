@php
    $product = theme_context('product', []);
    $media = collect($product['media'] ?? []);
    $primaryMedia = $media->first();
    $attributes = collect($product['skus'] ?? [])
        ->flatMap(fn (array $sku): array => $sku['attributes'] ?? [])
        ->groupBy('attribute_name')
        ->map(fn (\Illuminate\Support\Collection $items): array => $items->pluck('attribute_value')->unique()->values()->all());
    $defaultSku = collect($product['skus'] ?? [])->first();
    $defaultComparePrice = $defaultSku['compare_price'] ?? $product['min_compare_price'] ?? null;
    $defaultPrice = $defaultSku['price'] ?? $product['min_price'] ?? null;
    $defaultDiscount = (is_numeric($defaultComparePrice) && is_numeric($defaultPrice) && (float) $defaultComparePrice > (float) $defaultPrice)
        ? (int) round((((float) $defaultComparePrice - (float) $defaultPrice) / (float) $defaultComparePrice) * 100)
        : null;
    $rating = (float) ($product['rating_value'] ?? 0);
    $ratingPercent = max(0, min(100, ($rating / 5) * 100));
    $promoTitle = (string) theme_setting('product_promo_title', theme_text('products.promo_title'));
    $promoCopy = (string) theme_setting('product_promo_copy', theme_text('products.promo_copy'));
    $reviews = theme_context('reviews', []);
    $canReview = (bool) theme_context('can_review', false);
    $flashSale = theme_context('flash_sale');
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $product['name'] ?? theme_site_name()))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            <div class="cmbcore-product-detail">
                <div class="cmbcore-gallery" data-cmbcore-gallery>
                    <div class="cmbcore-gallery__main">
                        @if ($primaryMedia)
                            <img src="{{ $primaryMedia['url'] }}" alt="{{ $primaryMedia['alt_text'] ?: ($product['name'] ?? '') }}" data-gallery-target>
                        @else
                            <span class="cmbcore-product-card__placeholder">
                                <i class="fa-regular fa-image" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                    @if ($media->isNotEmpty())
                        <div class="cmbcore-gallery__thumbs">
                            @foreach ($media as $index => $item)
                                <button class="cmbcore-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}" type="button" data-gallery-thumb="{{ $item['url'] }}">
                                    <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] ?: ($product['name'] ?? '') }}">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="cmbcore-product-summary" data-cmbcore-product data-product='@json($product)'>
                    @if (!empty($product['category']['name']))
                        <a class="cmbcore-product-summary__category" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                            {{ $product['category']['name'] }}
                        </a>
                    @endif
                    <h1 class="cmbcore-product-summary__title">{{ $product['name'] ?? '' }}</h1>

                    <div class="cmbcore-product-summary__rating">
                        <span class="cmbcore-rating-value">{{ number_format($rating, 1) }}</span>
                        <span class="cmbcore-stars" aria-hidden="true">
                            <span style="width: {{ $ratingPercent }}%"></span>
                        </span>
                        <span class="cmbcore-product-summary__review-count">({{ (int) ($product['review_count'] ?? 0) }} đánh giá)</span>
                        <span class="cmbcore-product-summary__sep">|</span>
                        <span class="cmbcore-product-summary__sold">Đã bán {{ is_numeric($product['sold_count'] ?? 0) && ($product['sold_count'] ?? 0) >= 1000 ? number_format(($product['sold_count'] ?? 0) / 1000, 1) . 'k' : ($product['sold_count'] ?? 0) }}</span>
                    </div>

                    <div class="cmbcore-product-summary__price" data-product-price>
                        @if ($defaultDiscount)
                            <span class="cmbcore-product-summary__discount">-{{ $defaultDiscount }}%</span>
                        @endif
                        @if (is_numeric($defaultComparePrice) && (float) $defaultComparePrice > (float) ($defaultPrice ?? 0))
                            <del>{{ theme_money($defaultComparePrice) }}</del>
                        @endif
                        @if (is_numeric($defaultPrice))
                            <strong>{{ theme_money($defaultPrice) }}</strong>
                        @endif
                    </div>

                    @if (!empty($flashSale))
                        <div
                            class="cmbcore-product-summary__promo"
                            style="margin-top: 12px;"
                            data-test-title="Flash Sale"
                            data-flash-sale-countdown
                            data-flash-sale-ends-at="{{ $flashSale['ends_at'] }}"
                        >
                            <h2>{{ $flashSale['title'] }}</h2>
                            <p>Giá ưu đãi hiện tại: <strong>{{ theme_money($flashSale['sale_price']) }}</strong></p>
                            <p>
                                Kết thúc sau:
                                <span data-flash-sale-countdown-label>
                                    {{ $flashSale['ends_at'] }}
                                </span>
                            </p>
                        </div>
                    @endif

                    @if (!empty($product['short_description_html']))
                        <div class="cmbcore-product-summary__lead">
                            {!! $product['short_description_html'] !!}
                        </div>
                    @endif

                    <div class="cmbcore-product-summary__promo" data-test-title="{{ \Illuminate\Support\Str::ascii($promoTitle) }}">
                        <h2>{{ $promoTitle }}</h2>
                        <p>{{ $promoCopy }}</p>
                    </div>

                    @if ($attributes->isNotEmpty())
                        <div class="cmbcore-product-summary__options">
                            @foreach ($attributes as $attributeName => $values)
                                <div class="cmbcore-option-group">
                                    <strong>{{ $attributeName }}</strong>
                                    <div class="cmbcore-swatches">
                                        @foreach ($values as $index => $value)
                                            <button
                                                type="button"
                                                class="cmbcore-swatch {{ $index === 0 ? 'is-active' : '' }}"
                                                data-swatch-name="{{ $attributeName }}"
                                                data-swatch-value="{{ $value }}"
                                            >
                                                {{ $value }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="cmbcore-quantity">
                        <button type="button" data-quantity-step="-1">-</button>
                        <input type="number" value="1" min="1" data-quantity-input aria-label="{{ theme_text('products.quantity_label') }}">
                        <button type="button" data-quantity-step="1">+</button>
                    </div>

                    <form method="post" action="{{ route('storefront.cart.store') }}" class="cmbcore-product-summary__actions" data-product-purchase-form>
                        @csrf
                        <input type="hidden" name="product_sku_id" value="{{ $defaultSku['id'] ?? '' }}" data-product-sku-input>
                        <input type="hidden" name="quantity" value="1" data-product-quantity-input>
                        <button type="submit" class="cmbcore-button is-secondary cmbcore-button--uppercase">THÊM VÀO GIỎ HÀNG</button>
                        <button type="submit" formaction="{{ route('storefront.checkout.buy_now') }}" class="cmbcore-button is-primary cmbcore-button--uppercase">MUA NGAY</button>
                    </form>

                    {{-- Benefit icons grid (matches rhysman.vn) --}}
                    <div class="cmbcore-product-benefits">
                        <div class="cmbcore-product-benefit">
                            <span class="cmbcore-product-benefit__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M1 3h1l3.6 7.59L3.25 13c-.16.28-.25.61-.25.96C3 15.1 3.9 16 5 16h14m-9 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm8 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM5.82 13H17l2-8H4.21"/></svg>
            </span>
                            <div>
                                <strong>Giao hàng toàn quốc</strong>
                                <span>Miễn phí từ 500k</span>
                            </div>
                        </div>
                        <div class="cmbcore-product-benefit">
                            <span class="cmbcore-product-benefit__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </span>
                            <div>
                                <strong>Cam kết chính hãng</strong>
                                <span>100% hàng chính hãng</span>
                            </div>
                        </div>
                        <div class="cmbcore-product-benefit">
                            <span class="cmbcore-product-benefit__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
                            <div>
                                <strong>Hỗ trợ 24/7</strong>
                                <span>Tư vấn tận tình</span>
                            </div>
                        </div>
                        <div class="cmbcore-product-benefit">
                            <span class="cmbcore-product-benefit__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>
                            <div>
                                <strong>Đổi trả dễ dàng</strong>
                                <span>Trong vòng 7 ngày</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="cmbcore-product-description">
                <header class="cmbcore-product-description__header">
                    <span>{{ theme_text('products.procedure_kicker') }}</span>
                    <h2>{{ theme_text('products.description_tab') }}</h2>
                </header>
                <div class="cmbcore-prose">
                    {!! $product['description_html'] ?? '' !!}
                </div>
            </section>

            <section class="cmbcore-product-description">
                <header class="cmbcore-product-description__header">
                    <span>Trải nghiệm mua hàng</span>
                    <h2>Đánh giá sản phẩm</h2>
                </header>

                @if ($reviews === [])
                    <p>Sản phẩm chưa có đánh giá được duyệt.</p>
                @else
                    <div class="cmbcore-prose">
                        @foreach ($reviews as $review)
                            <article style="margin-bottom: 24px;">
                                <strong>{{ $review['title'] }}</strong>
                                <div>{{ str_repeat('★', (int) $review['rating']) }}{{ str_repeat('☆', 5 - (int) $review['rating']) }}</div>
                                <p>{{ $review['content'] }}</p>
                                <small>{{ $review['author_name'] }} @if($review['is_verified_purchase']) · Đã mua hàng @endif</small>
                                @if (!empty($review['admin_reply']))
                                    <div style="margin-top: 8px;"><strong>Phản hồi từ shop:</strong> {{ $review['admin_reply'] }}</div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif

                @auth
                    @if ($canReview)
                        <form method="post" action="{{ route('storefront.products.reviews.store', ['slug' => $product['slug']]) }}" class="cmbcore-form-grid">
                            @csrf
                            <label>
                                <span>Số sao</span>
                                <select name="rating">
                                    @for ($star = 5; $star >= 1; $star--)
                                        <option value="{{ $star }}">{{ $star }} sao</option>
                                    @endfor
                                </select>
                            </label>
                            <label class="is-full">
                                <span>Tiêu đề</span>
                                <input type="text" name="title" required>
                            </label>
                            <label class="is-full">
                                <span>Nội dung</span>
                                <textarea name="content" rows="4" required></textarea>
                            </label>
                            <button type="submit" class="cmbcore-button is-primary">Gửi đánh giá</button>
                        </form>
                    @else
                        <p>Bạn cần có đơn hàng đã xác nhận hoặc đã giao mới có thể đánh giá sản phẩm này.</p>
                    @endif
                @else
                    <p>Đăng nhập tài khoản đã mua sản phẩm để gửi đánh giá.</p>
                @endauth
            </section>

            @if (!empty(theme_context('related_products', [])))
                <section class="cmbcore-related-block">
                    <div class="cmbcore-section-title cmbcore-section-title--detail">
                        <h2>{{ theme_text('products.related_title') }}</h2>
                    </div>
                    <div class="cmbcore-product-grid">
                        @foreach (theme_context('related_products', []) as $relatedProduct)
                            @include(theme_view('partials.product-card'), ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection

