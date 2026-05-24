<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug N — Faux positifs : rule_fast_write_activity sur file_created
 *
 *   Avant : matchRule() hardcodé sur ['file_modified','modified','file_created','created']
 *   Score_weight = 30, seuil suspect = 25 → TOUT file_created dépassait le seuil
 *   seul → chaque création de .py, .json, .txt, .pdf générait une alerte suspect.
 *
 *   Fix PHP (DynamicDetectionEngineService) : 'file_created' et 'created' retirés
 *   de la liste. La création de fichiers chiffrés reste couverte par :
 *     - analyzeSensitiveExtension() (score par extension)
 *     - rule_mass_rename (file_encrypted_extension)
 *   Cette migration met à jour la description en base pour refléter le fix.
 *
 * Bug R — execution_status mismatch : 'executed' ≠ 'success'
 *
 *   AgentCommandController::result() pose execution_status='executed'.
 *   SocStatusSynchronizerService::syncIncidentFromActions() cherchait 'success'
 *   → $success toujours 0 → resolveIncident() jamais appelé automatiquement
 *   → les incidents restaient ouverts indéfiniment après exécution des actions.
 *   Fix PHP (SocStatusSynchronizerService) : recherche corrigée sur 'executed'.
 *   Pas de données à migrer (les actions passées restent en état 'executed' et
 *   seront correctement comptées dès la prochaine sync).
 *
 * Bug S — rollback_isolation jamais transmis à l'agent
 *
 *   AgentCommandController::pending() construisait $eligibleTypes uniquement avec
 *   isolate_host + kill_process. rollback_isolation était absent.
 *   De plus, si enable_real_isolation=0 et enable_real_process_kill=0 :
 *   $eligibleTypes=[] → early-return → AUCUNE commande envoyée, même rollback.
 *   → Un agent isolé ne pouvait jamais être dé-isolé via la SOC.
 *   Fix PHP (AgentCommandController) : rollback_isolation toujours dans
 *   $eligibleTypes, early-return supprimé.
 *   Pas de colonne DB à modifier.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bug N — mettre à jour la description de rule_fast_write_activity
        DB::table('detection_rules')
            ->where('code', 'rule_fast_write_activity')
            ->update([
                'description' => 'Logique hardcodée (Bug N fix) — matche uniquement si event_type ∈ '
                    .'{file_modified, modified}. '
                    .'file_created et created retirés (faux positifs : score=30 > seuil suspect=25). '
                    .'La création de fichiers chiffrés est couverte par analyzeSensitiveExtension() '
                    .'et rule_mass_rename. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('detection_rules')
            ->where('code', 'rule_fast_write_activity')
            ->update([
                'description' => 'Logique hardcodée — matche si event_type ∈ {file_modified, '
                    .'modified, file_created, created}. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at' => now(),
            ]);
    }
};
