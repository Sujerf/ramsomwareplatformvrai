<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correction des doublons de règles de détection et ajout de la règle
 * pour les processus suspects.
 *
 * Bug B — Double comptage des règles :
 *   Les règles "ancienne génération" (ids 1, 2, 4) sont désactivées car
 *   supersédées par leurs équivalents rule_* (ids 9, 10, 11) avec un
 *   meilleur scoring et une logique de correspondance plus large.
 *   La règle rule_sensitive_extension (id 8) est aussi désactivée :
 *   analyzeSensitiveExtension() gère déjà le scoring par extension de façon
 *   granulaire depuis la table sensitive_extensions — un double comptage
 *   ajoutait systématiquement +45 sur chaque extension sensible.
 *
 * Bug A — suspicious_process_detected jamais scoré :
 *   L'agent envoie des événements "suspicious_process_detected" pour openssl,
 *   gpg, cryptsetup, rclone, etc. Aucune règle ne les capturait → score=0,
 *   risk_level=normal, aucune alerte jamais déclenchée.
 *   La règle rule_suspicious_process (score:35, suspect) corrige ce manque.
 *   Elle est capturée via genericRuleMatch() (event_type exact).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Bug B : désactiver les doublons ancienne génération ───────────────

        DB::table('detection_rules')
            ->whereIn('code', [
                'file_modified_burst',    // doublonne rule_fast_write_activity
                'file_renamed_burst',     // doublonne rule_mass_rename
                'ransom_note_detected',   // doublonne rule_ransom_note
                'rule_sensitive_extension', // doublonne analyzeSensitiveExtension()
            ])
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);

        // ── Bug A : ajouter la règle pour les processus suspects ──────────────

        // Évite un doublon si la migration est rejouée
        if (! DB::table('detection_rules')->where('code', 'rule_suspicious_process')->exists()) {
            DB::table('detection_rules')->insert([
                'code'        => 'rule_suspicious_process',
                'name'        => 'Processus suspect détecté',
                'description' => 'Processus associé à des opérations de chiffrement ou d\'exfiltration '
                    .'détecté sur la machine (openssl, gpg, cryptsetup, rclone, 7z…). '
                    .'Un seul processus = suspect. Combiné à d\'autres signaux = haute probabilité ransomware.',
                'event_type'  => 'suspicious_process_detected',
                'score_weight'=> 35,
                'risk_level'  => 'suspect',
                'is_enabled'  => true,
                'metadata'    => json_encode([
                    'source'  => 'agent_process_monitor',
                    'keywords'=> ['openssl', 'gpg', 'cryptsetup', 'encfs', 'rclone', '7z', 'zip', 'tar'],
                ]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Réactiver les anciennes règles
        DB::table('detection_rules')
            ->whereIn('code', [
                'file_modified_burst',
                'file_renamed_burst',
                'ransom_note_detected',
                'rule_sensitive_extension',
            ])
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);

        // Supprimer la nouvelle règle
        DB::table('detection_rules')
            ->where('code', 'rule_suspicious_process')
            ->delete();
    }
};
