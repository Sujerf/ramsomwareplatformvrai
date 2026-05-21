<?php

namespace Database\Seeders;

use App\Models\AttackProfile;
use Illuminate\Database\Seeder;

class AttackProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'code' => 'ransomware_behavior',
                'name' => 'Comportement ransomware générique',
                'description' => 'Profil de détection basé sur des modifications, renommages, suppressions massives, extensions suspectes et notes de rançon.',
                'is_simulation' => false,
                'is_enabled' => true,
                'indicators' => [
                    'file_modification_burst',
                    'file_rename_burst',
                    'suspicious_extension',
                    'ransom_note',
                    'deletion_burst',
                ],
            ],
            [
                'code' => 'controlled_demo_ransomware',
                'name' => 'Simulation contrôlée ransomware',
                'description' => 'Profil utilisé uniquement pour les démonstrations contrôlées du mémoire. Il ne s’agit pas d’un vrai ransomware.',
                'is_simulation' => true,
                'is_enabled' => true,
                'indicators' => [
                    'simulated_file_rename',
                    'simulated_extension_locked',
                    'simulated_ransom_note',
                ],
            ],
            [
                'code' => 'shared_folder_propagation',
                'name' => 'Propagation sur dossier partagé',
                'description' => 'Profil orienté détection d’activité suspecte sur un partage réseau ou dossier commun.',
                'is_simulation' => false,
                'is_enabled' => true,
                'indicators' => [
                    'shared_folder_access',
                    'multi_host_activity',
                    'network_affected_hosts',
                ],
            ],
        ];

        foreach ($profiles as $profile) {
            AttackProfile::updateOrCreate(
                ['code' => $profile['code']],
                $profile
            );
        }
    }
}
