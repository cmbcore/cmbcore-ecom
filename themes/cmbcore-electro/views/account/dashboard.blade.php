@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.dashboard_title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('account.dashboard_title'),
        'breadcrumbs' => [
            ['label' => theme_text('account.dashboard_title')],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <div class="electro-account-layout">
                {{-- Sidebar --}}
                <div class="electro-account-sidebar">
                    <ul>
                        <li><a href="{{ route('storefront.account.dashboard') }}" class="active">{{ theme_text('account.sidebar.overview') }}</a></li>
                        <li><a href="{{ route('storefront.account.orders') }}">{{ theme_text('account.sidebar.orders') }}</a></li>
                        <li><a href="{{ route('storefront.wishlist.index') }}">{{ theme_text('account.sidebar.wishlist') }}</a></li>
                        <li><a href="{{ route('storefront.account.addresses') }}">{{ theme_text('account.sidebar.addresses') }}</a></li>
                        <li><a href="{{ route('storefront.account.returns') }}">{{ theme_text('account.sidebar.returns') }}</a></li>
                        <li>
                            <form method="post" action="{{ route('storefront.account.logout') }}">
                                @csrf
                                <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--electro-heading); padding:10px 15px; font-size:14px; width:100%; text-align:left;">{{ theme_text('account.actions.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>

                {{-- Content --}}
                <div class="electro-account-content">
                    @if (session('status'))
                        <div class="electro-alert electro-alert--success">{{ session('status') }}</div>
                    @endif

                    <h2>{{ $customer->name }}</h2>
                    <p style="color:var(--electro-grey);">{{ $customer->email }} · {{ $customer->phone }}</p>

                    {{-- Stats --}}
                    <div class="electro-row" style="margin: 20px 0;">
                        <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                            <div class="electro-order-summary" style="text-align:center;">
                                <h2 style="margin:0;">{{ $orders->total() }}</h2>
                                <span style="color:var(--electro-grey);">{{ theme_text('account.stats.orders') }}</span>
                            </div>
                        </div>
                        <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                            <div class="electro-order-summary" style="text-align:center;">
                                <h2 style="margin:0;">{{ $addresses->count() }}</h2>
                                <span style="color:var(--electro-grey);">{{ theme_text('account.stats.addresses') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Recent Orders --}}
                    <h3>{{ theme_text('account.recent_orders') }}</h3>
                    <table class="electro-cart-table">
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
    </div>
@endsection
