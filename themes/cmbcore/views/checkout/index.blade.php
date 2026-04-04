@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-checkout-layout">
                <div class="cmbcore-account-card cmbcore-account-card--wide">
                    <h1>{{ theme_text('checkout.title') }}</h1>

                    @if ($errors->any())
                        <div class="cmbcore-alert is-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="post" action="{{ route('storefront.checkout.place_order') }}" class="cmbcore-form-grid">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $mode }}">

                        <label><span>{{ theme_text('checkout.fields.customer_name') }}</span><input type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}"></label>
                        <label><span>{{ theme_text('checkout.fields.customer_phone') }}</span><input type="text" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}"></label>

                        @if (! $customer)
                            <label class="is-full"><span>{{ theme_text('checkout.fields.guest_email') }}</span><input type="email" name="guest_email" value="{{ old('guest_email') }}"></label>
                        @endif

                        @if ($customer && $addresses->isNotEmpty())
                            <label class="is-full">
                                <span>{{ theme_text('checkout.fields.saved_address') }}</span>
                                <select name="address_id">
                                    <option value="">{{ theme_text('checkout.placeholders.new_address') }}</option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected(old('address_id', $address->is_default ? $address->id : null) == $address->id)>
                                            {{ $address->label ?: $address->recipient_name }} - {{ $address->formattedAddress() }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <label><span>{{ theme_text('checkout.fields.recipient_name') }}</span><input type="text" name="recipient_name" value="{{ old('recipient_name', $customer?->name) }}"></label>
                        <label><span>{{ theme_text('checkout.fields.shipping_phone') }}</span><input type="text" name="shipping_phone" value="{{ old('shipping_phone', $customer?->phone) }}"></label>
                        <label><span>{{ theme_text('checkout.fields.province') }}</span><input type="text" name="province" value="{{ old('province') }}"></label>
                        <label><span>{{ theme_text('checkout.fields.district') }}</span><input type="text" name="district" value="{{ old('district') }}"></label>
                        <label><span>{{ theme_text('checkout.fields.ward') }}</span><input type="text" name="ward" value="{{ old('ward') }}"></label>
                        <label class="is-full"><span>{{ theme_text('checkout.fields.address_line') }}</span><input type="text" name="address_line" value="{{ old('address_line') }}"></label>
                        <label class="is-full"><span>{{ theme_text('checkout.fields.address_note') }}</span><input type="text" name="address_note" value="{{ old('address_note') }}"></label>
                        <label>
                            <span>Ma giảm giá</span>
                            <input type="text" name="coupon_code" value="{{ old('coupon_code', $checkout['coupon_code'] ?? '') }}" placeholder="VD: SALE10">
                        </label>
                        <label>
                            <span>Phuong thuc ship</span>
                            <select name="shipping_method_id">
                                @foreach (($checkout['shipping_methods'] ?? []) as $method)
                                    <option value="{{ $method['id'] }}" @selected(old('shipping_method_id', $checkout['selected_shipping_method']['id'] ?? null) == $method['id'])>
                                        {{ $method['name'] }} - {{ theme_money($method['fee']) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="is-full">
                            <span>Phuong thuc thanh toán</span>
                            <select name="payment_method">
                                @foreach (($checkout['payment_methods'] ?? []) as $method)
                                    <option value="{{ $method['code'] }}" @selected(old('payment_method', $checkout['selected_payment_method']['code'] ?? null) == $method['code'])>
                                        {{ $method['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="is-full"><span>{{ theme_text('checkout.fields.note') }}</span><textarea name="note" rows="4">{{ old('note') }}</textarea></label>

                        @if ($customer)
                            <label class="cmbcore-checkbox"><input type="checkbox" name="save_address" value="1"><span>{{ theme_text('checkout.actions.save_address') }}</span></label>
                            <label class="cmbcore-checkbox"><input type="checkbox" name="save_as_default" value="1"><span>{{ theme_text('checkout.actions.save_as_default') }}</span></label>
                            <label class="is-full"><span>{{ theme_text('checkout.fields.address_label') }}</span><input type="text" name="address_label" value="{{ old('address_label') }}"></label>
                        @endif

                        <button type="submit" class="cmbcore-button is-primary">{{ theme_text('checkout.actions.place_order') }}</button>
                    </form>
                </div>

                <div class="cmbcore-table-card">
                    <h2>{{ theme_text('checkout.summary_title') }}</h2>
                    <div class="cmbcore-checkout-items">
                        @foreach ($checkout['items'] as $item)
                            <div class="cmbcore-checkout-item">
                                <div>
                                    <strong>{{ $item['product_name'] }}</strong>
                                    @if ($item['sku_name'])
                                        <div>{{ $item['sku_name'] }}</div>
                                    @endif
                                </div>
                                <div>x{{ $item['quantity'] }}</div>
                                <div>{{ theme_money($item['line_total']) }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="cmbcore-cart-summary">
                        <p>Tam tinh: {{ theme_money($checkout['subtotal'] ?? 0) }}</p>
                        <p>Giảm giá: -{{ theme_money($checkout['discount_total'] ?? 0) }}</p>
                        <p>Van chuyen: {{ theme_money($checkout['shipping_total'] ?? 0) }}</p>
                        <p>Thue: {{ theme_money($checkout['tax_total'] ?? 0) }}</p>
                        <strong>{{ theme_text('checkout.fields.total') }}: {{ theme_money($checkout['grand_total']) }}</strong>
                        @if (!empty($checkout['selected_payment_method']['description']))
                            <p>{{ $checkout['selected_payment_method']['description'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
