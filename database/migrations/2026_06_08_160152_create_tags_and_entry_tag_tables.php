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
        // Enable citext extension for case-insensitive unique tags (only for pgsql)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        }

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // created as string, altered to citext below
            $table->string('slug');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Alter name column to use the citext extension (only for pgsql)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tags ALTER COLUMN name TYPE citext');
        }

        Schema::create('entry_tag', function (Blueprint $table) {
            $table->foreignId('entry_id')->constrained('entries')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['entry_id', 'tag_id']);
            
            // Index for reverse browse (all entries with a tag)
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_tag');
        Schema::dropIfExists('tags');
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP EXTENSION IF EXISTS citext');
        }
    }
};
