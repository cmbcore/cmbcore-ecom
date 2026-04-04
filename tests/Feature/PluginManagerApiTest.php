<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InstalledPlugin;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class PluginManagerApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $cleanupPaths = [];

    public function test_admin_user_can_list_enable_configure_and_disable_plugin(): void
    {
        $user = User::query()->create([
            'name' => 'Plugin Admin',
            'email' => 'plugins@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/plugins')
            ->assertOk()
            ->assertJsonFragment(['alias' => 'storefront-notice'])
            ->assertJsonFragment(['is_active' => false]);

        $this->actingAs($user)
            ->putJson('/api/admin/plugins/storefront-notice/enable')
            ->assertOk()
            ->assertJsonPath('data.plugin.is_active', true);

        $this->actingAs($user)
            ->putJson('/api/admin/plugins/storefront-notice/settings', [
                'settings' => [
                    'headline' => 'Ưu đãi flash sale',
                    'message' => 'Giảm 10% cho đơn đầu tiên trong ngày.',
                    'icon' => 'fa-solid fa-fire',
                    'tone' => 'neutral',
                    'enabled' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.settings.headline', 'Ưu đãi flash sale')
            ->assertJsonPath('data.settings.tone', 'neutral');

        $this->actingAs($user)
            ->getJson('/api/admin/modules')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Thông báo storefront'])
            ->assertJsonFragment([
                '/admin/plugins/storefront-notice/overview' => 'plugins/StorefrontNotice/resources/js/pages/StorefrontNoticeDashboard.jsx',
            ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ưu đãi flash sale')
            ->assertSee('Giảm 10% cho đơn đầu tiên trong ngày.');

        $this->actingAs($user)
            ->putJson('/api/admin/plugins/storefront-notice/disable')
            ->assertOk()
            ->assertJsonPath('data.plugin.is_active', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Ưu đãi flash sale');

        $plugin = InstalledPlugin::query()->where('alias', 'storefront-notice')->first();

        self::assertNotNull($plugin);
        self::assertFalse($plugin->is_active);
        self::assertSame('fa-solid fa-fire', $plugin->settings['icon'] ?? null);
    }

    public function test_admin_user_can_install_plugin_from_zip_package_and_run_migrations(): void
    {
        $user = User::query()->create([
            'name' => 'Plugin Installer',
            'email' => 'plugin-installer@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $pluginDirectory = base_path('plugins/DemoInstaller');
        $archivePath = $this->createZipPackage('demo-installer-plugin.zip', [
            'DemoInstaller/plugin.json' => json_encode([
                'name' => 'Demo Installer Plugin',
                'alias' => 'demo-installer',
                'version' => '1.0.0',
                'description' => 'ZIP installed plugin.',
                'author' => 'Tests',
                'install' => [
                    'migrations' => ['database/migrations'],
                    'auto_enable' => true,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'DemoInstaller/src/DemoInstallerPlugin.php' => <<<'PHP'
<?php

declare(strict_types=1);

namespace Plugins\DemoInstaller;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;

class DemoInstallerPlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    public function uninstall(): void
    {
    }
}
PHP,
            'DemoInstaller/database/migrations/2026_03_28_230000_create_demo_installer_logs_table.php' => <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_installer_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_installer_logs');
    }
};
PHP,
        ]);

        $this->cleanupPaths[] = $pluginDirectory;
        $this->cleanupPaths[] = $archivePath;

        $response = $this->actingAs($user)
            ->post('/api/admin/plugins/install', [
                'package' => new UploadedFile($archivePath, 'demo-installer-plugin.zip', 'application/zip', null, true),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.plugin.alias', 'demo-installer')
            ->assertJsonPath('data.plugin.is_active', true);

        self::assertDirectoryExists($pluginDirectory);
        self::assertTrue(InstalledPlugin::query()->where('alias', 'demo-installer')->where('is_active', true)->exists());
        self::assertTrue(Schema::hasTable('demo_installer_logs'));
    }

    protected function tearDown(): void
    {
        $files = app(Filesystem::class);

        foreach (array_reverse($this->cleanupPaths) as $path) {
            if (is_dir($path)) {
                $files->deleteDirectory($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        $this->cleanupPaths = [];

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function createZipPackage(string $filename, array $entries): string
    {
        $path = storage_path('app/testing/' . $filename);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $entryPath => $contents) {
            $zip->addFromString($entryPath, $contents);
        }

        $zip->close();

        return $path;
    }
}
