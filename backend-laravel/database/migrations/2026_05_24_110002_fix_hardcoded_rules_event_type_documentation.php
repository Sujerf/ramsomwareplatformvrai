<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug M — event_type en DB incohérent avec la logique hardcodée du moteur
 *
 * Dans DynamicDetectionEngineService::matchRule(), 4 règles ont un case
 * hardcodé dans le match() PHP — elles n'atteignent JAMAIS genericRuleMatch()
 * et leur event_type en base est donc totalement ignoré à l'exécution :
 *
 *   rule_mass_rename       : match hardcodé sur in_array(eventType, [...])
 *                            event_type en DB = 'file_moved' (liste partielle)
 *   rule_ransom_note       : match hardcodé sur looksLikeRansomNote($path)
 *                            event_type en DB = 'file_created' (trompeur)
 *   rule_fast_write_activity : match hardcodé sur in_array(eventType, [...])
 *                            event_type en DB = 'file_modified' (liste partielle)
 *   rule_simulation_marker : match hardcodé sur payload['is_simulation']
 *                            event_type en DB = 'simulation' (inexistant)
 *
 * Conséquence sur la maintenance :
 *   - Un opérateur qui lit la console de configuration voit un event_type
 *     trompeur et pense pouvoir changer le comportement depuis la DB.
 *   - Une modification de event_type en DB pour ces règles n'a aucun effet.
 *   - Confusion lors d'un audit ou d'un débogage.
 *
 * Fix :
 *   - Mettre event_type=NULL pour signaler explicitement "logique hardcodée,
 *     non pilotable depuis la DB".
 *   - Enrichir les descriptions pour documenter la logique PHP réelle.
 *   - rule_suspicious_process conserve event_type='suspicious_process_detected'
 *     (elle passe par genericRuleMatch → l'event_type en DB est réellement utilisé).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('detection_rules')
            ->where('code', 'rule_mass_rename')
            ->update([
                'event_type'  => null,
                'description' => 'Logique hardcodée — matche si event_type ∈ {file_moved, '
                    .'file_renamed, moved, renamed, file_encrypted_extension, '
                    .'mass_rename_detected}. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at'  => now(),
            ]);

        DB::table('detection_rules')
            ->where('code', 'rule_ransom_note')
            ->update([
                'event_type'  => null,
                'description' => 'Logique hardcodée — matche si looksLikeRansomNote($path) = true '
                    .'(keywords : readme, decrypt, recover, restore, ransom, '
                    .'how_to_decrypt, instructions dans le nom du fichier). '
                    .'event_type non utilisé. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at'  => now(),
            ]);

        DB::table('detection_rules')
            ->where('code', 'rule_fast_write_activity')
            ->update([
                'event_type'  => null,
                'description' => 'Logique hardcodée — matche si event_type ∈ {file_modified, '
                    .'modified, file_created, created}. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at'  => now(),
            ]);

        DB::table('detection_rules')
            ->where('code', 'rule_simulation_marker')
            ->update([
                'event_type'  => null,
                'description' => 'Logique hardcodée — matche si payload[is_simulation] = true. '
                    .'event_type non utilisé. '
                    .'Ne peut pas être modifié depuis la DB : voir DynamicDetectionEngineService.',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('detection_rules')
            ->where('code', 'rule_mass_rename')
            ->update(['event_type' => 'file_moved', 'updated_at' => now()]);

        DB::table('detection_rules')
            ->where('code', 'rule_ransom_note')
            ->update(['event_type' => 'file_created', 'updated_at' => now()]);

        DB::table('detection_rules')
            ->where('code', 'rule_fast_write_activity')
            ->update(['event_type' => 'file_modified', 'updated_at' => now()]);

        DB::table('detection_rules')
            ->where('code', 'rule_simulation_marker')
            ->update(['event_type' => 'simulation', 'updated_at' => now()]);
    }
};
