<?php

declare(strict_types=1);

return [
    'core_version' => env('APP_CORE_VERSION', '1.0.0'),
    'scan_paths' => [
        base_path(env('MODULES_PATH', 'modules')),
    ],
    'status_file' => storage_path('app/system/modules.json'),
    'admin_roles' => ['admin', 'editor'],
];
