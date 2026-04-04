<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias', 100)->unique();
            $table->string('version', 20);
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_themes');
    }
};
