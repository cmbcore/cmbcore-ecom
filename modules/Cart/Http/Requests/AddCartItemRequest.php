<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
