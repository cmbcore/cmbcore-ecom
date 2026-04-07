@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.title')))

@push('head')
    <style>
        /* ── Required field asterisk ─────────────────────────────── */
        .ck-field__required {
            color: #e02424;
            margin-left: 3px;
            font-size: 13px;
        }

        /* ── Field error ─────────────────────────────────────────── */
        .ck-field-error {
            display: none;
            margin-top: 4px;
            color: #e02424;
            font-size: 12px;
            font-weight: 500;
        }

        .ck-field-error.is-visible {
            display: block;
        }

        .ck-input-error {
            border-color: #e02424 !important;
        }

        /* ── Searchable dropdown (province/ward) ─────────────────── */
        .ck-select-wrap {
            position: relative;
        }

        .ck-select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(15, 23, 42, 0.14);
            background: #fff;
            color: #0f172a;
            font: inherit;
            font-size: 14px;
            cursor: pointer;
            text-align: left;
            user-select: none;
        }

        .ck-select-trigger:hover {
            border-color: rgba(15, 23, 42, 0.28);
        }

        .ck-select-trigger.is-placeholder {
            color: #9ca3af;
        }

        .ck-select-trigger .ck-select-caret {
            flex-shrink: 0;
            font-size: 10px;
            color: #6b7280;
            transition: transform 0.2s ease;
        }

        .ck-select-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 120;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #fff;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.14);
            display: none;
            flex-direction: column;
            max-height: 300px;
        }

        .ck-select-dropdown.is-open {
            display: flex;
        }

        .ck-select-trigger.is-open .ck-select-caret {
            transform: rotate(180deg);
        }

        .ck-select-search {
            flex-shrink: 0;
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .ck-select-search input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(15, 23, 42, 0.16);
            background: #f9fafb;
            font: inherit;
            font-size: 13px;
            outline: none;
        }

        .ck-select-search input:focus {
            border-color: #0f172a;
        }

        .ck-select-list {
            flex: 1;
            overflow-y: auto;
            margin: 0;
            padding: 4px 0;
            list-style: none;
        }

        .ck-select-list li {
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
            line-height: 1.3;
        }

        .ck-select-list li:hover,
        .ck-select-list li.is-focused {
            background: #f3f4f6;
        }

        .ck-select-list li.is-selected {
            background: #0f172a;
            color: #fff;
        }

        .ck-select-list li.is-hidden {
            display: none;
        }

        .ck-select-empty {
            padding: 16px 14px;
            color: #9ca3af;
            font-size: 13px;
            text-align: center;
        }

        /* ── Payment method cards ────────────────────────────────── */
        .ck-payment-grid {
            display: grid;
            gap: 10px;
        }

        .ck-payment-option {
            position: relative;
        }

        .ck-payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .ck-payment-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 2px solid rgba(15, 23, 42, 0.12);
            background: #fff;
            cursor: pointer;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .ck-payment-card:hover {
            border-color: rgba(15, 23, 42, 0.32);
        }

        .ck-payment-option input:checked + .ck-payment-card {
            border-color: #0f172a;
            background: #f8faff;
            box-shadow: 0 0 0 1px #0f172a;
        }

        .ck-payment-card__icon {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f1f5f9;
            font-size: 18px;
            color: #374151;
        }

        .ck-payment-option input:checked + .ck-payment-card .ck-payment-card__icon {
            background: #0f172a;
            color: #fff;
        }

        .ck-payment-card__body {
            flex: 1;
            min-width: 0;
        }

        .ck-payment-card__name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .ck-payment-card__desc {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 400;
            color: #64748b;
            line-height: 1.4;
        }

        .ck-payment-card__check {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color 0.18s, background 0.18s;
        }

        .ck-payment-option input:checked + .ck-payment-card .ck-payment-card__check {
            border-color: #0f172a;
            background: #0f172a;
        }

        .ck-payment-card__check::after {
            content: '';
            display: none;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #fff;
        }

        .ck-payment-option input:checked + .ck-payment-card .ck-payment-card__check::after {
            display: block;
        }

        /* ── Section heading ─────────────────────────────────────── */
        .ck-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 24px 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .ck-section-title:first-child {
            margin-top: 0;
        }

        .ck-section-title i {
            color: #64748b;
        }

        /* ── Submit area ─────────────────────────────────────────── */
        .ck-submit-area {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 8px;
        }

        .ck-submit-area .cmbcore-button {
            min-width: 200px;
            font-size: 15px;
            gap: 8px;
        }

        .ck-summary-head {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 15px;
        }

        .cmbcore-cart-summary p {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 14px;
            color: #4b5563;
        }

        .cmbcore-cart-summary strong {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 16px;
            color: #0f172a;
        }

        /* ── Shipping method selector ────────────────────────────── */
        .ck-shipping-grid {
            display: grid;
            gap: 8px;
        }

        .ck-shipping-option {
            position: relative;
        }

        .ck-shipping-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
        }

        .ck-shipping-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 11px 14px;
            border: 1.5px solid rgba(15, 23, 42, 0.1);
            cursor: pointer;
            font-size: 14px;
            transition: border-color 0.16s ease;
        }

        .ck-shipping-card:hover {
            border-color: rgba(15, 23, 42, 0.28);
        }

        .ck-shipping-option input:checked + .ck-shipping-card {
            border-color: #0f172a;
            background: #f8faff;
        }

        .ck-shipping-card strong {
            font-weight: 600;
        }

        .ck-shipping-card .ck-fee {
            margin-left: auto;
            font-weight: 700;
            color: #0f172a;
        }
    </style>
@endpush

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-checkout-layout">

                {{-- ── LEFT: Form ───────────────────────────────────────── --}}
                <div class="cmbcore-account-card cmbcore-account-card--wide">
                    <h1 style="font-size:22px;font-weight:700;margin-bottom:20px;">
                        <i class="fa-solid fa-bag-shopping" style="margin-right:8px;color:#64748b;"></i>
                        {{ theme_text('checkout.title') }}
                    </h1>

                    @if ($errors->any())
                        <div class="cmbcore-alert is-danger">
                            <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form id="ck-form" method="post" action="{{ route('storefront.checkout.place_order') }}" class="cmbcore-form-grid" novalidate>
                        @csrf
                        <input type="hidden" name="mode" value="{{ $mode }}">

                        {{-- ── Thông tin người đặt hàng ─────────────────── --}}
                        <div class="ck-section-title is-full">
                            <i class="fa-solid fa-user"></i> Thông tin người đặt hàng
                        </div>

                        <label>
                            <span>Họ và tên<span class="ck-field__required">*</span></span>
                            <input
                                id="ck-customer-name"
                                type="text"
                                name="customer_name"
                                value="{{ old('customer_name', $customer?->name) }}"
                                placeholder="Nhập họ và tên"
                                autocomplete="name"
                                data-required="true"
                            >
                            <span class="ck-field-error" data-for="ck-customer-name">Vui lòng nhập họ và tên.</span>
                        </label>

                        <label>
                            <span>Số điện thoại<span class="ck-field__required">*</span></span>
                            <input
                                id="ck-customer-phone"
                                type="tel"
                                name="customer_phone"
                                value="{{ old('customer_phone', $customer?->phone) }}"
                                placeholder="VD: 0912345678"
                                autocomplete="tel"
                                data-required="true"
                                data-pattern="^(0|\+84)[0-9]{9}$"
                            >
                            <span class="ck-field-error" data-for="ck-customer-phone">Số điện thoại không hợp lệ (VD: 0912345678).</span>
                        </label>

                        @if (! $customer)
                            <label class="is-full">
                                <span>Email nhận xác nhận đơn hàng<span class="ck-field__required">*</span></span>
                                <input
                                    id="ck-guest-email"
                                    type="email"
                                    name="guest_email"
                                    value="{{ old('guest_email') }}"
                                    placeholder="email@example.com"
                                    autocomplete="email"
                                    data-required="true"
                                    data-type="email"
                                >
                                <span class="ck-field-error" data-for="ck-guest-email">Địa chỉ email không hợp lệ.</span>
                            </label>
                        @endif

                        {{-- ── Địa chỉ giao hàng ────────────────────────── --}}
                        <div class="ck-section-title is-full">
                            <i class="fa-solid fa-location-dot"></i> Địa chỉ giao hàng
                        </div>

                        @if ($customer && $addresses->isNotEmpty())
                            <label class="is-full">
                                <span>Địa chỉ đã lưu</span>
                                <select name="address_id" id="ck-address-id">
                                    <option value="">— Nhập địa chỉ mới —</option>
                                    @foreach ($addresses as $address)
                                        <option
                                            value="{{ $address->id }}"
                                            @selected(old('address_id', $address->is_default ? $address->id : null) == $address->id)
                                        >
                                            {{ $address->label ?: $address->recipient_name }} — {{ $address->formattedAddress() }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <label>
                            <span>Người nhận hàng<span class="ck-field__required">*</span></span>
                            <input
                                id="ck-recipient-name"
                                type="text"
                                name="recipient_name"
                                value="{{ old('recipient_name', $customer?->name) }}"
                                placeholder="Tên người nhận"
                                autocomplete="name"
                                data-required="true"
                            >
                            <span class="ck-field-error" data-for="ck-recipient-name">Vui lòng nhập tên người nhận.</span>
                        </label>

                        <label>
                            <span>SĐT người nhận<span class="ck-field__required">*</span></span>
                            <input
                                id="ck-shipping-phone"
                                type="tel"
                                name="shipping_phone"
                                value="{{ old('shipping_phone', $customer?->phone) }}"
                                placeholder="VD: 0912345678"
                                autocomplete="tel"
                                data-required="true"
                                data-pattern="^(0|\+84)[0-9]{9}$"
                            >
                            <span class="ck-field-error" data-for="ck-shipping-phone">Số điện thoại không hợp lệ.</span>
                        </label>

                        {{-- Province searchable select --}}
                        <label>
                            <span>Tỉnh / Thành phố<span class="ck-field__required">*</span></span>
                            <input type="hidden" name="province_code" id="ck-province-code" value="{{ old('province_code') }}">
                            <input type="hidden" name="province" id="ck-province-name" value="{{ old('province') }}">
                            <div class="ck-select-wrap" id="ck-province-wrap" data-select="province">
                                <button type="button" class="ck-select-trigger is-placeholder" id="ck-province-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span id="ck-province-label">Chọn tỉnh / thành phố</span>
                                    <i class="fa-solid fa-chevron-down ck-select-caret"></i>
                                </button>
                                <div class="ck-select-dropdown" id="ck-province-dropdown" role="listbox">
                                    <div class="ck-select-search">
                                        <input type="text" placeholder="Tìm tỉnh / thành phố…" id="ck-province-search" autocomplete="off" spellcheck="false">
                                    </div>
                                    <ul class="ck-select-list" id="ck-province-list"></ul>
                                    <div class="ck-select-empty" id="ck-province-empty" style="display:none;">Không tìm thấy kết quả</div>
                                </div>
                            </div>
                            <span class="ck-field-error" id="ck-province-error">Vui lòng chọn tỉnh / thành phố.</span>
                        </label>

                        {{-- Ward searchable select (loads after province) --}}
                        <label>
                            <span>Phường / Xã<span class="ck-field__required">*</span></span>
                            <input type="hidden" name="ward_code" id="ck-ward-code" value="{{ old('ward_code') }}">
                            <input type="hidden" name="ward" id="ck-ward-name" value="{{ old('ward') }}">
                            <div class="ck-select-wrap" id="ck-ward-wrap" data-select="ward">
                                <button type="button" class="ck-select-trigger is-placeholder" id="ck-ward-trigger" aria-haspopup="listbox" aria-expanded="false" disabled>
                                    <span id="ck-ward-label">Chọn tỉnh trước</span>
                                    <i class="fa-solid fa-chevron-down ck-select-caret"></i>
                                </button>
                                <div class="ck-select-dropdown" id="ck-ward-dropdown" role="listbox">
                                    <div class="ck-select-search">
                                        <input type="text" placeholder="Tìm phường / xã…" id="ck-ward-search" autocomplete="off" spellcheck="false">
                                    </div>
                                    <ul class="ck-select-list" id="ck-ward-list"></ul>
                                    <div class="ck-select-empty" id="ck-ward-empty" style="display:none;">Không tìm thấy kết quả</div>
                                </div>
                            </div>
                            <span class="ck-field-error" id="ck-ward-error">Vui lòng chọn phường / xã.</span>
                        </label>

                        <label class="is-full">
                            <span>Địa chỉ cụ thể (số nhà, tên đường)<span class="ck-field__required">*</span></span>
                            <input
                                id="ck-address-line"
                                type="text"
                                name="address_line"
                                value="{{ old('address_line') }}"
                                placeholder="VD: 123 Đường Nguyễn Trãi"
                                data-required="true"
                            >
                            <span class="ck-field-error" data-for="ck-address-line">Vui lòng nhập địa chỉ cụ thể.</span>
                        </label>

                        <label class="is-full">
                            <span>Ghi chú đơn hàng</span>
                            <textarea name="note" rows="3" placeholder="Hướng dẫn giao hàng, ghi chú cho người bán…">{{ old('note') }}</textarea>
                        </label>

                        {{-- ── Mã giảm giá & Vận chuyển ────────────────── --}}
                        <div class="ck-section-title is-full">
                            <i class="fa-solid fa-truck"></i> Vận chuyển & Ưu đãi
                        </div>

                        {{-- Coupon --}}
                        <div class="is-full" id="ck-coupon-wrap">
                            <label style="display:grid;gap:8px;font-size:14px;font-weight:600;color:#1e293b;">
                                <span>Mã giảm giá</span>
                            </label>
                            <div style="display:flex;gap:8px;margin-top:6px;">
                                <div style="flex:1;position:relative;">
                                    <input
                                        id="ck-coupon-input"
                                        type="text"
                                        name="coupon_code"
                                        value="{{ old('coupon_code', $checkout['coupon_code'] ?? '') }}"
                                        placeholder="VD: SALE10"
                                        style="width:100%;padding:12px 14px;border:1px solid rgba(15,23,42,0.14);font:inherit;font-size:14px;text-transform:uppercase;"
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                </div>
                                <button
                                    type="button"
                                    id="ck-coupon-btn"
                                    class="cmbcore-button is-secondary"
                                    style="white-space:nowrap;min-width:110px;"
                                >
                                    <i class="fa-solid fa-tag" style="margin-right:6px;"></i>Áp dụng
                                </button>
                            </div>
                            {{-- Server-side error on page reload --}}
                            @if (!empty($checkout['coupon_error']))
                                <div class="cmbcore-alert is-danger" style="margin-top:8px;font-size:13px;">
                                    <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                                    {{ $checkout['coupon_error'] }}
                                </div>
                            @endif
                            {{-- Applied coupon badge (server-rendered) --}}
                            @if (!empty($checkout['coupon_code']))
                                <div id="ck-coupon-applied" style="display:flex;align-items:center;gap:8px;margin-top:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;font-size:13px;color:#166534;">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Đã áp dụng mã <strong>{{ $checkout['coupon_code'] }}</strong> — giảm {{ theme_money($checkout['discount_total'] ?? 0) }}</span>
                                    <button type="button" id="ck-coupon-remove" style="margin-left:auto;padding:0;border:0;background:transparent;color:#64748b;font-size:13px;cursor:pointer;" title="Xoá coupon">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            @else
                                <div id="ck-coupon-applied" style="display:none;"></div>
                            @endif
                            {{-- AJAX feedback (hidden initially) --}}
                            <div id="ck-coupon-msg" style="display:none;margin-top:8px;padding:8px 12px;font-size:13px;"></div>
                        </div>

                        <div class="is-full">
                            <label style="display:grid;gap:8px;font-size:14px;font-weight:600;color:#1e293b;">
                                <span>Phương thức vận chuyển<span class="ck-field__required">*</span></span>
                            </label>
                            <div class="ck-shipping-grid" style="margin-top:6px;">
                                @foreach (($checkout['shipping_methods'] ?? []) as $method)
                                    <label class="ck-shipping-option">
                                        <input
                                            type="radio"
                                            name="shipping_method_id"
                                            value="{{ $method['id'] }}"
                                            @checked(old('shipping_method_id', $checkout['selected_shipping_method']['id'] ?? null) == $method['id'])
                                        >
                                        <div class="ck-shipping-card">
                                            <i class="fa-solid fa-truck-fast" style="color:#64748b;"></i>
                                            <strong>{{ $method['name'] }}</strong>
                                            <span class="ck-fee">{{ theme_money($method['fee']) }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── Phương thức thanh toán ───────────────────── --}}
                        <div class="ck-section-title is-full">
                            <i class="fa-solid fa-credit-card"></i> Phương thức thanh toán
                        </div>

                        <div class="is-full">
                            <div class="ck-payment-grid" id="ck-payment-grid">
                                @php
                                    $paymentIconMap = [
                                        'cod'         => ['icon' => 'fa-solid fa-money-bill-wave',  'color' => '#16a34a', 'desc' => 'Trả tiền mặt khi nhận hàng. Không cần thẻ hay tài khoản ngân hàng.'],
                                        'bank_transfer'=> ['icon' => 'fa-solid fa-building-columns', 'color' => '#2563eb', 'desc' => 'Chuyển khoản ngân hàng. Đơn sẽ được xác nhận sau khi thanh toán.'],
                                        'vnpay'       => ['icon' => 'fa-solid fa-qrcode',            'color' => '#0ea5e9', 'desc' => 'Thanh toán qua VNPay – QR code hoặc thẻ ATM/Visa/Master.'],
                                        'momo'        => ['icon' => 'fa-solid fa-wallet',            'color' => '#c026d3', 'desc' => 'Thanh toán qua ví MoMo. Nhanh, an toàn, không phí.'],
                                        'zalopay'     => ['icon' => 'fa-solid fa-bolt',              'color' => '#0369a1', 'desc' => 'Thanh toán qua ZaloPay – hỗ trợ thẻ ngân hàng và ví ZaloPay.'],
                                    ];
                                    $selectedPayment = old('payment_method', $checkout['selected_payment_method']['code'] ?? null);
                                @endphp

                                @foreach (($checkout['payment_methods'] ?? []) as $method)
                                    @php
                                        $meta = $paymentIconMap[$method['code']] ?? [
                                            'icon'  => 'fa-solid fa-circle-dollar-to-slot',
                                            'color' => '#374151',
                                            'desc'  => $method['description'] ?? '',
                                        ];
                                    @endphp
                                    <label class="ck-payment-option">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $method['code'] }}"
                                            @checked($selectedPayment === $method['code'] || ($loop->first && ! $selectedPayment))
                                        >
                                        <div class="ck-payment-card">
                                            <div class="ck-payment-card__icon" style="color:{{ $meta['color'] }};">
                                                <i class="{{ $meta['icon'] }}"></i>
                                            </div>
                                            <div class="ck-payment-card__body">
                                                <div class="ck-payment-card__name">{{ $method['label'] }}</div>
                                                <div class="ck-payment-card__desc">
                                                    {{ $method['description'] ?? $meta['desc'] }}
                                                </div>
                                            </div>
                                            <div class="ck-payment-card__check"></div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <span class="ck-field-error" id="ck-payment-error">Vui lòng chọn phương thức thanh toán.</span>
                        </div>

                        {{-- ── Lưu địa chỉ (logged-in only) ───────────── --}}
                        @if ($customer)
                            <div class="ck-section-title is-full">
                                <i class="fa-solid fa-star"></i> Lưu địa chỉ
                            </div>
                            <label class="cmbcore-checkbox">
                                <input type="checkbox" name="save_address" value="1">
                                <span>{{ theme_text('checkout.actions.save_address') }}</span>
                            </label>
                            <label class="cmbcore-checkbox">
                                <input type="checkbox" name="save_as_default" value="1">
                                <span>{{ theme_text('checkout.actions.save_as_default') }}</span>
                            </label>
                            <label class="is-full">
                                <span>Nhãn địa chỉ (VD: Nhà, Văn phòng)</span>
                                <input type="text" name="address_label" value="{{ old('address_label') }}" placeholder="VD: Nhà riêng">
                            </label>
                        @endif

                        {{-- ── Submit ───────────────────────────────────── --}}
                        <div class="ck-submit-area">
                            <button type="submit" class="cmbcore-button is-primary">
                                <i class="fa-solid fa-lock" style="font-size:13px;"></i>
                                {{ theme_text('checkout.actions.place_order') }}
                            </button>
                            <a href="{{ route('storefront.cart.index') }}" style="font-size:13px;color:#64748b;">
                                <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>Quay lại giỏ hàng
                            </a>
                        </div>
                    </form>
                </div>

                {{-- ── RIGHT: Order summary ─────────────────────────────── --}}
                <div class="cmbcore-table-card" style="align-self:start;position:sticky;top:88px;">
                    <div class="ck-summary-head">
                        <i class="fa-solid fa-receipt" style="color:#64748b;"></i>
                        {{ theme_text('checkout.summary_title') }}
                    </div>

                    <div class="cmbcore-checkout-items" style="margin-top:16px;">
                        @foreach ($checkout['items'] as $item)
                            <div class="cmbcore-checkout-item">
                                <div>
                                    <strong style="font-size:13px;">{{ $item['product_name'] }}</strong>
                                    @if ($item['sku_name'])
                                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $item['sku_name'] }}</div>
                                    @endif
                                </div>
                                <div style="font-size:13px;color:#6b7280;">x{{ $item['quantity'] }}</div>
                                <div style="font-size:14px;font-weight:700;">{{ theme_money($item['line_total']) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="cmbcore-cart-summary">
                        <p><span>Tạm tính</span><span>{{ theme_money($checkout['subtotal'] ?? 0) }}</span></p>
                        {{-- Discount row: shown when coupon applied, also AJAX-updatable via id --}}
                        <p id="ck-summary-discount"
                           style="{{ ($checkout['discount_total'] ?? 0) > 0 ? '' : 'display:none;' }}color:#16a34a;">
                            <span>Giảm giá</span>
                            <span class="ck-discount-val">-{{ theme_money($checkout['discount_total'] ?? 0) }}</span>
                        </p>
                        <p><span>Vận chuyển</span><span>{{ theme_money($checkout['shipping_total'] ?? 0) }}</span></p>
                        @if (($checkout['tax_total'] ?? 0) > 0)
                            <p><span>Thuế</span><span>{{ theme_money($checkout['tax_total']) }}</span></p>
                        @endif
                        <hr style="width:100%;border:none;border-top:1px solid #e5e7eb;margin:4px 0;">
                        <strong>
                            <span>{{ theme_text('checkout.fields.total') }}</span>
                            <span style="color:var(--cmbcore-primary,#ed2524);font-size:18px;">
                                {{ theme_money($checkout['grand_total']) }}
                            </span>
                        </strong>
                    </div>
                    {{-- Inject subtotal for coupon AJAX --}}
                    <script>window.__ckSubtotal = {{ (int)($checkout['subtotal'] ?? 0) }};</script>

                    @if (! empty($checkout['selected_payment_method']['description']))
                        <div style="margin-top:14px;padding:12px 14px;background:#f1f5f9;font-size:13px;color:#475569;">
                            <i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>
                            {{ $checkout['selected_payment_method']['description'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── API base ──────────────────────────────────────────────── */
    const API = '/api/address';

    /* ── Searchable select factory ─────────────────────────────── */
    function SearchSelect({ triggerId, labelId, searchId, listId, emptyId, codeInputId, nameInputId, onSelect }) {
        const trigger  = document.getElementById(triggerId);
        const label    = document.getElementById(labelId);
        const search   = document.getElementById(searchId);
        const list     = document.getElementById(listId);
        const empty    = document.getElementById(emptyId);
        const codeInput = document.getElementById(codeInputId);
        const nameInput = document.getElementById(nameInputId);
        const dropdown = trigger?.nextElementSibling;

        if (!trigger || !dropdown) return;

        let items = []; // [{code, name}]
        let focusedIndex = -1;

        function open() {
            dropdown.classList.add('is-open');
            trigger.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            search.value = '';
            renderList(items);
            search.focus();
            focusedIndex = -1;
        }

        function close() {
            dropdown.classList.remove('is-open');
            trigger.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function select(code, name) {
            codeInput.value = code;
            nameInput.value = name;
            label.textContent = name;
            trigger.classList.remove('is-placeholder');
            close();
            if (onSelect) onSelect(code, name);
        }

        function renderList(filtered) {
            list.innerHTML = '';
            focusedIndex = -1;
            const noResult = filtered.length === 0;
            empty.style.display = noResult ? 'block' : 'none';
            filtered.forEach((item, idx) => {
                const li = document.createElement('li');
                li.textContent = item.name;
                li.setAttribute('role', 'option');
                li.setAttribute('data-code', item.code);
                if (item.code === codeInput.value) li.classList.add('is-selected');
                li.addEventListener('click', () => select(item.code, item.name));
                li.addEventListener('mouseenter', () => {
                    setFocus(idx, filtered);
                });
                list.appendChild(li);
            });
        }

        function setFocus(idx, filtered) {
            const lis = list.querySelectorAll('li');
            lis.forEach(li => li.classList.remove('is-focused'));
            if (idx >= 0 && idx < lis.length) {
                lis[idx].classList.add('is-focused');
                lis[idx].scrollIntoView({ block: 'nearest' });
            }
            focusedIndex = idx;
        }

        function filterList(q) {
            const query = q.trim().toLowerCase();
            if (!query) return renderList(items);
            const filtered = items.filter(it => it.name.toLowerCase().includes(query));
            renderList(filtered);
        }

        function load(data) {
            items = data.map(d => ({ code: d.code, name: d.name }));
            trigger.disabled = false;
        }

        function reset(placeholder) {
            items = [];
            codeInput.value = '';
            nameInput.value = '';
            label.textContent = placeholder;
            trigger.classList.add('is-placeholder');
            trigger.disabled = true;
            list.innerHTML = '';
        }

        // Events
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (trigger.disabled) return;
            dropdown.classList.contains('is-open') ? close() : open();
        });

        search.addEventListener('input', () => filterList(search.value));

        // Keyboard navigation
        search.addEventListener('keydown', (e) => {
            const lis = Array.from(list.querySelectorAll('li:not(.is-hidden)'));
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                focusedIndex = Math.min(focusedIndex + 1, lis.length - 1);
                setFocus(focusedIndex, []);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                focusedIndex = Math.max(focusedIndex - 1, 0);
                setFocus(focusedIndex, []);
            } else if (e.key === 'Enter' && focusedIndex >= 0) {
                e.preventDefault();
                lis[focusedIndex]?.click();
            } else if (e.key === 'Escape') {
                close();
                trigger.focus();
            }
        });

        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                close();
            }
        });

        return { load, reset, select };
    }

    /* ── Init province select ──────────────────────────────────── */
    let wardSelect;

    const provinceSelect = SearchSelect({
        triggerId: 'ck-province-trigger',
        labelId: 'ck-province-label',
        searchId: 'ck-province-search',
        listId: 'ck-province-list',
        emptyId: 'ck-province-empty',
        codeInputId: 'ck-province-code',
        nameInputId: 'ck-province-name',
        onSelect: (code) => {
            wardSelect.reset('Đang tải…');
            fetch(`${API}/provinces/${code}/communes`)
                .then(r => r.json())
                .then(json => {
                    wardSelect.load(json.data || []);
                    document.getElementById('ck-ward-trigger').disabled = false;
                    document.getElementById('ck-ward-label').textContent = 'Chọn phường / xã';
                })
                .catch(() => wardSelect.reset('Không tải được, thử lại'));
        },
    });

    /* ── Init ward select ──────────────────────────────────────── */
    wardSelect = SearchSelect({
        triggerId: 'ck-ward-trigger',
        labelId: 'ck-ward-label',
        searchId: 'ck-ward-search',
        listId: 'ck-ward-list',
        emptyId: 'ck-ward-empty',
        codeInputId: 'ck-ward-code',
        nameInputId: 'ck-ward-name',
        onSelect: () => {},
    });

    /* ── Load provinces on page load ───────────────────────────── */
    document.getElementById('ck-province-label').textContent = 'Đang tải…';
    fetch(`${API}/provinces`)
        .then(r => r.json())
        .then(json => {
            provinceSelect.load(json.data || []);
            document.getElementById('ck-province-label').textContent = 'Chọn tỉnh / thành phố';
            document.getElementById('ck-province-trigger').classList.add('is-placeholder');

            // Restore selected values from old() if present
            const savedProvinceCode = document.getElementById('ck-province-code').value;
            const savedProvinceName = document.getElementById('ck-province-name').value;
            if (savedProvinceCode && savedProvinceName) {
                document.getElementById('ck-province-label').textContent = savedProvinceName;
                document.getElementById('ck-province-trigger').classList.remove('is-placeholder');
                // Load wards for restored province
                fetch(`${API}/provinces/${savedProvinceCode}/communes`)
                    .then(r => r.json())
                    .then(json => {
                        wardSelect.load(json.data || []);
                        const savedWardCode = document.getElementById('ck-ward-code').value;
                        const savedWardName = document.getElementById('ck-ward-name').value;
                        if (savedWardCode && savedWardName) {
                            document.getElementById('ck-ward-label').textContent = savedWardName;
                            document.getElementById('ck-ward-trigger').classList.remove('is-placeholder');
                        } else {
                            document.getElementById('ck-ward-label').textContent = 'Chọn phường / xã';
                        }
                        document.getElementById('ck-ward-trigger').disabled = false;
                    });
            }
        })
        .catch(() => {
            document.getElementById('ck-province-label').textContent = 'Không tải được';
        });

    /* ── Form validation ───────────────────────────────────────── */
    const form = document.getElementById('ck-form');

    function showError(el, msg) {
        el.classList.add('ck-input-error');
        const err = el.closest('label')?.querySelector('.ck-field-error') ||
                    document.querySelector(`.ck-field-error[data-for="${el.id}"]`);
        if (err) {
            if (msg) err.textContent = msg;
            err.classList.add('is-visible');
        }
    }

    function clearError(el) {
        el.classList.remove('ck-input-error');
        const err = el.closest('label')?.querySelector('.ck-field-error') ||
                    document.querySelector(`.ck-field-error[data-for="${el.id}"]`);
        if (err) err.classList.remove('is-visible');
    }

    function validateField(el) {
        const val = el.value.trim();
        const required = el.dataset.required === 'true';
        const type = el.dataset.type;
        const pattern = el.dataset.pattern;

        if (required && !val) { showError(el); return false; }
        if (type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) { showError(el); return false; }
        if (pattern && val && !new RegExp(pattern).test(val)) { showError(el); return false; }

        clearError(el);
        return true;
    }

    // Live validation on blur
    form.querySelectorAll('input[data-required], input[data-type], input[data-pattern]').forEach(el => {
        el.addEventListener('blur', () => validateField(el));
        el.addEventListener('input', () => {
            if (el.classList.contains('ck-input-error')) validateField(el);
        });
    });

    form.addEventListener('submit', (e) => {
        let valid = true;

        // Standard inputs
        form.querySelectorAll('input[data-required], input[data-type], input[data-pattern]').forEach(el => {
            if (!validateField(el)) valid = false;
        });

        // Province
        const provinceCode = document.getElementById('ck-province-code').value;
        const provinceErr = document.getElementById('ck-province-error');
        if (!provinceCode) {
            provinceErr.classList.add('is-visible');
            valid = false;
        } else {
            provinceErr.classList.remove('is-visible');
        }

        // Ward
        const wardCode = document.getElementById('ck-ward-code').value;
        const wardErr = document.getElementById('ck-ward-error');
        if (!wardCode) {
            wardErr.classList.add('is-visible');
            valid = false;
        } else {
            wardErr.classList.remove('is-visible');
        }

        // Payment method
        const paymentChecked = form.querySelector('input[name="payment_method"]:checked');
        const paymentErr = document.getElementById('ck-payment-error');
        if (!paymentChecked) {
            paymentErr.classList.add('is-visible');
            valid = false;
        } else {
            paymentErr.classList.remove('is-visible');
        }

        if (!valid) {
            e.preventDefault();
            // Scroll to first error
            const firstErr = form.querySelector('.ck-input-error, .ck-field-error.is-visible');
            firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    /* ── AJAX Coupon Apply ─────────────────────────────────────── */
    (function initCoupon() {
        const couponInput   = document.getElementById('ck-coupon-input');
        const couponBtn     = document.getElementById('ck-coupon-btn');
        const couponApplied = document.getElementById('ck-coupon-applied');
        const couponMsg     = document.getElementById('ck-coupon-msg');
        const couponRemove  = document.getElementById('ck-coupon-remove');

        if (!couponInput || !couponBtn) return;

        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0,
            }).format(amount);
        }

        function setMsg(text, isSuccess) {
            couponMsg.textContent = text;
            couponMsg.style.display = text ? 'block' : 'none';
            couponMsg.style.background = isSuccess ? '#f0fdf4' : '#fff1f2';
            couponMsg.style.border = isSuccess ? '1px solid #bbf7d0' : '1px solid #fecdd3';
            couponMsg.style.color = isSuccess ? '#166534' : '#be123c';
        }

        function hideMsg() {
            couponMsg.style.display = 'none';
        }

        // Update the discount line in the order summary (right panel)
        function updateSummaryDiscount(discountTotal, code) {
            const discountRow = document.getElementById('ck-summary-discount');
            if (!discountRow) return;
            if (discountTotal > 0) {
                discountRow.style.display = '';
                const valEl = discountRow.querySelector('.ck-discount-val');
                if (valEl) valEl.textContent = '-' + formatMoney(discountTotal);
            } else {
                discountRow.style.display = 'none';
            }
        }

        async function applyCoupon() {
            const code = couponInput.value.trim().toUpperCase();
            if (!code) { setMsg('Vui lòng nhập mã giảm giá.', false); return; }

            couponBtn.disabled = true;
            couponBtn.textContent = 'Đang kiểm tra…';
            hideMsg();

            try {
                const response = await fetch('/api/storefront/coupon/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        coupon_code: code,
                        subtotal: window.__ckSubtotal ?? 0,
                    }),
                });
                const json = await response.json();

                if (json.success) {
                    // Show applied badge
                    couponApplied.innerHTML = `
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Đã áp dụng mã <strong>${json.code}</strong> — giảm ${formatMoney(json.discount_total)}</span>
                        <button type="button" id="ck-coupon-remove" style="margin-left:auto;padding:0;border:0;background:transparent;color:#64748b;font-size:13px;cursor:pointer;" title="Xoá coupon">
                            <i class="fa-solid fa-xmark"></i>
                        </button>`;
                    couponApplied.style.cssText = 'display:flex;align-items:center;gap:8px;margin-top:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;font-size:13px;color:#166534;';
                    couponApplied.querySelector('#ck-coupon-remove')?.addEventListener('click', removeCoupon);
                    couponInput.value = json.code;
                    hideMsg();
                    updateSummaryDiscount(json.discount_total, json.code);
                } else {
                    setMsg(json.message ?? json.errors?.coupon_code?.[0] ?? 'Coupon không hợp lệ.', false);
                    couponApplied.style.display = 'none';
                }
            } catch {
                setMsg('Không thể kiểm tra coupon. Vui lòng thử lại.', false);
            } finally {
                couponBtn.disabled = false;
                couponBtn.innerHTML = '<i class="fa-solid fa-tag" style="margin-right:6px;"></i>Áp dụng';
            }
        }

        function removeCoupon() {
            couponInput.value = '';
            couponApplied.style.display = 'none';
            hideMsg();
            updateSummaryDiscount(0, null);
        }

        couponBtn.addEventListener('click', applyCoupon);
        couponInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
        });
        couponRemove?.addEventListener('click', removeCoupon);
    })();

})();
</script>
@endpush
