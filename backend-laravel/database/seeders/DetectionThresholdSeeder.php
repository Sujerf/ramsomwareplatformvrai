<?php

namespace Database\Seeders;

use App\Models\DetectionThreshold;
use Illuminate\Database\Seeder;

class DetectionThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            [
                'key' => 'suspect_score_min',
                'label' => 'Score minimum suspect',
                'value' => 25,
                'unit' => 'points',
                'description' => 'Score minimum pour considérer une activité comme suspecte.',
                'is_enabled' => true,
            ],
            [
                'key' => 'high_score_min',
                'label' => 'Score minimum élevé',
                'value' => 55,
                'unit' => 'points',
                'description' => 'Score minimum pour classer une activité en risque élevé.',
                'is_enabled' => true,
            ],
            [
                'key' => 'critical_score_min',
                'label' => 'Score minimum critique',
                'value' => 80,
                'unit' => 'points',
                'description' => 'Score minimum pour classer une activité en risque critique.',
                'is_enabled' => true,
            ],
            [
                'key' => 'file_modification_burst_threshold',
                'label' => 'Seuil modifications massives',
                'value' => 30,
                'unit' => 'fichiers/minute',
                'description' => 'Nombre de modifications de fichiers dans une fenêtre courte.',
                'is_enabled' => true,
            ],
            [
                'key' => 'rename_burst_threshold',
                'label' => 'Seuil renommages massifs',
                'value' => 20,
                'unit' => 'fichiers/minute',
                'description' => 'Nombre de renommages de fichiers dans une fenêtre courte.',
                'is_enabled' => true,
            ],
            [
                'key' => 'unknown_extension_threshold',
                'label' => 'Seuil extensions inconnues',
                'value' => 10,
                'unit' => 'extensions/minute',
                'description' => 'Nombre d’extensions inconnues ou suspectes observées.',
                'is_enabled' => true,
            ],
            [
                'key' => 'deletion_burst_threshold',
                'label' => 'Seuil suppressions massives',
                'value' => 20,
                'unit' => 'fichiers/minute',
                'description' => 'Nombre de suppressions de fichiers dans une fenêtre courte.',
                'is_enabled' => true,
            ],
            [
                'key' => 'network_affected_hosts_threshold',
                'label' => 'Seuil hôtes affectés réseau',
                'value' => 2,
                'unit' => 'hôtes',
                'description' => 'Nombre minimum de machines affectées pour suspecter une propagation réseau.',
                'is_enabled' => true,
            ],
        ];

        foreach ($thresholds as $threshold) {
            $threshold['code'] = $threshold['code'] ?? $threshold['key'];
            DetectionThreshold::updateOrCreate(
                ['key' => $threshold['key']],
                $threshold
            );
        }
    }
}
