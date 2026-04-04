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
@endphp

<article class="cmbcore-product-card">
    <a class="cmbcore-product-card__media" href="{{ $productUrl }}">
        @if ($discount)
            <span class="cmbcore-product-card__badge">-{{ $discount }}%</span>
        @endif
        @if (!empty($product['primary_media_url']))
            <img src="{{ $product['primary_media_url'] }}" alt="{{ $product['name'] }}">
        @else
            <span class="cmbcore-product-card__placeholder">
                <i class="fa-regular fa-image" aria-hidden="true"></i>
            </span>
        @endif
    </a>

    <div class="cmbcore-product-card__body">
        <h3>
            <a href="{{ $productUrl }}">{{ $product['name'] }}</a>
        </h3>
        <div class="cmbcore-product-card__price">
            @if (is_numeric($comparePrice) && (float) $comparePrice > (float) ($minPrice ?? 0))
                <del>{{ theme_money($comparePrice) }}</del>
            @endif
            @if (is_numeric($minPrice))
                <strong>
                    {{ theme_money($minPrice) }}
                    @if (is_numeric($maxPrice) && (float) $maxPrice > (float) $minPrice)
                        - {{ theme_money($maxPrice) }}
                    @endif
                </strong>
            @endif
        </div>
        <div class="cmbcore-product-card__meta">
            <div class="cmbcore-product-card__rating">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
                <span>{{ number_format($rating, 1) }}</span>
                <span class="cmbcore-product-card__reviews">({{ $reviewCount }})</span>
            </div>
            <span class="cmbcore-product-card__sold">
                {{ theme_text('products.sold_label', ['count' => $soldCount]) }}
            </span>
        </div>
        @if (!empty($product['category']['name']))
            <a class="cmbcore-product-card__category" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                {{ $product['category']['name'] }}
            </a>
        @endif
        @auth
            <form method="post" action="{{ route('storefront.wishlist.toggle', ['slug' => $product['slug']]) }}" style="margin-top: 12px;">
                @csrf
                <button type="submit" class="cmbcore-button is-secondary">Yêu thích</button>
            </form>
        @endauth
    </div>
</article>

