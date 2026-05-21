<?php

namespace Database\Seeders;

use App\Models\ProtectionPolicy;
use Illuminate\Database\Seeder;

class ProtectionPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'code' => 'suspect_alert_only',
                'name' => 'Risque suspect — alerte uniquement',
                'scope' => 'host',
                'risk_level' => 'suspect',
                'alert_only' => true,
                'emergency_backup' => false,
                'lock_safe_copy' => false,
                'isolate_host' => false,
                'kill_process' => false,
                'restrict_path' => false,
                'execution_mode' => 'automatic',
                'is_enabled' => true,
                'allow_admin_override' => true,
                'description' => 'Pour un risque suspect, le système notifie sans action agressive.',
                'configuration' => [
                    'notify_ui' => true,
                    'notify_sound' => true,
                    'notify_mail' => false,
                ],
            ],
            [
                'code' => 'high_backup_and_approval',
                'name' => 'Risque élevé — sauvegarde et validation humaine',
                'scope' => 'host',
                'risk_level' => 'high',
                'alert_only' => false,
                'emergency_backup' => true,
                'lock_safe_copy' => true,
                'isolate_host' => false,
                'kill_process' => false,
                'restrict_path' => true,
                'execution_mode' => 'approval_required',
                'is_enabled' => true,
                'allow_admin_override' => true,
                'description' => 'Pour un risque élevé, le système propose une sauvegarde d’urgence et une restriction du chemin surveillé après validation.',
                'configuration' => [
                    'requires_admin_review' => true,
                    'safe_copy' => true,
                ],
            ],
            [
                'code' => 'critical_isolation_approval',
                'name' => 'Risque critique — isolation après approbation',
                'scope' => 'host',
                'risk_level' => 'critical',
                'alert_only' => false,
                'emergency_backup' => true,
                'lock_safe_copy' => true,
                'isolate_host' => true,
                'kill_process' => false,
                'restrict_path' => true,
                'execution_mode' => 'approval_required',
                'is_enabled' => true,
                'allow_admin_override' => true,
                'description' => 'Pour un risque critique, le système propose l’isolation de la machine et la protection des copies sûres, avec validation humaine.',
                'configuration' => [
                    'requires_admin_review' => true,
                    'real_isolation_depends_on_system_setting' => true,
                ],
            ],
            [
                'code' => 'critical_manual_process_kill',
                'name' => 'Risque critique — arrêt processus manuel',
                'scope' => 'host',
                'risk_level' => 'critical',
                'alert_only' => false,
                'emergency_backup' => false,
                'lock_safe_copy' => false,
                'isolate_host' => false,
                'kill_process' => true,
                'restrict_path' => false,
                'execution_mode' => 'manual_only',
                'is_enabled' => true,
                'allow_admin_override' => true,
                'description' => 'L’arrêt réel d’un processus reste manuel pour éviter une action dangereuse ou destructive.',
                'configuration' => [
                    'requires_manual_execution' => true,
                    'real_process_kill_depends_on_system_setting' => true,
                ],
            ],
        ];

        foreach ($policies as $policy) {
            ProtectionPolicy::updateOrCreate(
                ['code' => $policy['code']],
                $policy
            );
        }
    }
}
