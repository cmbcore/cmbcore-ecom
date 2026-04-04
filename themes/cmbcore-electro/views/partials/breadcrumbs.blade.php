@php
    $breadcrumbs = $breadcrumbs ?? [];
@endphp

<div class="electro-breadcrumb">
    <div class="electro-container">
        @if (!empty($pageTitle))
            <h3 class="electro-breadcrumb-header">{{ $pageTitle }}</h3>
        @endif
        <ul class="electro-breadcrumb-tree">
            <li><a href="{{ theme_home_url() }}">{{ theme_text('navigation.home') }}</a></li>
            @foreach ($breadcrumbs as $crumb)
                @if (!empty($crumb['url']))
                    <li><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                @else
                    <li class="active">{{ $crumb['label'] }}</li>
                @endif
            @endforeach
        </ul>
    </div>
</div>
