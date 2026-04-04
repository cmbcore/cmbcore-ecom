<section class="cmbcore-page-block cmbcore-page-block--cta" @if (!empty($props['background_image'])) style="background-image: linear-gradient(rgba(0,0,0,.46), rgba(0,0,0,.46)), url('{{ theme_media_url((string) $props['background_image']) }}');" @endif>
    <div class="cmbcore-page-block__copy">
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
</section>
