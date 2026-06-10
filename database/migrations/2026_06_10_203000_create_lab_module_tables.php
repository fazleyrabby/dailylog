<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Add 'lab' to Postgres enum
        DB::statement("ALTER TYPE entry_type ADD VALUE IF NOT EXISTS 'lab'");

        // Drop and Recreate CHECK constraint to support 'lab' with 'active' status
        DB::statement("ALTER TABLE entries DROP CONSTRAINT IF EXISTS chk_entries_status_per_type");
        DB::statement(<<<'SQL'
            ALTER TABLE entries ADD CONSTRAINT chk_entries_status_per_type CHECK (
                (type = 'task'     AND status IN ('open','done')) OR
                (type = 'note'     AND status IN ('draft','active','archived')) OR
                (type = 'journal'  AND status = 'active') OR
                (type = 'bookmark') OR
                (type = 'quote') OR
                (type = 'resource' AND status IN ('to_consume','consuming','done')) OR
                (type = 'learning' AND status IN ('active','paused','completed','abandoned')) OR
                (type = 'idea'     AND status IN ('spark','exploring','parked','shipped','killed')) OR
                (type = 'lab'      AND status = 'active')
            )
        SQL);

        // Create lab_items table
        Schema::create('lab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('entries')->onDelete('cascade');
            $table->foreignId('target_entry_id')->nullable()->constrained('entries')->onDelete('set null');
            $table->string('type'); // 'sticky', 'text', 'reference'
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(180);
            $table->integer('height')->default(180);
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_items');

        DB::statement("ALTER TABLE entries DROP CONSTRAINT IF EXISTS chk_entries_status_per_type");
        DB::statement(<<<'SQL'
            ALTER TABLE entries ADD CONSTRAINT chk_entries_status_per_type CHECK (
                (type = 'task'     AND status IN ('open','done')) OR
                (type = 'note'     AND status IN ('draft','active','archived')) OR
                (type = 'journal'  AND status = 'active') OR
                (type = 'bookmark') OR
                (type = 'quote') OR
                (type = 'resource' AND status IN ('to_consume','consuming','done')) OR
                (type = 'learning' AND status IN ('active','paused','completed','abandoned')) OR
                (type = 'idea'     AND status IN ('spark','exploring','parked','shipped','killed'))
            )
        SQL);
    }
};
