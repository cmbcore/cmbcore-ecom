@extends(theme_layout('app'))

@section('title', theme_site_name())

@section('content')
    <section class="hero">
        <div class="container">
            <div class="hero__card">
                <span class="hero__eyebrow">{{ theme_text('fallback.eyebrow') }}</span>
                <h1 class="hero__title">{{ theme_text('fallback.title') }}</h1>
                <p class="hero__copy">{{ theme_text('fallback.description') }}</p>
            </div>
        </div>
    </section>
@endsection
