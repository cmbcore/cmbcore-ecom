@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.order_detail_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.orders_title'), 'url' => route('storefront.account.orders')],
                    ['label' => $order->order_number],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.order_detail_kicker') }}</span>
                                <h1>{{ $order->order_number }}</h1>
                                <p>{{ theme_text('account.order_detail_description') }}</p>
                            </div>
                            <div class="sf-header__actions">
                                @include(theme_view('partials.order-status'), ['type' => 'order', 'value' => $order->order_status])
                                @include(theme_view('partials.order-status'), ['type' => 'fulfillment', 'value' => $order->fulfillment_status])
                                @include(theme_view('partials.order-status'), ['type' => 'payment', 'value' => $order->payment_status])
                            </div>
                        </div>

                        <div class="sf-account__stats">
                            <div class="sf-product__fact">
                                <span>{{ theme_text('orders.labels.subtotal') }}</span>
                                <strong>{{ theme_money($order->subtotal) }}</strong>
                            </div>
                            <div class="sf-product__fact">
                                <span>{{ theme_text('orders.labels.grand_total') }}</span>
                                <strong>{{ theme_money($order->grand_total) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="sf-account__panel cmbcore-account-card">
                        <h2>{{ theme_text('orders.items_title') }}</h2>
                        <div class="sf-table-card">
                            <table class="sf-table">
                                <thead>
                                <tr>
                                    <th>{{ theme_text('cart.fields.product') }}</th>
                                    <th>{{ theme_text('cart.fields.quantity') }}</th>
                                    <th>{{ theme_text('cart.fields.price') }}</th>
                                    <th>{{ theme_text('cart.fields.total') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->product_name }}</strong>
                                            @if ($item->sku_name)
                                                <div>{{ $item->sku_name }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ theme_money($item->unit_price) }}</td>
                                        <td>{{ theme_money($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-form-grid sf-form-grid--2">
                            <div class="sf-summary-card">
                                <h2>{{ theme_text('orders.shipping_title') }}</h2>
                                <div class="sf-checkout__line"><span>{{ theme_text('checkout.fields.recipient_name') }}</span><strong>{{ $order->shipping_recipient_name }}</strong></div>
                                <div class="sf-checkout__line"><span>{{ theme_text('checkout.fields.shipping_phone') }}</span><strong>{{ $order->shipping_phone }}</strong></div>
                                <div class="sf-checkout__line"><span>{{ theme_text('orders.labels.shipping_address') }}</span><strong>{{ $order->shipping_full_address }}</strong></div>
                            </div>
                            <div class="sf-summary-card">
                                <h2>{{ theme_text('orders.timeline_title') }}</h2>
                                @foreach ($order->histories as $history)
                                    <div class="sf-checkout__line">
                                        <span>{{ theme_text('orders.statuses.' . ($history->to_status ?? 'pending')) }}</span>
                                        <strong>{{ $history->created_at?->format('d/m/Y H:i') }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
