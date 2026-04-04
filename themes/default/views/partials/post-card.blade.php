@php
    $post = $post ?? [];
    $publishedAt = !empty($post['published_at'])
        ? \Illuminate\Support\Carbon::parse($post['published_at'])->locale(theme_locale())
        : null;
@endphp

<article class="sf-post-card">
    <a class="sf-post-card__media" href="{{ theme_route_url('storefront.blog.show', ['slug' => $post['slug']]) }}">
        @if (!empty($post['featured_image_url']))
            <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] ?? '' }}">
        @else
            <span class="sf-post-card__placeholder">
                <i class="fa-regular fa-image" aria-hidden="true"></i>
            </span>
        @endif
    </a>

    <div class="sf-post-card__body">
        <div class="sf-post-card__meta">
            @if (!empty($post['category']['name']))
                <span>{{ $post['category']['name'] }}</span>
            @endif
            @if ($publishedAt)
                <span>{{ $publishedAt->translatedFormat('d M Y') }}</span>
            @endif
        </div>

        <h3>
            <a href="{{ theme_route_url('storefront.blog.show', ['slug' => $post['slug']]) }}">{{ $post['title'] ?? '' }}</a>
        </h3>

        @if (!empty($post['excerpt']))
            <p>{{ $post['excerpt'] }}</p>
        @endif

        <div class="sf-post-card__footer">
            <span>{{ theme_text('blog.view_count', ['count' => $post['view_count'] ?? 0]) }}</span>
            <a class="sf-button sf-button--ghost sf-button--small" href="{{ theme_route_url('storefront.blog.show', ['slug' => $post['slug']]) }}">
                {{ theme_text('blog.read_more') }}
            </a>
        </div>
    </div>
</article>
