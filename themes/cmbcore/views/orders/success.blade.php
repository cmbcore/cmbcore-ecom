@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('checkout.success_title')))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container cmbcore-account-shell">
            <div class="cmbcore-account-card">
                <h1>{{ theme_text('checkout.success_title') }}</h1>
                <p>{{ theme_text('checkout.success_description', ['order' => $order_number]) }}</p>
                <a href="{{ route('storefront.products.index') }}" class="cmbcore-button is-primary">{{ theme_text('checkout.actions.continue_shopping') }}</a>
            </div>
        </div>
    </section>
@endsection
