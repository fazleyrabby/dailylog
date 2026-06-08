<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'task_details'     => 'task',
            'bookmark_details' => 'bookmark',
            'resource_details' => 'resource',
            'learning_details' => 'learning',
            'quote_details'    => 'quote',
        ];

        foreach ($map as $table => $type) {
            $fn = "trg_{$table}_type_check";
            DB::statement(<<<SQL
                CREATE OR REPLACE FUNCTION {$fn}() RETURNS trigger AS \$body\$
                BEGIN
                    IF (SELECT type FROM entries WHERE id = NEW.entry_id) <> '{$type}'::entry_type THEN
                        RAISE EXCEPTION '{$table} requires entries.type = ''{$type}'' (entry_id=%)', NEW.entry_id;
                    END IF;
                    RETURN NEW;
                END
                \$body\$ LANGUAGE plpgsql;
            SQL);

            DB::statement("DROP TRIGGER IF EXISTS {$table}_type_check ON {$table}");
            DB::statement(<<<SQL
                CREATE TRIGGER {$table}_type_check
                BEFORE INSERT OR UPDATE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION {$fn}();
            SQL);
        }
    }

    public function down(): void
    {
        foreach (['task_details','bookmark_details','resource_details','learning_details','quote_details'] as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_type_check ON {$table}");
            DB::statement("DROP FUNCTION IF EXISTS trg_{$table}_type_check()");
        }
    }
};
