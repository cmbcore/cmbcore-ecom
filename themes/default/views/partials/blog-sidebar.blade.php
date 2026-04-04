@php
    $categories = $categories ?? theme_context('categories', []);
    $recentPosts = $recentPosts ?? theme_context('recent_posts', []);
@endphp

<div class="sf-sidebar__stack">
    @if (!empty($categories))
        <section class="sf-sidebar__card">
            <h3>{{ theme_text('blog.categories_title') }}</h3>
            <div class="sf-sidebar__list">
                @foreach ($categories as $category)
                    <a href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $category['slug']]) }}">
                        {{ $category['name'] }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($recentPosts))
        <section class="sf-sidebar__card">
            <h3>{{ theme_text('blog.recent_posts_title') }}</h3>
            <div class="sf-sidebar__posts">
                @foreach ($recentPosts as $post)
                    <a class="sf-sidebar__post" href="{{ theme_route_url('storefront.blog.show', ['slug' => $post['slug']]) }}">
                        <strong>{{ $post['title'] }}</strong>
                        @if (!empty($post['published_at']))
                            <small>{{ \Illuminate\Support\Carbon::parse($post['published_at'])->translatedFormat('d/m/Y') }}</small>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
