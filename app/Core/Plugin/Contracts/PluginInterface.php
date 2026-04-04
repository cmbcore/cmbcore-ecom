<?php

declare(strict_types=1);

namespace App\Core\Plugin\Contracts;

use App\Core\Plugin\HookManager;

interface PluginInterface
{
    public function boot(HookManager $hooks): void;

    public function activate(): void;

    public function deactivate(): void;

    public function uninstall(): void;
}