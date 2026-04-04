@php
    $items = $items ?? theme_context('breadcrumbs', []);
@endphp

@if (!empty($items))
    <nav class="sf-breadcrumbs" aria-label="Breadcrumb">
        <ol>
            @foreach ($items as $item)
                <li>
                    @if (!empty($item['url']))
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
