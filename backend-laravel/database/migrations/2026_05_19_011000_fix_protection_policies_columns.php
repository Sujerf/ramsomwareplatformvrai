<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protection_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('protection_policies', 'action_type')) {
                $table->string('action_type')->default('notify')->after('risk_level');
            }

            if (! Schema::hasColumn('protection_policies', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            if (! Schema::hasColumn('protection_policies', 'description')) {
                $table->text('description')->nullable();
            }
        });

        /*
         * Certaines anciennes colonnes peuvent être en ENUM.
         * On les transforme en VARCHAR pour accepter :
         * scope = agent, path, global
         * execution_mode = automatic, approval_required, manual
         *
         * ALTER TABLE ... MODIFY est spécifique à MySQL — ignoré sur SQLite (tests).
         */
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE protection_policies MODIFY scope VARCHAR(80) NOT NULL DEFAULT 'agent'");
            DB::statement("ALTER TABLE protection_policies MODIFY risk_level VARCHAR(80) NOT NULL DEFAULT 'suspect'");
            DB::statement("ALTER TABLE protection_policies MODIFY execution_mode VARCHAR(80) NOT NULL DEFAULT 'manual'");
            DB::statement("ALTER TABLE protection_policies MODIFY action_type VARCHAR(120) NOT NULL DEFAULT 'notify'");
        }
    }

    public function down(): void
    {
        // Ne rien supprimer pour éviter de casser la configuration.
    }
};
