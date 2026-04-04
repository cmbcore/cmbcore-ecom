<?php

declare(strict_types=1);

return [
    'default_locale' => env('APP_LOCALE', 'vi'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'cookie_name' => env('APP_LOCALE_COOKIE', 'cmbcore_locale'),
    'supported' => [
        'vi' => [
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'native_name' => 'Tiếng Việt',
            'icon' => 'fa-solid fa-earth-asia',
        ],
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'icon' => 'fa-solid fa-earth-americas',
        ],
    ],
];
