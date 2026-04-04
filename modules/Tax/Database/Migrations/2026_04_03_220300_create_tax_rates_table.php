<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('tax_total', 12, 2)->default(0)->after('shipping_total');
        });

        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('province')->nullable();
            $table->decimal('rate', 8, 4)->default(0);
            $table->decimal('threshold', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('tax_total');
        });
    }
};
