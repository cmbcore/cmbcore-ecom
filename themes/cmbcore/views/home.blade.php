@php
    $heroSlides = theme_context('hero_slides', []);
    $productSections = theme_context('product_sections', []);
    $latestPosts = theme_context('latest_posts', []);
    $latestPostsTitle = (string) theme_setting('home_latest_posts_title', theme_text('home.latest_posts_title'));
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_site_name()))

@section('content')
    <section class="cmbcore-hero js-cmbcore-slider" data-slider-interval="5600">
        <div class="cmbcore-hero__viewport">
            @foreach ($heroSlides as $index => $slide)
                <article class="cmbcore-hero__slide {{ $index === 0 ? 'is-active' : '' }}">
                    @php
                        $slideLink = !empty($slide['link_url']) ? theme_url(ltrim((string) $slide['link_url'], '/')) : null;
                        $slideTarget = ($slide['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                    @endphp
                    @if ($slideLink)
                        <a class="cmbcore-hero__media-link" href="{{ $slideLink }}" target="{{ $slideTarget }}" @if ($slideTarget === '_blank') rel="noreferrer noopener" @endif aria-label="{{ $slide['alt'] ?? $slide['title'] ?? theme_site_name() }}">
                    @endif
                        <picture>
                            @if (!empty($slide['mobile']))
                                <source media="(max-width: 767px)" srcset="{{ theme_media_url($slide['mobile'] ?? null) }}">
                            @endif
                            <img src="{{ theme_media_url($slide['desktop'] ?? $slide['mobile'] ?? null) }}" alt="{{ $slide['alt'] ?? theme_site_name() }}">
                        </picture>
                    @if ($slideLink)
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
        @if (count($heroSlides) > 1)
            <button type="button" class="cmbcore-hero__arrow is-prev" data-slider-prev aria-label="Slide trước">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="cmbcore-hero__arrow is-next" data-slider-next aria-label="Slide tiếp theo">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        @endif
        @if (count($heroSlides) > 1)
            <div class="cmbcore-hero__dots" role="tablist">
                @foreach ($heroSlides as $index => $slide)
                    <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-slider-dot="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    @foreach ($productSections as $section)
        @if (!empty($section['products']))
            <section class="cmbcore-section cmbcore-shelf">
                <div class="cmbcore-container">
                    @if (($section['source_type'] ?? '') === 'featured')
                        <span id="home-best-sellers"></span>
                    @endif
                    @if (!empty($section['banner_image_url']) && !empty($section['category']['slug']))
                        <a class="cmbcore-shelf__banner" href="{{ $section['link_url'] }}">
                            <img src="{{ $section['banner_image_url'] }}" alt="{{ $section['title'] ?? '' }}">
                        </a>
                    @endif
                    <div class="cmbcore-section-title" data-test-title="{{ \Illuminate\Support\Str::ascii($section['title'] ?? '') }}">
                        <h2>{{ $section['title'] ?? '' }}</h2>
                        <a href="{{ $section['link_url'] ?? theme_route_url('storefront.products.index') }}">{{ theme_text('products.view_all') }}</a>
                    </div>
                    <div class="cmbcore-product-grid">
                        @foreach ($section['products'] as $product)
                            @include(theme_view('partials.product-card'), ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    @if ($latestPosts !== [])
        <section class="cmbcore-section cmbcore-shelf cmbcore-shelf--posts">
            <div class="cmbcore-container">
                <div class="cmbcore-section-title" data-test-title="{{ \Illuminate\Support\Str::ascii($latestPostsTitle) }}">
                    <h2>{{ $latestPostsTitle }}</h2>
                    <a href="{{ theme_route_url('storefront.blog.index') }}">{{ theme_text('blog.read_more') }}</a>
                </div>
                <div class="cmbcore-post-grid">
                    @foreach ($latestPosts as $post)
                        @include(theme_view('partials.post-card'), ['post' => $post, 'horizontal' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

