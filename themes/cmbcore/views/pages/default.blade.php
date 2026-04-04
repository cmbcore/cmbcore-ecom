@php
    $page = theme_context('content_page', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $page['title'] ?? theme_site_name()))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            <article class="cmbcore-static-page">
                <header class="cmbcore-static-page__header">
                    @if (!empty($page['featured_image_url']))
                        <div class="cmbcore-static-page__cover">
                            <img src="{{ $page['featured_image_url'] }}" alt="{{ $page['title'] ?? theme_site_name() }}">
                        </div>
                    @endif
                    <h1>{{ $page['title'] ?? '' }}</h1>
                    @if (!empty($page['excerpt_html']))
                        <div class="cmbcore-static-page__lead">{!! $page['excerpt_html'] !!}</div>
                    @endif
                </header>

                <div class="cmbcore-prose cmbcore-prose--page">
                    {!! $page['content_html'] ?? '' !!}
                </div>
            </article>
        </div>
    </section>
@endsection

