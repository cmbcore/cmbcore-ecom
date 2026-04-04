@php
    $isImageRight = ($props['image_position'] ?? 'right') === 'right';
@endphp

<section class="cmbcore-page-block cmbcore-page-block--media {{ $isImageRight ? 'is-image-right' : 'is-image-left' }}">
    <div class="cmbcore-page-block__inner">
        @if (!empty($props['image']))
            <div class="cmbcore-page-block__media">
                <img src="{{ theme_media_url((string) $props['image']) }}" alt="{{ $props['title'] ?? theme_site_name() }}">
            </div>
        @endif
        <div class="cmbcore-page-block__copy">
            @if (!empty($props['title']))
                <h2>{{ $props['title'] }}</h2>
            @endif
            @if (!empty($props['body']))
                <p>{{ $props['body'] }}</p>
            @endif
            @if (!empty($props['link_label']) && !empty($props['link_url']))
                <a class="cmbcore-button is-secondary" href="{{ theme_url((string) $props['link_url']) }}">{{ $props['link_label'] }}</a>
            @endif
        </div>
    </div>
</section>
