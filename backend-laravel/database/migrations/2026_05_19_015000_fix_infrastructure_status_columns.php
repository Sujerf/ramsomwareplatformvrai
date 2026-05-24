<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ALTER TABLE ... MODIFY est spécifique à MySQL — sans effet sur SQLite (tests).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('managed_networks')) {
            if (Schema::hasColumn('managed_networks', 'status')) {
                DB::statement("ALTER TABLE managed_networks MODIFY status VARCHAR(80) NOT NULL DEFAULT 'detected'");
            }

            if (Schema::hasColumn('managed_networks', 'interface_name')) {
                DB::statement("ALTER TABLE managed_networks MODIFY interface_name VARCHAR(120) NULL");
            }
        }

        if (Schema::hasTable('discovered_hosts')) {
            if (Schema::hasColumn('discovered_hosts', 'discovery_status')) {
                DB::statement("ALTER TABLE discovered_hosts MODIFY discovery_status VARCHAR(80) NOT NULL DEFAULT 'detected'");
            }

            if (Schema::hasColumn('discovered_hosts', 'host_role')) {
                DB::statement("ALTER TABLE discovered_hosts MODIFY host_role VARCHAR(80) NULL DEFAULT 'client'");
            }
        }
    }

    public function down(): void
    {
        // On ne revient pas aux ENUM pour éviter les blocages.
    }
};
