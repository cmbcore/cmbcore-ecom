<?php

declare(strict_types=1);

namespace Modules\Address\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Address\Models\Commune;
use Modules\Address\Models\Province;
use Throwable;

/**
 * Artisan command: address:sync
 *
 * Fetches provinces + communes from the CAS address-kit API and upserts
 * them into the local database.  Runs incrementally — existing rows are
 * updated in-place so the command is safe to re-run at any time.
 *
 * Usage:
 *   php artisan address:sync              # sync all provinces
 *   php artisan address:sync --province=01 # sync only HN communes
 */
class SyncAddressCommand extends Command
{
    protected $signature = 'address:sync
                            {--province= : Chỉ sync xã/phường của tỉnh có code này}
                            {--force : Xóa toàn bộ dữ liệu cũ trước khi sync}';

    protected $description = 'Cào dữ liệu tỉnh/thành phố và xã/phường từ API CAS address-kit và lưu vào database';

    private const BASE_URL = 'https://production.cas.so/address-kit/2025-07-01';

    private const TIMEOUT = 30;

    private const CHUNK_SIZE = 200;

    public function handle(): int
    {
        if ($this->option('force')) {
            $this->warnForceWipe();
        }

        // ── 1. Sync provinces ────────────────────────────────────────────
        $this->info('📡 Đang tải danh sách tỉnh/thành phố...');
        $provinces = $this->fetchProvinces();

        if ($provinces === null) {
            return self::FAILURE;
        }

        $this->syncProvinces($provinces);

        // ── 2. Sync communes per province ────────────────────────────────
        $filterCode = $this->option('province');
        $targets = $filterCode
            ? array_filter($provinces, static fn ($p): bool => $p['code'] === $filterCode)
            : $provinces;

        if ($filterCode && $targets === []) {
            $this->error("Không tìm thấy tỉnh có code: {$filterCode}");

            return self::FAILURE;
        }

        $total = count($targets);
        $this->info("🏘️  Đang sync xã/phường cho {$total} tỉnh/thành...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_values($targets) as $province) {
            $this->syncCommunesForProvince($province);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Sync hoàn tất!');
        $this->printStats();

        return self::SUCCESS;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, string>>|null
     */
    private function fetchProvinces(): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::BASE_URL . '/provinces');

            if (! $response->ok()) {
                $this->error("API trả về lỗi {$response->status()} khi lấy danh sách tỉnh.");

                return null;
            }

            $data = $response->json();

            return is_array($data['provinces'] ?? null) ? $data['provinces'] : null;
        } catch (Throwable $e) {
            $this->error('Lỗi kết nối API tỉnh: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param  array<int, array<string, string>>  $provinces
     */
    private function syncProvinces(array $provinces): void
    {
        $rows = array_map(static fn ($p): array => [
            'code' => (string) ($p['code'] ?? ''),
            'name' => trim((string) ($p['name'] ?? '')),
            'english_name' => trim((string) ($p['englishName'] ?? '')),
            'administrative_level' => trim((string) ($p['administrativeLevel'] ?? '')),
            'decree' => trim((string) ($p['decree'] ?? '')),
            'created_at' => now(),
            'updated_at' => now(),
        ], $provinces);

        $rows = array_filter($rows, static fn ($r): bool => $r['code'] !== '');

        foreach (array_chunk(array_values($rows), self::CHUNK_SIZE) as $chunk) {
            Province::query()->upsert($chunk, ['code'], [
                'name', 'english_name', 'administrative_level', 'decree', 'updated_at',
            ]);
        }

        $this->line('  → ' . count($rows) . ' tỉnh/thành đã upsert.');
    }

    /**
     * @param  array<string, string>  $province
     */
    private function syncCommunesForProvince(array $province): void
    {
        $code = (string) ($province['code'] ?? '');

        if ($code === '') {
            return;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::BASE_URL . "/provinces/{$code}/communes");

            if (! $response->ok()) {
                $this->newLine();
                $this->warn("  ⚠ Tỉnh {$code}: API trả về {$response->status()}, bỏ qua.");

                return;
            }

            $data = $response->json();
            $communes = is_array($data['communes'] ?? null) ? $data['communes'] : [];

            if ($communes === []) {
                return;
            }

            $rows = array_map(static fn ($c): array => [
                'code' => (string) ($c['code'] ?? ''),
                'name' => trim(preg_replace('/\s+/', ' ', (string) ($c['name'] ?? '')) ?? ''),
                'english_name' => trim((string) ($c['englishName'] ?? '')),
                'administrative_level' => trim((string) ($c['administrativeLevel'] ?? '')),
                'province_code' => (string) ($c['provinceCode'] ?? $code),
                'decree' => trim((string) ($c['decree'] ?? '')),
                'created_at' => now(),
                'updated_at' => now(),
            ], $communes);

            $rows = array_filter($rows, static fn ($r): bool => $r['code'] !== '' && $r['name'] !== '');

            foreach (array_chunk(array_values($rows), self::CHUNK_SIZE) as $chunk) {
                Commune::query()->upsert($chunk, ['code'], [
                    'name', 'english_name', 'administrative_level', 'province_code', 'decree', 'updated_at',
                ]);
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->warn("  ⚠ Tỉnh {$code}: " . $e->getMessage());
        }
    }

    private function warnForceWipe(): void
    {
        $this->warn('⚠️  --force: Xóa toàn bộ dữ liệu địa chỉ cũ...');
        DB::statement('DELETE FROM address_communes');
        DB::statement('DELETE FROM address_provinces');
        $this->info('   → Đã xóa sạch.');
    }

    private function printStats(): void
    {
        $provinces = Province::query()->count();
        $communes = Commune::query()->count();
        $this->table(
            ['Bảng', 'Số bản ghi'],
            [
                ['address_provinces', $provinces],
                ['address_communes', $communes],
            ]
        );
    }
}
