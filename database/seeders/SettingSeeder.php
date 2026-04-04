<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(SettingService $settingService): void
    {
        $settingService->sync([
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'CMBCORE Shop',
                'type' => 'text',
                'label' => 'Tên website',
                'description' => 'Tên hiển thị mặc định của website.',
                'position' => 10,
            ],
            [
                'group' => 'localization',
                'key' => 'default_locale',
                'value' => 'vi',
                'type' => 'text',
                'label' => 'Ngôn ngữ mặc định',
                'description' => 'Ngôn ngữ dùng cho cả storefront và admin khi chưa có lựa chọn riêng.',
                'position' => 10,
            ],
            [
                'group' => 'localization',
                'key' => 'supported_locales',
                'value' => ['vi', 'en'],
                'type' => 'json',
                'label' => 'Ngôn ngữ được bật',
                'description' => 'Danh sách locale đang cho phép sử dụng trong hệ thống.',
                'position' => 20,
            ],
        ]);
    }
}
