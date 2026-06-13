<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';

        Schema::table('projects', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
        });

        if ($isPgsql) {
            DB::statement("ALTER TABLE projects ADD CONSTRAINT chk_projects_status CHECK (status IN ('active','paused','completed','archived'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS chk_projects_status');
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
        });
    }
};
