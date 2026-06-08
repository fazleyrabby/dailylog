<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entry_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('entries')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('entries')->onDelete('cascade');
            $table->string('relation')->default('references'); // references, supports, duplicates...
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['source_id', 'target_id', 'relation']);
            $table->index('target_id'); // index for backlinks queries
        });

        // Add a check constraint to prevent an entry from linking to itself
        DB::statement('ALTER TABLE entry_links ADD CONSTRAINT chk_source_target_distinct CHECK (source_id <> target_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_links');
    }
};
