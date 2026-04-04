@php
    $toolbarItems = theme_setting_json('mobile_toolbar_items', []);
@endphp

<nav class="electro-mobile-toolbar" aria-label="Mobile shortcuts">
    @foreach ($toolbarItems as $item)
        <a class="electro-mobile-toolbar__item" href="{{ theme_url($item['url'] ?? '/') }}" @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noreferrer noopener" @endif>
            <i class="{{ $item['icon'] ?? 'fa fa-home' }} electro-mobile-toolbar__icon" aria-hidden="true"></i>
            <span>{{ $item['label'] ?? '' }}</span>
        </a>
    @endforeach
</nav>
