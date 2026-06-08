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
        Schema::create('resource_details', function (Blueprint $table) {
            $table->foreignId('entry_id')->primary()->constrained('entries')->onDelete('cascade');
            $table->string('resource_type'); // book, video, article, tool, repo, doc
            $table->string('author')->nullable();
            $table->text('url')->nullable();
            $table->string('consume_state')->default('to_consume'); // to_consume, consuming, done
            $table->unsignedSmallInteger('rating')->nullable(); // 1-5 rating
            $table->text('external_ref')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_details');
    }
};
