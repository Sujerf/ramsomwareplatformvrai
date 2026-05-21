<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'discovered_host_id')) {
                $table->unsignedBigInteger('discovered_host_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('agents', 'enrollment_status')) {
                $table->string('enrollment_status')->default('enrolled')->after('status');
            }

            if (! Schema::hasColumn('agents', 'enrollment_token')) {
                $table->string('enrollment_token')->nullable()->after('agent_uuid');
            }

            if (! Schema::hasColumn('agents', 'enrolled_at')) {
                $table->timestamp('enrolled_at')->nullable()->after('last_seen_at');
            }
        });

        if (Schema::hasColumn('agents', 'status')) {
            DB::statement("ALTER TABLE agents MODIFY status VARCHAR(80) NOT NULL DEFAULT 'active'");
        }

        if (Schema::hasColumn('agents', 'enrollment_status')) {
            DB::statement("ALTER TABLE agents MODIFY enrollment_status VARCHAR(80) NOT NULL DEFAULT 'enrolled'");
        }

        Schema::table('discovered_hosts', function (Blueprint $table) {
            if (! Schema::hasColumn('discovered_hosts', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('managed_network_id');
            }

            if (! Schema::hasColumn('discovered_hosts', 'enrollment_status')) {
                $table->string('enrollment_status')->default('not_enrolled')->after('discovery_status');
            }

            if (! Schema::hasColumn('discovered_hosts', 'enrolled_at')) {
                $table->timestamp('enrolled_at')->nullable()->after('last_seen_at');
            }
        });

        if (Schema::hasColumn('discovered_hosts', 'enrollment_status')) {
            DB::statement("ALTER TABLE discovered_hosts MODIFY enrollment_status VARCHAR(80) NOT NULL DEFAULT 'not_enrolled'");
        }
    }

    public function down(): void
    {
        // On garde les colonnes pour préserver la liaison historique.
    }
};
