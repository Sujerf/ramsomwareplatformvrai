<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug E — 8 seuils fantômes polluent la table detection_thresholds.
 *
 * Les entrées ids 1–8 (suspect_score_min, high_score_min, critical_score_min,
 * file_modification_burst_threshold, rename_burst_threshold,
 * unknown_extension_threshold, deletion_burst_threshold,
 * network_affected_hosts_threshold) ont toutes :
 *   min_score = 0, max_score = NULL, risk_level = 'normal'
 *
 * Elles ont été créées lors du seeding initial comme des paramètres de
 * configuration opérationnels (seuils de burst, etc.) mais ont atterri dans
 * la mauvaise table — leur structure ne correspond pas au modèle de scoring
 * utilisé par matchThreshold().
 *
 * Impact concret : la console d'admin affiche 12 "seuils" dont 8 incohérents,
 * et matchThreshold() pourrait théoriquement les sélectionner en cas de tie.
 * Les 4 vrais seuils (ids 9–12, codes threshold_*) gèrent correctement les
 * plages 0–24 / 25–49 / 50–79 / 80+.
 *
 * Correction : désactiver les 8 fantômes sans les supprimer (rollback possible).
 */
return new class extends Migration
{
    /** Codes des seuils fantômes — jamais utilisés par le moteur de scoring. */
    private array $phantomCodes = [
        'suspect_score_min',
        'high_score_min',
        'critical_score_min',
        'file_modification_burst_threshold',
        'rename_burst_threshold',
        'unknown_extension_threshold',
        'deletion_burst_threshold',
        'network_affected_hosts_threshold',
    ];

    public function up(): void
    {
        DB::table('detection_thresholds')
            ->whereIn('code', $this->phantomCodes)
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('detection_thresholds')
            ->whereIn('code', $this->phantomCodes)
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);
    }
};
