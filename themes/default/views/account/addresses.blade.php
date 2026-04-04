@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.addresses_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.addresses_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.addresses_kicker') }}</span>
                                <h1>{{ theme_text('account.addresses_title') }}</h1>
                                <p>{{ theme_text('account.addresses_description') }}</p>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="sf-alert is-success">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="sf-alert is-error">
                                @foreach ($errors->all() as $error)
                                    <span>{{ $error }}</span>
                                @endforeach
                            </div>
                        @endif

                        <form method="post" action="{{ route('storefront.account.addresses.store') }}" class="sf-form-grid sf-form-grid--2">
                            @csrf
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.address_label') }}</span>
                                <input type="text" name="label" required>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.recipient_name') }}</span>
                                <input type="text" name="recipient_name" required>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.phone') }}</span>
                                <input type="text" name="phone" required>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.province') }}</span>
                                <input type="text" name="province">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.district') }}</span>
                                <input type="text" name="district">
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.ward') }}</span>
                                <input type="text" name="ward">
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('account.fields.address_line') }}</span>
                                <input type="text" name="address_line" required>
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('account.fields.address_note') }}</span>
                                <textarea name="address_note"></textarea>
                            </label>
                            <label class="sf-field" style="grid-auto-flow: column; justify-content: start; align-items: center;">
                                <input type="checkbox" name="is_default" value="1" style="width: auto;">
                                <span>{{ theme_text('account.actions.set_default') }}</span>
                            </label>
                            <div>
                                <button type="submit" class="sf-button sf-button--primary">{{ theme_text('account.actions.save_address') }}</button>
                            </div>
                        </form>
                    </div>

                    @foreach ($addresses as $address)
                        <div class="sf-account__panel cmbcore-account-card">
                            <div class="sf-account__hero">
                                <div>
                                    <h2>{{ $address->label }}</h2>
                                    <p>{{ $address->formattedAddress() }}</p>
                                </div>
                                @if ($address->is_default)
                                    <span class="sf-status sf-status--success">{{ theme_text('account.default_badge') }}</span>
                                @endif
                            </div>

                            <form method="post" action="{{ route('storefront.account.addresses.update', ['id' => $address->id]) }}" class="sf-form-grid sf-form-grid--2">
                                @csrf
                                <label class="sf-field"><span>{{ theme_text('account.fields.address_label') }}</span><input type="text" name="label" value="{{ $address->label }}" required></label>
                                <label class="sf-field"><span>{{ theme_text('account.fields.recipient_name') }}</span><input type="text" name="recipient_name" value="{{ $address->recipient_name }}" required></label>
                                <label class="sf-field"><span>{{ theme_text('account.fields.phone') }}</span><input type="text" name="phone" value="{{ $address->phone }}" required></label>
                                <label class="sf-field"><span>{{ theme_text('account.fields.province') }}</span><input type="text" name="province" value="{{ $address->province }}"></label>
                                <label class="sf-field"><span>{{ theme_text('account.fields.district') }}</span><input type="text" name="district" value="{{ $address->district }}"></label>
                                <label class="sf-field"><span>{{ theme_text('account.fields.ward') }}</span><input type="text" name="ward" value="{{ $address->ward }}"></label>
                                <label class="sf-field is-full"><span>{{ theme_text('account.fields.address_line') }}</span><input type="text" name="address_line" value="{{ $address->address_line }}" required></label>
                                <label class="sf-field is-full"><span>{{ theme_text('account.fields.address_note') }}</span><textarea name="address_note">{{ $address->address_note }}</textarea></label>
                                <div>
                                    <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('account.actions.save_address') }}</button>
                                </div>
                            </form>

                            <div class="sf-header__actions">
                                <form method="post" action="{{ route('storefront.account.addresses.default', ['id' => $address->id]) }}">
                                    @csrf
                                    <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('account.actions.set_default') }}</button>
                                </form>
                                <form method="post" action="{{ route('storefront.account.addresses.destroy', ['id' => $address->id]) }}">
                                    @csrf
                                    <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('account.actions.delete_address') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
