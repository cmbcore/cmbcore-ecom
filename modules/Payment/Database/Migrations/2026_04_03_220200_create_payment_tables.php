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
            $table->json('payment_meta')->nullable()->after('payment_status');
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->json('callback_data')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        DB::table('installed_plugins')->updateOrInsert(
            ['alias' => 'payment-cod'],
            [
                'name' => 'Thanh toán COD',
                'version' => '1.0.0',
                'is_active' => true,
                'settings' => json_encode([
                    'enabled' => true,
                    'instructions' => 'Khach thanh toán khi nhận hàng.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('payment_meta');
        });
    }
};
