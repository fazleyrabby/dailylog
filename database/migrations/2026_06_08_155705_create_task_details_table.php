<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_details', function (Blueprint $table) {
            $table->foreignId('entry_id')->primary()->constrained('entries')->onDelete('cascade');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('priority')->default(0); // 0 = low, 1 = normal, 2 = high, 3 = urgent
            $table->string('recurrence')->nullable();
            $table->timestamps();

            // Partial index to scan active/overdue tasks quickly
            $table->index(['entry_id', 'due_at'])->whereNull('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_details');
    }
};
