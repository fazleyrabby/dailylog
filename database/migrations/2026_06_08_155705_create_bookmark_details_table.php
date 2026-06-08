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
        Schema::create('bookmark_details', function (Blueprint $table) {
            $table->foreignId('entry_id')->primary()->constrained('entries')->onDelete('cascade');
            $table->text('url');
            $table->string('site')->nullable();
            $table->text('description')->nullable();
            $table->text('favicon_url')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->string('review_state')->default('unread'); // unread, reviewed
            $table->jsonb('raw_meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmark_details');
    }
};
