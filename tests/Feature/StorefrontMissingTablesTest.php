<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontMissingTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_and_product_listing_do_not_crash_when_product_tables_are_missing(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'flash_sale_items',
            'stock_movements',
            'wishlists',
            'review_images',
            'product_reviews',
            'product_media',
            'product_skus',
            'products',
            'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        $this->get('/')
            ->assertOk();

        $this->get('/san-pham')
            ->assertOk();
    }

    public function test_root_slug_fallback_returns_not_found_instead_of_sql_error_when_content_tables_are_missing(): void
    {
        Schema::drop('blog_posts');
        Schema::drop('blog_categories');
        Schema::drop('pages');
        Schema::drop('categories');

        $this->get('/gioi-thieu')
            ->assertNotFound();

        $this->get('/tay-te-bao-chet-body-may-lan-1-tuan')
            ->assertNotFound();

        $this->get('/bai-viet')
            ->assertOk();
    }
}
