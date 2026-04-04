@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('checkout.title'),
        'breadcrumbs' => [
            ['label' => theme_text('cart.title'), 'url' => route('storefront.cart.index')],
            ['label' => theme_text('checkout.title')],
        ],
    ])

    <div class="electro-section">
        <div class="electro-container">
            @if ($errors->any())
                <div class="electro-alert electro-alert--error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('storefront.checkout.place_order') }}">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="electro-checkout-layout">
                    {{-- BILLING --}}
                    <div class="electro-checkout-billing">
                        <h3>{{ theme_text('checkout.title') }}</h3>

                        <div class="electro-form-group">
                            <label>{{ theme_text('checkout.fields.customer_name') }} *</label>
                            <input class="electro-input" type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                        </div>
                        <div class="electro-form-group">
                            <label>{{ theme_text('checkout.fields.customer_phone') }} *</label>
                            <input class="electro-input" type="text" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}" required>
                        </div>

                        @if (!$customer)
                            <div class="electro-form-group">
                                <label>{{ theme_text('checkout.fields.guest_email') }}</label>
                                <input class="electro-input" type="email" name="guest_email" value="{{ old('guest_email') }}">
                            </div>
                        @endif

                        @if ($customer && $addresses->isNotEmpty())
                            <div class="electro-form-group">
                                <label>{{ theme_text('checkout.fields.saved_address') }}</label>
                                <select class="electro-input-select" name="address_id" style="width:100%;">
                                    <option value="">{{ theme_text('checkout.placeholders.new_address') }}</option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}">{{ $address->label }} · {{ $address->formattedAddress() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="electro-row">
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('checkout.fields.recipient_name') }}</label>
                                    <input class="electro-input" type="text" name="recipient_name" value="{{ old('recipient_name', $customer?->name) }}">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('checkout.fields.shipping_phone') }}</label>
                                    <input class="electro-input" type="text" name="shipping_phone" value="{{ old('shipping_phone', $customer?->phone) }}">
                                </div>
                            </div>
                        </div>

                        <div class="electro-row">
                            <div class="electro-col" style="flex:0 0 33.33%; max-width:33.33%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('checkout.fields.province') }}</label>
                                    <input class="electro-input" type="text" name="province" value="{{ old('province') }}">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 33.33%; max-width:33.33%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('checkout.fields.district') }}</label>
                                    <input class="electro-input" type="text" name="district" value="{{ old('district') }}">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 33.33%; max-width:33.33%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('checkout.fields.ward') }}</label>
                                    <input class="electro-input" type="text" name="ward" value="{{ old('ward') }}">
                                </div>
                            </div>
                        </div>

                        <div class="electro-form-group">
                            <label>{{ theme_text('checkout.fields.address_line') }}</label>
                            <input class="electro-input" type="text" name="address_line" value="{{ old('address_line') }}">
                        </div>
                        <div class="electro-form-group">
                            <label>{{ theme_text('checkout.fields.note') }}</label>
                            <textarea class="electro-input" name="note">{{ old('note') }}</textarea>
                        </div>

                        @if ($customer)
                            <div class="electro-form-group">
                                <label><input type="checkbox" name="save_address" value="1"> {{ theme_text('checkout.actions.save_address') }}</label>
                            </div>
                        @endif

                        {{-- Shipping Method --}}
                        <h3 style="margin-top:20px;">{{ theme_text('checkout.shipping_title') }}</h3>
                        @foreach (($checkout['shipping_methods'] ?? []) as $method)
                            <div class="electro-input-radio">
                                <label>
                                    <input type="radio" name="shipping_method_id" value="{{ $method['id'] }}" @checked(($checkout['selected_shipping_method']['id'] ?? null) === $method['id'])>
                                    {{ $method['name'] }} — <strong>{{ theme_money($method['fee']) }}</strong>
                                </label>
                            </div>
                        @endforeach

                        {{-- Payment Method --}}
                        <h3 style="margin-top:20px;">{{ theme_text('checkout.payment_title') }}</h3>
                        @foreach (($checkout['payment_methods'] ?? []) as $method)
                            <div class="electro-input-radio">
                                <label>
                                    <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(($checkout['selected_payment_method']['code'] ?? null) === $method['code'])>
                                    {{ $method['label'] }}
                                    @if (!empty($method['description']))
                                        <br><small style="color:var(--electro-grey);">{{ $method['description'] }}</small>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>

                    {{-- ORDER SUMMARY --}}
                    <div class="electro-checkout-order">
                        <div class="electro-order-summary">
                            <h3>{{ theme_text('checkout.summary_title') }}</h3>

                            @foreach (($checkout['items'] ?? []) as $item)
                                <div class="electro-order-col">
                                    <span>{{ $item['product_name'] }} × {{ $item['quantity'] }}</span>
                                    <strong>{{ theme_money($item['line_total']) }}</strong>
                                </div>
                            @endforeach

                            <div class="electro-order-col">
                                <span>{{ theme_text('orders.labels.subtotal') }}</span>
                                <strong>{{ theme_money($checkout['subtotal'] ?? 0) }}</strong>
                            </div>
                            <div class="electro-order-col">
                                <span>{{ theme_text('orders.labels.discount_total') }}</span>
                                <strong>{{ theme_money($checkout['discount_total'] ?? 0) }}</strong>
                            </div>
                            <div class="electro-order-col">
                                <span>{{ theme_text('orders.labels.shipping_total') }}</span>
                                <strong>{{ theme_money($checkout['shipping_total'] ?? 0) }}</strong>
                            </div>
                            <div class="electro-order-col">
                                <span>{{ theme_text('checkout.fields.total') }}</span>
                                <strong class="electro-order-total">{{ theme_money($checkout['grand_total'] ?? 0) }}</strong>
                            </div>

                            <button type="submit" class="electro-primary-btn electro-order-submit">
                                {{ theme_text('checkout.actions.place_order') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
