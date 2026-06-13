<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            // Prices per 1 000 tokens, 6 decimal places for sub-cent precision.
            $table->decimal('input_price_per_1k', 10, 6)->default(0);
            $table->decimal('output_price_per_1k', 10, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unique(['provider', 'name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
