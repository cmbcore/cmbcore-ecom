@php
    $storefrontReadiness = app(\App\Services\StorefrontDataReadiness::class);
    $productCategories = $storefrontReadiness->hasProductCategories()
        ? \Modules\Category\Models\Category::query()
            ->roots()
            ->active()
            ->ordered()
            ->get()
        : collect();
    $recentPosts = collect($recentPosts ?? []);
@endphp

<aside class="cmbcore-sidebar">
    <div class="cmbcore-sidebar__widget">
        <h3>{{ theme_text('products.category_sidebar_title') }}</h3>
        <ul class="cmbcore-sidebar__links">
            @foreach ($productCategories as $category)
                <li>
                    <a href="{{ theme_route_url('storefront.product-categories.show', ['slug' => $category->slug]) }}">{{ $category->name }}</a>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($recentPosts->isNotEmpty())
        <div class="cmbcore-sidebar__widget">
            <h3>{{ theme_text('blog.recent_posts_title') }}</h3>
            <div class="cmbcore-sidebar__posts">
                @foreach ($recentPosts as $recentPost)
                    <a class="cmbcore-sidebar__post" href="{{ theme_route_url('storefront.blog.show', ['slug' => $recentPost['slug']]) }}">
                        @if (!empty($recentPost['featured_image_url']))
                            <img src="{{ $recentPost['featured_image_url'] }}" alt="{{ $recentPost['title'] }}">
                        @endif
                        <span>
                            <strong>{{ $recentPost['title'] }}</strong>
                            @if (!empty($recentPost['published_at']))
                                <small>{{ \Illuminate\Support\Carbon::parse($recentPost['published_at'])->locale(theme_locale())->translatedFormat('d.m.Y') }}</small>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</aside>

