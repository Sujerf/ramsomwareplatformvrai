<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug H — critical_isolation_approval génère 4 actions à elle seule (7 au total)
 *
 *   Avant :
 *     critical_isolation_approval : backup=1 safe=1 iso=1 restrict=1  → 4 actions
 *     critical_manual_process_kill : kill=1                            → 1 action
 *     policy_isolate_critical_agent : alert_only=1                     → 1 action (redondant)
 *     policy_kill_process_manual : alert_only=1                        → 1 action (redondant)
 *     ──────────────────────────────────────────────────────────────────────────
 *     Total : 7 actions pour 1 incident                                → confusion opérateur
 *
 *   Après :
 *     critical_isolation_approval : backup=1 iso=1 (safe+restrict retirés) → 2 actions
 *     critical_manual_process_kill : kill=1                                 → 1 action
 *     policy_isolate_critical_agent : DÉSACTIVÉE (alert_only redondant)
 *     policy_kill_process_manual : DÉSACTIVÉE (alert_only redondant)
 *     ──────────────────────────────────────────────────────────────────────────
 *     Total : 3 actions ciblées                                         → lisible
 *
 *   Raisonnement :
 *   - safe_copy et restrict_path sont des réponses de niveau "high" (préservent
 *     les données, limitent l'accès) — elles appartiennent à high_backup_and_approval,
 *     pas à une politique d'isolation critique.
 *   - Les deux alert_only critical ne servent à rien : le moteur crée déjà une
 *     AlertNotification via NotificationService indépendamment des politiques.
 *
 * Bug I — Doublon politiques suspect (deux alert_only automatiques identiques)
 *
 *   suspect_alert_only (id=1) et policy_notify_suspect (id=5) sont identiques :
 *   même risk_level, même flag alert_only=1, même mode automatic.
 *   → 2 actions alert_only créées par incident suspect.
 *   Fix : désactiver policy_notify_suspect (id=5), garder suspect_alert_only (id=1).
 *
 * Bug K — 4 règles de détection actives mais orphelines
 *
 *   Ces règles ont un event_type que l'agent Python n'envoie jamais :
 *     extension_detected    (event_type=extension_detected)
 *     file_deleted_burst    (event_type=file_deleted)       — pas de on_deleted dans l'agent
 *     shared_folder_access  (event_type=shared_folder_access)
 *     multi_host_propagation (event_type=multi_host_propagation)
 *   Score = 0 pour toujours. Désactivées sans suppression (rollback possible).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Bug H : nettoyer critical_isolation_approval ──────────────────────
        DB::table('protection_policies')
            ->where('code', 'critical_isolation_approval')
            ->update([
                // On conserve : backup (sauvegarde d'urgence avant isolation) + iso
                // On retire  : lock_safe_copy (niveau high) + restrict_path (niveau high)
                'lock_safe_copy' => false,
                'restrict_path'  => false,
                'updated_at'     => now(),
            ]);

        // ── Bug H : désactiver les deux alert_only redondants pour critical ────
        DB::table('protection_policies')
            ->whereIn('code', ['policy_isolate_critical_agent', 'policy_kill_process_manual'])
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);

        // ── Bug I : désactiver le doublon suspect ─────────────────────────────
        DB::table('protection_policies')
            ->where('code', 'policy_notify_suspect')
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);

        // ── Bug K : désactiver les règles orphelines ──────────────────────────
        DB::table('detection_rules')
            ->whereIn('code', [
                'extension_detected',     // event_type jamais émis par l'agent
                'file_deleted_burst',     // pas de on_deleted dans l'agent Python
                'shared_folder_access',   // non implémenté côté agent
                'multi_host_propagation', // non implémenté côté agent
            ])
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Restaurer critical_isolation_approval
        DB::table('protection_policies')
            ->where('code', 'critical_isolation_approval')
            ->update([
                'lock_safe_copy' => true,
                'restrict_path'  => true,
                'updated_at'     => now(),
            ]);

        // Réactiver les politiques désactivées
        DB::table('protection_policies')
            ->whereIn('code', [
                'policy_isolate_critical_agent',
                'policy_kill_process_manual',
                'policy_notify_suspect',
            ])
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);

        // Réactiver les règles orphelines
        DB::table('detection_rules')
            ->whereIn('code', [
                'extension_detected',
                'file_deleted_burst',
                'shared_folder_access',
                'multi_host_propagation',
            ])
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);
    }
};
