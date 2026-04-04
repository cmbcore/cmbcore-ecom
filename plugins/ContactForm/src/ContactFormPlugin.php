<?php

declare(strict_types=1);

namespace Plugins\ContactForm;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Plugins\ContactForm\Models\ContactForm;

class ContactFormPlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
        // Register block definition for the Puck page builder
        $hooks->filter('page.block_definitions', function (array $blocks): array {
            // Load available forms as select options for the block
            $formOptions = $this->formOptions();

            $blocks[] = [
                'type'     => 'ContactForm',
                'label'    => 'Form liên hệ',
                'category' => 'Plugin',
                'source'   => 'plugin',
                'fields'   => [
                    // --- Form selection ---
                    ['key' => 'form_id', 'label' => 'Chọn form', 'type' => 'select',
                     'options' => $formOptions, 'default' => ''],

                    // --- Style: Layout ---
                    ['key' => 'layout_width', 'label' => 'Độ rộng', 'type' => 'select', 'default' => 'contained',
                     'options' => [
                         ['value' => 'full',      'label' => 'Toàn màn hình'],
                         ['value' => 'contained', 'label' => 'Căn giữa (800px)'],
                         ['value' => 'narrow',    'label' => 'Hẹp (520px)'],
                     ]],
                    ['key' => 'padding', 'label' => 'Khoảng đệm', 'type' => 'select', 'default' => 'md',
                     'options' => [
                         ['value' => 'sm', 'label' => 'Nhỏ'], ['value' => 'md', 'label' => 'Vừa'],
                         ['value' => 'lg', 'label' => 'Lớn'], ['value' => 'xl', 'label' => 'Rất lớn'],
                     ]],

                    // --- Style: Colors ---
                    ['key' => 'bg_color',       'label' => 'Màu nền (hex)',          'type' => 'text', 'default' => '#ffffff'],
                    ['key' => 'text_color',     'label' => 'Màu chữ (hex)',          'type' => 'text', 'default' => '#1f2937'],
                    ['key' => 'label_color',    'label' => 'Màu nhãn field (hex)',   'type' => 'text', 'default' => '#374151'],
                    ['key' => 'input_bg',       'label' => 'Màu nền input (hex)',    'type' => 'text', 'default' => '#f9fafb'],
                    ['key' => 'input_border',   'label' => 'Màu viền input (hex)',   'type' => 'text', 'default' => '#d1d5db'],
                    ['key' => 'btn_color',      'label' => 'Màu nút gửi (hex)',      'type' => 'text', 'default' => '#1677ff'],
                    ['key' => 'btn_text_color', 'label' => 'Màu chữ nút (hex)',      'type' => 'text', 'default' => '#ffffff'],

                    // --- Style: Shape ---
                    ['key' => 'border_radius', 'label' => 'Bo góc', 'type' => 'select', 'default' => 'md',
                     'options' => [
                         ['value' => 'none', 'label' => 'Vuông'], ['value' => 'sm', 'label' => 'Bo nhẹ'],
                         ['value' => 'md',   'label' => 'Bo vừa'], ['value' => 'lg', 'label' => 'Bo nhiều'],
                         ['value' => 'pill', 'label' => 'Pill'],
                     ]],
                    ['key' => 'shadow', 'label' => 'Bóng đổ', 'type' => 'select', 'default' => 'none',
                     'options' => [
                         ['value' => 'none', 'label' => 'Không'], ['value' => 'sm', 'label' => 'Nhẹ'],
                         ['value' => 'md',   'label' => 'Vừa'],   ['value' => 'lg', 'label' => 'Nhiều'],
                     ]],

                    // --- Style: Button ---
                    ['key' => 'btn_style', 'label' => 'Kiểu nút', 'type' => 'select', 'default' => 'filled',
                     'options' => [
                         ['value' => 'filled',  'label' => 'Filled'],
                         ['value' => 'outline',  'label' => 'Outline'],
                         ['value' => 'ghost',    'label' => 'Ghost'],
                     ]],

                    // --- Style: Animation ---
                    ['key' => 'animation', 'label' => 'Hiệu ứng xuất hiện', 'type' => 'select', 'default' => 'none',
                     'options' => [
                         ['value' => 'none',       'label' => 'Không'],
                         ['value' => 'fade-in',    'label' => 'Fade In'],
                         ['value' => 'slide-up',   'label' => 'Slide Up'],
                         ['value' => 'slide-left', 'label' => 'Slide từ trái'],
                         ['value' => 'zoom-in',    'label' => 'Zoom In'],
                     ]],
                ],
            ];

            return $blocks;
        });

        // Register view namespace
        View::addNamespace('contactform', base_path('plugins/ContactForm/resources/views'));

        // Register frontend & admin routes
        Route::middleware('web')->group(base_path('plugins/ContactForm/src/routes.php'));
        require base_path('plugins/ContactForm/src/routes_api.php');
    }

    public function activate(): void
    {
        $migrations = base_path('plugins/ContactForm/database/migrations');

        if (is_dir($migrations)) {
            Artisan::call('migrate', [
                '--path'  => 'plugins/ContactForm/database/migrations',
                '--force' => true,
            ]);
        }
    }

    public function deactivate(): void
    {
    }

    public function uninstall(): void
    {
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function formOptions(): array
    {
        try {
            if (! Schema::hasTable('contact_forms')) {
                return [['value' => '', 'label' => '— Cần tạo form trước —']];
            }

            $forms = ContactForm::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ContactForm $f): array => ['value' => (string) $f->id, 'label' => $f->name])
                ->all();

            return $forms !== []
                ? $forms
                : [['value' => '', 'label' => '— Chưa có form nào —']];
        } catch (\Throwable) {
            return [['value' => '', 'label' => '— Cần tạo form trước —']];
        }
    }
}
