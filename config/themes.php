<?php

declare(strict_types=1);

return [
    'scan_paths' => [
        base_path(env('THEMES_PATH', 'themes')),
    ],
    'default' => env('DEFAULT_THEME', 'cmbcore'),
];

