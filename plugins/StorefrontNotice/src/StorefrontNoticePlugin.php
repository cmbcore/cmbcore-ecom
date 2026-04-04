<?php

declare(strict_types=1);

namespace Plugins\StorefrontNotice;

use App\Core\Plugin\Contracts\PluginInterface;
use App\Core\Plugin\HookManager;
use App\Models\InstalledPlugin;
use Illuminate\Support\Facades\Log;
use Modules\Product\Models\Product;

class StorefrontNoticePlugin implements PluginInterface
{
    public function boot(HookManager $hooks): void
    {
        $hooks->register('theme.head', function (): string {
            if (! $this->isEnabled()) {
                return '';
            }

            return <<<'HTML'
<style>
    .plugin-storefront-notice {
        padding: 16px 0 0;
    }

    .plugin-storefront-notice__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-radius: 24px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.14), rgba(255, 255, 255, 0.94));
    }

    .plugin-storefront-notice__inner.is-neutral {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.18), rgba(255, 255, 255, 0.94));
    }

    .plugin-storefront-notice__body {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .plugin-storefront-notice__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 999px;
        background: rgba(15, 118, 110, 0.12);
        color: var(--theme-primary-color, #0f766e);
    }

    .plugin-storefront-notice__headline {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .plugin-storefront-notice__message {
        margin: 4px 0 0;
        color: #475569;
    }

    @media (max-width: 768px) {
        .plugin-storefront-notice__inner,
        .plugin-storefront-notice__body {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
HTML;
        });

        $hooks->register('theme.footer', function (): string {
            if (! $this->isEnabled()) {
                return '';
            }

            $settings = $this->settings();
            $headline = e((string) ($settings['headline'] ?? ''));
            $message = e((string) ($settings['message'] ?? ''));
            $icon = e((string) ($settings['icon'] ?? 'fa-solid fa-bolt'));
            $toneClass = ($settings['tone'] ?? 'accent') === 'neutral' ? 'is-neutral' : 'is-accent';

            return <<<HTML
<section class="plugin-storefront-notice" data-plugin="storefront-notice">
    <div class="container">
        <div class="plugin-storefront-notice__inner {$toneClass}">
            <div class="plugin-storefront-notice__body">
                <span class="plugin-storefront-notice__icon"><i class="{$icon}" aria-hidden="true"></i></span>
                <div>
                    <p class="plugin-storefront-notice__headline">{$headline}</p>
                    <p class="plugin-storefront-notice__message">{$message}</p>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
        });

        $hooks->register('product.created', function (Product $product): void {
            Log::info('StorefrontNotice plugin observed product.created.', [
                'plugin' => 'storefront-notice',
                'product_id' => $product->id,
            ]);
        });
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    public function uninstall(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $plugin = InstalledPlugin::query()->where('alias', 'storefront-notice')->first();

        return is_array($plugin?->settings) ? $plugin->settings : [];
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? true);
    }
}
