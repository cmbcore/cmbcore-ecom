<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('shipping_method_code')->nullable()->after('shipping_phone');
            $table->string('shipping_method_name')->nullable()->after('shipping_method_code');
        });

        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('provinces')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('flat_rate');
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->decimal('min_order_value', 12, 2)->nullable();
            $table->decimal('max_order_value', 12, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $zoneId = DB::table('shipping_zones')->insertGetId([
            'name' => 'Toan quoc',
            'provinces' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shipping_methods')->insert([
            'shipping_zone_id' => $zoneId,
            'name' => 'Giao hang tieu chuan',
            'code' => 'standard',
            'type' => 'flat_rate',
            'fee' => 30000,
            'free_shipping_threshold' => 499000,
            'min_order_value' => null,
            'max_order_value' => null,
            'conditions' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_method_code', 'shipping_method_name']);
        });
    }
};
