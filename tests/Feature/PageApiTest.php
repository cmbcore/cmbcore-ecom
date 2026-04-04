<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Tests\TestCase;

class PageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_manage_pages_and_load_templates(): void
    {
        $user = User::query()->create([
            'name' => 'Page Admin',
            'email' => 'pages@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/pages/templates')
            ->assertOk()
            ->assertJsonFragment(['name' => 'default'])
            ->assertJsonFragment(['type' => 'hero']);

        $this->actingAs($user)
            ->postJson('/api/admin/pages', [
                'title' => 'Chinh sach giao hang',
                'excerpt' => 'Thong tin giao hang co ban.',
                'content' => 'Noi dung day du cua chinh sach giao hang.',
                'content_blocks' => [
                    [
                        'type' => 'cta',
                        'props' => [
                            'title' => 'Can ho tro them?',
                            'body' => 'Lien he de duoc tu van nhanh.',
                            'primary_label' => 'Lien he',
                            'primary_url' => '/gioi-thieu',
                        ],
                    ],
                ],
                'template' => 'default',
                'status' => Page::STATUS_PUBLISHED,
                'meta_title' => 'Chinh sach giao hang',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'chinh-sach-giao-hang')
            ->assertJsonPath('data.template', 'default')
            ->assertJsonPath('data.content_blocks.0.type', 'cta');

        /** @var Page $page */
        $page = Page::query()->firstOrFail();
        self::assertStringContainsString('[cmb_block type="cta"', (string) $page->content);

        $this->actingAs($user)
            ->getJson('/api/admin/pages')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Chinh sach giao hang']);

        $this->actingAs($user)
            ->putJson("/api/admin/pages/{$page->id}", [
                'title' => 'Chinh sach giao hang cập nhật',
                'slug' => 'chinh-sach-giao-hang',
                'excerpt' => 'Noi dung da cập nhật.',
                'content' => 'Chi tiet cập nhật.',
                'content_blocks' => [
                    [
                        'type' => 'hero',
                        'props' => [
                            'title' => 'Hero cập nhật',
                            'body' => 'Mo ta hero cập nhật.',
                        ],
                    ],
                ],
                'template' => 'default',
                'status' => Page::STATUS_PUBLISHED,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Chinh sach giao hang cập nhật')
            ->assertJsonPath('data.content_blocks.0.type', 'hero');

        $this->actingAs($user)
            ->deleteJson("/api/admin/pages/{$page->id}")
            ->assertOk();

        self::assertSoftDeleted('pages', ['id' => $page->id]);
    }
}
