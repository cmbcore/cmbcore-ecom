<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_crud_categories_and_fetch_tree(): void
    {
        $user = User::query()->create([
            'name' => 'Category Admin',
            'email' => 'category@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $rootResponse = $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'name' => 'Electronics',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.level', 1);

        $rootId = (int) $rootResponse->json('data.id');

        $childResponse = $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'parent_id' => $rootId,
                'name' => 'Phones',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.level', 2);

        $childId = (int) $childResponse->json('data.id');

        $grandChildResponse = $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'parent_id' => $childId,
                'name' => 'Android',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.level', 3);

        $grandChildId = (int) $grandChildResponse->json('data.id');

        $this->actingAs($user)
            ->getJson("/api/admin/categories/{$childId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Phones');

        $this->actingAs($user)
            ->putJson("/api/admin/categories/{$childId}", [
                'parent_id' => $rootId,
                'name' => 'Smartphones',
                'status' => 'active',
                'position' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Smartphones')
            ->assertJsonPath('data.position', 2);

        $this->actingAs($user)
            ->getJson('/api/admin/categories/tree')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Electronics')
            ->assertJsonPath('data.0.children.0.name', 'Smartphones')
            ->assertJsonPath('data.0.children.0.children.0.id', $grandChildId);

        $this->actingAs($user)
            ->deleteJson("/api/admin/categories/{$childId}")
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/admin/categories/tree')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.children', []);
    }

    public function test_admin_user_cannot_create_level_four_category(): void
    {
        $user = User::query()->create([
            'name' => 'Category Admin',
            'email' => 'category-depth@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $rootId = (int) $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'name' => 'Fashion',
                'status' => 'active',
            ])
            ->json('data.id');

        $childId = (int) $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'name' => 'Men',
                'parent_id' => $rootId,
                'status' => 'active',
            ])
            ->json('data.id');

        $grandChildId = (int) $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'name' => 'T-Shirts',
                'parent_id' => $childId,
                'status' => 'active',
            ])
            ->json('data.id');

        $this->actingAs($user)
            ->postJson('/api/admin/categories', [
                'name' => 'Cotton',
                'parent_id' => $grandChildId,
                'status' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }
}
