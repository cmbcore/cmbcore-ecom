<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_token')->nullable()->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('shopping_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('shopping_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('product_name');
            $table->string('sku_name')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_cart_items');
        Schema::dropIfExists('shopping_carts');
    }
};
