@php
    $posts = theme_context('posts', []);
    $categories = theme_context('blog_categories', []);
    $pagination = theme_context('pagination', []);
    $pageTitle = theme_text('blog.list_title');
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', $pageTitle))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => $pageTitle,
        'breadcrumbs' => [
            ['label' => $pageTitle],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <div class="electro-store-layout">
                {{-- Blog sidebar --}}
                <div class="electro-store-aside">
                    @include(theme_view('partials.blog-sidebar'), ['categories' => $categories])
                </div>

                {{-- Blog grid --}}
                <div class="electro-store-main">
                    @if (empty($posts))
                        <div class="electro-text-center" style="padding:60px 0;">
                            <h3>{{ theme_text('blog.empty_title') }}</h3>
                            <p>{{ theme_text('blog.empty_description') }}</p>
                        </div>
                    @else
                        <div class="electro-post-grid" style="grid-template-columns: repeat(2, 1fr);">
                            @foreach ($posts as $post)
                                @include(theme_view('partials.post-card'), ['post' => $post])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
