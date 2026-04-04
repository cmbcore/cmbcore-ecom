<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Http\Requests\Concerns\InteractsWithProductPayload;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class StoreProductRequest extends FormRequest
{
    use InteractsWithProductPayload;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProductPayload();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('admin.products.validation.name_required'),
            'skus.required' => __('admin.products.validation.skus_required'),
            'skus.array' => __('admin.products.validation.skus_required'),
            'skus.min' => __('admin.products.validation.skus_required'),
            'uploads.*.max' => __('admin.products.validation.file_too_large'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'status' => ['required', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_ARCHIVED])],
            'type' => ['required', Rule::in([Product::TYPE_SIMPLE, Product::TYPE_VARIABLE])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'rating_value' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'sold_count' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'skus' => ['required', 'array', 'min:1'],
            'skus.*.id' => ['nullable', 'integer', 'exists:product_skus,id'],
            'skus.*.client_key' => ['nullable', 'string', 'max:120'],
            'skus.*.name' => ['nullable', 'string', 'max:255'],
            'skus.*.sku_code' => ['nullable', 'string', 'max:100'],
            'skus.*.price' => ['required', 'numeric', 'min:0'],
            'skus.*.compare_price' => ['nullable', 'numeric', 'min:0'],
            'skus.*.cost' => ['nullable', 'numeric', 'min:0'],
            'skus.*.weight' => ['nullable', 'numeric', 'min:0'],
            'skus.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'skus.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'skus.*.barcode' => ['nullable', 'string', 'max:100'],
            'skus.*.status' => ['required', Rule::in([ProductSku::STATUS_ACTIVE, ProductSku::STATUS_INACTIVE])],
            'skus.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'skus.*.attributes' => ['nullable', 'array'],
            'skus.*.attributes.*.attribute_name' => ['required_with:skus.*.attributes.*.attribute_value', 'string', 'max:100'],
            'skus.*.attributes.*.attribute_value' => ['required_with:skus.*.attributes.*.attribute_name', 'string', 'max:255'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['nullable', 'integer', 'exists:product_media,id'],
            'media.*.upload_index' => ['nullable', 'integer', 'min:0'],
            'media.*.alt_text' => ['nullable', 'string', 'max:255'],
            'media.*.position' => ['nullable', 'integer', 'min:0'],
            'media.*.sku_key' => ['nullable', 'string', 'max:120'],
            'media.*.product_sku_id' => ['nullable', 'integer', 'exists:product_skus,id'],
            'media.*.resize_settings' => ['nullable', 'array'],
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['file', 'max:' . (string) config('product.media.max_file_size', 50 * 1024)],
        ];
    }
}
