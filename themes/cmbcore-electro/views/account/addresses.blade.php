@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.addresses_title')))

@section('content')
    @include(theme_view('partials.breadcrumbs'), [
        'pageTitle' => theme_text('account.addresses_title'),
        'breadcrumbs' => [
            ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
            ['label' => theme_text('account.addresses_title')],
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
                        <li><a href="{{ route('storefront.account.addresses') }}" class="active">{{ theme_text('account.sidebar.addresses') }}</a></li>
                        <li><a href="{{ route('storefront.account.returns') }}">{{ theme_text('account.sidebar.returns') }}</a></li>
                    </ul>
                </div>

                <div class="electro-account-content">
                    @if (session('status'))
                        <div class="electro-alert electro-alert--success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="electro-alert electro-alert--error">{{ $errors->first() }}</div>
                    @endif

                    <h2>{{ theme_text('account.addresses_title') }}</h2>

                    {{-- Existing addresses --}}
                    <div class="electro-row" style="margin-bottom:30px;">
                        @foreach ($addresses as $address)
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%; margin-bottom:15px;">
                                <div class="electro-order-summary">
                                    <h3>{{ $address->label ?: theme_text('account.default_address') }}</h3>
                                    <p>{{ $address->recipient_name }} · {{ $address->phone }}</p>
                                    <p>{{ $address->formattedAddress() }}</p>
                                    @if ($address->is_default)
                                        <span style="background:var(--electro-primary); color:#FFF; padding:2px 10px; border-radius:3px; font-size:11px;">{{ theme_text('account.default_badge') }}</span>
                                    @endif
                                    <div style="margin-top:10px; display:flex; gap:8px;">
                                        @if (!$address->is_default)
                                            <form method="post" action="{{ route('storefront.account.addresses.default', $address->id) }}">
                                                @csrf
                                                <button type="submit" class="electro-primary-btn" style="padding:6px 15px; font-size:12px; background:var(--electro-dark);">{{ theme_text('account.actions.set_default') }}</button>
                                            </form>
                                        @endif
                                        <form method="post" action="{{ route('storefront.account.addresses.destroy', $address->id) }}">
                                            @csrf
                                            <button type="submit" class="electro-primary-btn" style="padding:6px 15px; font-size:12px; background:#e74c3c;">{{ theme_text('account.actions.delete_address') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Add new address --}}
                    <h3>{{ theme_text('account.actions.add_address') }}</h3>
                    <form method="post" action="{{ route('storefront.account.addresses.store') }}">
                        @csrf
                        <div class="electro-row">
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.address_label') }}</label>
                                    <input class="electro-input" type="text" name="label">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.recipient_name') }}</label>
                                    <input class="electro-input" type="text" name="recipient_name">
                                </div>
                            </div>
                        </div>
                        <div class="electro-row">
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.phone') }}</label>
                                    <input class="electro-input" type="text" name="phone">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.province') }}</label>
                                    <input class="electro-input" type="text" name="province">
                                </div>
                            </div>
                        </div>
                        <div class="electro-row">
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.district') }}</label>
                                    <input class="electro-input" type="text" name="district">
                                </div>
                            </div>
                            <div class="electro-col" style="flex:0 0 50%; max-width:50%;">
                                <div class="electro-form-group">
                                    <label>{{ theme_text('account.fields.ward') }}</label>
                                    <input class="electro-input" type="text" name="ward">
                                </div>
                            </div>
                        </div>
                        <div class="electro-form-group">
                            <label>{{ theme_text('account.fields.address_line') }}</label>
                            <input class="electro-input" type="text" name="address_line">
                        </div>
                        <div class="electro-form-group">
                            <label>{{ theme_text('account.fields.address_note') }}</label>
                            <input class="electro-input" type="text" name="address_note">
                        </div>
                        <div class="electro-form-group">
                            <label><input type="checkbox" name="is_default" value="1"> {{ theme_text('account.actions.set_default') }}</label>
                        </div>
                        <button type="submit" class="electro-primary-btn">{{ theme_text('account.actions.save_address') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
