<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('term')->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
