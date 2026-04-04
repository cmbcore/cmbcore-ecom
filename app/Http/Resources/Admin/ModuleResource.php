<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Core\Module\Data\ModuleManifest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ModuleManifest $module */
        $module = $this->resource;

        return [
            'name' => $module->getNameKey() ? __($module->getNameKey()) : $module->getName(),
            'alias' => $module->getAlias(),
            'version' => $module->getVersion(),
            'enabled' => $module->isEnabled(),
            'dependencies' => $module->getDependencies(),
            'description' => $module->getDescriptionKey() ? __($module->getDescriptionKey()) : $module->getDescription(),
            'admin' => [
                'menu' => $module->getAdminMenu(),
                'pages' => $module->getAdminPages(),
            ],
            'path' => $module->getPath(),
        ];
    }
}
