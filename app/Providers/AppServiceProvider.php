<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Localization\LocalizationManager;
use App\Core\Plugin\HookManager;
use App\Core\Plugin\PluginManager;
use App\Core\Module\ModuleManager;
use App\Core\Theme\ThemeManager;
use App\Core\Theme\ThemeViewContext;
use App\Services\SettingService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HookManager::class);
        $this->app->singleton(LocalizationManager::class);
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(ThemeViewContext::class);
        $this->app->singleton(\App\Services\StorefrontDataReadiness::class);
        $this->app->singleton(\App\Core\Module\ModuleServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('hook', static function (string $expression): string {
            return "<?php echo app(\\" . HookManager::class . "::class)->render({$expression}); ?>";
        });
    }
}
