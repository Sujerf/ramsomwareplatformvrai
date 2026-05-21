<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $settings = [
        'require_human_approval_for_sensitive_actions' => [
            'value'      => '1',
            'value_type' => 'boolean',
            'group'      => 'protection',
            'label'      => 'Approbation humaine requise',
            'description' => "Exige une validation manuelle avant toute action sensible.",
            'default'    => '1',
        ],
        'min_risk_level_for_incident' => [
            'value'      => 'high',
            'value_type' => 'string',
            'group'      => 'detection',
            'label'      => 'Niveau minimum pour incident',
            'description' => "Niveau de risque minimum declenchant la creation automatique d'un incident.",
            'default'    => 'high',
        ],
        'min_risk_level_for_action' => [
            'value'      => 'high',
            'value_type' => 'string',
            'group'      => 'detection',
            'label'      => 'Niveau minimum pour action',
            'description' => "Niveau de risque minimum declenchant la proposition d'une action de protection.",
            'default'    => 'high',
        ],
    ];

    public function up(): void
    {
        foreach ($this->settings as $key => $data) {
            $exists = DB::table('system_settings')->where('key', $key)->exists();
            if (! $exists) {
                DB::table('system_settings')->insert([
                    'key'         => $key,
                    'value'       => $data['value'],
                    'value_type'  => $data['value_type'],
                    'group'       => $data['group'],
                    'label'       => $data['label'],
                    'description' => $data['description'],
                    'metadata'    => json_encode(['default_value' => $data['default']]),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', array_keys($this->settings))
            ->delete();
    }
};
