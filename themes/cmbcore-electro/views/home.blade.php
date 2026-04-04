@php
    $heroSlides = theme_context('hero_slides', []);
    $productSections = theme_context('product_sections', []);
    $latestPosts = theme_context('latest_posts', []);
    $categoryBanners = theme_setting_json('home_category_banners', []);
    $latestPostsTitle = (string) theme_setting('home_latest_posts_title', theme_text('home.latest_posts_title'));
    $newProductsTitle = (string) theme_setting('home_new_products_title', theme_text('home.latest_products_title'));
    $topSellingTitle = (string) theme_setting('home_top_selling_title', theme_text('home.best_sellers_title'));
    $hotDealTitle = (string) theme_setting('home_hot_deal_title', theme_text('home.fallback_title'));
    $newsletterEnabled = theme_setting('newsletter_enabled', '1') === '1';
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_site_name()))

@section('content')
    {{-- CATEGORY BANNERS --}}
    @if (!empty($categoryBanners))
        <div class="electro-section">
            <div class="electro-container">
                <div class="electro-row">
                    @foreach (array_slice($categoryBanners, 0, 3) as $banner)
                        <div class="electro-col electro-col-banner">
                            <div class="electro-shop">
                                <div class="electro-shop-img">
                                    @if (!empty($banner['image']))
                                        <img src="{{ theme_media_url($banner['image'] ?? null) }}" alt="{{ $banner['title'] ?? '' }}">
                                    @endif
                                </div>
                                <div class="electro-shop-body">
                                    <h3>{{ $banner['title'] ?? '' }}</h3>
                                    <a href="{{ theme_url($banner['url'] ?? '#') }}" class="electro-cta-btn">
                                        {{ theme_text('home.actions.shop_now') }} <i class="fa fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- PRODUCT SECTIONS (New Products, Top Selling from backend) --}}
    @foreach ($productSections as $sectionIndex => $section)
        @if (!empty($section['products']))
            <div class="electro-section">
                <div class="electro-container">
                    <div class="electro-section-title">
                        <h3 class="electro-title">{{ $section['title'] ?? '' }}</h3>
                        <div class="electro-section-nav">
                            <a href="{{ $section['link_url'] ?? theme_route_url('storefront.products.index') }}" class="electro-view-all">
                                {{ theme_text('products.view_all') }}
                            </a>
                        </div>
                    </div>

                    {{-- Product grid --}}
                    <div class="electro-product-grid">
                        @foreach ($section['products'] as $product)
                            @include(theme_view('partials.product-card'), ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- HOT DEAL after first section --}}
            @if ($sectionIndex === 0)
                <div class="electro-hot-deal electro-section">
                    <div class="electro-container">
                        <div class="electro-hot-deal-inner">
                            <h2 class="electro-text-uppercase">{{ $hotDealTitle }}</h2>
                            <p>{{ theme_text('home.description') }}</p>
                            <a class="electro-primary-btn electro-cta-btn" href="{{ theme_route_url('storefront.products.index') }}">
                                {{ theme_text('home.actions.shop_now') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endforeach

    {{-- LATEST POSTS --}}
    @if ($latestPosts !== [])
        <div class="electro-section">
            <div class="electro-container">
                <div class="electro-section-title">
                    <h3 class="electro-title">{{ $latestPostsTitle }}</h3>
                    <div class="electro-section-nav">
                        <a href="{{ theme_route_url('storefront.blog.index') }}" class="electro-view-all">
                            {{ theme_text('blog.read_more') }}
                        </a>
                    </div>
                </div>
                <div class="electro-post-grid">
                    @foreach ($latestPosts as $post)
                        @include(theme_view('partials.post-card'), ['post' => $post])
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- NEWSLETTER --}}
    @if ($newsletterEnabled)
        @include(theme_view('partials.newsletter'))
    @endif
@endsection
