@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.register_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            <div class="sf-account__panel sf-auth-card" style="max-width: 640px; margin: 0 auto;">
                <div>
                    <span class="sf-kicker">{{ theme_text('account.register_kicker') }}</span>
                    <h1>{{ theme_text('account.register_title') }}</h1>
                    <p>{{ theme_text('account.register_description') }}</p>
                </div>

                @if ($errors->any())
                    <div class="sf-alert is-error">
                        @foreach ($errors->all() as $error)
                            <span>{{ $error }}</span>
                        @endforeach
                    </div>
                @endif

                <form method="post" action="{{ route('storefront.account.register.submit') }}" class="sf-form-grid sf-form-grid--2">
                    @csrf
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.name') }}</span>
                        <input type="text" name="name" value="{{ old('name') }}" required>
                    </label>
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.phone') }}</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" required>
                    </label>
                    <label class="sf-field is-full">
                        <span>{{ theme_text('account.fields.email') }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.password') }}</span>
                        <input type="password" name="password" required>
                    </label>
                    <label class="sf-field">
                        <span>{{ theme_text('account.fields.password_confirmation') }}</span>
                        <input type="password" name="password_confirmation" required>
                    </label>
                    <div>
                        <button type="submit" class="sf-button sf-button--primary">{{ theme_text('account.actions.register') }}</button>
                    </div>
                </form>

                <p>{{ theme_text('account.have_account') }} <a href="{{ route('storefront.account.login') }}">{{ theme_text('account.actions.login') }}</a></p>
            </div>
        </div>
    </section>
@endsection
