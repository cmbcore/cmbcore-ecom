@php
    $post = theme_context('post', []);
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $post['title'] ?? theme_site_name()))

@section('content')
    <section class="sf-article">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-article__header">
                <span class="sf-kicker">{{ theme_text('blog.detail_kicker') }}</span>
                <h1>{{ $post['title'] ?? '' }}</h1>
                <div class="sf-header__actions">
                    @if (!empty($post['author_name']))
                        <span class="sf-pill">{{ theme_text('blog.meta.author_name') }}: {{ $post['author_name'] }}</span>
                    @endif
                    @if (!empty($post['published_at']))
                        <span class="sf-pill">{{ theme_text('blog.meta.published_at') }}: {{ \Illuminate\Support\Carbon::parse($post['published_at'])->translatedFormat('d/m/Y') }}</span>
                    @endif
                    <span class="sf-pill">{{ theme_text('blog.view_count', ['count' => $post['view_count'] ?? 0]) }}</span>
                </div>
                @if (!empty($post['excerpt']))
                    <p>{{ $post['excerpt'] }}</p>
                @endif
            </div>

            <div class="sf-article__layout">
                <aside class="sf-sidebar__stack">
                    @if (!empty(theme_context('toc', [])))
                        <section class="sf-sidebar__card">
                            <h3>{{ theme_text('blog.contents_title') }}</h3>
                            <div class="sf-article__toc">
                                @foreach (theme_context('toc', []) as $item)
                                    <a href="#{{ $item['anchor'] }}">{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @include(theme_view('partials.blog-sidebar'), [
                        'categories' => theme_context('categories', []),
                        'recentPosts' => theme_context('recent_posts', []),
                    ])
                </aside>

                <article class="sf-article__body">
                    @if (!empty($post['featured_image_url']))
                        <div class="sf-product__main-media" style="aspect-ratio: 16 / 8; margin-bottom: 1rem;">
                            <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] ?? '' }}">
                        </div>
                    @endif
                    <div class="sf-article__content">
                        {!! $post['content_html'] ?? $post['content'] ?? '' !!}
                    </div>
                </article>
            </div>

            @if (!empty(theme_context('related_posts', [])))
                <section class="sf-section">
                    <div class="sf-section-heading">
                        <div>
                            <span class="sf-kicker">{{ theme_text('blog.related_kicker') }}</span>
                            <h2>{{ theme_text('blog.related_title') }}</h2>
                            <p>{{ theme_text('blog.related_description') }}</p>
                        </div>
                    </div>

                    <div class="sf-post-grid">
                        @foreach (theme_context('related_posts', []) as $relatedPost)
                            @include(theme_view('partials.post-card'), ['post' => $relatedPost])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
