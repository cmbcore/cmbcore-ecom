<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $requiresAddressFields = $user === null || ! $this->filled('address_id');

        return [
            'mode' => ['nullable', 'in:cart,buy_now'],
            'product_sku_id' => ['required_if:mode,buy_now', 'nullable', 'integer', 'exists:product_skus,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'address_id' => ['nullable', 'integer'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'recipient_name' => [$requiresAddressFields ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping_phone' => [$requiresAddressFields ? 'required' : 'nullable', 'string', 'max:30'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'address_line' => [$requiresAddressFields ? 'required' : 'nullable', 'string', 'max:500'],
            'address_note' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'save_address' => ['nullable', 'boolean'],
            'save_as_default' => ['nullable', 'boolean'],
            'address_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
