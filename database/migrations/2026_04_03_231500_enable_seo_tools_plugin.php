<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('installed_plugins')) {
            return;
        }

        DB::table('installed_plugins')->updateOrInsert(
            ['alias' => 'seo-tools'],
            [
                'name' => 'SEO Tools',
                'version' => '1.0.0',
                'is_active' => true,
                'settings' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('installed_plugins')) {
            return;
        }

        DB::table('installed_plugins')->where('alias', 'seo-tools')->delete();
    }
};
