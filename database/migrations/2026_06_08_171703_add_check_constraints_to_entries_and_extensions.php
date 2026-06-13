<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Entries: per-type status CHECK. Bookmark/quote use extension state fields → permissive here.
        DB::statement('ALTER TABLE entries ALTER COLUMN status DROP DEFAULT');
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

        DB::statement('ALTER TABLE entries ADD CONSTRAINT chk_entries_body_format CHECK (body_format IN (\'markdown\',\'html\'))');

        // Task details
        DB::statement('ALTER TABLE task_details ADD CONSTRAINT chk_task_priority CHECK (priority BETWEEN 0 AND 3)');

        // Learning details
        DB::statement('ALTER TABLE learning_details ADD CONSTRAINT chk_learning_progress CHECK (progress BETWEEN 0 AND 100)');
        DB::statement("ALTER TABLE learning_details ADD CONSTRAINT chk_learning_kind CHECK (kind IN ('course','path','certification','topic'))");
        DB::statement("ALTER TABLE learning_details ADD CONSTRAINT chk_learning_status CHECK (status IN ('active','paused','completed','abandoned'))");

        // Bookmark details
        DB::statement("ALTER TABLE bookmark_details ADD CONSTRAINT chk_bookmark_review_state CHECK (review_state IN ('unread','reviewed'))");

        // Resource details
        DB::statement("ALTER TABLE resource_details ADD CONSTRAINT chk_resource_type CHECK (resource_type IN ('book','video','article','tool','repo','doc'))");
        DB::statement("ALTER TABLE resource_details ADD CONSTRAINT chk_resource_consume_state CHECK (consume_state IN ('to_consume','consuming','done'))");
        DB::statement('ALTER TABLE resource_details ADD CONSTRAINT chk_resource_rating CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $drops = [
            ['entries', 'chk_entries_status_per_type'],
            ['entries', 'chk_entries_body_format'],
            ['task_details', 'chk_task_priority'],
            ['learning_details', 'chk_learning_progress'],
            ['learning_details', 'chk_learning_kind'],
            ['learning_details', 'chk_learning_status'],
            ['bookmark_details', 'chk_bookmark_review_state'],
            ['resource_details', 'chk_resource_type'],
            ['resource_details', 'chk_resource_consume_state'],
            ['resource_details', 'chk_resource_rating'],
        ];
        foreach ($drops as [$t, $c]) {
            DB::statement("ALTER TABLE {$t} DROP CONSTRAINT IF EXISTS {$c}");
        }
        DB::statement("ALTER TABLE entries ALTER COLUMN status SET DEFAULT 'active'");
    }
};
