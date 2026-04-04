@php
    $categories = $categories ?? [];
@endphp

<div class="electro-aside-widget">
    <h3 class="electro-aside-title">{{ theme_text('blog.categories_title') }}</h3>
    <ul class="electro-footer-links">
        @foreach ($categories as $cat)
            <li>
                <a href="{{ theme_route_url('storefront.blog.categories.show', ['slug' => $cat['slug'] ?? '']) }}">
                    {{ $cat['name'] ?? '' }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
