@php
    $level = (int) ($props['level'] ?? 2);
    $level = max(1, min(6, $level));
    $tag = 'h' . $level;
@endphp

<section class="cmbcore-page-block cmbcore-page-block--heading">
    <{{ $tag }}>{{ $props['text'] ?? '' }}</{{ $tag }}>
</section>
