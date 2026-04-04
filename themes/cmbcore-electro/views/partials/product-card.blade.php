@php
    $minPrice = $product['min_price'] ?? null;
    $maxPrice = $product['max_price'] ?? null;
    $comparePrice = $product['min_compare_price'] ?? $product['max_compare_price'] ?? null;
    $discount = (is_numeric($comparePrice) && is_numeric($minPrice) && (float) $comparePrice > (float) $minPrice)
        ? (int) round((((float) $comparePrice - (float) $minPrice) / (float) $comparePrice) * 100)
        : null;
    $rating = (float) ($product['rating_value'] ?? 0);
    $reviewCount = (int) ($product['review_count'] ?? 0);
    $soldCount = $product['sold_count'] ?? 0;
    $productUrl = theme_route_url('storefront.products.show', ['slug' => $product['slug']]);
@endphp

<article class="electro-product-card">
    <a class="electro-product-card__media" href="{{ $productUrl }}">
        @if ($discount)
            <span class="electro-product-card__badge">-{{ $discount }}%</span>
        @endif
        @if (!empty($product['primary_media_url']))
            <img src="{{ $product['primary_media_url'] }}" alt="{{ $product['name'] }}">
        @else
            <span class="electro-product-card__placeholder">
                <i class="fa fa-image" aria-hidden="true"></i>
            </span>
        @endif
    </a>
    <div class="electro-product-card__body">
        @if (!empty($product['category']['name']))
            <a class="electro-product-card__category" href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $product['category']['slug']]) }}">
                {{ $product['category']['name'] }}
            </a>
        @endif
        <h3><a href="{{ $productUrl }}">{{ $product['name'] }}</a></h3>
        <div class="electro-product-card__price">
            @if (is_numeric($minPrice))
                <strong>{{ theme_money($minPrice) }}</strong>
                @if (is_numeric($maxPrice) && (float) $maxPrice > (float) $minPrice)
                    - {{ theme_money($maxPrice) }}
                @endif
            @endif
            @if (is_numeric($comparePrice) && (float) $comparePrice > (float) ($minPrice ?? 0))
                <del>{{ theme_money($comparePrice) }}</del>
            @endif
        </div>
        <div class="electro-product-card__meta">
            <div class="electro-product-card__rating">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="fa {{ $i <= round($rating) ? 'fa-star' : 'fa-star-o' }}"></i>
                @endfor
                <span>({{ $reviewCount }})</span>
            </div>
            <span class="electro-product-card__sold">
                {{ theme_text('products.sold_label', ['count' => $soldCount]) }}
            </span>
        </div>
        <div class="electro-product-card__btns">
            @auth
                <form method="post" action="{{ route('storefront.wishlist.toggle', ['slug' => $product['slug']]) }}" style="display:inline">
                    @csrf
                    <button type="submit" title="{{ theme_text('products.add_to_cart') }}"><i class="fa fa-heart-o"></i></button>
                </form>
            @endauth
        </div>
    </div>
    <form method="post" action="{{ route('storefront.cart.store') }}">
        @csrf
        <input type="hidden" name="product_slug" value="{{ $product['slug'] }}">
        @if (!empty($product['default_sku_id']))
            <input type="hidden" name="sku_id" value="{{ $product['default_sku_id'] }}">
        @endif
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="electro-product-card__add-to-cart">
            <i class="fa fa-shopping-cart"></i> {{ theme_text('products.add_to_cart') }}
        </button>
    </form>
</article>
