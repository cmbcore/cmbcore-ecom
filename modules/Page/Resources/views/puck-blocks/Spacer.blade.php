@php
    $height = max(8, min(200, (int) ($props['height'] ?? 40)));
@endphp

<div class="cmbcore-page-block cmbcore-page-block--spacer" style="height: {{ $height }}px"></div>
