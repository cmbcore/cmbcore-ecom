<?php

declare(strict_types=1);

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Order\Models\Order;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'guest_email' => $order->guest_email,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'shipping_recipient_name' => $order->shipping_recipient_name,
            'shipping_phone' => $order->shipping_phone,
            'shipping_method_code' => $order->shipping_method_code,
            'shipping_method_name' => $order->shipping_method_name,
            'shipping_full_address' => $order->shipping_full_address,
            'shipping_meta' => $order->shipping_meta ?? [],
            'note' => $order->note,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_meta' => $order->payment_meta ?? [],
            'fulfillment_status' => $order->fulfillment_status,
            'order_status' => $order->order_status,
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'coupon_code' => $order->coupon_code,
            'coupon_snapshot' => $order->coupon_snapshot ?? [],
            'shipping_total' => (float) $order->shipping_total,
            'tax_total' => (float) $order->tax_total,
            'grand_total' => (float) $order->grand_total,
            'source' => $order->source,
            'items_count' => $order->items_count ?? $order->items?->count() ?? 0,
            'user' => $order->relationLoaded('user') && $order->user
                ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone,
                ]
                : null,
            'items' => $order->relationLoaded('items')
                ? OrderItemResource::collection($order->items)->resolve()
                : [],
            'payments' => $order->relationLoaded('payments')
                ? $order->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'gateway' => $payment->gateway,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'reference' => $payment->reference,
                    'meta' => $payment->meta ?? [],
                    'confirmed_at' => $payment->confirmed_at?->toISOString(),
                    'refunded_at' => $payment->refunded_at?->toISOString(),
                    'created_at' => $payment->created_at?->toISOString(),
                ])->values()->all()
                : [],
            'histories' => $order->relationLoaded('histories')
                ? $order->histories->map(fn ($history): array => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'changed_by' => $history->changed_by,
                    'actor_name' => $history->relationLoaded('actor') ? $history->actor?->name : null,
                    'created_at' => $history->created_at?->toISOString(),
                ])->values()->all()
                : [],
            'created_at' => $order->created_at?->toISOString(),
        ];
    }
}
