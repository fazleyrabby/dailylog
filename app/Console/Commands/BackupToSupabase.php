<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Mirrors the local (primary) database to the remote Supabase database so
 * Supabase serves as an off-site backup.
 *
 * The copy is a full overwrite wrapped in a single remote transaction:
 * `session_replication_role = replica` disables FK/triggers for the session so
 * rows can be cleared and re-inserted in any order. On any failure the
 * transaction rolls back and the backup is left untouched.
 */
class BackupToSupabase extends Command
{
    protected $signature = 'backup:supabase {--chunk=500 : Rows per insert batch}';

    protected $description = 'Mirror the local database to the Supabase backup database';

    /** Ephemeral runtime tables that should not be mirrored. */
    private const SKIP = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];

    public function handle(): int
    {
        if (! config('database.connections.supabase.host')) {
            $this->error('Supabase backup connection is not configured (set SUPABASE_DB_* env vars).');

            return self::FAILURE;
        }

        $local = DB::connection('pgsql');
        $remote = DB::connection('supabase');
        $chunk = max(50, (int) $this->option('chunk'));

        $tables = collect($local->select(
            "select tablename from pg_tables where schemaname = 'public' order by tablename"
        ))->pluck('tablename')
            ->reject(fn ($t) => in_array($t, self::SKIP, true))
            ->values();

        $this->info("Mirroring {$tables->count()} tables to Supabase...");
        $total = 0;

        try {
            $remote->transaction(function () use ($remote, $local, $tables, $chunk, &$total) {
                $remote->statement("SET session_replication_role = 'replica'");

                // Clear target tables first (FK triggers are disabled above).
                foreach ($tables as $table) {
                    $remote->table($table)->delete();
                }

                foreach ($tables as $table) {
                    // Exclude GENERATED ALWAYS columns (e.g. tsvector search
                    // columns) — Postgres rejects inserting values into them
                    // and recomputes them automatically.
                    $columns = $this->insertableColumns($local, $table);
                    if ($columns === []) {
                        continue;
                    }
                    $orderBy = $columns[0];
                    $rows = 0;
                    $local->table($table)->select($columns)->orderBy($orderBy)->chunk($chunk, function ($batch) use ($remote, $table, &$rows) {
                        $remote->table($table)->insert(
                            $batch->map(fn ($r) => (array) $r)->all()
                        );
                        $rows += $batch->count();
                    });
                    $total += $rows;
                    $this->line(sprintf('  %-28s %5d rows', $table, $rows));
                }

                // Restore normal trigger behaviour before commit.
                $remote->statement("SET session_replication_role = 'origin'");
            });
        } catch (Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        Cache::forever('supabase_backup_last_at', now()->toIso8601String());
        $this->info("Done. {$total} rows mirrored to Supabase.");

        return self::SUCCESS;
    }

    /**
     * Columns that can be written, in ordinal order. Excludes GENERATED ALWAYS
     * columns (Postgres rejects values for them). The first entry doubles as a
     * stable order key for chunking.
     *
     * @return array<int, string>
     */
    private function insertableColumns($connection, string $table): array
    {
        return collect($connection->select(
            "select column_name from information_schema.columns where table_schema = 'public' and table_name = ? and is_generated <> 'ALWAYS' order by ordinal_position",
            [$table]
        ))->pluck('column_name')->all();
    }
}
