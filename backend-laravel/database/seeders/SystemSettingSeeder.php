<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'         => 'ui_theme',
                'value'       => 'soc_dark',
                'value_type'  => 'string',
                'group'       => 'ui',
                'label'       => 'Theme interface',
                'description' => 'Theme visuel principal de la console SOC.',
                'metadata'    => ['default_value' => 'soc_dark'],
            ],
            [
                'key'         => 'notification_ui_enabled',
                'value'       => '1',
                'value_type'  => 'boolean',
                'group'       => 'notifications',
                'label'       => 'Notifications interface',
                'description' => "Active les notifications visibles dans l'interface.",
                'metadata'    => ['default_value' => '1'],
            ],
            [
                'key'         => 'notification_sound_enabled',
                'value'       => '1',
                'value_type'  => 'boolean',
                'group'       => 'notifications',
                'label'       => 'Alarme sonore navigateur',
                'description' => "Active l'alarme sonore navigateur pour les alertes importantes.",
                'metadata'    => ['default_value' => '1'],
            ],
            [
                'key'         => 'notification_mail_enabled',
                'value'       => '0',
                'value_type'  => 'boolean',
                'group'       => 'notifications',
                'label'       => 'Notifications mail',
                'description' => "Active ou desactive l'envoi de mails d'alerte.",
                'metadata'    => ['default_value' => '0'],
            ],
            [
                'key'         => 'notification_mail_recipient',
                'value'       => '',
                'value_type'  => 'string',
                'group'       => 'notifications',
                'label'       => 'Destinataire mail alerte',
                'description' => "Adresse mail de l'administrateur a notifier.",
                'metadata'    => ['default_value' => ''],
            ],
            [
                'key'         => 'notification_min_risk_level',
                'value'       => 'high',
                'value_type'  => 'string',
                'group'       => 'notifications',
                'label'       => 'Niveau minimum de notification',
                'description' => 'Niveau minimum de risque declenchant les notifications importantes.',
                'metadata'    => ['default_value' => 'high'],
            ],
            [
                'key'         => 'protection_execution_enabled',
                'value'       => '1',
                'value_type'  => 'boolean',
                'group'       => 'protection',
                'label'       => 'Execution des protections',
                'description' => "Active la generation et l'execution des actions de protection.",
                'metadata'    => ['default_value' => '1'],
            ],
            [
                'key'         => 'enable_real_isolation',
                'value'       => '0',
                'value_type'  => 'boolean',
                'group'       => 'protection',
                'label'       => 'Isolation reelle',
                'description' => "Autorise les actions d'isolation reelle. Desactive par defaut pour securite.",
                'metadata'    => ['default_value' => '0'],
            ],
            [
                'key'         => 'enable_real_process_kill',
                'value'       => '0',
                'value_type'  => 'boolean',
                'group'       => 'protection',
                'label'       => "Arret reel de processus",
                'description' => "Autorise l'arret reel de processus. Desactive par defaut pour securite.",
                'metadata'    => ['default_value' => '0'],
            ],
            [
                'key'         => 'safe_copy_root',
                'value'       => storage_path('app/ransomshield/safe-copies'),
                'value_type'  => 'string',
                'group'       => 'protection',
                'label'       => 'Dossier copies sures',
                'description' => "Chemin de stockage des copies sures ou sauvegardes d'urgence.",
                'metadata'    => ['default_value' => storage_path('app/ransomshield/safe-copies')],
            ],
            [
                'key'         => 'require_human_approval_for_sensitive_actions',
                'value'       => '1',
                'value_type'  => 'boolean',
                'group'       => 'protection',
                'label'       => 'Approbation humaine requise',
                'description' => "Exige une validation manuelle avant toute action sensible (isolation, arret processus).",
                'metadata'    => ['default_value' => '1'],
            ],
            [
                'key'         => 'min_risk_level_for_incident',
                'value'       => 'high',
                'value_type'  => 'string',
                'group'       => 'detection',
                'label'       => 'Niveau minimum pour incident',
                'description' => "Niveau de risque minimum declenchant la creation automatique d'un incident.",
                'metadata'    => ['default_value' => 'high'],
            ],
            [
                'key'         => 'min_risk_level_for_action',
                'value'       => 'high',
                'value_type'  => 'string',
                'group'       => 'detection',
                'label'       => 'Niveau minimum pour action',
                'description' => "Niveau de risque minimum declenchant la proposition d'une action de protection.",
                'metadata'    => ['default_value' => 'high'],
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
