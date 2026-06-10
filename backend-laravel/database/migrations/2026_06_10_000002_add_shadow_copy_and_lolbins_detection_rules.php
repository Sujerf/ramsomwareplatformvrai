<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute deux règles de détection pour les phases amont d'une attaque ransomware :
 *
 *  rule_shadow_copy_deletion — score 95 / critical
 *    Signal quasi-certain : aucun usage légitime de "vssadmin delete shadows"
 *    ou "wmic shadowcopy delete" en dehors d'une attaque ou d'un test red-team.
 *    L'agent Python envoie shadow_copy_deletion_detected après avoir matché les
 *    patterns SHADOW_COPY_CMDLINE_PATTERNS sur la ligne de commande du processus.
 *
 *  rule_lolbins_abuse — score 70 / high
 *    Détecte les outils Windows légitimes utilisés à des fins malveillantes :
 *    certutil, bitsadmin, mshta, regsvr32, PowerShell encodé, rundll32 JS…
 *    Signal moins certain que VSS (faux positifs possibles en environnement dev),
 *    mais très discriminant en production.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('detection_rules')->upsert(
            [
                [
                    'code'         => 'rule_shadow_copy_deletion',
                    'name'         => 'Suppression des clichés VSS',
                    'description'  => 'Détecte la suppression des sauvegardes VSS/LVM via vssadmin, wmic, bcdedit ou wbadmin. Signal quasi-certain d\'une attaque ransomware.',
                    'event_type'   => null,
                    'risk_level'   => 'critical',
                    'score_weight' => 95,
                    'is_enabled'   => true,
                    'conditions'   => json_encode([
                        'event_types' => ['shadow_copy_deletion_detected'],
                    ]),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'code'         => 'rule_lolbins_abuse',
                    'name'         => 'LOLBin détourné',
                    'description'  => 'Détecte l\'utilisation malveillante d\'outils Windows légitimes : certutil, bitsadmin, mshta, regsvr32, PowerShell encodé, rundll32 JS.',
                    'event_type'   => null,
                    'risk_level'   => 'high',
                    'score_weight' => 70,
                    'is_enabled'   => true,
                    'conditions'   => json_encode([
                        'event_types' => ['lolbins_abuse_detected'],
                    ]),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ],
            ['code'],                           // clé de conflit
            ['name', 'description', 'event_type', 'risk_level', 'score_weight', 'is_enabled', 'conditions', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('detection_rules')
            ->whereIn('code', ['rule_shadow_copy_deletion', 'rule_lolbins_abuse'])
            ->delete();
    }
};
