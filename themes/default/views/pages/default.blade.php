@php
    $contentPage = theme_context('content_page', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $contentPage['title'] ?? theme_site_name()))

@section('content')
    <section class="sf-page">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-page__hero">
                <span class="sf-kicker">{{ theme_text('pages.eyebrow') }}</span>
                <h1>{{ $contentPage['title'] ?? '' }}</h1>
                @if (!empty($contentPage['excerpt_html']))
                    <p>{!! strip_tags((string) $contentPage['excerpt_html']) !!}</p>
                @endif
            </div>

            <article class="sf-page__body">
                @if (!empty($contentPage['featured_image_url']))
                    <div class="sf-product__main-media" style="aspect-ratio: 16 / 8; margin-bottom: 1rem;">
                        <img src="{{ $contentPage['featured_image_url'] }}" alt="{{ $contentPage['title'] ?? '' }}">
                    </div>
                @endif

                <div class="sf-page__content">
                    {!! $contentPage['content_html'] ?? $contentPage['content'] ?? '' !!}
                </div>

                @if (!empty($contentPage['content_blocks']))
                    <div class="sf-account__list" style="margin-top: 1.5rem;">
                        @foreach ($contentPage['content_blocks'] as $block)
                            <section class="sf-panel">
                                <div class="sf-summary-card">
                                    <h3>{{ data_get($block, 'props.title', theme_text('pages.block_title')) }}</h3>
                                    <p>{{ data_get($block, 'props.body', '') }}</p>
                                    @if (data_get($block, 'props.primary_url') && data_get($block, 'props.primary_label'))
                                        <a class="sf-button sf-button--primary" href="{{ theme_url((string) data_get($block, 'props.primary_url')) }}">
                                            {{ data_get($block, 'props.primary_label') }}
                                        </a>
                                    @endif
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>
    </section>
@endsection
