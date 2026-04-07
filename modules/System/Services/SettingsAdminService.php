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
                'icon' => $group['icon'] ?? null,
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
            // ── Thông tin cửa hàng ─────────────────────────────────────────
            [
                'key' => 'general',
                'label' => 'Thông tin cửa hàng',
                'icon' => 'fa-solid fa-store',
                'description' => 'Tên shop, logo, favicon và thông tin liên hệ hiển thị trên storefront.',
                'fields' => [
                    ['key' => 'site_name', 'label' => 'Tên cửa hàng', 'type' => 'text', 'default' => config('app.name'), 'description' => 'Hiển thị trên tab trình duyệt và email.'],
                    ['key' => 'logo', 'label' => 'Logo', 'type' => 'media', 'default' => null],
                    ['key' => 'favicon', 'label' => 'Favicon (ICO/PNG)', 'type' => 'media', 'default' => null],
                    ['key' => 'address', 'label' => 'Địa chỉ cửa hàng', 'type' => 'text', 'default' => null],
                    ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text', 'default' => null],
                    ['key' => 'contact_email', 'label' => 'Email liên hệ', 'type' => 'text', 'default' => config('mail.from.address')],
                    ['key' => 'facebook_url', 'label' => 'Facebook URL', 'type' => 'text', 'default' => null],
                    ['key' => 'zalo_url', 'label' => 'Zalo URL', 'type' => 'text', 'default' => null],
                    ['key' => 'youtube_url', 'label' => 'YouTube URL', 'type' => 'text', 'default' => null],
                ],
            ],

            // ── Tiền tệ & Đơn vị ──────────────────────────────────────────
            [
                'key' => 'currency',
                'label' => 'Tiền tệ & Đơn vị',
                'icon' => 'fa-solid fa-coins',
                'description' => 'Cấu hình đơn vị tiền tệ hiển thị trên frontend và admin.',
                'fields' => [
                    ['key' => 'currency_code', 'label' => 'Mã tiền tệ (ISO)', 'type' => 'text', 'default' => 'VND', 'description' => 'VD: VND, USD, EUR'],
                    ['key' => 'currency_symbol', 'label' => 'Ký hiệu', 'type' => 'text', 'default' => '₫'],
                    ['key' => 'currency_format', 'label' => 'Định dạng số', 'type' => 'text', 'default' => '#,##0 ₫', 'description' => 'VD: #,##0 ₫  hoặc  $#,##0.00'],
                    ['key' => 'decimal_separator', 'label' => 'Dấu thập phân', 'type' => 'text', 'default' => ','],
                    ['key' => 'thousands_separator', 'label' => 'Dấu phân nghìn', 'type' => 'text', 'default' => '.'],
                ],
            ],

            // ── Email / SMTP ───────────────────────────────────────────────
            [
                'key' => 'email',
                'label' => 'Email & SMTP',
                'icon' => 'fa-solid fa-envelope',
                'description' => 'Cấu hình máy chủ SMTP để gửi email thông báo đơn hàng, đặt lại mật khẩu…',
                'fields' => [
                    ['key' => 'mail_mailer', 'label' => 'Driver', 'type' => 'text', 'default' => config('mail.default'), 'description' => 'smtp / log / array / mailgun'],
                    ['key' => 'mail_host', 'label' => 'SMTP Host', 'type' => 'text', 'default' => config('mail.mailers.smtp.host'), 'description' => 'VD: smtp.gmail.com'],
                    ['key' => 'mail_port', 'label' => 'SMTP Port', 'type' => 'number', 'default' => (int) config('mail.mailers.smtp.port', 587), 'description' => '465 (SSL) hoặc 587 (TLS)'],
                    ['key' => 'mail_encryption', 'label' => 'Mã hoá', 'type' => 'text', 'default' => config('mail.mailers.smtp.encryption', 'tls'), 'description' => 'tls hoặc ssl'],
                    ['key' => 'mail_username', 'label' => 'SMTP Username', 'type' => 'text', 'default' => config('mail.mailers.smtp.username')],
                    ['key' => 'mail_password', 'label' => 'SMTP Password', 'type' => 'password', 'default' => config('mail.mailers.smtp.password')],
                    ['key' => 'mail_from_name', 'label' => 'Tên người gửi', 'type' => 'text', 'default' => config('mail.from.name')],
                    ['key' => 'mail_from_address', 'label' => 'Địa chỉ người gửi', 'type' => 'text', 'default' => config('mail.from.address')],
                ],
            ],

            // ── SEO ────────────────────────────────────────────────────────
            [
                'key' => 'seo',
                'label' => 'SEO & Analytics',
                'icon' => 'fa-solid fa-magnifying-glass-chart',
                'description' => 'Tiêu đề mặc định, meta description và tích hợp Google Analytics.',
                'fields' => [
                    ['key' => 'site_title', 'label' => 'Tiêu đề site', 'type' => 'text', 'default' => config('app.name'), 'description' => 'Hiển thị trên tab trình duyệt nếu trang không có tiêu đề riêng.'],
                    ['key' => 'meta_description', 'label' => 'Meta description mặc định', 'type' => 'text', 'default' => null],
                    ['key' => 'google_analytics_id', 'label' => 'Google Analytics ID', 'type' => 'text', 'default' => null, 'description' => 'VD: G-XXXXXXXXXX'],
                    ['key' => 'google_tag_manager_id', 'label' => 'Google Tag Manager ID', 'type' => 'text', 'default' => null, 'description' => 'VD: GTM-XXXXXXX'],
                    ['key' => 'facebook_pixel_id', 'label' => 'Facebook Pixel ID', 'type' => 'text', 'default' => null],
                    ['key' => 'robots_content', 'label' => 'Nội dung robots.txt', 'type' => 'textarea', 'default' => "User-agent: *\nAllow: /\nSitemap: /sitemap.xml"],
                ],
            ],

            // ── Order & Checkout ───────────────────────────────────────────
            [
                'key' => 'order',
                'label' => 'Đơn hàng & Thanh toán',
                'icon' => 'fa-solid fa-bag-shopping',
                'description' => 'Cấu hình quy trình đặt hàng và các tuỳ chọn liên quan.',
                'fields' => [
                    ['key' => 'order_prefix', 'label' => 'Tiền tố mã đơn', 'type' => 'text', 'default' => 'ODR', 'description' => 'Tiền tố thêm vào trước mã đơn hàng.'],
                    ['key' => 'guest_checkout', 'label' => 'Cho phép đặt hàng không cần tài khoản', 'type' => 'boolean', 'default' => true],
                    ['key' => 'stock_check_on_checkout', 'label' => 'Kiểm tra tồn kho khi checkout', 'type' => 'boolean', 'default' => true],
                    ['key' => 'auto_confirm_cod', 'label' => 'Tự động xác nhận đơn COD', 'type' => 'boolean', 'default' => false],
                    ['key' => 'min_order_amount', 'label' => 'Giá trị đơn hàng tối thiểu (₫)', 'type' => 'number', 'default' => 0],
                ],
            ],

            // ── Chính sách ─────────────────────────────────────────────────
            [
                'key' => 'policy',
                'label' => 'Trang chính sách',
                'icon' => 'fa-solid fa-file-shield',
                'description' => 'Slug của các trang chính sách hiển thị trên footer và checkout.',
                'fields' => [
                    ['key' => 'terms_page_slug', 'label' => 'Trang điều khoản sử dụng', 'type' => 'text', 'default' => 'dieu-khoan-su-dung'],
                    ['key' => 'privacy_page_slug', 'label' => 'Trang chính sách bảo mật', 'type' => 'text', 'default' => 'chinh-sach-bao-mat'],
                    ['key' => 'shipping_page_slug', 'label' => 'Trang chính sách giao hàng', 'type' => 'text', 'default' => 'chinh-sach-giao-hang'],
                    ['key' => 'return_page_slug', 'label' => 'Trang chính sách đổi trả', 'type' => 'text', 'default' => 'chinh-sach-doi-tra'],
                    ['key' => 'warranty_page_slug', 'label' => 'Trang chính sách bảo hành', 'type' => 'text', 'default' => 'chinh-sach-bao-hanh'],
                ],
            ],
        ];
    }
}
