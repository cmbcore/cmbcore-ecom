<figure class="cmbcore-page-block cmbcore-page-block--image">
    @if (!empty($props['src']))
        <img src="{{ theme_media_url((string) $props['src']) }}" alt="{{ $props['alt'] ?? '' }}" loading="lazy">
    @endif
    @if (!empty($props['caption']))
        <figcaption>{{ $props['caption'] }}</figcaption>
    @endif
</figure>
