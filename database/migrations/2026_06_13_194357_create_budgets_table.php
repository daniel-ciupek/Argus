<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            // Nullable: budget can be global (no agent) or per-agent.
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period');              // BudgetPeriod enum cast in model
            $table->decimal('limit_amount', 12, 4);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
