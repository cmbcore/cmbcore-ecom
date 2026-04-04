<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Plugin\PluginManager;
use App\Core\Theme\ThemeManager;

class ModuleServiceProvider
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly PluginManager $pluginManager,
        private readonly ThemeManager $themeManager,
    ) {
    }

    public function register(): void
    {
        $this->moduleManager->registerEnabledProviders();
    }

    public function boot(): void
    {
        $this->themeManager->registerNamespaces();
        $this->pluginManager->bootActivePlugins();
    }
}