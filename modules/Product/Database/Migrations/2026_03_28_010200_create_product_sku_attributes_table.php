<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sku_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->string('attribute_name', 100);
            $table->string('attribute_value');
            $table->timestamps();

            $table->index(['attribute_name', 'attribute_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sku_attributes');
    }
};
