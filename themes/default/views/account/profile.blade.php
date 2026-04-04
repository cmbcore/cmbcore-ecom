@php
    $avatarUrl = theme_media_url(is_string($customer->avatar) ? $customer->avatar : null, is_string($customer->avatar) ? theme_url($customer->avatar) : '');
@endphp

@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.profile_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.profile_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.profile_kicker') }}</span>
                                <h1>{{ theme_text('account.profile_title') }}</h1>
                                <p>{{ theme_text('account.profile_description') }}</p>
                            </div>
                            @if ($avatarUrl !== '')
                                <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}" style="width: 92px; height: 92px; border-radius: 24px; object-fit: cover;">
                            @endif
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

                        <form method="post" action="{{ route('storefront.account.profile.update') }}" enctype="multipart/form-data" class="sf-form-grid sf-form-grid--2">
                            @csrf
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.name') }}</span>
                                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required>
                            </label>
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.phone') }}</span>
                                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required>
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('account.fields.email') }}</span>
                                <input type="email" name="email" value="{{ old('email', $customer->email) }}" required>
                            </label>
                            <label class="sf-field is-full">
                                <span>{{ theme_text('account.fields.avatar') }}</span>
                                <input type="file" name="avatar" accept="image/*">
                            </label>
                            <div>
                                <button type="submit" class="sf-button sf-button--primary">{{ theme_text('account.actions.save_profile') }}</button>
                            </div>
                        </form>
                    </div>

                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-section-heading">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.password_kicker') }}</span>
                                <h2>{{ theme_text('account.password_title') }}</h2>
                            </div>
                        </div>

                        <form method="post" action="{{ route('storefront.account.password.change') }}" class="sf-form-grid sf-form-grid--2">
                            @csrf
                            <label class="sf-field">
                                <span>{{ theme_text('account.fields.current_password') }}</span>
                                <input type="password" name="current_password" required>
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
                                <button type="submit" class="sf-button sf-button--ghost">{{ theme_text('account.actions.change_password') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
