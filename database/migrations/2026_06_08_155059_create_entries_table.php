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
        DB::statement("DROP TYPE IF EXISTS entry_type");
        DB::statement("CREATE TYPE entry_type AS ENUM ('task', 'note', 'journal', 'bookmark', 'quote', 'resource', 'learning', 'idea')");

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // created as string, altered to enum below
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('body_format')->default('markdown');
            $table->string('status')->default('active');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('pinned')->default(false);
            $table->date('occurred_on')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            // Index declarations
            $table->index(['user_id', 'type', 'archived_at']);
            $table->index(['user_id', 'pinned']);
            $table->index(['user_id', 'last_activity_at']);
            $table->index(['user_id', 'occurred_on']);
        });

        // Alter type column to be the native Postgres enum
        DB::statement("ALTER TABLE entries ALTER COLUMN type TYPE entry_type USING type::entry_type");

        // Add the search_vector generated column and its GIN index
        DB::statement("ALTER TABLE entries ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
            setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
            setweight(to_tsvector('english', coalesce(body, '')), 'B')
        ) STORED");

        DB::statement("CREATE INDEX idx_entries_search_vector ON entries USING gin(search_vector)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
        DB::statement("DROP TYPE IF EXISTS entry_type");
    }
};
