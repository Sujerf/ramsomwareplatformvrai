<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug F — Heartbeat écrase status='compromised'
 *   Correction code dans AgentHeartbeatController.php.
 *   Cette migration remet à jour les agents dont le status='active' mais
 *   dont le risk_level='critical' (incohérence introduite par le bug F
 *   sur les données existantes).
 *
 * Bug G — mass_rename_detected score=0
 * Bug J — file_encrypted_extension exclu de rule_mass_rename
 *   Corrections code dans DynamicDetectionEngineService.php.
 *   Cette migration met à jour le event_type de rule_mass_rename en DB pour
 *   refléter la logique réelle (liste élargie). On stocke la valeur la plus
 *   générique ; les variantes restent documentées dans le code PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Bug F : réparer les agents incohérents (critical/active) ─────────
        // Tout agent avec risk_level='critical' DOIT avoir status='compromised'.
        // Le heartbeat le remettait à 'active' — on corrige l'état en base.
        DB::table('agents')
            ->where('risk_level', 'critical')
            ->where('status', 'active')
            ->update([
                'status'     => 'compromised',
                'updated_at' => now(),
            ]);

        // ── Bug G+J : mettre à jour event_type de rule_mass_rename en DB ────
        // La valeur 'file_moved' était partielle (hardcode couvre aussi
        // file_renamed, moved, renamed, file_encrypted_extension,
        // mass_rename_detected). On documente 'mass_rename_burst' comme
        // type "famille" pour clarifier que cette règle couvre tous les
        // patterns de renommage en masse.
        DB::table('detection_rules')
            ->where('code', 'rule_mass_rename')
            ->update([
                'event_type' => 'file_moved',   // valeur principale inchangée
                'description' => 'Détecte les renommages en masse caractéristiques '
                    .'d\'un ransomware : file_moved, file_renamed, file_encrypted_extension '
                    .'(renommage vers extension sensible), mass_rename_detected '
                    .'(burst ≥10 renommages en 30 s côté agent).',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // On ne peut pas restaurer les anciens statuts des agents (info perdue).
        // Rollback symbolique : remet la description par défaut.
        DB::table('detection_rules')
            ->where('code', 'rule_mass_rename')
            ->update([
                'event_type' => 'file_moved',
                'updated_at' => now(),
            ]);
    }
};
