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
        // Add 'wallet' to Postgres enum
        DB::statement("ALTER TYPE entry_type ADD VALUE IF NOT EXISTS 'wallet'");

        // Drop and Recreate CHECK constraint to support 'wallet'
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
                (type = 'lab'      AND status = 'active') OR
                (type = 'wallet'   AND status IN ('active','archived'))
            )
        SQL);

        // Create wallet_details table
        Schema::create('wallet_details', function (Blueprint $table) {
            $table->foreignId('entry_id')->primary()->constrained('entries')->onDelete('cascade');
            $table->string('type'); // 'cash', 'bank', 'credit', 'investment', 'savings'
            $table->decimal('initial_balance', 15, 2)->default(0.00);
            $table->string('currency', 10)->default('BDT');
            $table->timestamps();
        });

        // Add CHECK constraint on wallet_details type
        DB::statement("ALTER TABLE wallet_details ADD CONSTRAINT chk_wallet_type CHECK (type IN ('cash','bank','credit','investment','savings'))");

        // Create wallet_transactions table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('entries')->onDelete('cascade');
            $table->foreignId('target_wallet_id')->nullable()->constrained('entries')->onDelete('set null');
            $table->string('type'); // 'income', 'expense', 'transfer'
            $table->decimal('amount', 15, 2);
            $table->date('occurred_on');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Add CHECK constraint on wallet_transactions type and amount
        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT chk_transaction_type CHECK (type IN ('income','expense','transfer'))");
        DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT chk_transaction_amount CHECK (amount > 0)");

        // Add integrity trigger for wallet_details type check
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION trg_wallet_details_type_check() RETURNS trigger AS $body$
            BEGIN
                IF (SELECT type FROM entries WHERE id = NEW.entry_id) <> 'wallet'::entry_type THEN
                    RAISE EXCEPTION 'wallet_details requires entries.type = ''wallet'' (entry_id=%)', NEW.entry_id;
                END IF;
                RETURN NEW;
            END
            $body$ LANGUAGE plpgsql;
        SQL);

        DB::statement("DROP TRIGGER IF EXISTS wallet_details_type_check ON wallet_details");
        DB::statement(<<<'SQL'
            CREATE TRIGGER wallet_details_type_check
            BEFORE INSERT OR UPDATE ON wallet_details
            FOR EACH ROW EXECUTE FUNCTION trg_wallet_details_type_check();
        SQL);
    }

    public function down(): void
    {
        // Drop trigger and function
        DB::statement("DROP TRIGGER IF EXISTS wallet_details_type_check ON wallet_details");
        DB::statement("DROP FUNCTION IF EXISTS trg_wallet_details_type_check()");

        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallet_details');

        // Restore check constraint (without 'wallet')
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
    }
};
