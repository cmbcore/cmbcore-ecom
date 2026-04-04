<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_fetch_localization_payload(): void
    {
        $this->getJson('/api/admin/localization')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_locale', 'vi')
            ->assertJsonPath('data.supported_locales.0.code', 'vi');
    }

    public function test_guest_can_switch_locale_and_receive_cookie(): void
    {
        $this->putJson('/api/admin/localization', ['locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.current_locale', 'en')
            ->assertPlainCookie('cmbcore_locale', 'en');
    }

    public function test_modules_api_returns_localized_menu_labels(): void
    {
        $user = User::query()->create([
            'name' => 'Localization Admin',
            'email' => 'localization@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withHeader('X-Locale', 'en')
            ->getJson('/api/admin/modules')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Dashboard'])
            ->assertJsonFragment(['label' => 'Categories'])
            ->assertJsonFragment(['name' => 'Categories']);
    }
}
