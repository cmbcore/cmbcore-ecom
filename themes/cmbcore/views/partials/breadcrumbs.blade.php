@if (!empty($items ?? []))
    <nav class="cmbcore-breadcrumbs" aria-label="Breadcrumb">
        <ol>
            @foreach ($items as $item)
                <li>
                    <a class="cmbcore-breadcrumbs__item" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif

