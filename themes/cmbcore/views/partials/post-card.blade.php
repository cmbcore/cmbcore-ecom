@php
    $horizontal  = (bool) ($horizontal ?? false);
    $publishedAt = !empty($post['published_at'])
        ? \Illuminate\Support\Carbon::parse($post['published_at'])->locale(theme_locale())->translatedFormat('d/m/Y')
        : null;
    $postUrl = theme_route_url('storefront.blog.show', ['slug' => $post['slug']]);
@endphp

<article class="cmbcore-post-card {{ $horizontal ? 'is-horizontal' : '' }}">
    <a class="cmbcore-post-card__media" href="{{ $postUrl }}" tabindex="-1" aria-hidden="true">
        @if (!empty($post['featured_image_url']))
            <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] }}" loading="lazy">
        @else
            <span class="cmbcore-post-card__placeholder">
                <i class="fa-regular fa-image" aria-hidden="true"></i>
            </span>
        @endif
    </a>
    <div class="cmbcore-post-card__body">
        <div class="cmbcore-post-card__meta">
            @if (!empty($post['category']['name']))
                <a class="cmbcore-post-card__cat"
                   href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $post['category']['slug']]) }}">
                    {{ $post['category']['name'] }}
                </a>
            @endif
            @if ($publishedAt)
                <span class="cmbcore-post-card__date">
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $publishedAt }}
                </span>
            @endif
        </div>
        <h3 class="cmbcore-post-card__title">
            <a href="{{ $postUrl }}">{{ $post['title'] }}</a>
        </h3>
        @if (!empty($post['excerpt']))
            <p class="cmbcore-post-card__excerpt">{{ $post['excerpt'] }}</p>
        @endif
        <a class="cmbcore-post-card__readmore" href="{{ $postUrl }}">
            {{ theme_text('blog.read_more') }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
