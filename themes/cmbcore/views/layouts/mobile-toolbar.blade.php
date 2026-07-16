@php
    $cartUrl = route('storefront.cart.index');
    $blogUrl = theme_route_url('storefront.blog.categories.show', ['slug' => 'tin-tuc']);
    $cartCount = (int) (app(\Modules\Cart\Services\CartService::class)->headerPreview(auth()->user())['total_quantity'] ?? 0);
@endphp

<nav class="cmbcore-mobile-toolbar" aria-label="Mobile shortcuts">
    <a class="cmbcore-mobile-toolbar__item" href="{{ theme_home_url() }}">
        <i class="fa-solid fa-house cmbcore-mobile-toolbar__icon" aria-hidden="true"></i>
        <span>Trang chủ</span>
    </a>
    <button class="cmbcore-mobile-toolbar__item" type="button" data-cmbcore-drawer-toggle aria-controls="cmbcore-drawer" aria-expanded="false">
        <i class="fa-solid fa-bars cmbcore-mobile-toolbar__icon" aria-hidden="true"></i>
        <span>Danh mục</span>
    </button>
    <a class="cmbcore-mobile-toolbar__item" href="{{ $blogUrl }}">
        <i class="fa-regular fa-comments cmbcore-mobile-toolbar__icon" aria-hidden="true"></i>
        <span>Tư vấn</span>
    </a>
    <a class="cmbcore-mobile-toolbar__item" href="#home-best-sellers">
        <i class="fa-regular fa-bell cmbcore-mobile-toolbar__icon" aria-hidden="true"></i>
        <span>Top bán chạy</span>
    </a>
    <a class="cmbcore-mobile-toolbar__item cmbcore-mobile-toolbar__item--cart" href="{{ $cartUrl }}">
        <span class="cmbcore-mobile-toolbar__icon-wrap">
            <i class="fa-solid fa-bag-shopping cmbcore-mobile-toolbar__icon" aria-hidden="true"></i>
            <span class="cmbcore-cart-badge" data-cart-badge style="{{ $cartCount > 0 ? '' : 'display:none' }}">{{ $cartCount }}</span>
        </span>
        <span>Giỏ hàng</span>
    </a>
</nav>
