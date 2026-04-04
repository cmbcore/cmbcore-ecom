@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.title')))

@section('content')
    <section class="sf-checkout">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('cart.title'), 'url' => route('storefront.cart.index')],
                    ['label' => theme_text('checkout.title')],
                ],
            ])

            @if ($errors->any())
                <div class="sf-alert is-error">
                    @foreach ($errors->all() as $error)
                        <span>{{ $error }}</span>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('storefront.checkout.place_order') }}" class="sf-catalog__layout">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="sf-account__list">
                    <div class="sf-account__panel">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('checkout.kicker') }}</span>
                                <h1>{{ theme_text('checkout.title') }}</h1>
                                <p>{{ theme_text('checkout.description') }}</p>
                            </div>
                        </div>

                        <div class="sf-form-grid sf-form-grid--2">
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.customer_name') }}</span>
                                <input type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.customer_phone') }}</span>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}" required>
                            </label>

                            @if (!$customer)
                                <label class="sf-field is-full">
                                    <span>{{ theme_text('checkout.fields.guest_email') }}</span>
                                    <input type="email" name="guest_email" value="{{ old('guest_email') }}">
                                </label>
                            @endif

                            @if ($customer && $addresses->isNotEmpty())
                                <label class="sf-field is-full">
                                    <span>{{ theme_text('checkout.fields.saved_address') }}</span>
                                    <select name="address_id">
                                        <option value="">{{ theme_text('checkout.placeholders.new_address') }}</option>
                                        @foreach ($addresses as $address)
                                            <option value="{{ $address->id }}">{{ $address->label }} · {{ $address->formattedAddress() }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.recipient_name') }}</span>
                                <input type="text" name="recipient_name" value="{{ old('recipient_name', $customer?->name) }}">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.shipping_phone') }}</span>
                                <input type="text" name="shipping_phone" value="{{ old('shipping_phone', $customer?->phone) }}">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.province') }}</span>
                                <input type="text" name="province" value="{{ old('province') }}">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.district') }}</span>
                                <input type="text" name="district" value="{{ old('district') }}">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.ward') }}</span>
                                <input type="text" name="ward" value="{{ old('ward') }}">
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('checkout.fields.address_line') }}</span>
                                <input type="text" name="address_line" value="{{ old('address_line') }}">
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('checkout.fields.address_note') }}</span>
                                <textarea name="address_note">{{ old('address_note') }}</textarea>
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('checkout.fields.note') }}</span>
                                <textarea name="note">{{ old('note') }}</textarea>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.address_label') }}</span>
                                <input type="text" name="address_label" value="{{ old('address_label') }}">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('checkout.fields.coupon_code') }}</span>
                                <input type="text" name="coupon_code" value="{{ old('coupon_code', $checkout['coupon_code'] ?? '') }}">
                            </label>
                            @if ($customer)
                                <label class="sf-field" style="grid-auto-flow: column; justify-content: start; align-items: center;">
                                    <input type="checkbox" name="save_address" value="1" style="width: auto;">
                                    <span>{{ theme_text('checkout.actions.save_address') }}</span>
                                </label>
                                <label class="sf-field" style="grid-auto-flow: column; justify-content: start; align-items: center;">
                                    <input type="checkbox" name="save_as_default" value="1" style="width: auto;">
                                    <span>{{ theme_text('checkout.actions.save_as_default') }}</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="sf-account__panel">
                        <h2>{{ theme_text('checkout.shipping_title') }}</h2>
                        <div class="sf-account__list">
                            @foreach (($checkout['shipping_methods'] ?? []) as $method)
                                <label class="sf-field">
                                    <span style="display: flex; justify-content: space-between; gap: 1rem;">
                                        <span>
                                            <input type="radio" name="shipping_method_id" value="{{ $method['id'] }}" @checked(($checkout['selected_shipping_method']['id'] ?? null) === $method['id']) style="width: auto;">
                                            {{ $method['name'] }}
                                        </span>
                                        <strong>{{ theme_money($method['fee']) }}</strong>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="sf-account__panel">
                        <h2>{{ theme_text('checkout.payment_title') }}</h2>
                        <div class="sf-account__list">
                            @foreach (($checkout['payment_methods'] ?? []) as $method)
                                <label class="sf-field">
                                    <span>
                                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(($checkout['selected_payment_method']['code'] ?? null) === $method['code']) style="width: auto;">
                                        {{ $method['label'] }}
                                    </span>
                                    @if (!empty($method['description']))
                                        <small>{{ $method['description'] }}</small>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <aside class="sf-sidebar__stack">
                    <div class="sf-summary-card">
                        <h2>{{ theme_text('checkout.summary_title') }}</h2>
                        @foreach (($checkout['items'] ?? []) as $item)
                            <div class="sf-checkout__line">
                                <span>{{ $item['product_name'] }} × {{ $item['quantity'] }}</span>
                                <strong>{{ theme_money($item['line_total']) }}</strong>
                            </div>
                        @endforeach
                        <div class="sf-checkout__line"><span>{{ theme_text('orders.labels.subtotal') }}</span><strong>{{ theme_money($checkout['subtotal'] ?? 0) }}</strong></div>
                        <div class="sf-checkout__line"><span>{{ theme_text('orders.labels.discount_total') }}</span><strong>{{ theme_money($checkout['discount_total'] ?? 0) }}</strong></div>
                        <div class="sf-checkout__line"><span>{{ theme_text('orders.labels.shipping_total') }}</span><strong>{{ theme_money($checkout['shipping_total'] ?? 0) }}</strong></div>
                        <div class="sf-checkout__line"><span>{{ theme_text('orders.labels.tax_total') }}</span><strong>{{ theme_money($checkout['tax_total'] ?? 0) }}</strong></div>
                        <div class="sf-checkout__line"><span>{{ theme_text('checkout.fields.total') }}</span><strong>{{ theme_money($checkout['grand_total'] ?? 0) }}</strong></div>
                        <div class="sf-alert is-success">{{ theme_text('checkout.cod_notice') }}</div>
                        <button type="submit" class="sf-button sf-button--primary">{{ theme_text('checkout.actions.place_order') }}</button>
                    </div>
                </aside>
            </form>
        </div>
    </section>
@endsection
