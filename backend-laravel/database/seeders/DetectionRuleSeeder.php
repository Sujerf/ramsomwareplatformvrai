<?php

namespace Database\Seeders;

use App\Models\DetectionRule;
use Illuminate\Database\Seeder;

class DetectionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'code' => 'file_modified_burst',
                'name' => 'Modification massive de fichiers',
                'event_type' => 'file_modified',
                'risk_level' => 'high',
                'score_weight' => 30,
                'is_enabled' => true,
                'description' => 'Détecte un volume anormal de modifications de fichiers sur une courte période.',
                'conditions' => [
                    'threshold_key' => 'file_modification_burst_threshold',
                    'window_seconds' => 60,
                ],
            ],
            [
                'code' => 'file_renamed_burst',
                'name' => 'Renommage massif de fichiers',
                'event_type' => 'file_renamed',
                'risk_level' => 'high',
                'score_weight' => 35,
                'is_enabled' => true,
                'description' => 'Détecte un volume anormal de renommages de fichiers sur une courte période.',
                'conditions' => [
                    'threshold_key' => 'rename_burst_threshold',
                    'window_seconds' => 60,
                ],
            ],
            [
                'code' => 'extension_detected',
                'name' => 'Extension suspecte détectée',
                'event_type' => 'extension_detected',
                'risk_level' => 'critical',
                'score_weight' => 45,
                'is_enabled' => true,
                'description' => 'Détecte l’apparition d’une extension associée à un chiffrement ou une compromission.',
                'conditions' => [
                    'uses_sensitive_extensions_category' => 'suspicious',
                ],
            ],
            [
                'code' => 'ransom_note_detected',
                'name' => 'Note de rançon détectée',
                'event_type' => 'ransom_note_detected',
                'risk_level' => 'critical',
                'score_weight' => 50,
                'is_enabled' => true,
                'description' => 'Détecte la création ou présence d’un fichier ressemblant à une note de rançon.',
                'conditions' => [
                    'keywords' => [
                        'README_FOR_DECRYPT',
                        'HOW_TO_DECRYPT',
                        'DECRYPT_INSTRUCTIONS',
                        'RECOVER_FILES',
                        'ransom',
                    ],
                ],
            ],
            [
                'code' => 'file_deleted_burst',
                'name' => 'Suppression massive de fichiers',
                'event_type' => 'file_deleted',
                'risk_level' => 'high',
                'score_weight' => 30,
                'is_enabled' => true,
                'description' => 'Détecte des suppressions massives de fichiers sur une courte période.',
                'conditions' => [
                    'threshold_key' => 'deletion_burst_threshold',
                    'window_seconds' => 60,
                ],
            ],
            [
                'code' => 'shared_folder_access',
                'name' => 'Activité suspecte sur dossier partagé',
                'event_type' => 'shared_folder_access',
                'risk_level' => 'high',
                'score_weight' => 25,
                'is_enabled' => true,
                'description' => 'Détecte une activité anormale sur un dossier réseau ou un partage surveillé.',
                'conditions' => [
                    'scope' => 'share',
                ],
            ],
            [
                'code' => 'multi_host_propagation',
                'name' => 'Propagation multi-hôtes',
                'event_type' => 'multi_host_propagation',
                'risk_level' => 'critical',
                'score_weight' => 60,
                'is_enabled' => true,
                'description' => 'Détecte des signaux similaires sur plusieurs machines du réseau.',
                'conditions' => [
                    'threshold_key' => 'network_affected_hosts_threshold',
                    'window_seconds' => 300,
                ],
            ],
        ];

        foreach ($rules as $rule) {
            DetectionRule::updateOrCreate(
                ['code' => $rule['code']],
                $rule
            );
        }
    }
}
