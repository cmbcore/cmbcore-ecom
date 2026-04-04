@php
    $post = theme_context('post', []);
    $relatedPosts = theme_context('related_posts', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $post['title'] ?? theme_text('blog.list_title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => $post['title'] ?? '',
        'breadcrumbs' => [
            ['label' => theme_text('blog.list_title'), 'url' => theme_route_url('storefront.blog.index')],
            ['label' => $post['title'] ?? ''],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <article style="max-width:800px; margin:0 auto;">
                @if (!empty($post['featured_image_url']))
                    <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] ?? '' }}" style="width:100%; border-radius:4px; margin-bottom:20px;">
                @endif

                <h1>{{ $post['title'] ?? '' }}</h1>

                <div style="color:var(--electro-grey); font-size:13px; margin-bottom:20px;">
                    @if (!empty($post['author_name']))
                        <span>{{ $post['author_name'] }}</span> ·
                    @endif
                    @if (!empty($post['published_at']))
                        <span>{{ \Illuminate\Support\Carbon::parse($post['published_at'])->translatedFormat('d/m/Y') }}</span>
                    @endif
                </div>

                <div class="electro-product-description">
                    {!! $post['content_html'] ?? $post['content'] ?? '' !!}
                </div>
            </article>

            @if (!empty($relatedPosts))
                <div class="electro-section" style="padding-top:30px;">
                    <div class="electro-section-title">
                        <h3 class="electro-title">{{ theme_text('blog.related_title') }}</h3>
                    </div>
                    <div class="electro-post-grid">
                        @foreach ($relatedPosts as $relPost)
                            @include(theme_view('partials.post-card'), ['post' => $relPost])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
