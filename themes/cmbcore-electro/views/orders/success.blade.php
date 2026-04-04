@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.success_title')))

@section('content')
    <div class="electro-section">
        <div class="electro-container electro-text-center" style="padding: 80px 0;">
            <i class="fa fa-check-circle" style="font-size:64px; color:#27ae60; margin-bottom:20px;"></i>
            <h1>{{ theme_text('checkout.success_title') }}</h1>
            <p style="max-width:500px; margin:15px auto 30px; color:var(--electro-grey);">
                {{ theme_text('checkout.success_description', ['order' => $order_number]) }}
            </p>
            <div style="display:flex; justify-content:center; gap:15px; flex-wrap:wrap;">
                <a class="electro-primary-btn" href="{{ route('storefront.products.index') }}">{{ theme_text('checkout.actions.continue_shopping') }}</a>
                @auth
                    <a class="electro-primary-btn" style="background:var(--electro-dark);" href="{{ route('storefront.account.orders') }}">{{ theme_text('account.actions.view_all_orders') }}</a>
                @endauth
            </div>
        </div>
    </div>
@endsection
