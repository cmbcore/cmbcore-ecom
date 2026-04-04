@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('cart.title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('cart.title'),
        'breadcrumbs' => [
            ['label' => theme_text('cart.title')],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            @if (session('status'))
                <div class="electro-alert electro-alert--success">{{ session('status') }}</div>
            @endif

            @if (empty($cart['items']))
                <div class="electro-text-center" style="padding: 60px 0;">
                    <h2>{{ theme_text('cart.empty_title') }}</h2>
                    <p>{{ theme_text('cart.empty') }}</p>
                    <a class="electro-primary-btn" href="{{ route('storefront.products.index') }}">{{ theme_text('checkout.actions.continue_shopping') }}</a>
                </div>
            @else
                <div class="electro-store-layout">
                    <div class="electro-store-main" style="flex: 1;">
                        <table class="electro-cart-table">
                            <thead>
                            <tr>
                                <th>{{ theme_text('cart.fields.product') }}</th>
                                <th>{{ theme_text('cart.fields.price') }}</th>
                                <th>{{ theme_text('cart.fields.quantity') }}</th>
                                <th>{{ theme_text('cart.fields.total') }}</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($cart['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="electro-cart-product">
                                            @if (!empty($item['image_url']))
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}">
                                            @endif
                                            <div>
                                                <strong>{{ $item['product_name'] }}</strong>
                                                @if (!empty($item['sku_name']))
                                                    <br><small>{{ $item['sku_name'] }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ theme_money($item['unit_price']) }}</td>
                                    <td>
                                        <form method="post" action="{{ route('storefront.cart.update', ['id' => $item['id']]) }}" style="display:flex; gap:5px; align-items:center;">
                                            @csrf
                                            <div class="electro-input-number">
                                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99">
                                                <span class="electro-qty-up">+</span>
                                                <span class="electro-qty-down">-</span>
                                            </div>
                                            <button type="submit" class="electro-primary-btn" style="padding: 6px 15px; font-size:12px;">{{ theme_text('cart.actions.update') }}</button>
                                        </form>
                                    </td>
                                    <td><strong>{{ theme_money($item['line_total']) }}</strong></td>
                                    <td>
                                        <form method="post" action="{{ route('storefront.cart.destroy', ['id' => $item['id']]) }}">
                                            @csrf
                                            <button type="submit" style="background:none; border:none; color:var(--electro-primary); cursor:pointer; font-size:16px;"><i class="fa fa-close"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="electro-store-aside" style="flex: 0 0 300px; width:300px;">
                        <div class="electro-order-summary">
                            <h3>{{ theme_text('checkout.summary_title') }}</h3>
                            <div class="electro-order-col">
                                <span>{{ theme_text('cart.fields.quantity') }}</span>
                                <strong>{{ $cart['total_quantity'] ?? 0 }}</strong>
                            </div>
                            <div class="electro-order-col">
                                <span>{{ theme_text('orders.labels.subtotal') }}</span>
                                <strong>{{ theme_money($cart['subtotal'] ?? 0) }}</strong>
                            </div>
                            <div class="electro-order-col">
                                <span>{{ theme_text('orders.labels.grand_total') }}</span>
                                <strong class="electro-order-total">{{ theme_money($cart['grand_total'] ?? 0) }}</strong>
                            </div>
                            <a class="electro-primary-btn electro-order-submit" href="{{ route('storefront.checkout.index') }}">
                                {{ theme_text('cart.actions.checkout') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
