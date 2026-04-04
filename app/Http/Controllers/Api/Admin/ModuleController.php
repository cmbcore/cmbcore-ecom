<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Core\Module\ModuleManager;
use App\Core\Plugin\PluginManager;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ModuleResource;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    public function __invoke(ModuleManager $moduleManager, PluginManager $pluginManager): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'modules' => ModuleResource::collection($moduleManager->getAll())->resolve(),
                'menus' => $moduleManager->getAdminMenus(),
                'pages' => array_merge(
                    $moduleManager->getAdminPages(),
                    $pluginManager->getAdminPages(),
                ),
            ],
            'message' => __('admin.system.modules_loaded'),
        ]);
    }
}
