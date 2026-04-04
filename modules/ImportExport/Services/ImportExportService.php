<?php

declare(strict_types=1);

namespace Modules\ImportExport\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;

class ImportExportService
{
    private const CHUNK_SIZE = 500;

    public function exportCsv(): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['name', 'slug', 'status', 'category_slug', 'brand', 'sku_code', 'sku_name', 'price', 'compare_price', 'stock_quantity']);

        // Use cursor() instead of get() to avoid loading all records into memory
        Product::query()
            ->with(['category', 'skus'])
            ->cursor()
            ->each(function (Product $product) use ($stream): void {
                foreach ($product->skus as $sku) {
                    fputcsv($stream, [
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'status' => $product->status,
                        'category_slug' => $product->category?->slug,
                        'brand' => $product->brand,
                        'sku_code' => $sku->sku_code,
                        'sku_name' => $sku->name,
                        'price' => (float) $sku->price,
                        'compare_price' => (float) ($sku->compare_price ?? 0),
                        'stock_quantity' => (int) $sku->stock_quantity,
                    ]);
                }
            });

        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    public function importCsv(UploadedFile $file): int
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle) ?: [];
        $count = 0;
        $batch = [];

        // Process in chunks to avoid long-running single transactions
        while (($row = fgetcsv($handle)) !== false) {
            $payload = array_combine($header, $row);

            if (! is_array($payload) || trim((string) ($payload['name'] ?? '')) === '') {
                continue;
            }

            $batch[] = $payload;

            if (count($batch) >= self::CHUNK_SIZE) {
                $count += $this->processBatch($batch);
                $batch = [];
            }
        }

        // Process remaining rows
        if ($batch !== []) {
            $count += $this->processBatch($batch);
        }

        fclose($handle);

        return $count;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function processBatch(array $rows): int
    {
        $count = 0;

        DB::transaction(function () use ($rows, &$count): void {
            foreach ($rows as $payload) {
                $category = $this->resolveCategory((string) ($payload['category_slug'] ?? 'default'));

                /** @var Product $product */
                $product = Product::query()->updateOrCreate(
                    ['slug' => trim((string) ($payload['slug'] ?? '')) ?: Str::slug((string) $payload['name'])],
                    [
                        'name' => trim((string) $payload['name']),
                        'status' => trim((string) ($payload['status'] ?? Product::STATUS_ACTIVE)) ?: Product::STATUS_ACTIVE,
                        'type' => Product::TYPE_SIMPLE,
                        'category_id' => $category->id,
                        'brand' => trim((string) ($payload['brand'] ?? '')) ?: null,
                    ],
                );

                ProductSku::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku_code' => trim((string) ($payload['sku_code'] ?? '')) ?: Str::upper($product->slug) . '-001',
                    ],
                    [
                        'name' => trim((string) ($payload['sku_name'] ?? 'Default SKU')) ?: 'Default SKU',
                        'price' => (float) ($payload['price'] ?? 0),
                        'compare_price' => is_numeric($payload['compare_price'] ?? null) ? (float) $payload['compare_price'] : null,
                        'stock_quantity' => (int) ($payload['stock_quantity'] ?? 0),
                        'status' => ProductSku::STATUS_ACTIVE,
                    ],
                );

                $count++;
            }
        });

        return $count;
    }

    private function resolveCategory(string $slug): Category
    {
        /** @var Category $category */
        $category = Category::query()->firstOrCreate(
            ['slug' => trim($slug) ?: 'default'],
            [
                'name' => Str::headline(trim($slug) ?: 'default'),
                'level' => 1,
                'status' => Category::STATUS_ACTIVE,
            ],
        );

        return $category;
    }
}
