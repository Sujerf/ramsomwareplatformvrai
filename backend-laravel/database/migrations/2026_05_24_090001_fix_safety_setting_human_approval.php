<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug C — require_human_approval_for_sensitive_actions valait '0' en base.
 *
 * La valeur par défaut dans la migration de backfill était '1', mais la
 * migration ne fait un INSERT que si la clé est absente (IF NOT EXISTS).
 * La clé existait déjà avec '0' (remplie lors du premier seeding manuel),
 * donc elle n'a jamais été corrigée.
 *
 * Un isolement ou un kill de processus sans approbation humaine = risque
 * opérationnel grave (faux positif → machine métier mise hors ligne).
 * Cette migration force la valeur à '1'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->where('key', 'require_human_approval_for_sensitive_actions')
            ->update([
                'value'      => '1',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'require_human_approval_for_sensitive_actions')
            ->update([
                'value'      => '0',
                'updated_at' => now(),
            ]);
    }
};
