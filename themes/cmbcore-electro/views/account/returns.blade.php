@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.returns_title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('account.returns_title'),
        'breadcrumbs' => [
            ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
            ['label' => theme_text('account.returns_title')],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            <div class="electro-account-layout">
                <div class="electro-account-sidebar">
                    <ul>
                        <li><a href="{{ route('storefront.account.dashboard') }}">{{ theme_text('account.sidebar.overview') }}</a></li>
                        <li><a href="{{ route('storefront.account.orders') }}">{{ theme_text('account.sidebar.orders') }}</a></li>
                        <li><a href="{{ route('storefront.wishlist.index') }}">{{ theme_text('account.sidebar.wishlist') }}</a></li>
                        <li><a href="{{ route('storefront.account.addresses') }}">{{ theme_text('account.sidebar.addresses') }}</a></li>
                        <li><a href="{{ route('storefront.account.returns') }}" class="active">{{ theme_text('account.sidebar.returns') }}</a></li>
                    </ul>
                </div>

                <div class="electro-account-content">
                    @if (session('status'))
                        <div class="electro-alert electro-alert--success">{{ session('status') }}</div>
                    @endif

                    <h2>{{ theme_text('account.returns_title') }}</h2>
                    <p style="color:var(--electro-grey);">{{ theme_text('account.returns_description') }}</p>

                    {{-- Return requests list --}}
                    <table class="electro-cart-table" style="margin:20px 0;">
                        <thead>
                        <tr>
                            <th>{{ theme_text('returns.fields.order') }}</th>
                            <th>{{ theme_text('returns.fields.product') }}</th>
                            <th>{{ theme_text('returns.fields.quantity') }}</th>
                            <th>{{ theme_text('returns.fields.status') }}</th>
                            <th>{{ theme_text('returns.fields.created_at') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($return_requests as $request)
                            <tr>
                                <td>{{ $request['order']['order_number'] ?? '-' }}</td>
                                <td>
                                    {{ $request['item']['product_name'] ?? theme_text('returns.full_order') }}
                                    @if (!empty($request['item']['sku_name']))
                                        <br><small>{{ $request['item']['sku_name'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $request['requested_quantity'] }}</td>
                                <td>{{ $request['status'] }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($request['created_at'])->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ theme_text('returns.empty') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{-- New return request --}}
                    <h3>{{ theme_text('returns.form_description') }}</h3>
                    @forelse ($eligible_orders as $order)
                        <div class="electro-order-summary" style="margin-bottom:15px;">
                            <h3>{{ $order->order_number }}</h3>
                            <form method="post" action="{{ route('storefront.account.returns.store', ['orderNumber' => $order->order_number]) }}">
                                @csrf
                                <div class="electro-form-group">
                                    <label>{{ theme_text('returns.fields.product') }}</label>
                                    <select class="electro-input-select" name="order_item_id" style="width:100%;">
                                        <option value="">{{ theme_text('returns.full_order') }}</option>
                                        @foreach ($order->items as $item)
                                            <option value="{{ $item->id }}">{{ $item->product_name }}{{ $item->sku_name ? ' - ' . $item->sku_name : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="electro-form-group">
                                    <label>{{ theme_text('returns.fields.quantity') }}</label>
                                    <input class="electro-input" type="number" min="1" name="requested_quantity" value="1" required>
                                </div>
                                <div class="electro-form-group">
                                    <label>{{ theme_text('returns.fields.reason') }}</label>
                                    <textarea class="electro-input" name="reason" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="electro-primary-btn">{{ theme_text('returns.submit') }}</button>
                            </form>
                        </div>
                    @empty
                        <p style="color:var(--electro-grey);">{{ theme_text('returns.unavailable_description') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
