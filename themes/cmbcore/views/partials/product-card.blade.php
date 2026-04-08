@php
    $minPrice = $product['min_price'] ?? null;
    $maxPrice = $product['max_price'] ?? null;
    $comparePrice = $product['min_compare_price'] ?? $product['max_compare_price'] ?? null;
    $discount = (is_numeric($comparePrice) && is_numeric($minPrice) && (float) $comparePrice > (float) $minPrice)
        ? (int) round((((float) $comparePrice - (float) $minPrice) / (float) $comparePrice) * 100)
        : null;
    $rating = (float) ($product['rating_value'] ?? 0);
    $productUrl = theme_route_url('storefront.products.show', ['slug' => $product['slug']]);
    $reviewCount = (int) ($product['review_count'] ?? 0);
    $soldCount = $product['sold_count'] ?? 0;
    $defaultSkuId = collect($product['skus'] ?? [])->first()['id'] ?? '';
    $soldDisplay = is_numeric($soldCount) && (float)$soldCount >= 1000
        ? number_format((float)$soldCount / 1000, 1) . 'k'
        : $soldCount;
@endphp

<article class="cmbcore-product-card">
    {{-- Product image --}}
    <div class="cmbcore-product-card__media-wrap">
        <a class="cmbcore-product-card__media" href="{{ $productUrl }}">
            @if ($discount)
                <span class="cmbcore-product-card__badge">-{{ $discount }}%</span>
            @endif
            @if (!empty($product['primary_media_url']))
                <img src="{{ $product['primary_media_url'] }}" alt="{{ $product['name'] }}" loading="lazy">
            @else
                <span class="cmbcore-product-card__placeholder">
                    <i class="fa-regular fa-image" aria-hidden="true"></i>
                </span>
            @endif
        </a>

        {{-- Quick-buy bag icon button (outside <a> to avoid invalid nesting) --}}
        <form method="post" action="{{ route('storefront.cart.store') }}" class="cmbcore-product-card__quick-buy">
            @csrf
            <input type="hidden" name="product_sku_id" value="{{ $defaultSkuId }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="cmbcore-product-card__cart-btn" aria-label="Thêm vào giỏ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </button>
        </form>
    </div>

    <div class="cmbcore-product-card__body">
        <h3>
            <a href="{{ $productUrl }}">{{ $product['name'] }}</a>
        </h3>
        <div class="cmbcore-product-card__price-row">
            <div class="cmbcore-product-card__price">
                @if (is_numeric($minPrice))
                    <strong>{{ theme_money($minPrice) }}</strong>
                @endif
                @if (is_numeric($comparePrice) && (float) $comparePrice > (float) ($minPrice ?? 0))
                    <del>{{ theme_money($comparePrice) }}</del>
                @endif
            </div>
            @if ($discount)
                <span class="cmbcore-product-card__discount-inline">-{{ $discount }}%</span>
            @endif
        </div>
        <div class="cmbcore-product-card__meta">
            @if ($rating > 0 || $reviewCount > 0)
                <div class="cmbcore-product-card__rating">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <span>{{ number_format($rating, 1) }}</span>
                    <span class="cmbcore-product-card__reviews">({{ $reviewCount }})</span>
                </div>
            @endif
            @if ($soldCount)
                <span class="cmbcore-product-card__sold">Đã bán {{ $soldDisplay }}</span>
            @endif
        </div>
    </div>
</article>
