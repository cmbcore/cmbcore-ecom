@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.returns_title')))

@section('content')
    <section class="sf-account cmbcore-section cmbcore-section--compact">
        <div class="sf-container">
            @include(theme_view('partials.breadcrumbs'), [
                'items' => [
                    ['label' => theme_text('navigation.home'), 'url' => theme_home_url()],
                    ['label' => theme_text('account.dashboard_title'), 'url' => route('storefront.account.dashboard')],
                    ['label' => theme_text('account.returns_title')],
                ],
            ])

            <div class="sf-account">
                @include(theme_view('partials.account-sidebar'))

                <div class="sf-account__list">
                    <div class="sf-account__panel cmbcore-account-card">
                        <div class="sf-account__hero">
                            <div>
                                <span class="sf-kicker">{{ theme_text('account.returns_kicker') }}</span>
                                <h1>{{ theme_text('account.returns_title') }}</h1>
                                <p>{{ theme_text('account.returns_description') }}</p>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="sf-alert is-success">{{ session('status') }}</div>
                        @endif

                        <div class="sf-table-card">
                            <table class="sf-table">
                                <thead>
                                <tr>
                                    <th>{{ theme_text('returns.fields.order') }}</th>
                                    <th>{{ theme_text('returns.fields.product') }}</th>
                                    <th>{{ theme_text('returns.fields.quantity') }}</th>
                                    <th>{{ theme_text('returns.fields.status') }}</th>
                                    <th>{{ theme_text('returns.fields.created_at') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($return_requests as $request)
                                    <tr>
                                        <td>{{ $request['order']['order_number'] ?? '-' }}</td>
                                        <td>{{ $request['item']['product_name'] ?? theme_text('returns.full_order') }}</td>
                                        <td>{{ $request['requested_quantity'] }}</td>
                                        <td><span class="sf-status sf-status--muted">{{ $request['status'] }}</span></td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($request['created_at'])->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">{{ theme_text('returns.empty') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @forelse ($eligible_orders as $returnOrder)
                        <div class="sf-account__panel cmbcore-account-card">
                            <div class="sf-account__hero">
                                <div>
                                    <h2>{{ $returnOrder->order_number }}</h2>
                                    <p>{{ theme_text('returns.form_description') }}</p>
                                </div>
                            </div>

                            <form method="post" action="{{ route('storefront.account.returns.store', ['orderNumber' => $returnOrder->order_number]) }}" class="sf-form-grid sf-form-grid--2">
                                @csrf
                                <label class="sf-field">
                                    <span>{{ theme_text('returns.fields.product') }}</span>
                                    <select name="order_item_id">
                                        <option value="">{{ theme_text('returns.full_order') }}</option>
                                        @foreach ($returnOrder->items as $item)
                                            <option value="{{ $item->id }}">{{ $item->product_name }}{{ $item->sku_name ? ' - ' . $item->sku_name : '' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="sf-field">
                                    <span>{{ theme_text('returns.fields.quantity') }}</span>
                                    <input type="number" min="1" name="requested_quantity" value="1" required>
                                </label>
                                <label class="sf-field is-full">
                                    <span>{{ theme_text('returns.fields.reason') }}</span>
                                    <textarea name="reason" required></textarea>
                                </label>
                                <div>
                                    <button type="submit" class="sf-button sf-button--primary">{{ theme_text('returns.submit') }}</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="sf-empty-state">
                            <h2>{{ theme_text('returns.unavailable_title') }}</h2>
                            <p>{{ theme_text('returns.unavailable_description') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
