@php
    $product = $product ?? [];
    $primaryMedia = $product['primary_media_url']
        ?? (collect($product['media'] ?? [])->firstWhere('type', 'image')['url'] ?? null);
    $minPrice = $product['min_price'] ?? 0;
    $comparePrice = $product['min_compare_price'] ?? null;
    $hasDiscount = is_numeric($comparePrice) && (float) $comparePrice > (float) $minPrice;
    $discountPercent = $hasDiscount
        ? (int) round((((float) $comparePrice - (float) $minPrice) / (float) $comparePrice) * 100)
        : null;
    $rating = (float) ($product['rating_value'] ?? 0);
    $ratingPercent = max(0, min(100, ($rating / 5) * 100));
@endphp

<article class="sf-product-card">
    <a class="sf-product-card__media" href="{{ theme_route_url('storefront.products.show', ['slug' => $product['slug']]) }}">
        @if ($primaryMedia)
            <img src="{{ $primaryMedia }}" alt="{{ $product['name'] ?? '' }}">
        @else
            <span class="sf-product-card__placeholder">
                <i class="fa-regular fa-image" aria-hidden="true"></i>
            </span>
        @endif

        @if ($discountPercent)
            <span class="sf-product-card__badge">-{{ $discountPercent }}%</span>
        @elseif (!empty($product['flash_sale']))
            <span class="sf-product-card__badge is-accent">Sale</span>
        @endif
    </a>

    <div class="sf-product-card__body">
        @if (!empty($product['category']['name']))
            <a class="sf-product-card__category" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                {{ $product['category']['name'] }}
            </a>
        @endif

        <h3>
            <a href="{{ theme_route_url('storefront.products.show', ['slug' => $product['slug']]) }}">{{ $product['name'] ?? '' }}</a>
        </h3>

        <div class="sf-product-card__price">
            <strong>{{ theme_money($minPrice) }}</strong>
            @if ($hasDiscount)
                <del>{{ theme_money($comparePrice) }}</del>
            @endif
        </div>

        <div class="sf-product-card__meta">
            <span class="sf-rating" aria-label="{{ number_format($rating, 1) }}">
                <span class="sf-rating__stars"><span style="width: {{ $ratingPercent }}%"></span></span>
                <span>{{ number_format($rating, 1) }}</span>
            </span>
            <span>{{ theme_text('products.sold_label', ['count' => $product['sold_count'] ?? 0]) }}</span>
        </div>

        <div class="sf-product-card__actions">
            <span>{{ theme_text('products.stock_label', ['count' => $product['total_stock'] ?? 0]) }}</span>
            <a class="sf-button sf-button--ghost sf-button--small" href="{{ theme_route_url('storefront.products.show', ['slug' => $product['slug']]) }}">
                {{ theme_text('products.view_detail') }}
            </a>
        </div>
    </div>
</article>
