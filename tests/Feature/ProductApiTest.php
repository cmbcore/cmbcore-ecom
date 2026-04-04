<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Plugin\HookManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_create_variable_product_with_skus_and_media(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'products@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Áo thun',
            'slug' => 'ao-thun',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $image = UploadedFile::fake()->image('ao-thun.jpg', 1200, 1200);
        $video = UploadedFile::fake()->create('gioi-thieu.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->post('/api/admin/products', [
            'name' => 'Áo thun premium',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_VARIABLE,
            'category_id' => $category->id,
            'brand' => 'CMBCORE Studio',
            'is_featured' => '1',
            'meta_title' => 'Áo thun premium',
            'meta_description' => 'Áo thun premium cho storefront',
            'skus' => [
                [
                    'client_key' => 'sku-red-m',
                    'name' => 'Đỏ - M',
                    'price' => 199000,
                    'stock_quantity' => 12,
                    'status' => 'active',
                    'attributes' => [
                        ['attribute_name' => 'Màu sắc', 'attribute_value' => 'Đỏ'],
                        ['attribute_name' => 'Size', 'attribute_value' => 'M'],
                    ],
                ],
                [
                    'client_key' => 'sku-blue-l',
                    'name' => 'Xanh - L',
                    'price' => 229000,
                    'stock_quantity' => 6,
                    'status' => 'active',
                    'attributes' => [
                        ['attribute_name' => 'Màu sắc', 'attribute_value' => 'Xanh'],
                        ['attribute_name' => 'Size', 'attribute_value' => 'L'],
                    ],
                ],
            ],
            'media' => [
                [
                    'upload_index' => 0,
                    'alt_text' => 'Ảnh chính sản phẩm',
                    'sku_key' => 'sku-red-m',
                    'resize_settings' => ['widths' => [200, 400, 800]],
                ],
                [
                    'upload_index' => 1,
                    'alt_text' => 'Video giới thiệu sản phẩm',
                ],
            ],
            'uploads' => [$image, $video],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Áo thun premium')
            ->assertJsonPath('data.type', Product::TYPE_VARIABLE)
            ->assertJsonPath('data.sku_count', 2)
            ->assertJsonPath('data.media_count', 2)
            ->assertJsonPath('data.total_stock', 18)
            ->assertJsonPath('data.min_price', 199000)
            ->assertJsonPath('data.max_price', 229000)
            ->assertJsonPath('data.media.0.type', 'image')
            ->assertJsonPath('data.media.1.type', 'video');

        $productId = (int) $response->json('data.id');
        $product = Product::query()->with(['skus.attributes', 'media'])->findOrFail($productId);

        self::assertCount(2, $product->skus);
        self::assertCount(2, $product->media);
        self::assertSame(1, $category->fresh()->product_count);

        foreach ($product->media as $media) {
            Storage::disk('public')->assertExists($media->path);
        }

        $this->actingAs($user)
            ->getJson("/api/admin/products/{$productId}/skus")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($user)
            ->getJson("/api/admin/products/{$productId}/media")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_user_can_update_and_delete_product_while_syncing_category_counts(): void
    {
        $user = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'products-sync@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $categoryA = Category::query()->create([
            'name' => 'Áo sơ mi',
            'slug' => 'ao-so-mi',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $categoryB = Category::query()->create([
            'name' => 'Quần dài',
            'slug' => 'quan-dai',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $createResponse = $this->actingAs($user)
            ->postJson('/api/admin/products', [
                'name' => 'Sơ mi linen',
                'status' => Product::STATUS_ACTIVE,
                'type' => Product::TYPE_SIMPLE,
                'category_id' => $categoryA->id,
                'skus' => [
                    [
                        'client_key' => 'sku-default',
                        'name' => 'Mặc định',
                        'price' => 259000,
                        'stock_quantity' => 8,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertCreated();

        $productId = (int) $createResponse->json('data.id');
        $skuId = (int) $createResponse->json('data.skus.0.id');

        self::assertSame(1, $categoryA->fresh()->product_count);
        self::assertSame(0, $categoryB->fresh()->product_count);

        $this->actingAs($user)
            ->putJson("/api/admin/products/{$productId}", [
                'name' => 'Sơ mi linen nâng cấp',
                'status' => Product::STATUS_ACTIVE,
                'type' => Product::TYPE_SIMPLE,
                'category_id' => $categoryB->id,
                'skus' => [
                    [
                        'id' => $skuId,
                        'client_key' => 'sku-default',
                        'name' => 'Mặc định',
                        'price' => 279000,
                        'stock_quantity' => 5,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sơ mi linen nâng cấp')
            ->assertJsonPath('data.category_id', $categoryB->id)
            ->assertJsonPath('data.total_stock', 5);

        self::assertSame(0, $categoryA->fresh()->product_count);
        self::assertSame(1, $categoryB->fresh()->product_count);

        $this->actingAs($user)
            ->deleteJson("/api/admin/products/{$productId}")
            ->assertOk();

        self::assertNotNull(Product::query()->withTrashed()->findOrFail($productId)->deleted_at);
        self::assertSame(0, $categoryB->fresh()->product_count);
    }

    public function test_simple_product_rejects_multiple_skus(): void
    {
        $user = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'products-validation@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/admin/products', [
                'name' => 'Áo khoác basic',
                'status' => Product::STATUS_ACTIVE,
                'type' => Product::TYPE_SIMPLE,
                'skus' => [
                    [
                        'client_key' => 'sku-1',
                        'price' => 299000,
                        'stock_quantity' => 3,
                        'status' => 'active',
                    ],
                    [
                        'client_key' => 'sku-2',
                        'price' => 319000,
                        'stock_quantity' => 2,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_product_hooks_can_filter_payload_and_receive_lifecycle_events(): void
    {
        $user = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'products-hooks@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $events = [];
        $hooks = app(HookManager::class);

        $hooks->filter('product.creating', function (array $data) use (&$events): array {
            $events[] = 'creating';
            $data['brand'] = 'Plugin Brand';

            return $data;
        });

        $hooks->register('product.created', function (Product $product) use (&$events): void {
            $events[] = 'created:' . $product->id;
        });

        $hooks->filter('product.updating', function (array $data, Product $product) use (&$events): array {
            $events[] = 'updating:' . $product->id;
            $data['meta_title'] = 'Đã lọc bởi hook';

            return $data;
        });

        $hooks->register('product.updated', function (Product $product) use (&$events): void {
            $events[] = 'updated:' . $product->id;
        });

        $hooks->register('product.deleting', function (Product $product) use (&$events): void {
            $events[] = 'deleting:' . $product->id;
        });

        $hooks->register('product.deleted', function (int $productId) use (&$events): void {
            $events[] = 'deleted:' . $productId;
        });

        $createResponse = $this->actingAs($user)
            ->postJson('/api/admin/products', [
                'name' => 'Áo polo hook',
                'status' => Product::STATUS_ACTIVE,
                'type' => Product::TYPE_SIMPLE,
                'skus' => [
                    [
                        'client_key' => 'sku-hook',
                        'price' => 199000,
                        'stock_quantity' => 3,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.brand', 'Plugin Brand');

        $productId = (int) $createResponse->json('data.id');
        $skuId = (int) $createResponse->json('data.skus.0.id');

        $this->actingAs($user)
            ->putJson("/api/admin/products/{$productId}", [
                'name' => 'Áo polo hook cập nhật',
                'status' => Product::STATUS_ACTIVE,
                'type' => Product::TYPE_SIMPLE,
                'skus' => [
                    [
                        'id' => $skuId,
                        'client_key' => 'sku-hook',
                        'price' => 209000,
                        'stock_quantity' => 4,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.meta_title', 'Đã lọc bởi hook');

        $this->actingAs($user)
            ->deleteJson("/api/admin/products/{$productId}")
            ->assertOk();

        self::assertContains('creating', $events);
        self::assertContains('created:' . $productId, $events);
        self::assertContains('updating:' . $productId, $events);
        self::assertContains('updated:' . $productId, $events);
        self::assertContains('deleting:' . $productId, $events);
        self::assertContains('deleted:' . $productId, $events);
    }
}
