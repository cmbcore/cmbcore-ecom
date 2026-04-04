@php
    $blogTitle = (string) theme_setting('blog_list_title', theme_text('blog.list_title'));
    $blogDescription = (string) theme_setting('blog_list_description', theme_text('blog.list_description'));
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $blogTitle))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            @include(theme_view('partials.breadcrumbs'), ['items' => theme_context('breadcrumbs', [])])

            <header class="cmbcore-archive-header">
                <span class="cmbcore-kicker">{{ theme_setting('blog_eyebrow', theme_text('blog.eyebrow')) }}</span>
                <h1>{{ theme_context('page.title', $blogTitle) }}</h1>
                <p>{{ theme_context('page.meta_description', $blogDescription) }}</p>
            </header>

            <div class="cmbcore-blog-layout">
                <div>
                    <form class="cmbcore-search cmbcore-search--catalog" action="{{ theme_route_url('storefront.blog.index') }}" method="get">
                        <span class="cmbcore-search__icon">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </span>
                        <input
                            type="search"
                            name="search"
                            value="{{ theme_context('filters.search', '') }}"
                            placeholder="{{ theme_text('blog.search_placeholder') }}"
                        >
                    </form>

                    <div class="cmbcore-chip-group">
                        <a class="cmbcore-chip {{ empty(theme_context('selected_category')) ? 'is-active' : '' }}" href="{{ theme_route_url('storefront.blog.index') }}">
                            {{ theme_text('products.all_categories') }}
                        </a>
                        @foreach (theme_context('categories', []) as $category)
                            <a class="cmbcore-chip {{ theme_context('selected_category.slug') === $category['slug'] ? 'is-active' : '' }}" href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $category['slug']]) }}">
                                {{ $category['name'] }}
                            </a>
                        @endforeach
                    </div>

                    @if (theme_context('posts', []) === [])
                        <div class="cmbcore-empty-state">
                            <h2>{{ theme_text('blog.empty_title') }}</h2>
                            <p>{{ theme_text('blog.empty_description') }}</p>
                        </div>
                    @else
                        <div class="cmbcore-post-list">
                            @foreach (theme_context('posts', []) as $post)
                                @include(theme_view('partials.post-card'), ['post' => $post, 'horizontal' => true])
                            @endforeach
                        </div>
                    @endif

                    @if (theme_context('pagination.last_page', 1) > 1)
                        <div class="cmbcore-pagination">
                            @if (theme_context('pagination.prev_url'))
                                <a class="cmbcore-button is-secondary" href="{{ theme_context('pagination.prev_url') }}">{{ theme_text('blog.pagination.previous') }}</a>
                            @endif
                            <span>{{ theme_text('blog.pagination.status', ['current' => theme_context('pagination.current_page', 1), 'last' => theme_context('pagination.last_page', 1)]) }}</span>
                            @if (theme_context('pagination.next_url'))
                                <a class="cmbcore-button is-secondary" href="{{ theme_context('pagination.next_url') }}">{{ theme_text('blog.pagination.next') }}</a>
                            @endif
                        </div>
                    @endif
                </div>

                @include(theme_view('partials.blog-sidebar'), ['recentPosts' => theme_context('recent_posts', [])])
            </div>
        </div>
    </section>
@endsection

