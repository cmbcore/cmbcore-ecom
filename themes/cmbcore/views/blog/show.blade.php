@php
    $post        = theme_context('post', []);
    $toc         = theme_context('toc', []);
    $recentPosts = theme_context('recent_posts', []);
    $relatedPosts = theme_context('related_posts', []);
    $publishedAt = !empty($post['published_at'])
        ? \Illuminate\Support\Carbon::parse($post['published_at'])->locale(theme_locale())->translatedFormat('d/m/Y')
        : null;
    $updatedAt = !empty($post['updated_at'])
        ? \Illuminate\Support\Carbon::parse($post['updated_at'])->locale(theme_locale())->translatedFormat('d/m/Y')
        : null;

    // Build hierarchical TOC with numbered labels (1, 2, 2.1, 2.2...)
    $numberedToc = [];
    if (!empty($toc)) {
        $h2Counter = 0;
        $h3Counter = 0;
        foreach ($toc as $item) {
            if ((int) $item['level'] === 2) {
                $h2Counter++;
                $h3Counter = 0;
                $numberedToc[] = array_merge($item, ['number' => (string) $h2Counter]);
            } elseif ((int) $item['level'] === 3) {
                $h3Counter++;
                $numberedToc[] = array_merge($item, ['number' => $h2Counter . '.' . $h3Counter]);
            } else {
                $numberedToc[] = array_merge($item, ['number' => '']);
            }
        }
    }
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $post['title'] ?? theme_site_name()))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            <div class="cmbcore-blog-layout cmbcore-blog-layout--detail">
                <article class="cmbcore-article" itemscope itemtype="https://schema.org/BlogPosting">

                    {{-- Post header --}}
                    <header class="cmbcore-article__header">
                        @if (!empty($post['category']['name']))
                            <a class="cmbcore-article__category"
                               href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $post['category']['slug']]) }}">
                                {{ $post['category']['name'] }}
                            </a>
                        @endif
                        <h1 itemprop="headline">{{ $post['title'] ?? '' }}</h1>
                        <div class="cmbcore-article__meta">
                            @if ($updatedAt)
                                <span>
                                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                    Cập nhật lần cuối {{ $updatedAt }}
                                    @if (!empty($post['author_name'])) bởi <strong>{{ $post['author_name'] }}</strong>@endif
                                </span>
                            @elseif ($publishedAt)
                                <span>
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $publishedAt }}
                                    @if (!empty($post['author_name'])) · <strong>{{ $post['author_name'] }}</strong>@endif
                                </span>
                            @endif
                            @if (!empty($post['view_count']))
                                <span>
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                    {{ number_format((int) $post['view_count']) }} lượt xem
                                </span>
                            @endif
                        </div>
                    </header>

                    {{-- Featured image --}}
                    @if (!empty($post['featured_image_url']))
                        <figure class="cmbcore-article__cover-wrap">
                            <img class="cmbcore-article__cover"
                                 src="{{ $post['featured_image_url'] }}"
                                 alt="{{ $post['title'] ?? '' }}"
                                 itemprop="image">
                        </figure>
                    @endif

                    {{-- Excerpt --}}
                    @if (!empty($post['excerpt']))
                        <div class="cmbcore-article__excerpt">
                            <p>{{ $post['excerpt'] }}</p>
                        </div>
                    @endif

                    {{-- TABLE OF CONTENTS (inline, like rhysman.vn) --}}
                    @if (!empty($numberedToc))
                        <div class="cmbcore-toc-inline" data-toc-inline>
                            <div class="cmbcore-toc-inline__header">
                                <span class="cmbcore-toc-inline__icon">
                                    <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                                </span>
                                <strong>Nội dung bài viết</strong>
                                <button type="button" class="cmbcore-toc-inline__toggle"
                                        data-toc-toggle aria-expanded="true" aria-label="Ẩn/hiện mục lục">
                                    <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ol class="cmbcore-toc-inline__list" data-toc-list>
                                @foreach ($numberedToc as $item)
                                    <li class="cmbcore-toc-inline__item level-{{ $item['level'] }}">
                                        <a href="#{{ $item['id'] }}" data-toc-link>
                                            <span class="cmbcore-toc-inline__num">{{ $item['number'] }}</span>
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    {{-- Main content --}}
                    <div class="cmbcore-prose" itemprop="articleBody">
                        {!! $post['content_html'] ?? '' !!}
                    </div>

                    {{-- Social share / related tags --}}
                    @if (!empty($post['tags']))
                        <div class="cmbcore-article__tags">
                            @foreach ($post['tags'] as $tag)
                                <span class="cmbcore-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>

                {{-- Sidebar --}}
                @include(theme_view('partials.blog-sidebar'), ['recentPosts' => $recentPosts])
            </div>

            {{-- Related posts --}}
            @if (!empty($relatedPosts))
                <section class="cmbcore-related-block">
                    <div class="cmbcore-section-title cmbcore-section-title--detail">
                        <h2>{{ theme_text('blog.related_title') }}</h2>
                    </div>
                    <div class="cmbcore-post-grid">
                        @foreach ($relatedPosts as $relatedPost)
                            @include(theme_view('partials.post-card'), ['post' => $relatedPost, 'horizontal' => false])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
