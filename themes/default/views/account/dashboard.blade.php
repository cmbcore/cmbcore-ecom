@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.dashboard_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.dashboard_kicker') }}</span>
                                <h1>{{ $customer->name }}</h1>
                                <p>{{ $customer->email }} · {{ $customer->phone }}</p>
                            </div>
                            <a class="sf-button sf-button--ghost" href="{{ route('storefront.account.profile') }}">{{ theme_text('account.actions.edit_profile') }}</a>
                        </div>

                        @if (session('status'))
                            <div class="sf-alert is-success">{{ session('status') }}</div>
                        @endif

                        <div class="sf-account__stats">
                            <div class="sf-product__fact">
                                <span>{{ theme_text('account.stats.orders') }}</span>
                                <strong>{{ $orders->total() }}</strong>
                            </div>
                            <div class="sf-product__fact">
                                <span>{{ theme_text('account.stats.addresses') }}</span>
                                <strong>{{ $addresses->count() }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-section-heading">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.recent_orders') }}</span>
                                <h2>{{ theme_text('account.orders_title') }}</h2>
                            </div>
                            <a class="sf-button sf-button--ghost sf-button--small" href="{{ route('storefront.account.orders') }}">{{ theme_text('account.actions.view_all_orders') }}</a>
                        </div>

                        <div class="sf-table-card">
                            <table class="sf-table">
                                <thead>
                                <tr>
                                    <th>{{ theme_text('account.fields.order_number') }}</th>
                                    <th>{{ theme_text('account.fields.status') }}</th>
                                    <th>{{ theme_text('account.fields.total') }}</th>
                                    <th>{{ theme_text('account.fields.created_at') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td><a href="{{ route('storefront.account.orders.show', ['orderNumber' => $order->order_number]) }}">{{ $order->order_number }}</a></td>
                                        <td>@include(theme_view('partials.order-status'), ['type' => 'order', 'value' => $order->order_status])</td>
                                        <td>{{ theme_money($order->grand_total) }}</td>
                                        <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">{{ theme_text('account.empty_orders') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
