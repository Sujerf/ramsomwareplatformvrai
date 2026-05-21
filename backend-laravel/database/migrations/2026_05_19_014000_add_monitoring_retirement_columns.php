<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        });
    }

    public function down(): void
    {
        // On conserve ces colonnes pour garder l'historique.
    }
};
