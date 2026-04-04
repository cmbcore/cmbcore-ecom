@php
    $postUrl = theme_route_url('storefront.blog.show', ['slug' => $post['slug'] ?? '']);
@endphp

<article class="electro-post-card">
    <a class="electro-post-card__media" href="{{ $postUrl }}">
        @if (!empty($post['featured_image_url']))
            <img src="{{ $post['featured_image_url'] }}" alt="{{ $post['title'] ?? '' }}">
        @endif
    </a>
    <div class="electro-post-card__body">
        <span class="electro-post-card__date">
            @if (!empty($post['published_at']))
                {{ \Illuminate\Support\Carbon::parse($post['published_at'])->translatedFormat('d M, Y') }}
            @endif
        </span>
        <h3><a href="{{ $postUrl }}">{{ $post['title'] ?? '' }}</a></h3>
    </div>
</article>
