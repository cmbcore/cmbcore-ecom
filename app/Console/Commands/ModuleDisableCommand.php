<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Module\ModuleManager;
use Illuminate\Console\Command;
use Throwable;

class ModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {alias : Alias module}';

    protected $description = 'Tắt một module';

    public function handle(ModuleManager $moduleManager): int
    {
        try {
            $moduleManager->disable((string) $this->argument('alias'));
            $this->components->info('Đã tắt module.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
