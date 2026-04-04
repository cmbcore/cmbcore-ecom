<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'address_count' => $user->addresses_count ?? $user->addresses?->count() ?? 0,
            'order_count' => $user->orders_count ?? $user->orders?->count() ?? 0,
            'addresses' => $user->relationLoaded('addresses')
                ? CustomerAddressResource::collection($user->addresses)->resolve()
                : [],
            'orders' => $user->relationLoaded('orders')
                ? $user->orders->map(fn ($order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'grand_total' => (float) $order->grand_total,
                    'order_status' => $order->order_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'created_at' => $order->created_at?->toISOString(),
                ])->values()->all()
                : [],
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
