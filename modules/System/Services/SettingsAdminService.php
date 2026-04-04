<?php

declare(strict_types=1);

namespace Modules\System\Services;

use App\Services\SettingService;

class SettingsAdminService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function schema(): array
    {
        $schema = $this->defaultSchema();

        foreach ($schema as $group) {
            $definitions = [];

            foreach ((array) ($group['fields'] ?? []) as $index => $field) {
                if (! is_array($field) || ! isset($field['key'])) {
                    continue;
                }

                $definitions[] = [
                    'group' => (string) $group['key'],
                    'key' => (string) $field['key'],
                    'value' => $field['default'] ?? null,
                    'type' => (string) ($field['type'] ?? 'text'),
                    'label' => (string) ($field['label'] ?? $field['key']),
                    'description' => $field['description'] ?? null,
                    'options' => $field['options'] ?? null,
                    'position' => $index,
                ];
            }

            $this->settingService->sync($definitions);
        }

        return array_map(function (array $group): array {
            return [
                'key' => $group['key'],
                'label' => $group['label'],
                'description' => $group['description'] ?? null,
                'fields' => $this->settingService->definitions((string) $group['key']),
            ];
        }, $schema);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payload(): array
    {
        return $this->schema();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function update(array $payload): array
    {
        foreach ($this->schema() as $group) {
            $groupKey = (string) ($group['key'] ?? '');

            if ($groupKey === '' || ! array_key_exists($groupKey, $payload) || ! is_array($payload[$groupKey])) {
                continue;
            }

            $this->settingService->saveGroup($groupKey, $payload[$groupKey]);
        }

        return $this->payload();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultSchema(): array
    {
        return [
            [
                'key' => 'general',
                'label' => 'Thong tin cua hang',
                'description' => 'Ten shop, logo, favicon va thong tin lien he.',
                'fields' => [
                    ['key' => 'site_name', 'label' => 'Ten shop', 'type' => 'text', 'default' => config('app.name')],
                    ['key' => 'logo', 'label' => 'Logo', 'type' => 'media', 'default' => null],
                    ['key' => 'favicon', 'label' => 'Favicon', 'type' => 'media', 'default' => null],
                    ['key' => 'address', 'label' => 'Dia chi', 'type' => 'text', 'default' => null],
                    ['key' => 'phone', 'label' => 'So dien thoai', 'type' => 'text', 'default' => null],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'default' => config('mail.from.address')],
                ],
            ],
            [
                'key' => 'currency',
                'label' => 'Tien te',
                'fields' => [
                    ['key' => 'currency_code', 'label' => 'Ma tien te', 'type' => 'text', 'default' => 'VND'],
                    ['key' => 'currency_symbol', 'label' => 'Ky hieu', 'type' => 'text', 'default' => '₫'],
                    ['key' => 'currency_format', 'label' => 'Dinh dang', 'type' => 'text', 'default' => '#,##0 ₫'],
                ],
            ],
            [
                'key' => 'email',
                'label' => 'SMTP va email',
                'fields' => [
                    ['key' => 'mail_mailer', 'label' => 'Mailer', 'type' => 'text', 'default' => config('mail.default')],
                    ['key' => 'mail_host', 'label' => 'SMTP host', 'type' => 'text', 'default' => config('mail.mailers.smtp.host')],
                    ['key' => 'mail_port', 'label' => 'SMTP port', 'type' => 'number', 'default' => config('mail.mailers.smtp.port')],
                    ['key' => 'mail_username', 'label' => 'SMTP user', 'type' => 'text', 'default' => config('mail.mailers.smtp.username')],
                    ['key' => 'mail_password', 'label' => 'SMTP password', 'type' => 'text', 'default' => config('mail.mailers.smtp.password')],
                    ['key' => 'mail_from_name', 'label' => 'Ten nguoi gui', 'type' => 'text', 'default' => config('mail.from.name')],
                    ['key' => 'mail_from_address', 'label' => 'Dia chi nguoi gui', 'type' => 'text', 'default' => config('mail.from.address')],
                ],
            ],
            [
                'key' => 'seo',
                'label' => 'SEO chung',
                'fields' => [
                    ['key' => 'site_title', 'label' => 'Site title', 'type' => 'text', 'default' => config('app.name')],
                    ['key' => 'meta_description', 'label' => 'Meta description', 'type' => 'text', 'default' => null],
                    ['key' => 'google_analytics_id', 'label' => 'Google Analytics ID', 'type' => 'text', 'default' => null],
                    ['key' => 'robots_content', 'label' => 'Robots.txt', 'type' => 'text', 'default' => "User-agent: *\nAllow: /\nSitemap: /sitemap.xml"],
                ],
            ],
            [
                'key' => 'policy',
                'label' => 'Chinh sach',
                'fields' => [
                    ['key' => 'shipping_page_slug', 'label' => 'Trang giao hang', 'type' => 'text', 'default' => 'chinh-sach-giao-hang'],
                    ['key' => 'return_page_slug', 'label' => 'Trang đổi trả', 'type' => 'text', 'default' => 'chinh-sach-doi-tra'],
                    ['key' => 'warranty_page_slug', 'label' => 'Trang bao hanh', 'type' => 'text', 'default' => 'chinh-sach-bao-hanh'],
                ],
            ],
        ];
    }
}
