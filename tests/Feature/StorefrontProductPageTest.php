<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMedia;
use Modules\Product\Models\ProductSku;
use Tests\TestCase;

class StorefrontProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_product_listing_can_render_and_filter_by_category_route(): void
    {
        $giftCategory = Category::query()->create([
            'name' => 'Bộ quà tặng cho nam',
            'slug' => 'bo-qua-tang-cho-nam',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $bodyCategory = Category::query()->create([
            'name' => 'Chăm sóc cơ thể',
            'slug' => 'cham-soc-co-the',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $featuredProduct = Product::query()->create([
            'name' => 'Combo Nam Tính',
            'slug' => 'combo-nam-tinh',
            'short_description' => '<p>Combo quà tặng bán chạy cho nam.</p>',
            'description' => '<p>Mô tả đầy đủ cho combo nam tính.</p>',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_VARIABLE,
            'category_id' => $giftCategory->id,
            'brand' => 'Rhys Man',
            'is_featured' => true,
            'rating_value' => 4.98,
            'review_count' => 329,
            'sold_count' => 1250,
        ]);

        ProductSku::query()->create([
            'product_id' => $featuredProduct->id,
            'sku_code' => 'CBNT-HG',
            'name' => 'Hương gỗ',
            'price' => 399000,
            'compare_price' => 610000,
            'stock_quantity' => 24,
            'low_stock_threshold' => 5,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        ProductMedia::query()->create([
            'product_id' => $featuredProduct->id,
            'type' => ProductMedia::TYPE_IMAGE,
            'path' => 'products/combo-nam-tinh.jpg',
            'disk' => 'public',
            'filename' => 'combo-nam-tinh.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'position' => 0,
            'alt_text' => 'Combo Nam Tính',
        ]);

        $otherProduct = Product::query()->create([
            'name' => 'Body Mist Rhys Gentle',
            'slug' => 'body-mist-rhys-gentle',
            'short_description' => '<p>Body mist cho nam.</p>',
            'description' => '<p>Mô tả cho body mist.</p>',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_SIMPLE,
            'category_id' => $bodyCategory->id,
            'brand' => 'Rhys Man',
        ]);

        ProductSku::query()->create([
            'product_id' => $otherProduct->id,
            'sku_code' => 'BODY-MIST',
            'name' => 'Mặc định',
            'price' => 129000,
            'stock_quantity' => 15,
            'low_stock_threshold' => 5,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        $this->get('/san-pham')
            ->assertOk()
            ->assertSee('Combo Nam Tính')
            ->assertSee('Body Mist Rhys Gentle')
            ->assertSee('Sản phẩm Rhys Man')
            ->assertSee('Đã bán 1250');

        $this->get('/danh-muc-san-pham/bo-qua-tang-cho-nam')
            ->assertOk()
            ->assertSee('Bộ quà tặng cho nam')
            ->assertSee('Combo Nam Tính');
    }

    public function test_storefront_product_detail_renders_summary_related_products_and_increments_view_count(): void
    {
        $category = Category::query()->create([
            'name' => 'Bộ quà tặng cho nam',
            'slug' => 'bo-qua-tang-cho-nam',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'name' => 'Combo Nam Tính',
            'slug' => 'combo-nam-tinh',
            'short_description' => '<p>Combo quà tặng bán chạy cho nam.</p>',
            'description' => '<h2>Mô tả chi tiết</h2><p>Chi tiết đầy đủ của combo nam tính.</p>',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_VARIABLE,
            'category_id' => $category->id,
            'brand' => 'Rhys Man',
            'rating_value' => 4.98,
            'review_count' => 329,
            'sold_count' => 1250,
        ]);

        ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'CBNT-HG',
            'name' => 'Hương gỗ',
            'price' => 399000,
            'compare_price' => 610000,
            'stock_quantity' => 24,
            'low_stock_threshold' => 5,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'CBNT-HB',
            'name' => 'Hương biển',
            'price' => 399000,
            'compare_price' => 610000,
            'stock_quantity' => 18,
            'low_stock_threshold' => 5,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);

        ProductMedia::query()->create([
            'product_id' => $product->id,
            'type' => ProductMedia::TYPE_IMAGE,
            'path' => 'products/combo-nam-tinh.jpg',
            'disk' => 'public',
            'filename' => 'combo-nam-tinh.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'position' => 0,
            'alt_text' => 'Combo Nam Tính',
        ]);

        $relatedProduct = Product::query()->create([
            'name' => 'Lock Box',
            'slug' => 'lock-box',
            'short_description' => '<p>Phiên bản quà tặng cao cấp.</p>',
            'description' => '<p>Mô tả Lock Box.</p>',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_SIMPLE,
            'category_id' => $category->id,
            'brand' => 'Rhys Man',
        ]);

        ProductSku::query()->create([
            'product_id' => $relatedProduct->id,
            'sku_code' => 'LOCK-BOX',
            'name' => 'Mặc định',
            'price' => 799000,
            'compare_price' => 1100000,
            'stock_quantity' => 8,
            'low_stock_threshold' => 3,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        $this->get('/san-pham/combo-nam-tinh')
            ->assertOk()
            ->assertSee('Combo Nam Tính')
            ->assertSee('Uu dai va dong goi')
            ->assertSee('Mô tả chi tiết')
            ->assertSee('Sản phẩm liên quan')
            ->assertSee('Lock Box')
            ->assertSee('Đã bán 1250');

        self::assertSame(1, $product->fresh()->view_count);
    }
}
