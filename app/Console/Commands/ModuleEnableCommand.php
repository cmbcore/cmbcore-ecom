<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Module\ModuleManager;
use Illuminate\Console\Command;
use Throwable;

class ModuleEnableCommand extends Command
{
    protected $signature = 'module:enable {alias : Alias module}';

    protected $description = 'Bật một module';

    public function handle(ModuleManager $moduleManager): int
    {
        try {
            $moduleManager->enable((string) $this->argument('alias'));
            $this->components->info('Đã bật module.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
