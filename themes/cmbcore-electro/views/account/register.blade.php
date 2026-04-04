@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.register_title')))

@section('content')
    <div class="electro-section">
        <div class="electro-container" style="max-width:500px; margin: 0 auto;">
            <h1 class="electro-text-center">{{ theme_text('account.register_title') }}</h1>
            <p class="electro-text-center" style="color:var(--electro-grey); margin-bottom:30px;">{{ theme_text('account.register_description') }}</p>

            @if ($errors->any())
                <div class="electro-alert electro-alert--error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('storefront.account.register.submit') }}">
                @csrf
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.name') }}</label>
                    <input class="electro-input" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.email') }}</label>
                    <input class="electro-input" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.phone') }}</label>
                    <input class="electro-input" type="text" name="phone" value="{{ old('phone') }}" required>
                </div>
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.password') }}</label>
                    <input class="electro-input" type="password" name="password" required>
                </div>
                <div class="electro-form-group">
                    <label>{{ theme_text('account.fields.password_confirmation') }}</label>
                    <input class="electro-input" type="password" name="password_confirmation" required>
                </div>
                <button type="submit" class="electro-primary-btn" style="width:100%;">{{ theme_text('account.actions.register') }}</button>
            </form>

            <p class="electro-text-center" style="margin-top:20px;">
                {{ theme_text('account.have_account') }}
                <a href="{{ route('storefront.account.login') }}" style="color:var(--electro-primary);">{{ theme_text('account.actions.login') }}</a>
            </p>
        </div>
    </div>
@endsection
