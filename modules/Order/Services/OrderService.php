<?php

declare(strict_types=1);

namespace Modules\Order\Services;

use App\Core\Plugin\HookManager;
use App\Models\User;
use App\Support\SearchEscape;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\Models\ShoppingCart;
use Modules\Cart\Services\CartService;
use Modules\Coupon\Services\CouponService;
use Modules\Customer\Services\CustomerAddressService;
use Modules\FlashSale\Services\FlashSaleService;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Payment\Services\PaymentService;
use Modules\Shipping\Services\ShippingService;
use Modules\Tax\Services\TaxService;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CustomerAddressService $customerAddressService,
        private readonly HookManager $hookManager,
        private readonly ShippingService $shippingService,
        private readonly CouponService $couponService,
        private readonly TaxService $taxService,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly FlashSaleService $flashSaleService,
    ) {
    }

    public function previewCheckout(array $payload, ?User $user = null): array
    {
        return $this->buildCheckoutPayload($payload, $user);
    }

    public function placeOrder(array $payload, ?User $user = null): Order
    {
        return DB::transaction(function () use ($payload, $user): Order {
            $checkoutPayload = $this->buildCheckoutPayload($payload, $user);
            $shipping = $checkoutPayload['shipping'];
            $selectedShippingMethod = $checkoutPayload['selected_shipping_method'];
            $selectedPaymentMethod = $checkoutPayload['selected_payment_method'];

            /** @var Order $order */
            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'guest_email' => $user?->email ?? ($payload['guest_email'] ?? null),
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'shipping_recipient_name' => $shipping['recipient_name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_method_code' => $selectedShippingMethod['code'] ?? null,
                'shipping_method_name' => $selectedShippingMethod['name'] ?? null,
                'shipping_full_address' => $shipping['full_address'],
                'shipping_meta' => $shipping,
                'note' => $payload['note'] ?? null,
                'payment_method' => $selectedPaymentMethod['code'],
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'fulfillment_status' => Order::FULFILLMENT_STATUS_PENDING,
                'order_status' => Order::ORDER_STATUS_PENDING,
                'subtotal' => $checkoutPayload['subtotal'],
                'discount_total' => $checkoutPayload['discount_total'],
                'coupon_code' => $checkoutPayload['coupon_code'],
                'coupon_snapshot' => $checkoutPayload['coupon'],
                'shipping_total' => $checkoutPayload['shipping_total'],
                'tax_total' => $checkoutPayload['tax_total'],
                'grand_total' => $checkoutPayload['grand_total'],
                'source' => $user !== null ? Order::SOURCE_ACCOUNT : Order::SOURCE_GUEST,
            ]);

            foreach ($checkoutPayload['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_sku_id' => $item['product_sku_id'],
                    'product_name' => $item['product_name'],
                    'sku_name' => $item['sku_name'],
                    'attributes_snapshot' => $item['attributes'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'product_snapshot' => array_replace(
                        is_array($item['product'] ?? null) ? $item['product'] : [],
                        ['flash_sale' => $item['flash_sale'] ?? null],
                    ),
                ]);
            }

            $this->paymentService->process($order, ['checkout' => $checkoutPayload]);

            $order->histories()->create([
                'from_status' => null,
                'to_status' => Order::ORDER_STATUS_PENDING,
                'note' => 'Order created',
                'changed_by' => $user?->id,
            ]);

            if ($checkoutPayload['mode'] === 'cart') {
                $cart = $this->cartService->getOrCreateActiveCart($user);
                $cart->forceFill(['status' => ShoppingCart::STATUS_CONVERTED])->save();
                $this->hookManager->fire('cart.converted', $cart, $order);
            }

            if ($user !== null && ($payload['save_address'] ?? false) && empty($payload['address_id'])) {
                $this->customerAddressService->create($user, [
                    'label' => $payload['address_label'] ?? __('frontend.account.default_address'),
                    'recipient_name' => $shipping['recipient_name'],
                    'phone' => $shipping['phone'],
                    'province' => $shipping['province'],
                    'district' => $shipping['district'],
                    'ward' => $shipping['ward'],
                    'address_line' => $shipping['address_line'],
                    'address_note' => $shipping['address_note'],
                    'is_default' => (bool) ($payload['save_as_default'] ?? false),
                ]);
            }

            if ($checkoutPayload['coupon_code']) {
                $this->couponService->consume(
                    (string) $checkoutPayload['coupon_code'],
                    $order,
                    (float) $checkoutPayload['discount_total'],
                    $user,
                    $user?->email ?? ($payload['guest_email'] ?? null),
                );
            }

            $this->hookManager->fire('order.created', $order->load('items'));

            return $order->load(['items', 'histories', 'user', 'payments']);
        });
    }

    public function listForCustomer(User $user, array $filters = []): LengthAwarePaginator
    {
        $status = trim((string) ($filters['status'] ?? ''));
        $perPage = max(1, (int) ($filters['per_page'] ?? 10));

        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'payments'])
            ->when($status !== '', function ($query) use ($status): void {
                $query->where(function ($innerQuery) use ($status): void {
                    $innerQuery
                        ->where('order_status', $status)
                        ->orWhere('fulfillment_status', $status)
                        ->orWhere('payment_status', $status);
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForCustomer(User $user, string $orderNumber): Order
    {
        /** @var Order $order */
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'histories', 'payments'])
            ->firstOrFail();

        return $order;
    }

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('order.admin_per_page', 15)));

        return Order::query()
            ->with('user')
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $like = SearchEscape::like($search);
                    $innerQuery
                        ->where('order_number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_phone', 'like', $like);
                });
            })
            ->when(! empty($filters['order_status']), fn ($query) => $query->where('order_status', $filters['order_status']))
            ->when(! empty($filters['fulfillment_status']), fn ($query) => $query->where('fulfillment_status', $filters['fulfillment_status']))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForAdmin(int $id): Order
    {
        /** @var Order $order */
        $order = Order::query()->with(['user', 'items', 'histories.actor', 'payments'])->findOrFail($id);

        return $order;
    }

    public function updateStatuses(Order $order, array $payload, ?User $actor = null): Order
    {
        $allowedOrderStatuses = [
            Order::ORDER_STATUS_PENDING,
            Order::ORDER_STATUS_CONFIRMED,
            Order::ORDER_STATUS_CANCELLED,
        ];
        $allowedFulfillmentStatuses = [
            Order::FULFILLMENT_STATUS_PENDING,
            Order::FULFILLMENT_STATUS_PROCESSING,
            Order::FULFILLMENT_STATUS_SHIPPING,
            Order::FULFILLMENT_STATUS_DELIVERED,
        ];
        $allowedPaymentStatuses = [
            Order::PAYMENT_STATUS_UNPAID,
            Order::PAYMENT_STATUS_COD_PENDING,
            Order::PAYMENT_STATUS_PAID,
        ];

        $nextOrderStatus = in_array($payload['order_status'] ?? null, $allowedOrderStatuses, true)
            ? $payload['order_status']
            : $order->order_status;
        $nextFulfillmentStatus = in_array($payload['fulfillment_status'] ?? null, $allowedFulfillmentStatuses, true)
            ? $payload['fulfillment_status']
            : $order->fulfillment_status;
        $nextPaymentStatus = in_array($payload['payment_status'] ?? null, $allowedPaymentStatuses, true)
            ? $payload['payment_status']
            : $order->payment_status;

        $previousOrderStatus = $order->order_status;
        $previousFulfillmentStatus = $order->fulfillment_status;

        $order->forceFill([
            'order_status' => $nextOrderStatus,
            'fulfillment_status' => $nextFulfillmentStatus,
            'payment_status' => $nextPaymentStatus,
        ])->save();

        if ($previousOrderStatus !== $order->order_status) {
            $order->histories()->create([
                'from_status' => $previousOrderStatus,
                'to_status' => $order->order_status,
                'note' => $payload['note'] ?? null,
                'changed_by' => $actor?->id,
            ]);
        }

        if ($previousOrderStatus !== $order->order_status && $order->order_status === Order::ORDER_STATUS_CONFIRMED) {
            $this->hookManager->fire('order.confirmed', $order);
        }

        if ($previousOrderStatus !== $order->order_status && $order->order_status === Order::ORDER_STATUS_CANCELLED) {
            $this->hookManager->fire('order.cancelled', $order);
        }

        if ($previousFulfillmentStatus !== $order->fulfillment_status && $order->fulfillment_status === Order::FULFILLMENT_STATUS_DELIVERED) {
            $this->hookManager->fire('order.delivered', $order);
        }

        return $order->refresh()->load(['items', 'histories.actor', 'user', 'payments']);
    }

    private function buildCheckoutPayload(array $payload, ?User $user = null): array
    {
        $mode = ($payload['mode'] ?? 'cart') === 'buy_now' ? 'buy_now' : 'cart';
        $cartPayload = $mode === 'buy_now'
            ? $this->cartService->previewBuyNow((int) $payload['product_sku_id'], (int) ($payload['quantity'] ?? 1))
            : $this->cartService->activePayload($user);

        abort_if(empty($cartPayload['items']), 422, __('frontend.checkout.messages.empty_cart'));

        $this->inventoryService->assertAvailable($cartPayload['items']);
        $this->flashSaleService->assertAvailability($cartPayload['items']);

        $shipping = $this->resolveShipping($payload, $user);
        $shippingQuote = $this->shippingService->quote(
            $cartPayload,
            $shipping,
            isset($payload['shipping_method_id']) ? (int) $payload['shipping_method_id'] : null,
        );
        $couponQuote = $this->couponService->quote(
            $payload['coupon_code'] ?? null,
            (float) $cartPayload['subtotal'],
            $user,
            $user?->email ?? ($payload['guest_email'] ?? null),
        );
        $taxableAmount = max(0, (float) $cartPayload['subtotal'] - (float) $couponQuote['discount_total']) + (float) $shippingQuote['shipping_total'];
        $taxQuote = $this->taxService->quote($shipping, $taxableAmount);
        $paymentMethods = $this->paymentService->availableMethods();
        abort_if($paymentMethods === [], 422, 'Chua co gateway thanh toán nao duoc kich hoat.');

        $selectedPaymentCode = trim((string) ($payload['payment_method'] ?? $paymentMethods[0]['code'] ?? ''));
        $selectedPaymentMethod = collect($paymentMethods)->firstWhere('code', $selectedPaymentCode) ?? $paymentMethods[0];
        $grandTotal = max(0, (float) $cartPayload['subtotal'] - (float) $couponQuote['discount_total']) + (float) $shippingQuote['shipping_total'] + (float) $taxQuote['tax_total'];

        return array_merge($cartPayload, [
            'mode' => $mode,
            'shipping' => $shipping,
            'shipping_methods' => $shippingQuote['methods'],
            'selected_shipping_method' => $shippingQuote['selected_method'],
            'shipping_total' => (float) $shippingQuote['shipping_total'],
            'coupon_code' => $couponQuote['code'],
            'coupon' => $couponQuote['coupon'],
            'discount_total' => (float) $couponQuote['discount_total'],
            'tax_total' => (float) $taxQuote['tax_total'],
            'tax_rate' => $taxQuote['rate'],
            'payment_methods' => $paymentMethods,
            'selected_payment_method' => $selectedPaymentMethod,
            'grand_total' => round($grandTotal, 2),
        ]);
    }

    private function resolveShipping(array $payload, ?User $user = null): array
    {
        if ($user !== null && ! empty($payload['address_id'])) {
            $address = $this->customerAddressService->findForUser($user, (int) $payload['address_id']);

            return [
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'province' => $address->province,
                'district' => $address->district,
                'ward' => $address->ward,
                'address_line' => $address->address_line,
                'address_note' => $address->address_note,
                'full_address' => $address->formattedAddress(),
            ];
        }

        $fullAddress = collect([
            $payload['address_line'] ?? null,
            $payload['ward'] ?? null,
            $payload['district'] ?? null,
            $payload['province'] ?? null,
        ])->filter()->implode(', ');

        return [
            'recipient_name' => $payload['recipient_name'],
            'phone' => $payload['shipping_phone'],
            'province' => $payload['province'] ?? null,
            'district' => $payload['district'] ?? null,
            'ward' => $payload['ward'] ?? null,
            'address_line' => $payload['address_line'],
            'address_note' => $payload['address_note'] ?? null,
            'full_address' => $fullAddress,
        ];
    }

    private function generateOrderNumber(): string
    {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $number = 'ODR-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        // Fallback: use microsecond timestamp ensuring uniqueness
        return 'ODR-' . now()->format('YmdHisu');
    }
}
