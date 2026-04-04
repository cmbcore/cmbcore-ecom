@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('blog.list_title')))

@section('content')
    <section class="sf-article">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'))

            <div class="sf-catalog__hero">
                <div>
                    <span class="sf-kicker">{{ theme_text('blog.eyebrow') }}</span>
                    <h1>{{ theme_context('selected_category.name', theme_text('blog.list_title')) }}</h1>
                    <p>{{ theme_context('page.meta_description', theme_text('blog.list_description')) }}</p>
                </div>

                <form class="sf-catalog__search" action="{{ theme_route_url('storefront.blog.index') }}" method="get">
                    <input type="search" name="search" value="{{ theme_context('filters.search', '') }}" placeholder="{{ theme_text('blog.search_placeholder') }}">
                    <select name="category">
                        <option value="">{{ theme_text('blog.categories_title') }}</option>
                        @foreach (theme_context('categories', []) as $category)
                            <option value="{{ $category['slug'] }}" @selected(theme_context('filters.category') === $category['slug'])>{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    <div></div>
                    <div></div>
                    <button type="submit" class="sf-button sf-button--primary">{{ theme_text('blog.search_action') }}</button>
                </form>
            </div>

            <div class="sf-article__layout">
                <aside class="sf-sidebar__stack">
                    @include(theme_view('partials.blog-sidebar'), [
                        'categories' => theme_context('categories', []),
                        'recentPosts' => theme_context('recent_posts', []),
                    ])
                </aside>

                <div>
                    @if (theme_context('posts', []) === [])
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('blog.empty_title') }}</h2>
                            <p>{{ theme_text('blog.empty_description') }}</p>
                            <a class="sf-button sf-button--primary" href="{{ theme_route_url('storefront.blog.index') }}">
                                {{ theme_text('blog.browse_all') }}
                            </a>
                        </div>
                    @else
                        <div class="sf-post-grid">
                            @foreach (theme_context('posts', []) as $post)
                                @include(theme_view('partials.post-card'), ['post' => $post])
                            @endforeach
                        </div>

                        @if (theme_context('pagination.last_page', 1) > 1)
                            <div class="sf-pagination">
                                @if (theme_context('pagination.prev_url'))
                                    <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.prev_url') }}">
                                        {{ theme_text('blog.pagination.previous') }}
                                    </a>
                                @endif
                                <span class="sf-pill">
                                    {{ theme_text('blog.pagination.status', [
                                        'current' => theme_context('pagination.current_page', 1),
                                        'last' => theme_context('pagination.last_page', 1),
                                    ]) }}
                                </span>
                                @if (theme_context('pagination.next_url'))
                                    <a class="sf-button sf-button--ghost" href="{{ theme_context('pagination.next_url') }}">
                                        {{ theme_text('blog.pagination.next') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
