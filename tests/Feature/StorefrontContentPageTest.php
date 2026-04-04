<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Page\Models\Page;
use Tests\TestCase;

class StorefrontContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_blog_listing_category_and_root_detail_render_correctly(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'Tin tuc',
            'slug' => 'tin-tuc',
            'description' => 'Tin tuc moi nhat tu Rhys Man.',
            'status' => BlogCategory::STATUS_ACTIVE,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Tay te bao chet body may lan 1 tuan?',
            'slug' => 'tay-te-bao-chet-body-may-lan-1-tuan',
            'blog_category_id' => $category->id,
            'author_name' => 'Rhys Man Editorial',
            'excerpt' => 'Tan suat tay te bao chet phu hop cho nam gioi.',
            'content' => '<h2>Nen tay te bao chet body may lan 1 tuan?</h2><p>Noi dung chi tiet.</p><h3>Cac buoc thuc hien</h3><p>Chi tiet tung buoc.</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Cach de co the luon thom',
            'slug' => 'cach-de-co-the-luon-thom',
            'blog_category_id' => $category->id,
            'author_name' => 'Rhys Man Editorial',
            'excerpt' => 'Bài viết liên quan.',
            'content' => '<p>Noi dung cho bai liên quan.</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subHours(12),
        ]);

        $this->get('/bai-viet')
            ->assertOk()
            ->assertSee('Tay te bao chet body may lan 1 tuan?')
            ->assertSee('Tin tức mới nhất');

        $this->get('/category/tin-tuc')
            ->assertOk()
            ->assertSee('Tin tuc')
            ->assertSee('Tay te bao chet body may lan 1 tuan?');

        $this->get('/tay-te-bao-chet-body-may-lan-1-tuan')
            ->assertOk()
            ->assertSee('Tay te bao chet body may lan 1 tuan?')
            ->assertSee('Rhys Man Editorial')
            ->assertSee('Noi dung')
            ->assertSee('Cach de co the luon thom');

        self::assertSame(1, $post->fresh()->view_count);
    }

    public function test_storefront_static_page_can_render_from_root_slug(): void
    {
        $page = Page::query()->create([
            'title' => 'Gioi thieu',
            'slug' => 'gioi-thieu',
            'template' => 'default',
            'excerpt' => '<p>Cau chuyen thuong hieu Rhys Man.</p>',
            'content' => <<<HTML
<p>Noi dung chi tiet cua trang gioi thieu.</p>

[cmb_block type="cta" props="eyJ0aXRsZSI6IkNhbiBo4buXIHRy4bujIHRoZW0/IiwiYm9keSI6IkxpZW4gaGUgZGUgZHVvYyB0dSB2YW4gZ2lhbyBoYW5nIG5oYW5oLiIsInByaW1hcnlfbGFiZWwiOiJMaWVuIGhlIiwicHJpbWFyeV91cmwiOiIvZ2lvaS10aGlldSJ9"]
HTML,
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/gioi-thieu')
            ->assertOk()
            ->assertSee('Gioi thieu')
            ->assertSee('Cau chuyen thuong hieu Rhys Man.')
            ->assertSee('Noi dung chi tiet cua trang gioi thieu.')
            ->assertSee('giao hang nhanh')
            ->assertSee('Lien he');

        self::assertSame(1, $page->fresh()->view_count);
    }
}
