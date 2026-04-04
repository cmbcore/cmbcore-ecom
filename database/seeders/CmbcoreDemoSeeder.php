<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
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

class CmbcoreDemoSeeder extends Seeder
{
    public function run(SettingService $settingService): void
    {
        $settingService->sync([
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'CMBCORE',
                'type' => 'text',
                'label' => 'Ten website',
                'description' => 'Ten hien thi storefront demo Cmbcore.',
                'position' => 10,
            ],
        ]);

        $categories = $this->seedCategories();
        $this->seedProducts($categories);
        $this->syncCategoryCounts($categories);
        $blogCategory = $this->seedBlogCategory();
        $this->seedBlogPosts($blogCategory);
        $this->seedPage();
    }

    /**
     * Download a remote image and store it locally under the public disk.
     * Returns the relative path (e.g. "demo/abc123.jpg") for DB storage.
     * Falls back to the original URL on any download failure so seeding still succeeds.
     */
    private function downloadAndStoreImage(string $url, string $subfolder = 'demo'): string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("CmbcoreDemoSeeder: failed to download {$url} (HTTP {$response->status()})");
                return $url;
            }

            $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $extension = 'jpg';
            }

            $filename = Str::ulid() . '.' . $extension;
            $relativePath = $subfolder . '/' . $filename;

            Storage::disk('public')->put($relativePath, $response->body());

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning("CmbcoreDemoSeeder: exception downloading {$url}: {$e->getMessage()}");
            return $url;
        }
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [];

        foreach ([
            [
                'slug' => 'bo-qua-tang-cho-nam',
                'name' => 'Bo qua tang cho nam',
                'description' => 'Nhung combo va gift box duoc dong goi san de tang qua cho phai man.',
                'image' => 'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png',
                'position' => 10,
            ],
            [
                'slug' => 'cham-soc-co-the',
                'name' => 'Cham soc co the',
                'description' => 'Routine tam goi, ve sinh va body care theo tinh than Cmbcore.',
                'image' => 'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',
                'position' => 20,
            ],
            [
                'slug' => 'san-pham-khu-mui',
                'name' => 'Sản phẩm khu mui',
                'description' => 'Nhom sản phẩm giup co the luon thoang, sach va tu tin.',
                'image' => 'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien-cho-nam-gioi_7e4977521e4849eca099d725ce266a0d.jpg',
                'position' => 30,
            ],
            [
                'slug' => 'cham-soc-toc',
                'name' => 'Cham soc toc',
                'description' => 'Sap vuot, xit phong va cac sản phẩm grooming cho toc nam.',
                'image' => 'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',
                'position' => 40,
            ],
        ] as $payload) {
            $categories[$payload['slug']] = Category::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'parent_id' => null,
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'image' => $this->downloadAndStoreImage($payload['image'], 'demo/categories'),
                    'icon' => null,
                    'position' => $payload['position'],
                    'level' => 1,
                    'status' => Category::STATUS_ACTIVE,
                    'meta_title' => $payload['name'] . ' | CMBCORE',
                    'meta_description' => $payload['description'],
                ],
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $categories): void
    {
        $products = [
            [
                'slug' => 'combo-nam-tinh',
                'name' => 'COMBO NAM TINH (Tang bong tam than tre, tui va hop)',
                'type' => Product::TYPE_VARIABLE,
                'category' => 'bo-qua-tang-cho-nam',
                'brand' => 'CMBCORE',
                'short_description' => 'Combo dong goi san gom tam goi 3in1, sua rua mat, dung dich ve sinh va nuoc hoa nam.',
                'description' => <<<'HTML'
<p>Combo Nam Tinh duoc thiet ke nhu mot gift set hoan chinh, giup nguoi mua chot nhanh cho nhu cau tang qua, grooming va tao an tuong tu lan mo hop dau tien.</p>
<h2>Quy trinh san xuat</h2>
<p>Sản phẩm duoc curate theo routine nam gioi hien dai, uu tien mui huong de dung, bao bi chac tay va tinh san sang khi tang qua.</p>
<h3>Lua chon mui huong</h3>
<p>Moi bien the mang mot tinh cach rieng de nguoi mua co the chon nhanh theo gu cua nguoi nhan.</p>
<h3>Dong goi premium</h3>
<p>Hop va tui duoc set dong bo de tao cam giac cao cap, giam thao tác va tranh can bo sung phu kien khi tang.</p>
<h2>Vi sao duoc mua nhieu</h2>
<p>Ty le gia tri nhan duoc tren moi đơn hàng cao hon nho viec dong bo sản phẩm, gia compare cao va thong diep qua tang ro rang.</p>
HTML,
                'rating_value' => 5.0,
                'review_count' => 329,
                'sold_count' => 11100,
                'is_featured' => true,
                'skus' => [
                    [
                        'sku_code' => 'CBNT-HUONG-BIEN',
                        'name' => 'Huong Bien',
                        'price' => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 16,
                        'attributes' => [
                            ['attribute_name' => 'Loai sản phẩm', 'attribute_value' => 'Huong Bien'],
                        ],
                    ],
                    [
                        'sku_code' => 'CBNT-HUONG-GO',
                        'name' => 'Huong Go',
                        'price' => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 12,
                        'attributes' => [
                            ['attribute_name' => 'Loai sản phẩm', 'attribute_value' => 'Huong Go'],
                        ],
                    ],
                    [
                        'sku_code' => 'CBNT-HOA-CO',
                        'name' => 'Huong Hoa Co',
                        'price' => 399000,
                        'compare_price' => 610000,
                        'stock_quantity' => 9,
                        'attributes' => [
                            ['attribute_name' => 'Loai sản phẩm', 'attribute_value' => 'Huong Hoa Co'],
                        ],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png',
                    'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',
                    'https://rhysman.vn/wp-content/uploads/2025/05/cong-dung-cua-sua-tam-goi-3-in-1_73b97f72eec349ceb7d964c0fa84eecf.jpg',
                    'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',
                ],
            ],
            [
                'slug' => 'box-thuong',
                'name' => 'BOX THUONG (Mon qua tang rieng cho bo)',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'bo-qua-tang-cho-nam',
                'brand' => 'CMBCORE',
                'short_description' => 'Gift box co tong visual dam, gon va hop voi nhung dip tang qua ca nhan.',
                'description' => '<p>Box Thuong la lua chon dong goi gon, de chon va de tang.</p>',
                'rating_value' => 4.9,
                'review_count' => 28,
                'sold_count' => 740,
                'is_featured' => true,
                'skus' => [
                    [
                        'sku_code' => 'BOX-THUONG-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 1099000,
                        'compare_price' => 1600000,
                        'stock_quantity' => 5,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/smush-webp/2025/10/THUMB-2.jpg.webp',
                ],
            ],
            [
                'slug' => 'box-limited',
                'name' => 'BOX LIMITED (Co chu ky doc quyen)',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'bo-qua-tang-cho-nam',
                'brand' => 'CMBCORE',
                'short_description' => 'Phien ban box nhan manh tinh huu han va trinh bay sang trong.',
                'description' => '<p>Box Limited giu nhiet visual dac trung cua Cmbcore va tao diem nhan cho gift campaign.</p>',
                'rating_value' => 5.0,
                'review_count' => 5,
                'sold_count' => 92,
                'is_featured' => true,
                'skus' => [
                    [
                        'sku_code' => 'BOX-LIMITED-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 749000,
                        'compare_price' => 799000,
                        'stock_quantity' => 8,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/smush-webp/2025/07/ANH-THUMB-LIMITED-TI-KTOK.jpg.webp',
                ],
            ],
            [
                'slug' => 'lock-box',
                'name' => 'VALENTINE LOCK BOX',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'bo-qua-tang-cho-nam',
                'brand' => 'CMBCORE',
                'short_description' => 'Set qua tam goi, nuoc hoa va nen thom cho dip valentine.',
                'description' => '<p>Lock Box tao mot opening experience dung chat chien dich qua tang mua vu.</p>',
                'rating_value' => 4.8,
                'review_count' => 12,
                'sold_count' => 164,
                'is_featured' => true,
                'skus' => [
                    [
                        'sku_code' => 'LOCK-BOX-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 799000,
                        'compare_price' => 1100000,
                        'stock_quantity' => 4,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/smush-webp/2026/01/THUMB-LOCK-BOX.png.webp',
                ],
            ],
            [
                'slug' => 'sua-tam-goi-3in1-cmbcore-noble',
                'name' => 'Sua tam goi 3in1 Cmbcore Noble',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'cham-soc-co-the',
                'brand' => 'CMBCORE',
                'short_description' => 'Routine tam goi nhanh gon cho nam gioi can su tien loi.',
                'description' => '<p>Cong thuc 3in1 danh cho nhu cau tam, goi va lam sach co the moi ngay.</p>',
                'rating_value' => 4.9,
                'review_count' => 42,
                'sold_count' => 820,
                'is_featured' => false,
                'skus' => [
                    [
                        'sku_code' => 'NOBLE-3IN1-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 189000,
                        'compare_price' => 239000,
                        'stock_quantity' => 24,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/uploads/2025/05/cong-dung-cua-sua-tam-goi-3-in-1_73b97f72eec349ceb7d964c0fa84eecf.jpg',
                ],
            ],
            [
                'slug' => 'bodymist-cmbcore-gentle',
                'name' => 'Bodymist Cmbcore Gentle',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'san-pham-khu-mui',
                'brand' => 'CMBCORE',
                'short_description' => 'Body mist cho co the voi mui huong sach va do bam vua du.',
                'description' => '<p>Bodymist duoc dua vao category khu mui de mở rộng nhom sản phẩm freshness.</p>',
                'rating_value' => 4.8,
                'review_count' => 19,
                'sold_count' => 360,
                'is_featured' => false,
                'skus' => [
                    [
                        'sku_code' => 'BODYMIST-GENTLE-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 229000,
                        'compare_price' => 299000,
                        'stock_quantity' => 18,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/uploads/2025/05/chiet-xuat-tu-thanh-phan-tu-nhien_9a3c62cfb8a5401a8142af8c23543715.jpg',
                ],
            ],
            [
                'slug' => 'xit-phong-toc-cmbcore-speed',
                'name' => 'Xit phong toc Cmbcore Speed',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'cham-soc-toc',
                'brand' => 'CMBCORE',
                'short_description' => 'Xit phong nhanh, de tao nep va giu dang toc gon.',
                'description' => '<p>Sản phẩm grooming cho toc co visual manh, dung voi layout category hair care.</p>',
                'rating_value' => 4.7,
                'review_count' => 21,
                'sold_count' => 290,
                'is_featured' => false,
                'skus' => [
                    [
                        'sku_code' => 'SPEED-HAIRSPRAY-DEFAULT',
                        'name' => 'Mặc định',
                        'price' => 149000,
                        'compare_price' => 189000,
                        'stock_quantity' => 32,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',
                ],
            ],
            [
                'slug' => 'combo-sang-khoai',
                'name' => 'COMBO SANG KHOAI',
                'type' => Product::TYPE_SIMPLE,
                'category' => 'cham-soc-co-the',
                'brand' => 'CMBCORE',
                'short_description' => 'Combo gon de mo dau routine body care.',
                'description' => '<p>Combo Sang Khoai duoc dat de can bang gia tri va kha nang chot don nhanh.</p>',
                'rating_value' => 5.0,
                'review_count' => 33,
                'sold_count' => 142,
                'is_featured' => true,
                'skus' => [
                    [
                        'sku_code' => 'COMBO-SANG-KHOAI',
                        'name' => 'Mặc định',
                        'price' => 399000,
                        'compare_price' => 599000,
                        'stock_quantity' => 11,
                        'attributes' => [],
                    ],
                ],
                'media' => [
                    'https://rhysman.vn/wp-content/smush-webp/2025/05/THUMB-SANG-KHOAI-1.png.webp',
                ],
            ],
        ];

        foreach ($products as $payload) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'short_description' => $payload['short_description'],
                    'status' => Product::STATUS_ACTIVE,
                    'type' => $payload['type'],
                    'category_id' => $categories[$payload['category']]->id,
                    'brand' => $payload['brand'],
                    'meta_title' => $payload['name'] . ' | CMBCORE',
                    'meta_description' => strip_tags($payload['short_description']),
                    'meta_keywords' => 'cmbcore, grooming, qua tang cho nam',
                    'view_count' => 0,
                    'rating_value' => $payload['rating_value'],
                    'review_count' => $payload['review_count'],
                    'sold_count' => $payload['sold_count'],
                    'is_featured' => $payload['is_featured'],
                ],
            );

            ProductMedia::query()->where('product_id', $product->id)->delete();
            ProductSku::query()->where('product_id', $product->id)->delete();

            foreach ($payload['skus'] as $index => $skuPayload) {
                $sku = ProductSku::query()->create([
                    'product_id' => $product->id,
                    'sku_code' => $skuPayload['sku_code'],
                    'name' => $skuPayload['name'],
                    'price' => $skuPayload['price'],
                    'compare_price' => $skuPayload['compare_price'],
                    'cost' => null,
                    'weight' => null,
                    'stock_quantity' => $skuPayload['stock_quantity'],
                    'low_stock_threshold' => 5,
                    'barcode' => null,
                    'status' => ProductSku::STATUS_ACTIVE,
                    'sort_order' => $index,
                ]);

                foreach ($skuPayload['attributes'] as $attribute) {
                    ProductSkuAttribute::query()->create([
                        'product_sku_id' => $sku->id,
                        'attribute_name' => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }

            foreach ($payload['media'] as $index => $mediaUrl) {
                $localPath = $this->downloadAndStoreImage($mediaUrl, 'demo/products');

                ProductMedia::query()->create([
                    'product_id' => $product->id,
                    'product_sku_id' => null,
                    'type' => ProductMedia::TYPE_IMAGE,
                    'path' => $localPath,
                    'disk' => 'public',
                    'filename' => basename((string) parse_url($mediaUrl, PHP_URL_PATH)),
                    'mime_type' => $this->mimeTypeForUrl($mediaUrl),
                    'size' => 0,
                    'position' => $index,
                    'alt_text' => $product->name,
                    'metadata' => null,
                ]);
            }
        }
    }

    private function seedBlogCategory(): BlogCategory
    {
        return BlogCategory::query()->updateOrCreate(
            ['slug' => 'tin-tuc'],
            [
                'name' => 'Tin tuc',
                'description' => 'Noi dung tu van cham soc nam gioi va qua tang phong cach Cmbcore.',
                'image' => $this->downloadAndStoreImage(
                    'https://rhysman.vn/wp-content/smush-webp/2025/09/6-1.jpg.webp',
                    'demo/blog-categories',
                ),
                'status' => BlogCategory::STATUS_ACTIVE,
                'meta_title' => 'Tin tuc | CMBCORE',
                'meta_description' => 'Chuyen muc tin tuc storefront Cmbcore.',
            ],
        );
    }

    private function seedBlogPosts(BlogCategory $category): void
    {
        $posts = [
            [
                'slug' => 'tay-te-bao-chet-body-may-lan-1-tuan',
                'title' => 'Tay te bao chet body may lan 1 tuan?',
                'featured_image_url' => 'https://rhysman.vn/wp-content/smush-webp/2025/09/6-1.jpg.webp',
                'excerpt' => 'Huong dan tan suat tay te bao chet phu hop va quy trinh cham soc sau khi tay cho nam gioi.',
                'content' => <<<'HTML'
<p>Tay te bao chet body dung cach giup da sach, mem va lam nen tot hon cho cac buoc cham soc tiep theo. Van de quan trong khong nam o viec tay that manh, ma la tan suat va trinh tu hop ly.</p>
<h2>Nen tay te bao chet body may lan 1 tuan?</h2>
<p>Da thuong co the tay 1 den 2 lan moi tuan. Da nhay cam nen giam xuong 1 lan moi 7 den 10 ngay de tranh kho rat.</p>
<h2>Mot so cach tay te bao chet body</h2>
<h3>Tay te bao chet vat ly</h3>
<p>Dung hat scrub hoac bong tam de massage nhe tren da am.</p>
<h3>Tay te bao chet hoa hoc</h3>
<p>Su dung AHA, BHA hoac PHA de lam bong te bao chet nhe hon.</p>
<h2>Cac buoc tay te bao chet body dung cach</h2>
<h3>Buoc 1: Lam sach co the voi nuoc am</h3>
<p>Nuoc am giup lam mem lop da be mat va mo duong cho scrub hoat dong deu hon.</p>
<h3>Buoc 2: Su dung sua tam</h3>
<p>Lam sach co the truoc khi scrub de tranh massage len lop da dang con nhieu bui ban va dau thua.</p>
<h3>Buoc 3: Massage sản phẩm tay te bao chet</h3>
<p>Tập trung vao dau goi, khuyu tay, lung va nhung vung de tich tu te bao chet.</p>
<h3>Buoc 4: Xa sach va lau kho</h3>
<p>Xa lai bang nuoc sach, lau kho bang khan mem thay vi cha sat manh.</p>
<h3>Buoc 5: Duong am sau khi tay</h3>
<p>Duong am la buoc giup khoa lai do am va lam da mem hon sau khi da duoc lam sach sau.</p>
HTML,
                'published_at' => now()->subDays(3),
            ],
            [
                'slug' => 'nen-tay-te-bao-chet-khi-nao',
                'title' => 'Nen tay te bao chet khi nao: truoc hay sau tam?',
                'featured_image_url' => 'https://rhysman.vn/wp-content/smush-webp/2025/09/8.jpg.webp',
                'excerpt' => 'Giai dap trinh tu tam va scrub de toi uu kha nang lam sach va duong am.',
                'content' => '<p>Bài viết mở rộng cach sap xep routine body care de khong lam kho da va van giu hieu qua lam sach.</p>',
                'published_at' => now()->subDays(6),
            ],
            [
                'slug' => 'cach-de-co-the-luon-thom',
                'title' => 'Top 9 cach de co the luon thom cho phai man',
                'featured_image_url' => 'https://rhysman.vn/wp-content/smush-webp/2025/08/cach-de-co-the-luon-thom.jpg.webp',
                'excerpt' => 'Tổng hợp nhung buoc grooming don gian giup co the luon sach va co mui huong de chiu.',
                'content' => '<p>Ket hop tam goi dung loai, khu mui dung luc va giu quan ao sach la bo ba co ban de co the luon thoang thom.</p>',
                'published_at' => now()->subDays(10),
            ],
            [
                'slug' => 'sua-tam-khu-mui-co-the-cho-nam',
                'title' => 'Top 6 sua tam khu mui co the cho nam dang duoc chon nhieu',
                'featured_image_url' => 'https://rhysman.vn/wp-content/smush-webp/2025/09/sua-tam-khu-mui-co-the-cho-nam.jpg.webp',
                'excerpt' => 'Goi y cac dang sản phẩm body wash phu hop voi nhu cau sach, thoang va de layer mui huong.',
                'content' => '<p>Body wash khu mui la lua chon de can bang giua kha nang lam sach, mui huong va do de chiu tren da.</p>',
                'published_at' => now()->subDays(13),
            ],
        ];

        foreach ($posts as $payload) {
            $featuredImage = $this->downloadAndStoreImage($payload['featured_image_url'], 'demo/blog');

            BlogPost::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'title' => $payload['title'],
                    'blog_category_id' => $category->id,
                    'author_name' => 'Cmbcore Editorial',
                    'featured_image' => $featuredImage,
                    'excerpt' => $payload['excerpt'],
                    'content' => $payload['content'],
                    'status' => BlogPost::STATUS_PUBLISHED,
                    'published_at' => $payload['published_at'],
                    'is_featured' => $payload['slug'] === 'tay-te-bao-chet-body-may-lan-1-tuan',
                    'view_count' => 0,
                    'meta_title' => $payload['title'] . ' | CMBCORE',
                    'meta_description' => $payload['excerpt'],
                    'meta_keywords' => 'cmbcore, blog, cham soc nam',
                ],
            );
        }
    }

    private function seedPage(): void
    {
        $featuredImage = $this->downloadAndStoreImage(
            'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',
            'demo/pages',
        );
        $bodyImage = $this->downloadAndStoreImage(
            'https://rhysman.vn/wp-content/uploads/2025/05/chiet-xuat-tu-thanh-phan-tu-nhien_9a3c62cfb8a5401a8142af8c23543715.jpg',
            'demo/pages',
        );

        Page::query()->updateOrCreate(
            ['slug' => 'gioi-thieu'],
            [
                'title' => 'Gioi thieu',
                'template' => 'default',
                'featured_image' => $featuredImage,
                'excerpt' => 'Cmbcore theo duoi cach tiep can cham soc nam gioi gon, manh va de chon trong moi ngay.',
                'content' => <<<HTML
<p>Cmbcore la mot visual system va cung la mot trai nghiem mua sam huong den nam gioi the he moi: muon sản phẩm duoc chia nhom ro rang, nhin chac tay, va co the chot nhanh ma van cam thay premium.</p>
<p><img src="/storage/{$featuredImage}" alt="gioi thieu cmbcore"></p>
<h2>Tu phong cach den routine</h2>
<p>Storefront duoc dong bo tu category, product card, article layout cho den footer de khách hàng cam duoc mot tinh than xuyen suot: gon, dam, ro va nam tinh.</p>
<h2>Dong goi sản phẩm nhu mot mon qua</h2>
<p>Cmbcore tập trung vao cac combo va gift set co tinh san sang cao. Dieu nay giup trang sản phẩm va trang category khong chi ban hang, ma con dong vai tro nhu mot catalog qua tang co tinh tuyen chon.</p>
<p><img src="/storage/{$bodyImage}" alt="dong goi premium"></p>
<h2>Trai nghiem noi dung va tu van</h2>
<p>Ben canh sản phẩm, blog va page static giu vai tro giai thich cach dung, tan suat cham soc va ly do lua chon.</p>
HTML,
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now()->subDays(20),
                'view_count' => 0,
                'meta_title' => 'Gioi thieu | CMBCORE',
                'meta_description' => 'Thong tin gioi thieu thuong hieu Cmbcore.',
                'meta_keywords' => 'cmbcore, gioi thieu, grooming',
            ],
        );
    }

    /**
     * @param  array<string, Category>  $categories
     */
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

    private function mimeTypeForUrl(string $url): string
    {
        $extension = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return Arr::get([
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ], $extension, 'image/jpeg');
    }
}
