<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Certaines colonnes créées au début peuvent être des ENUM trop limités.
         * Le moteur dynamique utilise des valeurs comme :
         * file_moved, file_created, critical, waiting_approval, approval_required, etc.
         * On transforme donc les colonnes sensibles en VARCHAR.
         *
         * ALTER TABLE ... MODIFY est spécifique à MySQL.
         * Sur SQLite (tests), les colonnes sont déjà TEXT/VARCHAR — migration sans effet.
         */
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('events')) {
            if (Schema::hasColumn('events', 'event_type')) {
                DB::statement("ALTER TABLE events MODIFY event_type VARCHAR(120) NOT NULL");
            }

            if (Schema::hasColumn('events', 'risk_level')) {
                DB::statement("ALTER TABLE events MODIFY risk_level VARCHAR(50) NOT NULL DEFAULT 'normal'");
            }

            if (Schema::hasColumn('events', 'file_extension')) {
                DB::statement("ALTER TABLE events MODIFY file_extension VARCHAR(80) NULL");
            }
        }

        if (Schema::hasTable('alerts')) {
            if (Schema::hasColumn('alerts', 'risk_level')) {
                DB::statement("ALTER TABLE alerts MODIFY risk_level VARCHAR(50) NOT NULL DEFAULT 'normal'");
            }

            if (Schema::hasColumn('alerts', 'status')) {
                DB::statement("ALTER TABLE alerts MODIFY status VARCHAR(80) NOT NULL DEFAULT 'open'");
            }
        }

        if (Schema::hasTable('incidents')) {
            if (Schema::hasColumn('incidents', 'risk_level')) {
                DB::statement("ALTER TABLE incidents MODIFY risk_level VARCHAR(50) NOT NULL DEFAULT 'normal'");
            }

            if (Schema::hasColumn('incidents', 'status')) {
                DB::statement("ALTER TABLE incidents MODIFY status VARCHAR(80) NOT NULL DEFAULT 'open'");
            }
        }

        if (Schema::hasTable('protection_actions')) {
            if (Schema::hasColumn('protection_actions', 'action_type')) {
                DB::statement("ALTER TABLE protection_actions MODIFY action_type VARCHAR(120) NOT NULL");
            }

            if (Schema::hasColumn('protection_actions', 'decision_mode')) {
                DB::statement("ALTER TABLE protection_actions MODIFY decision_mode VARCHAR(80) NOT NULL DEFAULT 'manual'");
            }

            if (Schema::hasColumn('protection_actions', 'approval_status')) {
                DB::statement("ALTER TABLE protection_actions MODIFY approval_status VARCHAR(80) NOT NULL DEFAULT 'pending'");
            }

            if (Schema::hasColumn('protection_actions', 'execution_status')) {
                DB::statement("ALTER TABLE protection_actions MODIFY execution_status VARCHAR(80) NOT NULL DEFAULT 'pending'");
            }
        }

        if (Schema::hasTable('agents')) {
            if (Schema::hasColumn('agents', 'risk_level')) {
                DB::statement("ALTER TABLE agents MODIFY risk_level VARCHAR(50) NULL DEFAULT 'normal'");
            }

            if (Schema::hasColumn('agents', 'status')) {
                DB::statement("ALTER TABLE agents MODIFY status VARCHAR(80) NOT NULL DEFAULT 'active'");
            }

            if (Schema::hasColumn('agents', 'host_role')) {
                DB::statement("ALTER TABLE agents MODIFY host_role VARCHAR(80) NULL DEFAULT 'client'");
            }
        }
    }

    public function down(): void
    {
        // On ne revient pas aux ENUM pour éviter de bloquer le moteur dynamique.
    }
};
