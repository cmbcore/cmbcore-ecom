<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogPost;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;
use Tests\TestCase;

class StorefrontHomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_homepage_renders_theme_sections_with_dynamic_data(): void
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
            'short_description' => '<p>Combo quà tặng bán chạy.</p>',
            'description' => '<p>Mô tả sản phẩm.</p>',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_SIMPLE,
            'category_id' => $category->id,
            'brand' => 'Rhys Man',
            'is_featured' => true,
        ]);

        ProductSku::query()->create([
            'product_id' => $product->id,
            'sku_code' => 'CBNT',
            'name' => 'Mặc định',
            'price' => 399000,
            'stock_quantity' => 12,
            'low_stock_threshold' => 3,
            'status' => ProductSku::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        BlogPost::query()->create([
            'title' => 'Tẩy tế bào chết body mấy lần 1 tuần?',
            'slug' => 'tay-te-bao-chet-body-may-lan-1-tuan',
            'author_name' => 'Rhys Man Editorial',
            'excerpt' => 'Bài viết chăm sóc cơ thể cho nam.',
            'content' => '<p>Nội dung bài viết.</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ban chay nhat')
            ->assertSee('Tin tuc moi nhat')
            ->assertSee('Combo Nam Tính')
            ->assertSee('Tẩy tế bào chết body mấy lần 1 tuần?');
    }
}
