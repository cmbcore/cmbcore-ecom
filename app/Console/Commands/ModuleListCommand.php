<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Module\Data\ModuleManifest;
use App\Core\Module\ModuleManager;
use Illuminate\Console\Command;

class ModuleListCommand extends Command
{
    protected $signature = 'module:list {--enabled : Chỉ hiển thị module đang bật}';

    protected $description = 'Liệt kê các module đã nhận diện';

    public function handle(ModuleManager $moduleManager): int
    {
        $modules = $this->option('enabled')
            ? $moduleManager->getEnabled()
            : $moduleManager->getAll();

        $rows = $modules->map(static function (ModuleManifest $module): array {
            return [
                $module->getAlias(),
                $module->getName(),
                $module->getVersion(),
                $module->isEnabled() ? 'đang bật' : 'đang tắt',
                implode(', ', $module->getDependencies()),
            ];
        })->all();

        $this->table(['Alias', 'Tên', 'Phiên bản', 'Trạng thái', 'Phụ thuộc'], $rows);

        return self::SUCCESS;
    }
}
