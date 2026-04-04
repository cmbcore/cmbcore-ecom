@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.dashboard_title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-account-dashboard">
                <div class="cmbcore-account-dashboard__header">
                    <div>
                        <p class="cmbcore-account-dashboard__eyebrow">{{ theme_text('account.dashboard_title') }}</p>
                        <h1>{{ $customer->name }}</h1>
                        <p>{{ $customer->email }} · {{ $customer->phone }}</p>
                    </div>
                    <div class="cmbcore-account-dashboard__actions">
                        <a class="cmbcore-button is-secondary" href="{{ route('storefront.account.addresses') }}">{{ theme_text('account.actions.manage_addresses') }}</a>
                        <a class="cmbcore-button is-secondary" href="{{ route('storefront.account.returns') }}">Đổi trả</a>
                        <form method="post" action="{{ route('storefront.account.logout') }}">
                            @csrf
                            <button type="submit" class="cmbcore-button is-primary">{{ theme_text('account.actions.logout') }}</button>
                        </form>
                    </div>
                </div>

                @if (session('status'))
                    <div class="cmbcore-alert is-success">{{ session('status') }}</div>
                @endif

                <div class="cmbcore-data-grid">
                    <div class="cmbcore-data-card">
                        <strong>{{ $orders->total() }}</strong>
                        <span>{{ theme_text('account.stats.orders') }}</span>
                    </div>
                    <div class="cmbcore-data-card">
                        <strong>{{ $addresses->count() }}</strong>
                        <span>{{ theme_text('account.stats.addresses') }}</span>
                    </div>
                </div>

                <div class="cmbcore-table-card">
                    <div class="cmbcore-section-title cmbcore-section-title--detail">
                        <h2>{{ theme_text('account.recent_orders') }}</h2>
                    </div>
                    <table class="cmbcore-table">
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
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_status }} / {{ $order->fulfillment_status }}</td>
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
    </section>
@endsection
