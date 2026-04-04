@php
    $type = $type ?? 'order';
    $value = (string) ($value ?? '');
    $label = match ($type) {
        'payment' => theme_text('orders.payment_statuses.' . $value),
        'fulfillment' => theme_text('orders.fulfillment_statuses.' . $value),
        default => theme_text('orders.statuses.' . $value),
    };
    $tone = match ($value) {
        'confirmed', 'paid', 'delivered' => 'success',
        'cancelled' => 'danger',
        'shipping', 'processing', 'cod_pending' => 'info',
        default => 'muted',
    };
@endphp

<span class="sf-status sf-status--{{ $tone }}">{{ $label !== 'frontend.orders.statuses.' . $value && $label !== 'frontend.orders.payment_statuses.' . $value && $label !== 'frontend.orders.fulfillment_statuses.' . $value ? $label : $value }}</span>
