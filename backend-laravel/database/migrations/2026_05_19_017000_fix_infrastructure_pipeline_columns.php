<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('managed_networks')) {
            Schema::table('managed_networks', function (Blueprint $table) {
                if (! Schema::hasColumn('managed_networks', 'is_monitored')) {
                    $table->boolean('is_monitored')->default(true)->after('is_scannable');
                }

                if (! Schema::hasColumn('managed_networks', 'retired_at')) {
                    $table->timestamp('retired_at')->nullable()->after('last_scanned_at');
                }

                if (! Schema::hasColumn('managed_networks', 'retired_reason')) {
                    $table->text('retired_reason')->nullable()->after('retired_at');
                }
            });

            if (Schema::hasColumn('managed_networks', 'status')) {
                DB::statement("ALTER TABLE managed_networks MODIFY status VARCHAR(80) NOT NULL DEFAULT 'detected'");
            }
        }

        if (Schema::hasTable('discovered_hosts')) {
            Schema::table('discovered_hosts', function (Blueprint $table) {
                if (! Schema::hasColumn('discovered_hosts', 'is_monitored')) {
                    $table->boolean('is_monitored')->default(true)->after('discovery_status');
                }

                if (! Schema::hasColumn('discovered_hosts', 'retired_at')) {
                    $table->timestamp('retired_at')->nullable()->after('last_seen_at');
                }

                if (! Schema::hasColumn('discovered_hosts', 'retired_reason')) {
                    $table->text('retired_reason')->nullable()->after('retired_at');
                }

                if (! Schema::hasColumn('discovered_hosts', 'enrollment_status')) {
                    $table->string('enrollment_status')->default('not_enrolled')->after('discovery_status');
                }

                if (! Schema::hasColumn('discovered_hosts', 'agent_id')) {
                    $table->unsignedBigInteger('agent_id')->nullable()->after('managed_network_id');
                }
            });

            if (Schema::hasColumn('discovered_hosts', 'discovery_status')) {
                DB::statement("ALTER TABLE discovered_hosts MODIFY discovery_status VARCHAR(80) NOT NULL DEFAULT 'detected'");
            }

            if (Schema::hasColumn('discovered_hosts', 'host_role')) {
                DB::statement("ALTER TABLE discovered_hosts MODIFY host_role VARCHAR(80) NULL DEFAULT 'client'");
            }

            if (Schema::hasColumn('discovered_hosts', 'enrollment_status')) {
                DB::statement("ALTER TABLE discovered_hosts MODIFY enrollment_status VARCHAR(80) NOT NULL DEFAULT 'not_enrolled'");
            }
        }
    }

    public function down(): void
    {
        //
    }
};
