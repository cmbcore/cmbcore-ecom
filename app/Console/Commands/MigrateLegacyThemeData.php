<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chạy MỘT LẦN để migrate dữ liệu legacy (rhysman → cmbcore) ra khỏi DB.
 *
 * Sau khi chạy thành công, dữ liệu trong bảng installed_themes sẽ được làm sạch
 * và ThemeManager không còn cần giữ logic migration trong core.
 *
 * Cách dùng:
 *   php artisan theme:migrate-legacy
 *   php artisan theme:migrate-legacy --dry-run   (xem trước, không thay đổi)
 */
class MigrateLegacyThemeData extends Command
{
    protected $signature   = 'theme:migrate-legacy {--dry-run : Xem trước thay đổi mà không ghi vào DB}';
    protected $description = 'Migrate dữ liệu theme legacy (rhysman → cmbcore) trong database (chạy một lần)';

    /** Map alias cũ → alias mới */
    private const ALIAS_MAP = [
        'rhysman' => 'cmbcore',
    ];

    /**
     * Map URL đầy đủ của rhysman.vn → đường dẫn asset local của cmbcore.
     *
     * @var array<string, string>
     */
    private const ASSET_URL_MAP = [
        'https://rhysman.vn/wp-content/uploads/2026/01/BANNER-WEB-PC.png'
            => '/theme-assets/cmbcore/demo/hero-slide-1-desktop.png',
        'https://rhysman.vn/wp-content/smush-webp/2026/01/BANNER-WEB-MOBIE.png.webp'
            => '/theme-assets/cmbcore/demo/hero-slide-1-mobile.webp',
        'https://rhysman.vn/wp-content/smush-webp/2025/10/BANNER-WEB-Box-thuong.png.webp'
            => '/theme-assets/cmbcore/demo/hero-slide-2-desktop.webp',
        'https://rhysman.vn/wp-content/smush-webp/2025/10/BANNER-WEB-Box-thuong-M.png.webp'
            => '/theme-assets/cmbcore/demo/hero-slide-2-mobile.webp',
        'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png'
            => '/theme-assets/cmbcore/demo/quote-card-1.png',
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg'
            => '/theme-assets/cmbcore/demo/quote-card-2.jpg',
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien-cho-nam-gioi_7e4977521e4849eca099d725ce266a0d.jpg'
            => '/theme-assets/cmbcore/demo/quote-card-3.jpg',
        'https://rhysman.vn/wp-content/smush-webp/2025/06/logo-bct.png.webp'
            => '/theme-assets/cmbcore/demo/footer-badge.webp',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — không có thay đổi nào được ghi vào database.');
            $this->newLine();
        }

        if (! Schema::hasTable('installed_themes')) {
            $this->error('Bảng installed_themes chưa tồn tại. Chạy migrations trước.');
            return self::FAILURE;
        }

        $this->info('=== Theme Legacy Migration ===');
        $this->newLine();

        $aliasChanges   = $this->migrateAliases($dryRun);
        $settingChanges = $this->migrateSettings($dryRun);

        $this->newLine();
        $this->info("Hoàn thành: {$aliasChanges} alias đổi, {$settingChanges} theme settings được làm sạch.");

        if ($dryRun) {
            $this->warn('(Dry run — không có gì được lưu)');
        }

        return self::SUCCESS;
    }

    // ── Migrate alias (rhysman → cmbcore) ────────────────────────────────────

    private function migrateAliases(bool $dryRun): int
    {
        $changed = 0;

        foreach (self::ALIAS_MAP as $legacyAlias => $newAlias) {
            $legacyRow = DB::table('installed_themes')->where('alias', $legacyAlias)->first();

            if (! $legacyRow) {
                $this->line("  [alias] '{$legacyAlias}' không tìm thấy trong DB — bỏ qua.");
                continue;
            }

            $newRow = DB::table('installed_themes')->where('alias', $newAlias)->first();

            if ($newRow) {
                // New alias đã tồn tại → chuyển is_active nếu cần, xoá row cũ
                $this->line("  [alias] '{$legacyAlias}' → '{$newAlias}' (merge, xoá row cũ)");

                if (! $dryRun) {
                    if ($legacyRow->is_active && ! $newRow->is_active) {
                        DB::table('installed_themes')->where('alias', $newAlias)->update(['is_active' => true]);
                    }

                    DB::table('installed_themes')->where('alias', $legacyAlias)->delete();
                }
            } else {
                // New alias chưa tồn tại → đổi tên row
                $this->line("  [alias] '{$legacyAlias}' → '{$newAlias}' (rename)");

                if (! $dryRun) {
                    DB::table('installed_themes')
                        ->where('alias', $legacyAlias)
                        ->update(['alias' => $newAlias, 'updated_at' => now()]);
                }
            }

            $changed++;
        }

        return $changed;
    }

    // ── Migrate stored theme settings ────────────────────────────────────────

    private function migrateSettings(bool $dryRun): int
    {
        $themes  = DB::table('installed_themes')->get();
        $changed = 0;

        foreach ($themes as $theme) {
            if (! is_string($theme->settings)) {
                continue;
            }

            $original = $theme->settings;
            $migrated = $this->migrateSettingsJson($original);

            if ($migrated === $original) {
                $this->line("  [settings] '{$theme->alias}' — không có thay đổi.");
                continue;
            }

            $this->line("  [settings] '{$theme->alias}' — cập nhật (legacy paths/URLs → cmbcore).");

            if (! $dryRun) {
                DB::table('installed_themes')
                    ->where('alias', $theme->alias)
                    ->update(['settings' => $migrated, 'updated_at' => now()]);
            }

            $changed++;
        }

        return $changed;
    }

    private function migrateSettingsJson(string $json): string
    {
        // Thay thế path /theme-assets/rhysman/ → /theme-assets/cmbcore/
        $result = str_replace('/theme-assets/rhysman/', '/theme-assets/cmbcore/', $json);

        // Thay thế URL đầy đủ của rhysman.vn → local path
        foreach (self::ASSET_URL_MAP as $remoteUrl => $localPath) {
            $result = str_replace($remoteUrl, $localPath, $result);
        }

        return $result;
    }
}
