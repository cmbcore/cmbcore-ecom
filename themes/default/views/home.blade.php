@php
    $heroSlides = collect(theme_context('hero_slides', []))
        ->filter(static fn (mixed $slide): bool => is_array($slide))
        ->values();
    $productSections = theme_context('product_sections', []);
    $quoteCards = theme_context('quote_cards', []);
    $testimonials = theme_context('testimonials', []);
    $procedureSteps = theme_context('procedure_steps', []);
    $registerPanel = theme_context('register_panel', []);
    $latestPosts = theme_context('latest_posts', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_site_name()))

@section('content')
    <section class="sf-home__hero">
        <div class="sf-container">
            <div class="sf-home__hero-grid">
                <div class="sf-home__hero-copy">
                    <span class="sf-kicker">{{ theme_setting('hero_kicker', theme_text('home.eyebrow')) }}</span>
                    <h1>{{ theme_setting('hero_title', theme_text('home.title')) }}</h1>
                    <p>{{ theme_setting('hero_description', theme_text('home.description')) }}</p>

                    <div class="sf-home__hero-actions">
                        <a class="sf-button sf-button--primary" href="{{ theme_url((string) theme_setting('hero_primary_url', '/san-pham')) }}">
                            {{ theme_setting('hero_primary_label', theme_text('home.actions.shop_now')) }}
                        </a>
                        <a class="sf-button sf-button--ghost" href="{{ theme_url((string) theme_setting('hero_secondary_url', '/bai-viet')) }}">
                            {{ theme_setting('hero_secondary_label', theme_text('home.actions.read_journal')) }}
                        </a>
                    </div>

                    <div class="sf-home__hero-stats">
                        <div class="sf-home__stat">
                            <strong>{{ count($productSections) }}</strong>
                            <span>{{ theme_text('home.stats.product_sections') }}</span>
                        </div>
                        <div class="sf-home__stat">
                            <strong>{{ count($latestPosts) }}</strong>
                            <span>{{ theme_text('home.stats.latest_posts') }}</span>
                        </div>
                        <div class="sf-home__stat">
                            <strong>{{ count($testimonials) }}</strong>
                            <span>{{ theme_text('home.stats.customer_voice') }}</span>
                        </div>
                    </div>
                </div>

                <div class="sf-home__hero-media">
                    @if ($heroSlides->isNotEmpty())
                        <div class="sf-slider" data-sf-slider>
                            <div class="sf-slider__track">
                                @foreach ($heroSlides as $slide)
                                    @php
                                        $desktop = theme_media_url((string) ($slide['desktop'] ?? ''), theme_url((string) ($slide['desktop'] ?? '')));
                                        $mobile = theme_media_url((string) ($slide['mobile'] ?? ''), $desktop);
                                    @endphp
                                    <article class="sf-slide {{ $loop->first ? 'is-active' : '' }}" data-sf-slide>
                                        <div class="sf-home__slide-media">
                                            @if ($desktop !== '')
                                                <picture>
                                                    <source media="(max-width: 767px)" srcset="{{ $mobile }}">
                                                    <img src="{{ $desktop }}" alt="{{ $slide['alt'] ?? ($slide['title'] ?? theme_site_name()) }}">
                                                </picture>
                                            @endif

                                            <div class="sf-home__slide-copy">
                                                <span class="sf-kicker">{{ $slide['eyebrow'] ?? theme_text('home.slide_kicker') }}</span>
                                                <h2>{{ $slide['title'] ?? theme_site_name() }}</h2>
                                                @if (!empty($slide['body']))
                                                    <p>{{ $slide['body'] }}</p>
                                                @endif
                                                @if (!empty($slide['link_url']))
                                                    <div>
                                                        <a class="sf-button sf-button--primary" href="{{ theme_url((string) $slide['link_url']) }}" target="{{ ($slide['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self' }}" @if (($slide['link_target'] ?? '_self') === '_blank') rel="noreferrer noopener" @endif>
                                                            {{ $slide['link_label'] ?? theme_text('home.actions.explore_slide') }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            @if ($heroSlides->count() > 1)
                                <div class="sf-slider__nav">
                                    <button type="button" data-sf-slider-prev aria-label="{{ theme_text('common.previous') }}">
                                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                    </button>
                                    <div class="sf-slider__dots">
                                        @foreach ($heroSlides as $slide)
                                            <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-sf-slide-dot aria-label="{{ theme_text('home.slide_number', ['number' => $loop->iteration]) }}"></button>
                                        @endforeach
                                    </div>
                                    <button type="button" data-sf-slider-next aria-label="{{ theme_text('common.next') }}">
                                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="sf-home__slide-media">
                            <div class="sf-home__slide-copy">
                                <span class="sf-kicker">{{ theme_text('home.slide_kicker') }}</span>
                                <h2>{{ theme_text('home.fallback_title') }}</h2>
                                <p>{{ theme_text('home.fallback_copy') }}</p>
                                <a class="sf-button sf-button--primary" href="{{ theme_route_url('storefront.products.index') }}">
                                    {{ theme_text('products.view_all') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if (!empty($quoteCards))
        <section class="sf-section">
            <div class="sf-container">
                <div class="sf-section-heading">
                    <div>
                        <span class="sf-kicker">{{ theme_text('home.quote_kicker') }}</span>
                        <h2>{{ theme_text('home.quote_title') }}</h2>
                    </div>
                </div>

                <div class="sf-home__quote-grid">
                    @foreach ($quoteCards as $card)
                        <article class="sf-quote-card">
                            <h3>{{ $card['title'] ?? '' }}</h3>
                            <p>{{ $card['quote'] ?? '' }}</p>
                            @if (!empty($card['url']) && !empty($card['link_label']))
                                <a class="sf-button sf-button--ghost sf-button--small" href="{{ theme_url((string) $card['url']) }}">
                                    {{ $card['link_label'] }}
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @foreach ($productSections as $section)
        <section class="sf-section {{ $loop->even ? 'sf-section--alt' : '' }}">
            <div class="sf-container">
                <div class="sf-section-heading">
                    <div>
                        <span class="sf-kicker">{{ theme_text('home.product_section_kicker') }}</span>
                        <h2>{{ $section['title'] ?? theme_text('products.list_title') }}</h2>
                        @if (!empty($section['category']['name']))
                            <p>{{ $section['category']['name'] }}</p>
                        @endif
                    </div>
                    @if (!empty($section['link_url']))
                        <a class="sf-button sf-button--ghost" href="{{ $section['link_url'] }}">
                            {{ theme_text('products.view_all') }}
                        </a>
                    @endif
                </div>

                <div class="sf-product-grid">
                    @foreach (($section['products'] ?? []) as $product)
                        @include(theme_view('partials.product-card'), ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @if (!empty($procedureSteps))
        <section class="sf-section">
            <div class="sf-container">
                <div class="sf-section-heading">
                    <div>
                        <span class="sf-kicker">{{ theme_text('home.procedure_kicker') }}</span>
                        <h2>{{ theme_text('home.procedure_title') }}</h2>
                    </div>
                </div>

                <div class="sf-home__procedure-grid">
                    @foreach ($procedureSteps as $step)
                        <article class="sf-procedure-card">
                            <span class="sf-pill">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $step['title'] ?? '' }}</h3>
                            <p>{{ $step['text'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($testimonials))
        <section class="sf-section sf-section--alt">
            <div class="sf-container">
                <div class="sf-section-heading">
                    <div>
                        <span class="sf-kicker">{{ theme_text('home.feedback_kicker') }}</span>
                        <h2>{{ theme_text('home.feedback_title') }}</h2>
                    </div>
                </div>

                <div class="sf-home__testimonial-grid">
                    @foreach ($testimonials as $testimonial)
                        <article class="sf-testimonial">
                            <div>{{ str_repeat('★', max(1, min(5, (int) ($testimonial['rating'] ?? 5)))) }}</div>
                            <h3>{{ $testimonial['name'] ?? theme_site_name() }}</h3>
                            <p>{{ $testimonial['text'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (!empty($latestPosts))
        <section class="sf-section">
            <div class="sf-container">
                <div class="sf-section-heading">
                    <div>
                        <span class="sf-kicker">{{ theme_text('home.latest_posts_kicker') }}</span>
                        <h2>{{ theme_setting('home_latest_posts_title', theme_text('home.latest_posts_title')) }}</h2>
                    </div>
                    <a class="sf-button sf-button--ghost" href="{{ theme_route_url('storefront.blog.index') }}">
                        {{ theme_text('blog.browse_all') }}
                    </a>
                </div>

                <div class="sf-post-grid">
                    @foreach ($latestPosts as $post)
                        @include(theme_view('partials.post-card'), ['post' => $post])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="sf-section">
        <div class="sf-container">
            <div class="sf-home__register">
                <div>
                    <span class="sf-kicker">{{ theme_text('home.register_kicker') }}</span>
                    <h2>{{ $registerPanel['title'] ?? theme_text('home.register_title') }}</h2>
                    <p>{{ $registerPanel['body'] ?? theme_text('home.register_body') }}</p>
                    <div class="sf-home__register-actions">
                        <a class="sf-button sf-button--primary" href="{{ theme_url((string) ($registerPanel['primary_url'] ?? '/san-pham')) }}">
                            {{ $registerPanel['primary_label'] ?? theme_text('home.actions.shop_now') }}
                        </a>
                        <a class="sf-button sf-button--ghost" href="{{ theme_url((string) ($registerPanel['secondary_url'] ?? '/tai-khoan')) }}">
                            {{ $registerPanel['secondary_label'] ?? theme_text('home.actions.open_account') }}
                        </a>
                    </div>
                </div>

                <div>
                    <span class="sf-kicker">{{ theme_text('home.register_list_kicker') }}</span>
                    <ul>
                        @foreach (($registerPanel['bullet_points'] ?? []) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
