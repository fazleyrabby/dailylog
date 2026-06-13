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

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Replace full indexes with partial equivalents on entries.
        DB::statement('DROP INDEX IF EXISTS entries_user_id_pinned_index');
        DB::statement('CREATE INDEX entries_user_id_pinned_partial ON entries (user_id) WHERE pinned');

        DB::statement('DROP INDEX IF EXISTS entries_user_id_occurred_on_index');
        DB::statement("CREATE INDEX entries_user_id_journal_day_partial ON entries (user_id, occurred_on) WHERE type = 'journal'");

        DB::statement('CREATE INDEX entries_user_id_project_id_partial ON entries (user_id, project_id) WHERE project_id IS NOT NULL');

        // Partial unique: one journal entry per user per day (ignoring archived).
        DB::statement("CREATE UNIQUE INDEX uniq_entries_journal_per_day ON entries (user_id, occurred_on) WHERE type = 'journal' AND archived_at IS NULL");

        // Replace full task_details(entry_id, due_at) with partial on open tasks.
        DB::statement('DROP INDEX IF EXISTS task_details_entry_id_due_at_index');
        DB::statement('CREATE INDEX task_details_due_at_open_partial ON task_details (due_at) WHERE completed_at IS NULL');

        // Learning status reverse-lookup index.
        DB::statement('CREATE INDEX learning_details_status_idx ON learning_details (status)');

        // Trigram GIN on title for fuzzy/typo-tolerant search (docs/08 §3).
        DB::statement('CREATE INDEX entries_title_trgm_idx ON entries USING gin (title gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS entries_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS learning_details_status_idx');
        DB::statement('DROP INDEX IF EXISTS task_details_due_at_open_partial');
        DB::statement('CREATE INDEX task_details_entry_id_due_at_index ON task_details (entry_id, due_at)');
        DB::statement('DROP INDEX IF EXISTS uniq_entries_journal_per_day');
        DB::statement('DROP INDEX IF EXISTS entries_user_id_project_id_partial');
        DB::statement('DROP INDEX IF EXISTS entries_user_id_journal_day_partial');
        DB::statement('CREATE INDEX entries_user_id_occurred_on_index ON entries (user_id, occurred_on)');
        DB::statement('DROP INDEX IF EXISTS entries_user_id_pinned_partial');
        DB::statement('CREATE INDEX entries_user_id_pinned_index ON entries (user_id, pinned)');
    }
};
