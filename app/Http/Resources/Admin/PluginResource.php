<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Core\Plugin\Data\PluginManifest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PluginManifest $plugin */
        $plugin = $this->resource;

        return [
            'name' => $plugin->getName(),
            'alias' => $plugin->getAlias(),
            'version' => $plugin->getVersion(),
            'description' => $plugin->getDescription(),
            'author' => $plugin->getAuthor(),
            'url' => $plugin->getUrl(),
            'requires' => $plugin->getRequires(),
            'settings' => $plugin->getSettings(),
            'hooks' => [
                'listens' => $plugin->getListens(),
            ],
            'admin' => [
                'menu' => $plugin->getAdminMenu(),
                'pages' => $plugin->getAdminPages(),
            ],
            'path' => $plugin->getPath(),
        ];
    }
}
