@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.orders_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.orders_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.orders_kicker') }}</span>
                                <h1>{{ theme_text('account.orders_title') }}</h1>
                                <p>{{ theme_text('account.orders_description') }}</p>
                            </div>

                            <form method="get" action="{{ route('storefront.account.orders') }}">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="">{{ theme_text('account.filters.all_statuses') }}</option>
                                    @foreach (['pending', 'confirmed', 'cancelled', 'processing', 'shipping', 'delivered', 'unpaid', 'cod_pending', 'paid'] as $status)
                                        <option value="{{ $status }}" @selected(($selected_status ?? '') === $status)>{{ theme_text('orders.statuses.' . $status) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        <div class="sf-table-card">
                            <table class="sf-table">
                                <thead>
                                <tr>
                                    <th>{{ theme_text('account.fields.order_number') }}</th>
                                    <th>{{ theme_text('account.fields.status') }}</th>
                                    <th>{{ theme_text('account.fields.total') }}</th>
                                    <th>{{ theme_text('account.fields.created_at') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td>{{ $order->order_number }}</td>
                                        <td>
                                            @include(theme_view('partials.order-status'), ['type' => 'order', 'value' => $order->order_status])
                                            @include(theme_view('partials.order-status'), ['type' => 'fulfillment', 'value' => $order->fulfillment_status])
                                            @include(theme_view('partials.order-status'), ['type' => 'payment', 'value' => $order->payment_status])
                                        </td>
                                        <td>{{ theme_money($order->grand_total) }}</td>
                                        <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                        <td><a class="sf-button sf-button--ghost sf-button--small" href="{{ route('storefront.account.orders.show', ['orderNumber' => $order->order_number]) }}">{{ theme_text('account.actions.view_order') }}</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">{{ theme_text('account.empty_orders') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($orders, 'links'))
                            <div class="sf-pagination">
                                {{ $orders->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
