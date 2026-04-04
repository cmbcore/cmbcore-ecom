<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Module\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ModuleMigrateCommand extends Command
{
    protected $signature = 'module:migrate {alias : Alias module}';

    protected $description = 'Chạy migration cho một module';

    public function handle(ModuleManager $moduleManager): int
    {
        try {
            $moduleManager->migrate((string) $this->argument('alias'));
            $this->line(Artisan::output());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
