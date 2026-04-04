@php
    $items = theme_setting_json('mobile_toolbar_items', []);
@endphp

@if ($items !== [])
    <nav class="sf-mobile-toolbar" aria-label="Mobile shortcuts">
        @foreach ($items as $item)
            <a class="sf-mobile-toolbar__item" href="{{ theme_url((string) ($item['url'] ?? '#')) }}" target="{{ ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self' }}" @if (($item['target'] ?? '_self') === '_blank') rel="noreferrer noopener" @endif>
                <i class="{{ $item['icon'] ?? 'fa-solid fa-circle' }} sf-mobile-toolbar__icon" aria-hidden="true"></i>
                <span>{{ $item['label'] ?? '' }}</span>
            </a>
        @endforeach
    </nav>
@endif
