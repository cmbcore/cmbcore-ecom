<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_fetch_modules_api(): void
    {
        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'modules@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/modules')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['alias' => 'system'])
            ->assertJsonFragment(['alias' => 'category'])
            ->assertJsonFragment(['alias' => 'product'])
            ->assertJsonFragment(['alias' => 'blog'])
            ->assertJsonFragment(['alias' => 'page'])
            ->assertJsonFragment(['alias' => 'theme-manager'])
            ->assertJsonFragment(['alias' => 'plugin-manager'])
            ->assertJsonFragment(['label' => 'Bảng điều khiển'])
            ->assertJsonFragment(['label' => 'Danh mục'])
            ->assertJsonFragment(['label' => 'Sản phẩm'])
            ->assertJsonFragment(['label' => 'Bài viết'])
            ->assertJsonFragment(['label' => 'Trang tĩnh'])
            ->assertJsonFragment(['label' => 'Giao diện'])
            ->assertJsonFragment(['label' => 'Plugin']);
    }
}
