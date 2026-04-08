<?php

/**
 * scripts/download-demo-assets.php
 *
 * Script độc lập — chạy MỘT LẦN để tải ảnh demo từ remote về local.
 * Sau khi chạy, commit thư mục database/seeders/demo-assets/ vào git.
 *
 * Cách dùng:
 *   php scripts/download-demo-assets.php
 */

declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
define('OUTPUT_DIR', BASE_DIR . '/database/seeders/demo-assets/images');

// ── Danh sách ảnh cần tải ────────────────────────────────────────────────────
const DEMO_IMAGES = [
    // ── Categories ──────────────────────────────────────────────────────────
    'category-bo-qua-tang.png' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png',

    'category-cham-soc-co-the.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',

    'category-khu-mui.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien-cho-nam-gioi_7e4977521e4849eca099d725ce266a0d.jpg',

    'category-cham-soc-toc.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',

    // ── Products ─────────────────────────────────────────────────────────────
    'product-combo-nam-tinh-1.png' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/THUMB-NAM-TINH-1.png',

    'product-combo-nam-tinh-2.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',

    'product-combo-nam-tinh-3.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cong-dung-cua-sua-tam-goi-3-in-1_73b97f72eec349ceb7d964c0fa84eecf.jpg',

    'product-combo-nam-tinh-4.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',

    'product-box-thuong.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/10/THUMB-2.jpg.webp',

    'product-box-limited.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/07/ANH-THUMB-LIMITED-TI-KTOK.jpg.webp',

    'product-lock-box.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2026/01/THUMB-LOCK-BOX.png.webp',

    'product-sua-tam-3in1.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cong-dung-cua-sua-tam-goi-3-in-1_73b97f72eec349ceb7d964c0fa84eecf.jpg',

    'product-bodymist.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/chiet-xuat-tu-thanh-phan-tu-nhien_9a3c62cfb8a5401a8142af8c23543715.jpg',

    'product-xit-phong-toc.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/dac-diem-noi-bat_9d8efc0fd52a43f2a22fcea121875025.jpg',

    'product-combo-sang-khoai.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/05/THUMB-SANG-KHOAI-1.png.webp',

    // ── Blog ─────────────────────────────────────────────────────────────────
    'blog-category-tin-tuc.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/09/6-1.jpg.webp',

    'blog-post-scrub-timing.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/09/8.jpg.webp',

    'blog-post-body-scent.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/08/cach-de-co-the-luon-thom.jpg.webp',

    'blog-post-body-wash.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/09/sua-tam-khu-mui-co-the-cho-nam.jpg.webp',

    'blog-post-scrub-detail.webp' =>
        'https://rhysman.vn/wp-content/smush-webp/2025/09/6-1.jpg.webp',

    // ── Pages ────────────────────────────────────────────────────────────────
    'page-gioi-thieu-featured.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/cham-soc-toan-dien_09312636a1b9429e9955e161c9429c4c.jpg',

    'page-gioi-thieu-body.jpg' =>
        'https://rhysman.vn/wp-content/uploads/2025/05/chiet-xuat-tu-thanh-phan-tu-nhien_9a3c62cfb8a5401a8142af8c23543715.jpg',
];

// ── Helpers ──────────────────────────────────────────────────────────────────

function log_line(string $msg, string $level = 'INFO'): void
{
    $ts = date('H:i:s');
    echo "[{$ts}] [{$level}] {$msg}" . PHP_EOL;
}

function download_file(string $url, string $destPath): bool
{
    $context = stream_context_create([
        'http' => [
            'timeout'          => 30,
            'follow_location'  => 1,
            'max_redirects'    => 5,
            'user_agent'       => 'Mozilla/5.0 CMBCore/DemoAssetDownloader',
            'header'           => 'Accept: image/*,*/*',
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);

    $data = @file_get_contents($url, false, $context);

    if ($data === false || strlen($data) < 500) {
        return false;
    }

    file_put_contents($destPath, $data);
    return true;
}

// ── Main ─────────────────────────────────────────────────────────────────────

if (! is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0755, true);
    log_line('Created directory: ' . OUTPUT_DIR);
}

$success = 0;
$skipped = 0;
$failed  = 0;

foreach (DEMO_IMAGES as $filename => $url) {
    $destPath = OUTPUT_DIR . '/' . $filename;

    if (file_exists($destPath) && filesize($destPath) > 500) {
        log_line("SKIP  {$filename} (already exists)", 'SKIP');
        $skipped++;
        continue;
    }

    log_line("DOWN  {$filename} ← {$url}");
    $ok = download_file($url, $destPath);

    if ($ok) {
        $sizeKb = round(filesize($destPath) / 1024, 1);
        log_line("OK    {$filename} ({$sizeKb} KB)", 'OK');
        $success++;
    } else {
        log_line("FAIL  {$filename} ← {$url}", 'ERR');
        $failed++;
        // Tạo file placeholder 1x1 transparent PNG để seeder không bị lỗi
        if (str_ends_with($filename, '.png') || str_ends_with($filename, '.jpg')) {
            $placeholder = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            );
            file_put_contents($destPath, $placeholder);
            log_line("      → Created placeholder for {$filename}", 'WRN');
        }
    }
}

log_line('─────────────────────────────────────────────────────────');
log_line("Done: {$success} downloaded, {$skipped} skipped, {$failed} failed");
log_line('Next: commit database/seeders/demo-assets/ to git');
log_line('      php artisan db:seed --class=CmbcoreDemoSeeder');
