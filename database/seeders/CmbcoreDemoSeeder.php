<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Category\Models\Category;
use Modules\Page\Models\Page;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMedia;
use Modules\Product\Models\ProductSku;
use Modules\Product\Models\ProductSkuAttribute;

/**
 * Seeder dữ liệu demo CMBCORE.
 *
 * Tất cả ảnh được lấy từ database/seeders/demo-assets/images/ (local).
 * Để cập nhật ảnh: chạy scripts/download-demo-assets.php, sau đó commit.
 */
class CmbcoreDemoSeeder extends Seeder
{
    /** Đường dẫn đến thư mục ảnh demo local */
    private string $assetsDir;

    public function __construct()
    {
        $this->assetsDir = database_path('seeders/demo-assets/images');
    }

    public function run(SettingService $settingService): void
    {
        $settingService->sync([
            [
                'group'       => 'general',
                'key'         => 'site_name',
                'value'       => 'CMBCORE',
                'type'        => 'text',
                'label'       => 'Tên website',
                'description' => 'Tên hiển thị storefront demo CMBCORE.',
                'position'    => 10,
            ],
        ]);

        $categories   = $this->seedCategories();
        $this->seedProducts($categories);
        $this->syncCategoryCounts($categories);
        $blogCategory = $this->seedBlogCategory();
        $this->seedBlogPosts($blogCategory);
        $this->seedPage();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Categories
    // ──────────────────────────────────────────────────────────────────────────

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $categories = [];

        foreach ([
            [
                'slug'        => 'bo-qua-tang-cho-nam',
                'name'        => 'Bộ quà tặng cho nam',
                'description' => 'Những combo và gift box được đóng gói sẵn để tặng quà cho phái mạnh.',
                'image'       => 'category-bo-qua-tang.png',
                'position'    => 10,
            ],
            [
                'slug'        => 'cham-soc-co-the',
                'name'        => 'Chăm sóc cơ thể',
                'description' => 'Routine tắm gội, vệ sinh và body care theo tinh thần CMBCORE.',
                'image'       => 'category-cham-soc-co-the.jpg',
                'position'    => 20,
            ],
            [
                'slug'        => 'san-pham-khu-mui',
                'name'        => 'Sản phẩm khử mùi',
                'description' => 'Nhóm sản phẩm giúp cơ thể luôn thoáng, sạch và tự tin.',
                'image'       => 'category-khu-mui.jpg',
                'position'    => 30,
            ],
            [
                'slug'        => 'cham-soc-toc',
                'name'        => 'Chăm sóc tóc',
                'description' => 'Sáp vuốt, xịt phồng và các sản phẩm grooming cho tóc nam.',
                'image'       => 'category-cham-soc-toc.jpg',
                'position'    => 40,
            ],
        ] as $payload) {
            $categories[$payload['slug']] = Category::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'parent_id'        => null,
                    'name'             => $payload['name'],
                    'description'      => $payload['description'],
                    'image'            => $this->installLocalImage($payload['image'], 'demo/categories'),
                    'icon'             => null,
                    'position'         => $payload['position'],
                    'level'            => 1,
                    'status'           => Category::STATUS_ACTIVE,
                    'meta_title'       => $payload['name'] . ' | CMBCORE',
                    'meta_description' => $payload['description'],
                ],
            );
        }

        return $categories;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Products
    // ──────────────────────────────────────────────────────────────────────────

    /** @param array<string, Category> $categories */
    private function seedProducts(array $categories): void
    {
        $products = [
            [
                'slug'              => 'combo-nam-tinh',
                'name'              => 'COMBO NAM TÍNH (Tặng bông tắm thân trẻ, túi và hộp)',
                'type'              => Product::TYPE_VARIABLE,
                'category'          => 'bo-qua-tang-cho-nam',
                'brand'             => 'CMBCORE',
                'short_description' => 'Combo đóng gói sẵn gồm tắm gội 3in1, sữa rửa mặt, dung dịch vệ sinh và nước hoa nam.',
                'description'       => <<<'HTML'
<p>Combo Nam Tính được thiết kế như một gift set hoàn chỉnh, giúp người mua chốt nhanh cho nhu cầu tặng quà, grooming và tạo ấn tượng từ lần mở hộp đầu tiên.</p>
<h2>Quy trình sản xuất</h2>
<p>Sản phẩm được curate theo routine nam giới hiện đại, ưu tiên mùi hương dễ dùng, bao bì chắc tay và tính sẵn sàng khi tặng quà.</p>
<h3>Lựa chọn mùi hương</h3>
<p>Mỗi biến thể mang một tính cách riêng để người mua có thể chọn nhanh theo gu của người nhận.</p>
<h3>Đóng gói premium</h3>
<p>Hộp và túi được set đồng bộ để tạo cảm giác cao cấp, giảm thao tác và tránh cần bổ sung phụ kiện khi tặng.</p>
<h2>Vì sao được mua nhiều</h2>
<p>Tỉ lệ giá trị nhận được trên mỗi đơn hàng cao hơn nhờ việc đồng bộ sản phẩm, giá compare cao và thông điệp quà tặng rõ ràng.</p>
HTML,
                'rating_value'      => 5.0,
                'review_count'      => 329,
                'sold_count'        => 11100,
                'is_featured'       => true,
                'skus'              => [
                    [
                        'sku_code'   => 'CBNT-HUONG-BIEN',
                        'name'       => 'Hương Biển',
                        'price'      => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 16,
                        'attributes' => [['attribute_name' => 'Loại sản phẩm', 'attribute_value' => 'Hương Biển']],
                    ],
                    [
                        'sku_code'   => 'CBNT-HUONG-GO',
                        'name'       => 'Hương Gỗ',
                        'price'      => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 12,
                        'attributes' => [['attribute_name' => 'Loại sản phẩm', 'attribute_value' => 'Hương Gỗ']],
                    ],
                    [
                        'sku_code'   => 'CBNT-HOA-CO',
                        'name'       => 'Hương Hoa Cỏ',
                        'price'      => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 9,
                        'attributes' => [['attribute_name' => 'Loại sản phẩm', 'attribute_value' => 'Hương Hoa Cỏ']],
                    ],
                ],
                'media' => [
                    'product-combo-nam-tinh-1.png',
                    'product-combo-nam-tinh-2.jpg',
                    'product-combo-nam-tinh-3.jpg',
                    'product-combo-nam-tinh-4.jpg',
                ],
            ],
            [
                'slug'              => 'box-thuong',
                'name'              => 'BOX THƯỞNG (Món quà tặng riêng cho bố)',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'bo-qua-tang-cho-nam',
                'brand'             => 'CMBCORE',
                'short_description' => 'Gift box có tổng visual đậm, gọn và hợp với những dịp tặng quà cá nhân.',
                'description'       => '<p>Box Thưởng là lựa chọn đóng gói gọn, dễ chọn và dễ tặng.</p>',
                'rating_value'      => 4.9,
                'review_count'      => 28,
                'sold_count'        => 740,
                'is_featured'       => true,
                'skus'              => [
                    [
                        'sku_code'   => 'BOX-THUONG-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 1099000,
                        'compare_price' => 1600000,
                        'stock_quantity' => 5,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-box-thuong.webp'],
            ],
            [
                'slug'              => 'box-limited',
                'name'              => 'BOX LIMITED (Có chữ ký độc quyền)',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'bo-qua-tang-cho-nam',
                'brand'             => 'CMBCORE',
                'short_description' => 'Phiên bản box nhấn mạnh tính hữu hạn và trình bày sang trọng.',
                'description'       => '<p>Box Limited giữ nhiệt visual đặc trưng của CMBCORE và tạo điểm nhấn cho gift campaign.</p>',
                'rating_value'      => 5.0,
                'review_count'      => 5,
                'sold_count'        => 92,
                'is_featured'       => true,
                'skus'              => [
                    [
                        'sku_code'   => 'BOX-LIMITED-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 749000,
                        'compare_price' => 799000,
                        'stock_quantity' => 8,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-box-limited.webp'],
            ],
            [
                'slug'              => 'lock-box',
                'name'              => 'VALENTINE LOCK BOX',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'bo-qua-tang-cho-nam',
                'brand'             => 'CMBCORE',
                'short_description' => 'Set quà tắm gội, nước hoa và nến thơm cho dịp Valentine.',
                'description'       => '<p>Lock Box tạo một opening experience đúng chất chiến dịch quà tặng mùa vụ.</p>',
                'rating_value'      => 4.8,
                'review_count'      => 12,
                'sold_count'        => 164,
                'is_featured'       => true,
                'skus'              => [
                    [
                        'sku_code'   => 'LOCK-BOX-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 799000,
                        'compare_price' => 1100000,
                        'stock_quantity' => 4,
                        'attributes' => [],
                    ],
                ],
                // Lock box fallback sang box-limited nếu file chưa tải được
                'media' => ['product-lock-box.webp'],
                'media_fallback' => 'product-box-limited.webp',
            ],
            [
                'slug'              => 'sua-tam-goi-3in1-cmbcore-noble',
                'name'              => 'Sữa tắm gội 3in1 CMBCORE Noble',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'cham-soc-co-the',
                'brand'             => 'CMBCORE',
                'short_description' => 'Routine tắm gội nhanh gọn cho nam giới cần sự tiện lợi.',
                'description'       => '<p>Công thức 3in1 dành cho nhu cầu tắm, gội và làm sạch cơ thể mỗi ngày.</p>',
                'rating_value'      => 4.9,
                'review_count'      => 42,
                'sold_count'        => 820,
                'is_featured'       => false,
                'skus'              => [
                    [
                        'sku_code'   => 'NOBLE-3IN1-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 189000,
                        'compare_price' => 239000,
                        'stock_quantity' => 24,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-sua-tam-3in1.jpg'],
            ],
            [
                'slug'              => 'bodymist-cmbcore-gentle',
                'name'              => 'Bodymist CMBCORE Gentle',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'san-pham-khu-mui',
                'brand'             => 'CMBCORE',
                'short_description' => 'Body mist cho cơ thể với mùi hương sạch và độ bám vừa đủ.',
                'description'       => '<p>Bodymist được đưa vào category khử mùi để mở rộng nhóm sản phẩm freshness.</p>',
                'rating_value'      => 4.8,
                'review_count'      => 19,
                'sold_count'        => 360,
                'is_featured'       => false,
                'skus'              => [
                    [
                        'sku_code'   => 'BODYMIST-GENTLE-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 229000,
                        'compare_price' => 299000,
                        'stock_quantity' => 18,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-bodymist.jpg'],
            ],
            [
                'slug'              => 'xit-phong-toc-cmbcore-speed',
                'name'              => 'Xịt phồng tóc CMBCORE Speed',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'cham-soc-toc',
                'brand'             => 'CMBCORE',
                'short_description' => 'Xịt phồng nhanh, dễ tạo nếp và giữ dáng tóc gọn.',
                'description'       => '<p>Sản phẩm grooming cho tóc có visual mạnh, dùng với layout category hair care.</p>',
                'rating_value'      => 4.7,
                'review_count'      => 21,
                'sold_count'        => 290,
                'is_featured'       => false,
                'skus'              => [
                    [
                        'sku_code'   => 'SPEED-HAIRSPRAY-DEFAULT',
                        'name'       => 'Mặc định',
                        'price'      => 149000,
                        'compare_price' => 189000,
                        'stock_quantity' => 32,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-xit-phong-toc.jpg'],
            ],
            [
                'slug'              => 'combo-sang-khoai',
                'name'              => 'COMBO SẢNG KHOÁI',
                'type'              => Product::TYPE_SIMPLE,
                'category'          => 'cham-soc-co-the',
                'brand'             => 'CMBCORE',
                'short_description' => 'Combo gọn để mở đầu routine body care.',
                'description'       => '<p>Combo Sảng Khoái được đặt để cân bằng giá trị và khả năng chốt đơn nhanh.</p>',
                'rating_value'      => 5.0,
                'review_count'      => 33,
                'sold_count'        => 142,
                'is_featured'       => true,
                'skus'              => [
                    [
                        'sku_code'   => 'COMBO-SANG-KHOAI',
                        'name'       => 'Mặc định',
                        'price'      => 399000,
                        'compare_price' => 599000,
                        'stock_quantity' => 11,
                        'attributes' => [],
                    ],
                ],
                'media' => ['product-combo-sang-khoai.webp'],
            ],
        ];

        foreach ($products as $payload) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'name'              => $payload['name'],
                    'description'       => $payload['description'],
                    'short_description' => $payload['short_description'],
                    'status'            => Product::STATUS_ACTIVE,
                    'type'              => $payload['type'],
                    'category_id'       => $categories[$payload['category']]->id,
                    'brand'             => $payload['brand'],
                    'meta_title'        => $payload['name'] . ' | CMBCORE',
                    'meta_description'  => strip_tags($payload['short_description']),
                    'meta_keywords'     => 'cmbcore, grooming, quà tặng cho nam',
                    'view_count'        => 0,
                    'rating_value'      => $payload['rating_value'],
                    'review_count'      => $payload['review_count'],
                    'sold_count'        => $payload['sold_count'],
                    'is_featured'       => $payload['is_featured'],
                ],
            );

            ProductMedia::query()->where('product_id', $product->id)->delete();
            ProductSku::query()->where('product_id', $product->id)->delete();

            foreach ($payload['skus'] as $index => $skuPayload) {
                $sku = ProductSku::query()->create([
                    'product_id'        => $product->id,
                    'sku_code'          => $skuPayload['sku_code'],
                    'name'              => $skuPayload['name'],
                    'price'             => $skuPayload['price'],
                    'compare_price'     => $skuPayload['compare_price'],
                    'cost'              => null,
                    'weight'            => null,
                    'stock_quantity'    => $skuPayload['stock_quantity'],
                    'low_stock_threshold' => 5,
                    'barcode'           => null,
                    'status'            => ProductSku::STATUS_ACTIVE,
                    'sort_order'        => $index,
                ]);

                foreach ($skuPayload['attributes'] as $attribute) {
                    ProductSkuAttribute::query()->create([
                        'product_sku_id'  => $sku->id,
                        'attribute_name'  => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }

            foreach ($payload['media'] as $index => $localFilename) {
                $fallback = $payload['media_fallback'] ?? null;
                $localPath = $this->installLocalImage($localFilename, 'demo/products', $fallback);

                ProductMedia::query()->create([
                    'product_id'     => $product->id,
                    'product_sku_id' => null,
                    'type'           => ProductMedia::TYPE_IMAGE,
                    'path'           => $localPath,
                    'disk'           => 'public',
                    'filename'       => $localFilename,
                    'mime_type'      => $this->mimeTypeForFilename($localFilename),
                    'size'           => 0,
                    'position'       => $index,
                    'alt_text'       => $product->name,
                    'metadata'       => null,
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Blog
    // ──────────────────────────────────────────────────────────────────────────

    private function seedBlogCategory(): BlogCategory
    {
        return BlogCategory::query()->updateOrCreate(
            ['slug' => 'tin-tuc'],
            [
                'name'             => 'Tin tức',
                'description'      => 'Nội dung tư vấn chăm sóc nam giới và quà tặng phong cách CMBCORE.',
                'image'            => $this->installLocalImage('blog-category-tin-tuc.webp', 'demo/blog-categories'),
                'status'           => BlogCategory::STATUS_ACTIVE,
                'meta_title'       => 'Tin tức | CMBCORE',
                'meta_description' => 'Chuyên mục tin tức storefront CMBCORE.',
            ],
        );
    }

    private function seedBlogPosts(BlogCategory $category): void
    {
        $posts = [
            [
                'slug'          => 'tay-te-bao-chet-body-may-lan-1-tuan',
                'title'         => 'Tẩy tế bào chết body mấy lần 1 tuần?',
                'local_image'   => 'blog-post-scrub-detail.webp',
                'excerpt'       => 'Hướng dẫn tần suất tẩy tế bào chết phù hợp và quy trình chăm sóc sau khi tẩy cho nam giới.',
                'content'       => <<<'HTML'
<p>Tẩy tế bào chết body đúng cách giúp da sạch, mềm và làm nền tốt hơn cho các bước chăm sóc tiếp theo. Vấn đề quan trọng không nằm ở việc tẩy thật mạnh, mà là tần suất và trình tự hợp lý.</p>
<h2>Nên tẩy tế bào chết body mấy lần 1 tuần?</h2>
<p>Da thường có thể tẩy 1 đến 2 lần mỗi tuần. Da nhạy cảm nên giảm xuống 1 lần mỗi 7 đến 10 ngày để tránh khô rát.</p>
<h2>Một số cách tẩy tế bào chết body</h2>
<h3>Tẩy tế bào chết vật lý</h3>
<p>Dùng hạt scrub hoặc bông tắm để massage nhẹ trên da ẩm.</p>
<h3>Tẩy tế bào chết hóa học</h3>
<p>Sử dụng AHA, BHA hoặc PHA để làm bong tế bào chết nhẹ hơn.</p>
<h2>Các bước tẩy tế bào chết body đúng cách</h2>
<h3>Bước 1: Làm sạch cơ thể với nước ấm</h3>
<p>Nước ấm giúp làm mềm lớp da bề mặt và mở đường cho scrub hoạt động đều hơn.</p>
<h3>Bước 2: Sử dụng sữa tắm</h3>
<p>Làm sạch cơ thể trước khi scrub để tránh massage lên lớp da đang còn nhiều bụi bẩn và dầu thừa.</p>
<h3>Bước 3: Massage sản phẩm tẩy tế bào chết</h3>
<p>Tập trung vào đầu gối, khuỷu tay, lưng và những vùng dễ tích tụ tế bào chết.</p>
<h3>Bước 4: Xả sạch và lau khô</h3>
<p>Xả lại bằng nước sạch, lau khô bằng khăn mềm thay vì chà xát mạnh.</p>
<h3>Bước 5: Dưỡng ẩm sau khi tẩy</h3>
<p>Dưỡng ẩm là bước giúp khóa lại độ ẩm và làm da mềm hơn sau khi da được làm sạch sâu.</p>
HTML,
                'published_at'  => now()->subDays(3),
                'is_featured'   => true,
            ],
            [
                'slug'          => 'nen-tay-te-bao-chet-khi-nao',
                'title'         => 'Nên tẩy tế bào chết khi nào: trước hay sau tắm?',
                'local_image'   => 'blog-post-scrub-timing.webp',
                'excerpt'       => 'Giải đáp trình tự tắm và scrub để tối ưu khả năng làm sạch và dưỡng ẩm.',
                'content'       => '<p>Bài viết mở rộng cách sắp xếp routine body care để không làm khô da và vẫn giữ hiệu quả làm sạch.</p>',
                'published_at'  => now()->subDays(6),
                'is_featured'   => false,
            ],
            [
                'slug'          => 'cach-de-co-the-luon-thom',
                'title'         => 'Top 9 cách để cơ thể luôn thơm cho phái mạnh',
                'local_image'   => 'blog-post-body-scent.webp',
                'excerpt'       => 'Tổng hợp những bước grooming đơn giản giúp cơ thể luôn sạch và có mùi hương dễ chịu.',
                'content'       => '<p>Kết hợp tắm gội đúng loại, khử mùi đúng lúc và giữ quần áo sạch là bộ ba cơ bản để cơ thể luôn thoáng thơm.</p>',
                'published_at'  => now()->subDays(10),
                'is_featured'   => false,
            ],
            [
                'slug'          => 'sua-tam-khu-mui-co-the-cho-nam',
                'title'         => 'Top 6 sữa tắm khử mùi cơ thể cho nam đang được chọn nhiều',
                'local_image'   => 'blog-post-body-wash.webp',
                'excerpt'       => 'Gợi ý các dạng sản phẩm body wash phù hợp với nhu cầu sạch, thoáng và dễ layer mùi hương.',
                'content'       => '<p>Body wash khử mùi là lựa chọn để cân bằng giữa khả năng làm sạch, mùi hương và độ dễ chịu trên da.</p>',
                'published_at'  => now()->subDays(13),
                'is_featured'   => false,
            ],
        ];

        foreach ($posts as $payload) {
            $featuredImage = $this->installLocalImage($payload['local_image'], 'demo/blog');

            BlogPost::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'title'            => $payload['title'],
                    'blog_category_id' => $category->id,
                    'author_name'      => 'CMBCORE Editorial',
                    'featured_image'   => $featuredImage,
                    'excerpt'          => $payload['excerpt'],
                    'content'          => $payload['content'],
                    'status'           => BlogPost::STATUS_PUBLISHED,
                    'published_at'     => $payload['published_at'],
                    'is_featured'      => $payload['is_featured'],
                    'view_count'       => 0,
                    'meta_title'       => $payload['title'] . ' | CMBCORE',
                    'meta_description' => $payload['excerpt'],
                    'meta_keywords'    => 'cmbcore, blog, chăm sóc nam',
                ],
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Pages
    // ──────────────────────────────────────────────────────────────────────────

    private function seedPage(): void
    {
        $featuredImage = $this->installLocalImage('page-gioi-thieu-featured.jpg', 'demo/pages');
        $bodyImage     = $this->installLocalImage('page-gioi-thieu-body.jpg', 'demo/pages');

        Page::query()->updateOrCreate(
            ['slug' => 'gioi-thieu'],
            [
                'title'            => 'Giới thiệu',
                'template'         => 'default',
                'featured_image'   => $featuredImage,
                'excerpt'          => 'CMBCORE theo đuổi cách tiếp cận chăm sóc nam giới gọn, mạnh và dễ chọn trong mỗi ngày.',
                'content'          => <<<HTML
<p>CMBCORE là một visual system và cũng là một trải nghiệm mua sắm hướng đến nam giới thế hệ mới: muốn sản phẩm được chia nhóm rõ ràng, nhìn chắc tay và có thể chốt nhanh mà vẫn cảm thấy premium.</p>
<p><img src="/storage/{$featuredImage}" alt="Giới thiệu CMBCORE"></p>
<h2>Từ phong cách đến routine</h2>
<p>Storefront được đồng bộ từ category, product card, article layout cho đến footer để khách hàng cảm được một tinh thần xuyên suốt: gọn, đậm, rõ và nam tính.</p>
<h2>Đóng gói sản phẩm như một món quà</h2>
<p>CMBCORE tập trung vào các combo và gift set có tính sẵn sàng cao. Điều này giúp trang sản phẩm và trang category không chỉ bán hàng, mà còn đóng vai trò như một catalog quà tặng có tính tuyển chọn.</p>
<p><img src="/storage/{$bodyImage}" alt="Đóng gói premium CMBCORE"></p>
<h2>Trải nghiệm nội dung và tư vấn</h2>
<p>Bên cạnh sản phẩm, blog và page static giữ vai trò giải thích cách dùng, tần suất chăm sóc và lý do lựa chọn.</p>
HTML,
                'status'           => Page::STATUS_PUBLISHED,
                'published_at'     => now()->subDays(20),
                'view_count'       => 0,
                'meta_title'       => 'Giới thiệu | CMBCORE',
                'meta_description' => 'Thông tin giới thiệu thương hiệu CMBCORE.',
                'meta_keywords'    => 'cmbcore, giới thiệu, grooming',
            ],
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Utilities
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Sao chép file ảnh từ demo-assets/ vào storage/app/public/{subfolder}/.
     * Trả về relative path (e.g. "demo/products/product-box-thuong.webp").
     *
     * Nếu file local không tồn tại → fallback sang file khác hoặc trả về tên file gốc.
     */
    private function installLocalImage(
        string $filename,
        string $subfolder = 'demo',
        ?string $fallbackFilename = null,
    ): string {
        $sourcePath = $this->assetsDir . '/' . $filename;

        // Thử fallback nếu file chính không tồn tại hoặc quá nhỏ (placeholder)
        if ((! file_exists($sourcePath) || filesize($sourcePath) < 500) && $fallbackFilename !== null) {
            $fallbackPath = $this->assetsDir . '/' . $fallbackFilename;
            if (file_exists($fallbackPath) && filesize($fallbackPath) >= 500) {
                $sourcePath = $fallbackPath;
                $filename   = $fallbackFilename;
            }
        }

        if (! file_exists($sourcePath) || filesize($sourcePath) < 100) {
            Log::warning("CmbcoreDemoSeeder: local image not found — {$filename}. Run: php scripts/download-demo-assets.php");
            return $subfolder . '/' . $filename; // giữ path để không bị null
        }

        $relativePath = $subfolder . '/' . $filename;

        try {
            $disk    = config('media_library.disk', 'public');
            $content = File::get($sourcePath);
            Storage::disk($disk)->put($relativePath, $content);
        } catch (\Throwable $e) {
            Log::warning("CmbcoreDemoSeeder: failed to copy {$filename}: {$e->getMessage()}");
        }

        return $relativePath;
    }

    /** @param array<string, Category> $categories */
    private function syncCategoryCounts(array $categories): void
    {
        foreach ($categories as $category) {
            $category->forceFill([
                'product_count' => Product::query()
                    ->where('category_id', $category->id)
                    ->count(),
            ])->save();
        }
    }

    private function mimeTypeForFilename(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return Arr::get([
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
        ], $ext, 'image/jpeg');
    }
}
