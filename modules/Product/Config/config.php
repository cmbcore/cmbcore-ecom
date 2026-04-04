<?php

declare(strict_types=1);

return [
    'per_page' => 15,
    'media' => [
        'disk' => 'public',
        'max_images' => 9,
        'max_videos' => 1,
        'max_video_size' => 50 * 1024 * 1024,
        'max_file_size' => 50 * 1024,
        'image_widths' => [200, 400, 800],
    ],
];
