<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['group' => 'reports', 'key' => 'report_executive_enabled',   'label' => 'Rapport exécutif activé',  'value_type' => 'boolean', 'value' => '0',      'description' => "Active la génération et l'envoi automatique du rapport exécutif périodique."],
            ['group' => 'reports', 'key' => 'report_executive_recipient', 'label' => 'Destinataire du rapport',  'value_type' => 'string',  'value' => '',       'description' => "Adresse e-mail qui recevra le rapport exécutif (PDF en pièce jointe)."],
            ['group' => 'reports', 'key' => 'report_executive_frequency', 'label' => 'Fréquence du rapport',     'value_type' => 'string',  'value' => 'weekly', 'description' => "Fréquence de génération : weekly (chaque lundi à 8h) ou monthly (1er du mois à 8h)."],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'metadata'   => json_encode(['default_value' => $setting['value']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'report_executive_enabled',
            'report_executive_recipient',
            'report_executive_frequency',
        ])->delete();
    }
};
