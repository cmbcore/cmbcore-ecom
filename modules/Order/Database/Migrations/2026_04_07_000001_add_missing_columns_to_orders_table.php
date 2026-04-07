<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add columns that were missing from the initial orders migration.
 *
 * The following columns are written by OrderService::placeOrder() and
 * PaymentService::process() but were absent from the original schema,
 * causing a QueryException on every order placement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'shipping_method_code')) {
                $table->string('shipping_method_code')->nullable()->after('shipping_phone');
            }

            if (! Schema::hasColumn('orders', 'shipping_method_name')) {
                $table->string('shipping_method_name')->nullable()->after('shipping_method_code');
            }

            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('discount_total');
            }

            if (! Schema::hasColumn('orders', 'coupon_snapshot')) {
                $table->json('coupon_snapshot')->nullable()->after('coupon_code');
            }

            if (! Schema::hasColumn('orders', 'tax_total')) {
                $table->decimal('tax_total', 12, 2)->default(0)->after('shipping_total');
            }

            if (! Schema::hasColumn('orders', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'shipping_method_code',
                'shipping_method_name',
                'coupon_code',
                'coupon_snapshot',
                'tax_total',
                'payment_meta',
            ]);
        });
    }
};
