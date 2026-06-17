<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('system_settings')->where('key', 'agent_offline_threshold_seconds')->exists()) {
            return;
        }

        DB::table('system_settings')->insert([
            'group'       => 'monitoring',
            'key'         => 'agent_offline_threshold_seconds',
            'label'       => 'Seuil hors-ligne agent (secondes)',
            'value_type'  => 'integer',
            'value'       => '300',
            'description' => "Durée en secondes sans heartbeat avant qu'un agent soit considéré hors-ligne et qu'une alerte haute soit déclenchée. Défaut : 300 (5 min). Minimum recommandé : 90.",
            'metadata'    => json_encode(['default' => true, 'default_value' => '300'], JSON_UNESCAPED_UNICODE),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        \App\Models\SystemSetting::clearCache();
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'agent_offline_threshold_seconds')->delete();
        \App\Models\SystemSetting::clearCache();
    }
};
