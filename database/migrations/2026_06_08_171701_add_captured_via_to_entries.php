<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('captured_via')->nullable()->after('pinned');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE entries ADD CONSTRAINT chk_entries_captured_via CHECK (captured_via IS NULL OR captured_via IN ('palette','web','extension','share','import','api','seed'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE entries DROP CONSTRAINT IF EXISTS chk_entries_captured_via');
        }
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('captured_via');
        });
    }
};
