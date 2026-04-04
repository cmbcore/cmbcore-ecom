<section class="cmbcore-page-block cmbcore-page-block--hero">
    <div class="cmbcore-page-block__inner">
        <div class="cmbcore-page-block__copy">
            @if (!empty($props['eyebrow']))
                <span class="cmbcore-kicker">{{ $props['eyebrow'] }}</span>
            @endif
            @if (!empty($props['title']))
                <h2>{{ $props['title'] }}</h2>
            @endif
            @if (!empty($props['body']))
                <p>{{ $props['body'] }}</p>
            @endif
            @if (!empty($props['primary_label']) || !empty($props['secondary_label']))
                <div class="cmbcore-hero__actions">
                    @if (!empty($props['primary_label']) && !empty($props['primary_url']))
                        <a class="cmbcore-button is-primary" href="{{ theme_url((string) $props['primary_url']) }}">{{ $props['primary_label'] }}</a>
                    @endif
                    @if (!empty($props['secondary_label']) && !empty($props['secondary_url']))
                        <a class="cmbcore-button is-secondary" href="{{ theme_url((string) $props['secondary_url']) }}">{{ $props['secondary_label'] }}</a>
                    @endif
                </div>
            @endif
        </div>
        @if (!empty($props['image']))
            <div class="cmbcore-page-block__media">
                <img src="{{ theme_media_url((string) $props['image']) }}" alt="{{ $props['title'] ?? theme_site_name() }}">
            </div>
        @endif
    </div>
</section>
