@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.login_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            <div class="sf-account__panel sf-auth-card" style="max-width: 560px; margin: 0 auto;">
                <div>
                    <span class="sf-kicker">{{ theme_text('account.login_kicker') }}</span>
                    <h1>{{ theme_text('account.login_title') }}</h1>
                    <p>{{ theme_text('account.login_description') }}</p>
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

                <form method="post" action="{{ route('storefront.account.login.submit') }}" class="sf-form-grid">
                    @csrf
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.login') }}</span>
                        <input type="text" name="login" value="{{ old('login') }}" placeholder="{{ theme_text('account.placeholders.login') }}" required>
                    </label>
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.password') }}</span>
                        <input type="password" name="password" placeholder="{{ theme_text('account.placeholders.password') }}" required>
                    </label>
                    <label class="sf-field" style="grid-auto-flow: column; justify-content: start; align-items: center;">
                        <input type="checkbox" name="remember" value="1" checked style="width: auto;">
                        <span>{{ theme_text('account.fields.remember') }}</span>
                    </label>
                    <button type="submit" class="sf-button sf-button--primary">{{ theme_text('account.actions.login') }}</button>
                </form>

                <p>{{ theme_text('account.no_account') }} <a href="{{ route('storefront.account.register') }}">{{ theme_text('account.actions.register') }}</a></p>
            </div>
        </div>
    </section>
@endsection
