@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.reviews_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.reviews_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.reviews_kicker') }}</span>
                                <h1>{{ theme_text('account.reviews_title') }}</h1>
                                <p>{{ theme_text('account.reviews_description') }}</p>
                            </div>

                            <form method="get" action="{{ route('storefront.account.reviews') }}">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="">{{ theme_text('account.filters.all_statuses') }}</option>
                                    @foreach (['pending', 'approved', 'rejected'] as $status)
                                        <option value="{{ $status }}" @selected(($selected_status ?? '') === $status)>{{ theme_text('account.review_statuses.' . $status) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    @if ($reviews->count() === 0)
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('account.reviews_empty_title') }}</h2>
                            <p>{{ theme_text('account.reviews_empty_description') }}</p>
                        </div>
                    @else
                        <div class="sf-review-list">
                            @foreach ($reviews as $review)
                                <article class="sf-review-card">
                                    <div class="sf-product__review-head">
                                        <div>
                                            <strong>{{ $review->title }}</strong>
                                            <div>{{ $review->product?->name }}</div>
                                        </div>
                                        <span class="sf-status sf-status--muted">{{ theme_text('account.review_statuses.' . $review->status) }}</span>
                                    </div>
                                    <p>{{ $review->content }}</p>
                                    @if ($review->admin_reply)
                                        <div class="sf-alert is-success">{{ $review->admin_reply }}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <div class="sf-pagination">
                            {{ $reviews->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
