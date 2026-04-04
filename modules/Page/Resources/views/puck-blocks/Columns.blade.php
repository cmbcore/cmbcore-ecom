@php
    $count = max(2, min(4, (int) ($props['column_count'] ?? 2)));
@endphp

<section class="cmbcore-page-block cmbcore-page-block--columns" style="display: grid; grid-template-columns: repeat({{ $count }}, 1fr); gap: 24px;">
    @if (!empty($props['content']))
        <div class="cmbcore-prose" style="grid-column: 1 / -1">{!! $props['content'] !!}</div>
    @endif
</section>
