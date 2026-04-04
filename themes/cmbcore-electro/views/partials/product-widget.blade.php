@php
    $productUrl = theme_route_url('storefront.products.show', ['slug' => $product['slug']]);
    $minPrice = $product['min_price'] ?? null;
    $comparePrice = $product['min_compare_price'] ?? $product['max_compare_price'] ?? null;
@endphp

<div class="electro-product-widget">
    <div class="electro-product-widget__img">
        <a href="{{ $productUrl }}">
            @if (!empty($product['primary_media_url']))
                <img src="{{ $product['primary_media_url'] }}" alt="{{ $product['name'] }}">
            @endif
        </a>
    </div>
    <div class="electro-product-widget__body">
        @if (!empty($product['category']['name']))
            <span class="electro-product-card__category">{{ $product['category']['name'] }}</span>
        @endif
        <h3><a href="{{ $productUrl }}">{{ $product['name'] }}</a></h3>
        <div class="electro-product-card__price">
            @if (is_numeric($minPrice))
                <strong>{{ theme_money($minPrice) }}</strong>
            @endif
            @if (is_numeric($comparePrice) && (float) $comparePrice > (float) ($minPrice ?? 0))
                <del>{{ theme_money($comparePrice) }}</del>
            @endif
        </div>
    </div>
</div>
