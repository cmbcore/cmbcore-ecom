@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.login_title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container cmbcore-account-shell">
            <div class="cmbcore-account-card">
                <h1>{{ theme_text('account.login_title') }}</h1>
                <p>{{ theme_text('account.login_description') }}</p>

                @if (session('status'))
                    <div class="cmbcore-alert is-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="cmbcore-alert is-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('storefront.account.login.submit') }}" class="cmbcore-form-stack">
                    @csrf
                    <label>
                        <span>{{ theme_text('account.fields.login') }}</span>
                        <input type="text" name="login" value="{{ old('login') }}" placeholder="{{ theme_text('account.placeholders.login') }}">
                    </label>
                    <label>
                        <span>{{ theme_text('account.fields.password') }}</span>
                        <input type="password" name="password" placeholder="{{ theme_text('account.placeholders.password') }}">
                    </label>
                    <button type="submit" class="cmbcore-button is-primary">{{ theme_text('account.actions.login') }}</button>
                </form>

                <p class="cmbcore-account-card__foot">
                    {{ theme_text('account.no_account') }}
                    <a href="{{ route('storefront.account.register') }}">{{ theme_text('account.actions.register') }}</a>
                </p>
            </div>
        </div>
    </section>
@endsection
