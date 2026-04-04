@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.addresses_title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-account-dashboard">
                <div class="cmbcore-account-dashboard__header">
                    <div>
                        <p class="cmbcore-account-dashboard__eyebrow">{{ theme_text('account.addresses_title') }}</p>
                        <h1>{{ theme_text('account.addresses_title') }}</h1>
                    </div>
                    <a class="cmbcore-button is-secondary" href="{{ route('storefront.account.dashboard') }}">{{ theme_text('account.actions.back_dashboard') }}</a>
                </div>

                @if (session('status'))
                    <div class="cmbcore-alert is-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="cmbcore-alert is-danger">{{ $errors->first() }}</div>
                @endif

                <div class="cmbcore-address-grid">
                    @foreach ($addresses as $address)
                        <div class="cmbcore-address-card">
                            <h3>{{ $address->label ?: theme_text('account.default_address') }}</h3>
                            <p>{{ $address->recipient_name }} · {{ $address->phone }}</p>
                            <p>{{ $address->formattedAddress() }}</p>
                            @if ($address->is_default)
                                <span class="cmbcore-badge">{{ theme_text('account.default_badge') }}</span>
                            @endif
                            <div class="cmbcore-address-card__actions">
                                @if (! $address->is_default)
                                    <form method="post" action="{{ route('storefront.account.addresses.default', $address->id) }}">
                                        @csrf
                                        <button type="submit" class="cmbcore-button is-secondary">{{ theme_text('account.actions.set_default') }}</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('storefront.account.addresses.destroy', $address->id) }}">
                                    @csrf
                                    <button type="submit" class="cmbcore-button is-secondary">{{ theme_text('account.actions.delete_address') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="cmbcore-account-card cmbcore-account-card--wide">
                    <h2>{{ theme_text('account.actions.add_address') }}</h2>
                    <form method="post" action="{{ route('storefront.account.addresses.store') }}" class="cmbcore-form-grid">
                        @csrf
                        <label><span>{{ theme_text('account.fields.address_label') }}</span><input type="text" name="label"></label>
                        <label><span>{{ theme_text('account.fields.recipient_name') }}</span><input type="text" name="recipient_name"></label>
                        <label><span>{{ theme_text('account.fields.phone') }}</span><input type="text" name="phone"></label>
                        <label><span>{{ theme_text('account.fields.province') }}</span><input type="text" name="province"></label>
                        <label><span>{{ theme_text('account.fields.district') }}</span><input type="text" name="district"></label>
                        <label><span>{{ theme_text('account.fields.ward') }}</span><input type="text" name="ward"></label>
                        <label class="is-full"><span>{{ theme_text('account.fields.address_line') }}</span><input type="text" name="address_line"></label>
                        <label class="is-full"><span>{{ theme_text('account.fields.address_note') }}</span><input type="text" name="address_note"></label>
                        <label class="cmbcore-checkbox is-full"><input type="checkbox" name="is_default" value="1"><span>{{ theme_text('account.actions.set_default') }}</span></label>
                        <button type="submit" class="cmbcore-button is-primary">{{ theme_text('account.actions.save_address') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
