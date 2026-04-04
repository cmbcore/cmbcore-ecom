@php
    $horizontal = (bool) ($horizontal ?? false);
    $publishedAt = !empty($post['published_at'])
        ? \Illuminate\Support\Carbon::parse($post['published_at'])->locale(theme_locale())->translatedFormat('d.m.Y')
        : null;
    $postUrl = theme_route_url('storefront.blog.show', ['slug' => $post['slug']]);
@endphp

<article class="cmbcore-post-card {{ $horizontal ? 'is-horizontal' : '' }}">
    <a class="cmbcore-post-card__media" href="{{ $postUrl }}">
        @if (!empty($post['featured_image_url']))
            <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] }}">
        @else
            <span class="cmbcore-post-card__placeholder">
                <i class="fa-regular fa-image" aria-hidden="true"></i>
            </span>
        @endif
    </a>
    <div class="cmbcore-post-card__body">
        <div class="cmbcore-post-card__meta">
            @if ($publishedAt)
                <span>{{ $publishedAt }}</span>
            @endif
            @if (!empty($post['category']['name']))
                <span>{{ $post['category']['name'] }}</span>
            @endif
        </div>
        <h3>
            <a href="{{ $postUrl }}">{{ $post['title'] }}</a>
        </h3>
        @if (!empty($post['excerpt']))
            <p>{{ $post['excerpt'] }}</p>
        @endif
        <a class="cmbcore-post-card__readmore" href="{{ $postUrl }}">{{ theme_text('blog.read_more') }}</a>
    </div>
</article>

