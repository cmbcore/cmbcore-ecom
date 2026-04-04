@php
    $links = [
        ['route' => 'storefront.account.dashboard', 'label' => theme_text('account.sidebar.overview'), 'icon' => 'fa-solid fa-house'],
        ['route' => 'storefront.account.profile', 'label' => theme_text('account.sidebar.profile'), 'icon' => 'fa-regular fa-user'],
        ['route' => 'storefront.account.orders', 'label' => theme_text('account.sidebar.orders'), 'icon' => 'fa-solid fa-box'],
        ['route' => 'storefront.wishlist.index', 'label' => theme_text('account.sidebar.wishlist'), 'icon' => 'fa-regular fa-heart'],
        ['route' => 'storefront.account.reviews', 'label' => theme_text('account.sidebar.reviews'), 'icon' => 'fa-regular fa-star'],
        ['route' => 'storefront.account.addresses', 'label' => theme_text('account.sidebar.addresses'), 'icon' => 'fa-solid fa-location-dot'],
        ['route' => 'storefront.account.returns', 'label' => theme_text('account.sidebar.returns'), 'icon' => 'fa-solid fa-arrow-rotate-left'],
    ];
@endphp

<aside class="sf-account-sidebar cmbcore-account-card">
    <div class="sf-account-sidebar__card">
        <span class="sf-kicker">{{ theme_text('account.dashboard_title') }}</span>
        <nav class="sf-account-sidebar__nav" aria-label="{{ theme_text('account.dashboard_title') }}">
            @foreach ($links as $link)
                @php
                    $isActive = request()->routeIs($link['route']) || request()->routeIs($link['route'] . '.*');
                @endphp
                <a class="{{ $isActive ? 'is-active' : '' }}" href="{{ route($link['route']) }}">
                    <i class="{{ $link['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <form method="post" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="sf-button sf-button--ghost sf-account-sidebar__logout">
                {{ theme_text('account.actions.logout') }}
            </button>
        </form>
    </div>
</aside>
