<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $agentId = DB::table('agents')->value('id');
        $policyId = DB::table('protection_policies')->value('id');
        $now = now();

        // --- Event ---
        $eventId = DB::table('events')->insertGetId([
            'agent_id'       => $agentId,
            'event_uuid'     => Str::uuid(),
            'event_type'     => 'file_encrypted_extension',
            'path'           => '/home/user/documents/rapport_financier.docx.locked',
            'old_path'       => '/home/user/documents/rapport_financier.docx',
            'file_extension' => '.locked',
            'file_size'      => 204800,
            'file_hash'      => hash('sha256', 'test_file_content'),
            'score'          => 85,
            'risk_level'     => 'critical',
            'is_simulation'  => true,
            'raw_payload'    => json_encode([
                'pid'         => 4892,
                'process'     => 'unknown_process.exe',
                'user'        => 'DESKTOP\\user',
                'entropy'     => 7.92,
            ]),
            'metadata'       => json_encode(['source' => 'test_seeder']),
            'observed_at'    => $now->copy()->subMinutes(15),
            'created_at'     => $now->copy()->subMinutes(15),
            'updated_at'     => $now->copy()->subMinutes(15),
        ]);

        // --- Incident ---
        $incidentId = DB::table('incidents')->insertGetId([
            'agent_id'    => $agentId,
            'title'       => '[TEST] Chiffrement massif détecté — simulation ransomware',
            'description' => 'Activité suspecte de chiffrement de fichiers détectée sur la machine. Plusieurs extensions sensibles renommées en .locked en moins de 30 secondes.',
            'status'      => 'active',
            'risk_level'  => 'critical',
            'risk_score'  => 92,
            'detected_at' => $now->copy()->subMinutes(14),
            'metadata'    => json_encode([
                'source'     => 'test_seeder',
                'simulation' => true,
            ]),
            'created_at'  => $now->copy()->subMinutes(14),
            'updated_at'  => $now->copy()->subMinutes(14),
        ]);

        // --- Alert ---
        $alertId = DB::table('alerts')->insertGetId([
            'agent_id'    => $agentId,
            'incident_id' => $incidentId,
            'event_id'    => $eventId,
            'title'       => '[TEST] Extension .locked détectée sur fichier critique',
            'message'     => 'Le fichier rapport_financier.docx a été renommé en .locked. Entropie élevée (7.92) — chiffrement probable.',
            'status'      => 'active',
            'risk_level'  => 'critical',
            'score'       => 85,
            'detected_at' => $now->copy()->subMinutes(14),
            'metadata'    => json_encode(['source' => 'test_seeder']),
            'created_at'  => $now->copy()->subMinutes(14),
            'updated_at'  => $now->copy()->subMinutes(14),
        ]);

        // Update event with incident_id
        DB::table('events')->where('id', $eventId)->update(['incident_id' => $incidentId]);

        // --- ProtectionAction ---
        DB::table('protection_actions')->insert([
            'agent_id'              => $agentId,
            'incident_id'           => $incidentId,
            'protection_policy_id'  => $policyId,
            'action_type'           => 'isolate_host',
            'decision_mode'         => 'automatic',
            'execution_status'      => 'waiting_approval',
            'approval_status'       => 'pending',
            'is_reversible'         => true,
            'rollback_available'    => true,
            'description'           => 'Isoler la machine du réseau suite à détection de chiffrement massif.',
            'payload'               => json_encode([
                'target_ip'  => '192.168.1.50',
                'agent_uuid' => DB::table('agents')->where('id', $agentId)->value('agent_uuid'),
            ]),
            'proposed_at'           => $now->copy()->subMinutes(13),
            'created_at'            => $now->copy()->subMinutes(13),
            'updated_at'            => $now->copy()->subMinutes(13),
        ]);

        $this->command->info('Test data created:');
        $this->command->line("  event_id=$eventId | incident_id=$incidentId | alert_id=$alertId | action created");
    }
}
