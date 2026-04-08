@php
    $blogTitle       = (string) theme_setting('blog_list_title', theme_text('blog.list_title'));
    $blogDescription = (string) theme_setting('blog_list_description', theme_text('blog.list_description'));
    $pageTitle       = (string) theme_context('page.title', $blogTitle);
    $pageDescription = (string) theme_context('page.meta_description', $blogDescription);
    $posts           = theme_context('posts', []);
    $categories      = theme_context('categories', []);
    $selectedCategory = theme_context('selected_category');
    $recentPosts     = theme_context('recent_posts', []);
    $currentPage     = (int) theme_context('pagination.current_page', 1);
    $lastPage        = (int) theme_context('pagination.last_page', 1);
    $prevUrl         = theme_context('pagination.prev_url');
    $nextUrl         = theme_context('pagination.next_url');
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $blogTitle))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            {{-- Catalog header --}}
            <header class="cmbcore-catalog-header cmbcore-catalog-header--blog">
                <h1 class="cmbcore-catalog-header__title">{{ $pageTitle }}</h1>
            </header>

            {{-- Category chips --}}
            @if (!empty($categories))
                <div class="cmbcore-cat-chips">
                    <a class="cmbcore-cat-chip {{ empty($selectedCategory) ? 'is-active' : '' }}"
                       href="{{ theme_route_url('storefront.blog.index') }}">Tất cả</a>
                    @foreach ($categories as $category)
                        <a class="cmbcore-cat-chip {{ ($selectedCategory['slug'] ?? null) === $category['slug'] ? 'is-active' : '' }}"
                           href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $category['slug']]) }}">
                            {{ $category['name'] }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="cmbcore-blog-layout">
                {{-- Main posts column --}}
                <div class="cmbcore-blog-main">
                    @if ($posts === [])
                        <div class="cmbcore-empty-state">
                            <h2>{{ theme_text('blog.empty_title') }}</h2>
                            <p>{{ theme_text('blog.empty_description') }}</p>
                        </div>
                    @else
                        <div class="cmbcore-blog-post-list">
                            @foreach ($posts as $post)
                                @include(theme_view('partials.post-card'), ['post' => $post, 'horizontal' => true])
                            @endforeach
                        </div>
                    @endif

                    {{-- Numbered pagination --}}
                    @if ($lastPage > 1)
                        <nav class="cmbcore-pagination-numbered" aria-label="Phân trang">
                            @if ($prevUrl)
                                <a class="cmbcore-page-btn" href="{{ $prevUrl }}" aria-label="Trang trước">
                                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="cmbcore-page-btn is-disabled"><i class="fa-solid fa-chevron-left"></i></span>
                            @endif

                            @php
                                $range = 2;
                                $pStart = max(1, $currentPage - $range);
                                $pEnd   = min($lastPage, $currentPage + $range);
                                $baseUrl = !empty($selectedCategory['slug'])
                                    ? theme_route_url('storefront.blog.categories.show', ['slug' => $selectedCategory['slug']])
                                    : theme_route_url('storefront.blog.index');
                            @endphp

                            @if ($pStart > 1)
                                <a class="cmbcore-page-btn" href="{{ $baseUrl }}">1</a>
                                @if ($pStart > 2)<span class="cmbcore-page-ellipsis">…</span>@endif
                            @endif

                            @for ($p = $pStart; $p <= $pEnd; $p++)
                                @php $pUrl = $p === 1 ? $baseUrl : $baseUrl . '?page=' . $p; @endphp
                                @if ($p === $currentPage)
                                    <span class="cmbcore-page-btn is-current" aria-current="page">{{ $p }}</span>
                                @else
                                    <a class="cmbcore-page-btn" href="{{ $pUrl }}">{{ $p }}</a>
                                @endif
                            @endfor

                            @if ($pEnd < $lastPage)
                                @if ($pEnd < $lastPage - 1)<span class="cmbcore-page-ellipsis">…</span>@endif
                                <a class="cmbcore-page-btn" href="{{ $baseUrl }}?page={{ $lastPage }}">{{ $lastPage }}</a>
                            @endif

                            @if ($nextUrl)
                                <a class="cmbcore-page-btn" href="{{ $nextUrl }}" aria-label="Trang tiếp">
                                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="cmbcore-page-btn is-disabled"><i class="fa-solid fa-chevron-right"></i></span>
                            @endif
                        </nav>
                    @endif
                </div>

                {{-- Sidebar --}}
                @include(theme_view('partials.blog-sidebar'), ['recentPosts' => $recentPosts])
            </div>
        </div>
    </section>
@endsection
