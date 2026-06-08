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
        Schema::create('learning_details', function (Blueprint $table) {
            $table->foreignId('entry_id')->primary()->constrained('entries')->onDelete('cascade');
            $table->string('kind')->default('topic'); // course, path, certification, topic
            $table->string('provider')->nullable();
            $table->unsignedSmallInteger('progress')->default(0); // percentage 0-100
            $table->unsignedInteger('total_units')->nullable();
            $table->unsignedInteger('completed_units')->nullable();
            $table->string('status')->default('active'); // active, paused, completed, abandoned
            $table->date('target_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_details');
    }
};
