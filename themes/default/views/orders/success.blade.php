@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.success_title')))

@section('content')
    <section class="sf-success">
        <div class="sf-container">
            <div class="sf-auth-card" style="max-width: 680px; margin: 0 auto; text-align: center;">
                <span class="sf-kicker">{{ theme_text('checkout.success_kicker') }}</span>
                <h1>{{ theme_text('checkout.success_title') }}</h1>
                <p>{{ theme_text('checkout.success_description', ['order' => $order_number]) }}</p>
                <div class="sf-header__actions" style="justify-content: center;">
                    <a class="sf-button sf-button--primary" href="{{ route('storefront.products.index') }}">{{ theme_text('checkout.actions.continue_shopping') }}</a>
                    @auth
                        <a class="sf-button sf-button--ghost" href="{{ route('storefront.account.orders') }}">{{ theme_text('account.actions.view_all_orders') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>
@endsection
