@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('cart.title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-account-dashboard">
                <div class="cmbcore-account-dashboard__header">
                    <div>
                        <p class="cmbcore-account-dashboard__eyebrow">{{ theme_text('cart.title') }}</p>
                        <h1>{{ theme_text('cart.title') }}</h1>
                    </div>
                    @if (!empty($cart['items']))
                        <a class="cmbcore-button is-primary" href="{{ route('storefront.checkout.index') }}">{{ theme_text('cart.actions.checkout') }}</a>
                    @endif
                </div>

                @if (session('status'))
                    <div class="cmbcore-alert is-success">{{ session('status') }}</div>
                @endif

                <div class="cmbcore-table-card">
                    <table class="cmbcore-table">
                        <thead>
                        <tr>
                            <th>{{ theme_text('cart.fields.product') }}</th>
                            <th>{{ theme_text('cart.fields.quantity') }}</th>
                            <th>{{ theme_text('cart.fields.price') }}</th>
                            <th>{{ theme_text('cart.fields.total') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($cart['items'] as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item['product_name'] }}</strong>
                                    @if ($item['sku_name'])
                                        <div>{{ $item['sku_name'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('storefront.cart.update', $item['id']) }}" class="cmbcore-inline-form">
                                        @csrf
                                        <input type="number" min="1" name="quantity" value="{{ $item['quantity'] }}">
                                        <button type="submit" class="cmbcore-button is-secondary">{{ theme_text('cart.actions.update') }}</button>
                                    </form>
                                </td>
                                <td>{{ theme_money($item['unit_price']) }}</td>
                                <td>{{ theme_money($item['line_total']) }}</td>
                                <td>
                                    <form method="post" action="{{ route('storefront.cart.destroy', $item['id']) }}">
                                        @csrf
                                        <button type="submit" class="cmbcore-button is-secondary">{{ theme_text('cart.actions.remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ theme_text('cart.empty') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if (!empty($cart['items']))
                    <div class="cmbcore-cart-summary">
                        <strong>{{ theme_text('cart.fields.total') }}: {{ theme_money($cart['grand_total']) }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
