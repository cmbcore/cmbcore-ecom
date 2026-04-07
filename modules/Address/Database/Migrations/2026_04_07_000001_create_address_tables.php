<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Provinces (Tỉnh / Thành phố trực thuộc TW) ────────────────────
        Schema::create('address_provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique()->comment('Mã tỉnh/TP — VD: "01", "79"');
            $table->string('name')->comment('Tên đầy đủ — VD: "Thành phố Hà Nội"');
            $table->string('english_name')->default('');
            $table->string('administrative_level')->default('')->comment('Tỉnh / Thành phố Trung ương');
            $table->string('decree')->default('')->comment('Số nghị quyết / nghị định');
            $table->timestamps();
        });

        // ── Communes (Xã / Phường / Thị trấn) ─────────────────────────────
        Schema::create('address_communes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique()->comment('Mã xã/phường — VD: "00004"');
            $table->string('name')->comment('Tên đầy đủ — VD: "Phường Ba Đình"');
            $table->string('english_name')->default('');
            $table->string('administrative_level')->default('')->comment('Xã / Phường / Thị trấn');
            $table->string('province_code', 10)->comment('FK → address_provinces.code');
            $table->string('decree')->default('');
            $table->timestamps();

            $table->index('province_code');
            $table->foreign('province_code')
                ->references('code')
                ->on('address_provinces')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_communes');
        Schema::dropIfExists('address_provinces');
    }
};
