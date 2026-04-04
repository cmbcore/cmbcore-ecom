<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Order\Models\Order;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_status' => ['nullable', 'in:' . implode(',', [
                Order::ORDER_STATUS_PENDING,
                Order::ORDER_STATUS_CONFIRMED,
                Order::ORDER_STATUS_CANCELLED,
            ])],
            'fulfillment_status' => ['nullable', 'in:' . implode(',', [
                Order::FULFILLMENT_STATUS_PENDING,
                Order::FULFILLMENT_STATUS_PROCESSING,
                Order::FULFILLMENT_STATUS_SHIPPING,
                Order::FULFILLMENT_STATUS_DELIVERED,
            ])],
            'payment_status' => ['nullable', 'in:' . implode(',', [
                Order::PAYMENT_STATUS_UNPAID,
                Order::PAYMENT_STATUS_COD_PENDING,
                Order::PAYMENT_STATUS_PAID,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
