@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.register_title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container cmbcore-account-shell">
            <div class="cmbcore-account-card">
                <h1>{{ theme_text('account.register_title') }}</h1>
                <p>{{ theme_text('account.register_description') }}</p>

                @if ($errors->any())
                    <div class="cmbcore-alert is-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('storefront.account.register.submit') }}" class="cmbcore-form-stack">
                    @csrf
                    <label><span>{{ theme_text('account.fields.name') }}</span><input type="text" name="name" value="{{ old('name') }}"></label>
                    <label><span>{{ theme_text('account.fields.email') }}</span><input type="email" name="email" value="{{ old('email') }}"></label>
                    <label><span>{{ theme_text('account.fields.phone') }}</span><input type="text" name="phone" value="{{ old('phone') }}"></label>
                    <label><span>{{ theme_text('account.fields.password') }}</span><input type="password" name="password"></label>
                    <label><span>{{ theme_text('account.fields.password_confirmation') }}</span><input type="password" name="password_confirmation"></label>
                    <button type="submit" class="cmbcore-button is-primary">{{ theme_text('account.actions.register') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
