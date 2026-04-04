<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Core\Plugin\HookManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Support\SearchEscape;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class ProductService
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
        private readonly HookManager $hookManager,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('product.per_page', 15));

        return Product::query()
            ->with('category')
            ->withCount(['skus as sku_count', 'media as media_count'])
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withMin('skus as min_compare_price', 'compare_price')
            ->withMax('skus as max_compare_price', 'compare_price')
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters): void {
                    $search = SearchEscape::like((string) $filters['search']);
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('name', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('brand', 'like', $search);
                    });
                },
            )
            ->when(
                filled($filters['status'] ?? null),
                static fn (Builder $query) => $query->where('status', (string) $filters['status']),
            )
            ->when(
                filled($filters['type'] ?? null),
                static fn (Builder $query) => $query->where('type', (string) $filters['type']),
            )
            ->when(
                filled($filters['category_id'] ?? null),
                static fn (Builder $query) => $query->where('category_id', (int) $filters['category_id']),
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $uploads
     */
    public function create(array $data, array $uploads = []): Product
    {
        $data = $this->hookManager->applyFilter('product.creating', $data);

        return DB::transaction(function () use ($data, $uploads): Product {
            $product = Product::query()->create($this->productPayload($data));
            $skuIdMap = $this->syncSkus($product, (array) ($data['skus'] ?? []));
            $this->mediaUploadService->sync($product, (array) ($data['media'] ?? []), $uploads, $skuIdMap);
            $this->syncCategoryCounts([$product->category_id]);

            $product = $product->fresh(['category', 'skus.attributes', 'media']);
            $this->hookManager->fire('product.created', $product);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $uploads
     */
    public function update(Product $product, array $data, array $uploads = []): Product
    {
        $data = $this->hookManager->applyFilter('product.updating', $data, $product);

        return DB::transaction(function () use ($product, $data, $uploads): Product {
            $originalCategoryId = $product->category_id;
            $wasActive = $product->status === Product::STATUS_ACTIVE;

            $product->fill($this->productPayload($data, $product));
            $product->save();

            $skuIdMap = $this->syncSkus($product, (array) ($data['skus'] ?? []));
            $this->mediaUploadService->sync($product, (array) ($data['media'] ?? []), $uploads, $skuIdMap);

            $this->syncCategoryCounts([
                $originalCategoryId,
                $product->category_id,
                $wasActive || $product->status === Product::STATUS_ACTIVE ? $product->category_id : null,
            ]);

            $product = $product->fresh(['category', 'skus.attributes', 'media']);
            $this->hookManager->fire('product.updated', $product);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $categoryId = $product->category_id;
            $wasActive = $product->status === Product::STATUS_ACTIVE;

            $this->hookManager->fire('product.deleting', $product);
            $product->delete();

            if ($wasActive) {
                $this->syncCategoryCounts([$categoryId]);
            }

            $this->hookManager->fire('product.deleted', $product->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productPayload(array $data, ?Product $product = null): array
    {
        $name = trim((string) $data['name']);
        $baseSlug = trim((string) ($data['slug'] ?? '')) ?: $name;

        return [
            'name' => $name,
            'slug' => $this->generateUniqueSlug($baseSlug, $product?->id),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'status' => (string) ($data['status'] ?? Product::STATUS_DRAFT),
            'type' => (string) ($data['type'] ?? Product::TYPE_SIMPLE),
            'category_id' => $data['category_id'] ?? null,
            'brand' => $data['brand'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'rating_value' => array_key_exists('rating_value', $data) && $data['rating_value'] !== null && $data['rating_value'] !== ''
                ? (float) $data['rating_value']
                : null,
            'review_count' => (int) ($data['review_count'] ?? 0),
            'sold_count' => (int) ($data['sold_count'] ?? 0),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $skuPayloads
     * @return array<string, int>
     */
    private function syncSkus(Product $product, array $skuPayloads): array
    {
        $skuPayloads = array_values(array_filter($skuPayloads, static fn (mixed $payload): bool => is_array($payload)));

        if ($skuPayloads === []) {
            throw ValidationException::withMessages([
                'skus' => [__('admin.products.validation.skus_required')],
            ]);
        }

        if ($product->type === Product::TYPE_SIMPLE && count($skuPayloads) > 1) {
            throw ValidationException::withMessages([
                'type' => [__('admin.products.validation.simple_single_sku')],
            ]);
        }

        $existingSkus = $product->skus()->with('attributes')->get()->keyBy('id');
        $keepIds = [];
        $skuIdMap = [];

        foreach ($skuPayloads as $index => $payload) {
            $skuId = isset($payload['id']) ? (int) $payload['id'] : null;
            $sku = $skuId ? $existingSkus->get($skuId) : null;

            if ($skuId !== null && ! $sku instanceof ProductSku) {
                throw ValidationException::withMessages([
                    'skus' => [__('admin.products.validation.sku_not_found')],
                ]);
            }

            $sku ??= new ProductSku(['product_id' => $product->id]);
            $sku->fill([
                'product_id' => $product->id,
                'sku_code' => $this->resolveSkuCode($product, $payload, $index, $sku->id),
                'name' => $this->resolveSkuName($product, $payload, $index),
                'price' => (float) $payload['price'],
                'compare_price' => $payload['compare_price'] ?? null,
                'cost' => $payload['cost'] ?? null,
                'weight' => $payload['weight'] ?? null,
                'stock_quantity' => (int) $payload['stock_quantity'],
                'low_stock_threshold' => (int) ($payload['low_stock_threshold'] ?? 5),
                'barcode' => $payload['barcode'] ?? null,
                'status' => (string) ($payload['status'] ?? ProductSku::STATUS_ACTIVE),
                'sort_order' => (int) ($payload['sort_order'] ?? $index),
            ]);
            $sku->save();

            $keepIds[] = $sku->id;

            $clientKey = trim((string) ($payload['client_key'] ?? ''));
            $skuIdMap[$clientKey !== '' ? $clientKey : 'sku-' . $index] = $sku->id;

            $this->syncSkuAttributes($sku, (array) ($payload['attributes'] ?? []));
        }

        $product->skus()->whereNotIn('id', $keepIds)->delete();

        return $skuIdMap;
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     */
    private function syncSkuAttributes(ProductSku $sku, array $attributes): void
    {
        $attributes = array_values(array_filter(array_map(
            static function (mixed $attribute): ?array {
                if (! is_array($attribute)) {
                    return null;
                }

                $name = trim((string) ($attribute['attribute_name'] ?? ''));
                $value = trim((string) ($attribute['attribute_value'] ?? ''));

                if ($name === '' || $value === '') {
                    return null;
                }

                return [
                    'attribute_name' => $name,
                    'attribute_value' => $value,
                ];
            },
            $attributes,
        )));

        $sku->attributes()->delete();

        if ($attributes !== []) {
            $sku->attributes()->createMany($attributes);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSkuCode(Product $product, array $payload, int $index, ?int $ignoreId = null): string
    {
        $base = trim((string) ($payload['sku_code'] ?? ''));

        if ($base === '') {
            $base = strtoupper(Str::slug($product->slug ?: $product->name, '-')) . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
        }

        $code = Str::upper($base);
        $counter = 2;

        while (
            ProductSku::query()
                ->when($ignoreId, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->where('sku_code', $code)
                ->exists()
        ) {
            $code = Str::upper($base) . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSkuName(Product $product, array $payload, int $index): string
    {
        $name = trim((string) ($payload['name'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        $attributes = collect((array) ($payload['attributes'] ?? []))
            ->map(static fn (mixed $attribute): string => trim((string) data_get($attribute, 'attribute_value', '')))
            ->filter()
            ->values()
            ->all();

        if ($attributes !== []) {
            return implode(' - ', $attributes);
        }

        return $product->type === Product::TYPE_SIMPLE
            ? $product->name
            : $product->name . ' #' . ($index + 1);
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'product';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->when($ignoreId, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<int, int|null>  $categoryIds
     */
    private function syncCategoryCounts(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(
            $categoryIds,
            static fn (mixed $categoryId): bool => is_int($categoryId) && $categoryId > 0,
        )));

        if ($categoryIds === []) {
            return;
        }

        foreach ($categoryIds as $categoryId) {
            $count = Product::query()
                ->where('category_id', $categoryId)
                ->where('status', Product::STATUS_ACTIVE)
                ->count();

            Category::query()->whereKey($categoryId)->update(['product_count' => $count]);
        }
    }
}
