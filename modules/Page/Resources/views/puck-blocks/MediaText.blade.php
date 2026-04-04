<section class="cmbcore-page-block cmbcore-page-block--media-text {{ ($props['image_position'] ?? 'left') === 'right' ? 'is-reversed' : '' }}">
    <div class="cmbcore-page-block__inner">
        @if (!empty($props['image']))
            <div class="cmbcore-page-block__media">
                <img src="{{ theme_media_url((string) $props['image']) }}" alt="{{ $props['title'] ?? '' }}">
            </div>
        @endif
        <div class="cmbcore-page-block__copy">
            @if (!empty($props['title']))
                <h3>{{ $props['title'] }}</h3>
            @endif
            @if (!empty($props['body']))
                <div class="cmbcore-prose">{!! $props['body'] !!}</div>
            @endif
            @if (!empty($props['link_label']) && !empty($props['link_url']))
                <a class="cmbcore-link" href="{{ theme_url((string) $props['link_url']) }}">{{ $props['link_label'] }} →</a>
            @endif
        </div>
    </div>
</section>
