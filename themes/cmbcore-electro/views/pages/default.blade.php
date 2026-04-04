@php
    $page = theme_context('page', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $page['title'] ?? theme_site_name()))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => $page['title'] ?? '',
        'breadcrumbs' => [
            ['label' => $page['title'] ?? ''],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <article style="max-width:800px; margin:0 auto;">
                <h1>{{ $page['title'] ?? '' }}</h1>
                <div class="electro-product-description">
                    {!! $page['content_html'] ?? $page['content'] ?? '' !!}
                </div>
            </article>
        </div>
    </div>
@endsection
