@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', 'Đổi trả'))

@section('content')
    <section class="cmbcore-section cmbcore-section--compact">
        <div class="cmbcore-container">
            <div class="cmbcore-account-dashboard">
                <div class="cmbcore-account-dashboard__header">
                    <div>
                        <p class="cmbcore-account-dashboard__eyebrow">Hỗ trợ sau mua hàng</p>
                        <h1>Yêu cầu đổi trả</h1>
                        <p>Gửi yêu cầu đổi trả cho các đơn đã giao và theo dõi tiến độ xử lý.</p>
                    </div>
                    <div class="cmbcore-account-dashboard__actions">
                        <a class="cmbcore-button is-secondary" href="{{ route('storefront.account.dashboard') }}">Quay lại tài khoản</a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="cmbcore-alert is-success">{{ session('status') }}</div>
                @endif

                <div class="cmbcore-table-card" style="margin-bottom: 24px;">
                    <div class="cmbcore-section-title cmbcore-section-title--detail">
                        <h2>Yêu cầu đã gửi</h2>
                    </div>
                    <table class="cmbcore-table">
                        <thead>
                        <tr>
                            <th>Đơn hàng</th>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($return_requests as $request)
                            <tr>
                                <td>{{ $request['order']['order_number'] ?? '-' }}</td>
                                <td>
                                    {{ $request['item']['product_name'] ?? 'Toàn đơn' }}
                                    @if (!empty($request['item']['sku_name']))
                                        <div>{{ $request['item']['sku_name'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $request['requested_quantity'] }}</td>
                                <td>{{ $request['status'] }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($request['created_at'])->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Bạn chưa gửi yêu cầu đổi trả nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="cmbcore-account-card cmbcore-account-card--wide">
                    <h2>Tạo yêu cầu mới</h2>
                    <div class="cmbcore-form-grid">
                        @forelse ($eligible_orders as $order)
                            <div class="cmbcore-table-card" style="margin: 0;">
                                <div class="cmbcore-section-title cmbcore-section-title--detail">
                                    <h2>{{ $order->order_number }}</h2>
                                </div>
                                <p>Đã giao lúc {{ $order->updated_at?->format('d/m/Y H:i') }}</p>
                                <form method="post" action="{{ route('storefront.account.returns.store', ['orderNumber' => $order->order_number]) }}" class="cmbcore-form-grid">
                                    @csrf
                                    <label>
                                        <span>Sản phẩm</span>
                                        <select name="order_item_id">
                                            <option value="">Toàn đơn hàng</option>
                                            @foreach ($order->items as $item)
                                                <option value="{{ $item->id }}">{{ $item->product_name }}{{ $item->sku_name ? ' - ' . $item->sku_name : '' }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span>Số lượng</span>
                                        <input type="number" min="1" name="requested_quantity" value="1" required>
                                    </label>
                                    <label class="is-full">
                                        <span>Lý do</span>
                                        <textarea name="reason" rows="3" required></textarea>
                                    </label>
                                    <button type="submit" class="cmbcore-button is-primary">Gửi yêu cầu</button>
                                </form>
                            </div>
                        @empty
                            <p>Không có đơn nào đã giao để gửi yêu cầu đổi trả.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
