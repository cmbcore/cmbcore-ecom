<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete();
            $table->string('type', 20)->default('image');
            $table->string('path', 500);
            $table->string('disk', 20)->default('public');
            $table->string('filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->integer('position')->default(0);
            $table->string('alt_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
