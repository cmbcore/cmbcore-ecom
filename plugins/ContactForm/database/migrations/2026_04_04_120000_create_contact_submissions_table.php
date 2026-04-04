<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old fixed-column table if it exists and recreate with JSON data
        Schema::dropIfExists('contact_submissions');

        Schema::create('contact_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('contact_forms')->nullOnDelete();
            $table->json('data')->comment('All submitted field values as key=>value');
            $table->string('page_url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
