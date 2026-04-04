@php
    $post = theme_context('post', []);
    $publishedAt = !empty($post['published_at'])
        ? \Illuminate\Support\Carbon::parse($post['published_at'])->locale(theme_locale())->translatedFormat('d.m.Y')
        : null;
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $post['title'] ?? theme_site_name()))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            <div class="cmbcore-blog-layout cmbcore-blog-layout--detail">
                <article class="cmbcore-article">
                    @if (!empty($post['featured_image_url']))
                        <img class="cmbcore-article__cover" src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] }}">
                    @endif

                    <header class="cmbcore-article__header">
                        @if (!empty($post['category']['name']))
                            <a class="cmbcore-article__category" href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $post['category']['slug']]) }}">
                                {{ $post['category']['name'] }}
                            </a>
                        @endif
                        <h1>{{ $post['title'] ?? '' }}</h1>
                        <div class="cmbcore-article__meta">
                            @if ($publishedAt)
                                <span>{{ $publishedAt }}</span>
                            @endif
                            @if (!empty($post['author_name']))
                                <span>{{ $post['author_name'] }}</span>
                            @endif
                            <span>{{ theme_text('blog.view_count', ['count' => $post['view_count'] ?? 0]) }}</span>
                        </div>
                        @if (!empty($post['excerpt']))
                            <p>{{ $post['excerpt'] }}</p>
                        @endif
                    </header>

                    <div class="cmbcore-prose">
                        {!! $post['content_html'] ?? '' !!}
                    </div>
                </article>

                @include(theme_view('partials.blog-sidebar'), ['recentPosts' => theme_context('recent_posts', [])])
            </div>

            @if (!empty(theme_context('related_posts', [])))
                <section class="cmbcore-related-block">
                    <div class="cmbcore-section-title cmbcore-section-title--detail">
                        <h2>{{ theme_text('blog.related_title') }}</h2>
                    </div>
                    <div class="cmbcore-post-grid">
                        @foreach (theme_context('related_posts', []) as $relatedPost)
                            @include(theme_view('partials.post-card'), ['post' => $relatedPost, 'horizontal' => false])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        @if (!empty(theme_context('toc', [])))
            <aside class="cmbcore-toc is-minimized" data-cmbcore-toc>
                <button type="button" class="cmbcore-toc__trigger" data-cmbcore-toc-toggle aria-expanded="false">
                    <i class="fa-solid fa-list-ol" aria-hidden="true"></i>
                </button>
                <div class="cmbcore-toc__panel">
                    <div class="cmbcore-toc__header">
                        <strong>{{ theme_text('blog.contents_title') }}</strong>
                        <button type="button" data-cmbcore-toc-toggle aria-expanded="true">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <ol class="cmbcore-toc__list">
                        @foreach (theme_context('toc', []) as $item)
                            <li class="level-{{ $item['level'] }}">
                                <a href="#{{ $item['id'] }}">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </aside>
        @endif
    </section>
@endsection

