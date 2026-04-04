<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Module\ModuleServiceProvider as CoreModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app(CoreModuleServiceProvider::class)->register();
    }

    public function boot(): void
    {
        app(CoreModuleServiceProvider::class)->boot();
    }
}
