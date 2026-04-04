@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.login_title')))

@section('content')
    <div class="electro-section">
        <div class="electro-container" style="max-width:500px; margin: 0 auto;">
            <h1 class="electro-text-center">{{ theme_text('account.login_title') }}</h1>
            <p class="electro-text-center" style="color:var(--electro-grey); margin-bottom:30px;">{{ theme_text('account.login_description') }}</p>

            @if (session('status'))
                <div class="electro-alert electro-alert--success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="electro-alert electro-alert--error">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('storefront.account.login.submit') }}">
                @csrf
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.login') }}</label>
                    <input class="electro-input" type="text" name="login" value="{{ old('login') }}" placeholder="{{ theme_text('account.placeholders.login') }}" required>
                </div>
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.password') }}</label>
                    <input class="electro-input" type="password" name="password" placeholder="{{ theme_text('account.placeholders.password') }}" required>
                </div>
                <button type="submit" class="electro-primary-btn" style="width:100%;">{{ theme_text('account.actions.login') }}</button>
            </form>

            <p class="electro-text-center" style="margin-top:20px;">
                {{ theme_text('account.no_account') }}
                <a href="{{ route('storefront.account.register') }}" style="color:var(--electro-primary);">{{ theme_text('account.actions.register') }}</a>
            </p>
        </div>
    </div>
@endsection
