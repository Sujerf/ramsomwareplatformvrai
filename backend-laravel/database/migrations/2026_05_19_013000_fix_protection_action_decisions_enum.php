<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('protection_action_decisions')) {
            return;
        }

        // ALTER TABLE ... MODIFY est spécifique à MySQL — sans effet sur SQLite (tests).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        /*
         * Certaines colonnes peuvent avoir été créées en ENUM.
         * On les transforme en VARCHAR pour accepter toutes les décisions SOC :
         * approved, rejected, executed, rollback, cancelled, comment, system, etc.
         */
        if (Schema::hasColumn('protection_action_decisions', 'decision')) {
            DB::statement("ALTER TABLE protection_action_decisions MODIFY decision VARCHAR(80) NOT NULL");
        }

        if (Schema::hasColumn('protection_action_decisions', 'comment')) {
            DB::statement("ALTER TABLE protection_action_decisions MODIFY comment TEXT NULL");
        }

        if (Schema::hasColumn('protection_action_decisions', 'metadata')) {
            DB::statement("ALTER TABLE protection_action_decisions MODIFY metadata JSON NULL");
        }
    }

    public function down(): void
    {
        // On ne revient pas en ENUM pour éviter de rebloquer les décisions.
    }
};
