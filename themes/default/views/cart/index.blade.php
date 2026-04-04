@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('cart.title')))

@section('content')
    <section class="sf-cart">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('cart.title')],
                ],
            ])

            <div class="sf-catalog__layout">
                <div>
                    <div class="sf-account__panel">
                        <span class="sf-kicker">{{ theme_text('cart.kicker') }}</span>
                        <h1>{{ theme_text('cart.title') }}</h1>
                        <p>{{ theme_text('cart.description') }}</p>
                    </div>

                    @if (session('status'))
                        <div class="sf-alert is-success">{{ session('status') }}</div>
                    @endif

                    @if (empty($cart['items']))
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('cart.empty_title') }}</h2>
                            <p>{{ theme_text('cart.empty') }}</p>
                            <a class="sf-button sf-button--primary" href="{{ route('storefront.products.index') }}">{{ theme_text('checkout.actions.continue_shopping') }}</a>
                        </div>
                    @else
                        <div class="sf-table-card">
                            <table class="sf-table">
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
                                @foreach ($cart['items'] as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['product_name'] }}</strong>
                                            @if (!empty($item['sku_name']))
                                                <div>{{ $item['sku_name'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="post" action="{{ route('storefront.cart.update', ['id' => $item['id']]) }}" class="sf-header__actions">
                                                @csrf
                                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" style="max-width: 96px;">
                                                <button type="submit" class="sf-button sf-button--ghost sf-button--small">{{ theme_text('cart.actions.update') }}</button>
                                            </form>
                                        </td>
                                        <td>{{ theme_money($item['unit_price']) }}</td>
                                        <td>{{ theme_money($item['line_total']) }}</td>
                                        <td>
                                            <form method="post" action="{{ route('storefront.cart.destroy', ['id' => $item['id']]) }}">
                                                @csrf
                                                <button type="submit" class="sf-button sf-button--ghost sf-button--small">{{ theme_text('cart.actions.remove') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <aside class="sf-sidebar__stack">
                    <div class="sf-summary-card">
                        <h2>{{ theme_text('checkout.summary_title') }}</h2>
                        <div class="sf-summary-card__row"><span>{{ theme_text('cart.fields.quantity') }}</span><strong>{{ $cart['total_quantity'] ?? 0 }}</strong></div>
                        <div class="sf-summary-card__row"><span>{{ theme_text('orders.labels.subtotal') }}</span><strong>{{ theme_money($cart['subtotal'] ?? 0) }}</strong></div>
                        <div class="sf-summary-card__row"><span>{{ theme_text('orders.labels.grand_total') }}</span><strong class="sf-summary-card__amount">{{ theme_money($cart['grand_total'] ?? 0) }}</strong></div>
                        <a class="sf-button sf-button--primary" href="{{ route('storefront.checkout.index') }}">{{ theme_text('cart.actions.checkout') }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
