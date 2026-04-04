<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_manage_blog_categories(): void
    {
        $user = User::query()->create([
            'name' => 'Blog Admin',
            'email' => 'blog@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/admin/blog/categories', [
                'name' => 'Tin tức',
                'description' => 'Chuyên mục tin tức.',
                'status' => BlogCategory::STATUS_ACTIVE,
                'meta_title' => 'Tin tức Rhys Man',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tin-tuc')
            ->assertJsonPath('data.status', BlogCategory::STATUS_ACTIVE);

        /** @var BlogCategory $category */
        $category = BlogCategory::query()->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/admin/blog/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Tin tức']);

        $this->actingAs($user)
            ->putJson("/api/admin/blog/categories/{$category->id}", [
                'name' => 'Tin tức cập nhật',
                'slug' => 'tin-tuc',
                'description' => 'Mô tả cập nhật.',
                'status' => BlogCategory::STATUS_ACTIVE,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Tin tức cập nhật');

        $this->actingAs($user)
            ->deleteJson("/api/admin/blog/categories/{$category->id}")
            ->assertOk();

        self::assertSoftDeleted('blog_categories', ['id' => $category->id]);
    }

    public function test_admin_user_can_manage_blog_posts_with_category_linkage(): void
    {
        $user = User::query()->create([
            'name' => 'Blog Admin',
            'email' => 'blog-posts@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $category = BlogCategory::query()->create([
            'name' => 'Tin tức',
            'slug' => 'tin-tuc',
            'status' => BlogCategory::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->postJson('/api/admin/blog/posts', [
                'title' => 'Xu hướng storefront 2026',
                'author_name' => 'CMBCORE Editorial',
                'blog_category_id' => $category->id,
                'excerpt' => 'Tóm tắt cho bài viết thử nghiệm.',
                'content' => 'Nội dung chi tiết cho bài viết thử nghiệm.',
                'status' => BlogPost::STATUS_PUBLISHED,
                'is_featured' => true,
                'meta_title' => 'Xu hướng storefront 2026',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'xu-huong-storefront-2026')
            ->assertJsonPath('data.status', BlogPost::STATUS_PUBLISHED)
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.category.slug', 'tin-tuc');

        /** @var BlogPost $post */
        $post = BlogPost::query()->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/admin/blog/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Xu hướng storefront 2026']);

        $this->actingAs($user)
            ->putJson("/api/admin/blog/posts/{$post->id}", [
                'title' => 'Xu hướng storefront 2026 cập nhật',
                'slug' => 'xu-huong-storefront-2026',
                'author_name' => 'CMBCORE Editorial',
                'blog_category_id' => $category->id,
                'excerpt' => 'Phiên bản cập nhật.',
                'content' => 'Nội dung mới.',
                'status' => BlogPost::STATUS_PUBLISHED,
                'is_featured' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Xu hướng storefront 2026 cập nhật')
            ->assertJsonPath('data.is_featured', false)
            ->assertJsonPath('data.category.slug', 'tin-tuc');

        $this->actingAs($user)
            ->deleteJson("/api/admin/blog/posts/{$post->id}")
            ->assertOk();

        self::assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }
}
