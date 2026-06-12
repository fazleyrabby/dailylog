<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('entries')->onDelete('cascade');
            $table->string('type'); // 'income', 'expense'
            $table->string('category')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('frequency'); // 'daily', 'weekly', 'monthly', 'yearly'
            $table->date('next_due_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
