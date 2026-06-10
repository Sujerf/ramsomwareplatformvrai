<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rend les 4 règles hardcodées entièrement data-driven.
 *
 * Avant cette migration, DynamicDetectionEngineService::matchRule() utilisait
 * un match() PHP hardcodé pour rule_mass_rename, rule_ransom_note,
 * rule_fast_write_activity et rule_simulation_marker.
 *
 * Après cette migration, chaque règle porte ses conditions dans la colonne JSON
 * `conditions`, et le moteur les évalue de façon générique via evaluateConditions().
 * Ajouter une nouvelle règle complexe ne requiert plus de modification de code.
 *
 * Format des conditions :
 *   event_types[]         — liste des types d'événements déclencheurs
 *   filename_keywords[]   — au moins un mot-clé doit apparaître dans le nom du fichier
 *   path_excludes[]       — patterns d'exclusion ("browser_or_system" = helper dédié)
 *   require_simulation_flag — ne déclenche que si is_simulation=true
 */
return new class extends Migration
{
    public function up(): void
    {
        $rules = [
            'rule_mass_rename' => [
                'event_types'   => [
                    'file_moved',
                    'file_renamed',
                    'moved',                    // alias legacy
                    'renamed',                  // alias legacy
                    'file_encrypted_extension',
                    'mass_rename_detected',
                ],
                'path_excludes' => ['browser_or_system'],
            ],

            'rule_ransom_note' => [
                'filename_keywords' => [
                    'readme',
                    'decrypt',
                    'recover',
                    'how_to_decrypt',
                    'ransom',
                    'restore_files',
                    'instructions',
                ],
            ],

            'rule_fast_write_activity' => [
                'event_types'   => ['file_modified', 'modified'],
                'path_excludes' => ['browser_or_system'],
            ],

            'rule_simulation_marker' => [
                'require_simulation_flag' => true,
            ],
        ];

        foreach ($rules as $code => $conditions) {
            DB::table('detection_rules')
                ->where('code', $code)
                ->update(['conditions' => json_encode($conditions)]);
        }
    }

    public function down(): void
    {
        $codes = [
            'rule_mass_rename',
            'rule_ransom_note',
            'rule_fast_write_activity',
            'rule_simulation_marker',
        ];

        DB::table('detection_rules')
            ->whereIn('code', $codes)
            ->update(['conditions' => null]);
    }
};
