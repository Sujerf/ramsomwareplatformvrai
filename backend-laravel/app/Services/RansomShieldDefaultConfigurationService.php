<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RansomShieldDefaultConfigurationService
{
    public function resetAll(): array
    {
        return [
            'sensitive_extensions' => $this->syncSensitiveExtensions(),
            'detection_rules' => $this->syncDetectionRules(),
            'detection_thresholds' => $this->syncDetectionThresholds(),
            'protection_policies' => $this->syncProtectionPolicies(),
            'system_settings' => $this->syncSystemSettings(),
        ];
    }

    public function syncSensitiveExtensions(): int
    {
        $items = [
            ['extension' => 'locked',    'category' => 'suspicious', 'risk_level' => 'critical', 'score_weight' => 80, 'description' => 'Extension typique observée dans des scénarios ransomware.'],
            ['extension' => 'encrypted', 'category' => 'suspicious', 'risk_level' => 'critical', 'score_weight' => 80, 'description' => 'Extension indiquant un fichier potentiellement chiffré.'],
            ['extension' => 'crypt',     'category' => 'suspicious', 'risk_level' => 'critical', 'score_weight' => 75, 'description' => 'Extension suspecte liée au chiffrement.'],
            ['extension' => 'enc',       'category' => 'suspicious', 'risk_level' => 'high',     'score_weight' => 60, 'description' => 'Extension de chiffrement potentielle.'],
            ['extension' => 'pay',       'category' => 'suspicious', 'risk_level' => 'high',     'score_weight' => 55, 'description' => 'Extension ou marqueur potentiellement lié à une demande de rançon.'],
            ['extension' => 'docx',      'category' => 'important',  'risk_level' => 'suspect',  'score_weight' => 10, 'description' => 'Document bureautique sensible à surveiller en cas de modification massive.'],
            ['extension' => 'xlsx',      'category' => 'important',  'risk_level' => 'suspect',  'score_weight' => 10, 'description' => 'Tableur sensible à surveiller en cas de modification massive.'],
            ['extension' => 'pdf',       'category' => 'important',  'risk_level' => 'suspect',  'score_weight' => 10, 'description' => 'Document PDF sensible à surveiller en cas de modification massive.'],
            ['extension' => 'csv',       'category' => 'important',  'risk_level' => 'suspect',  'score_weight' => 10, 'description' => 'Fichier CSV sensible à surveiller en cas de modification massive.'],
            ['extension' => 'sql',       'category' => 'important',  'risk_level' => 'high',     'score_weight' => 45, 'description' => 'Dump ou fichier SQL potentiellement critique.'],
            ['extension' => 'zip',       'category' => 'suspicious', 'risk_level' => 'suspect',  'score_weight' => 15, 'description' => 'Archive à surveiller selon contexte.'],
        ];

        foreach ($items as $item) {
            DB::table('sensitive_extensions')->updateOrInsert(
                ['extension' => $item['extension']],
                $this->withTimestamps('sensitive_extensions', [
                    'extension' => $item['extension'],
                    'category' => $item['category'],
                    'risk_level' => $item['risk_level'],
                    'score_weight' => $item['score_weight'],
                    'is_enabled' => true,
                    'description' => $item['description'],
                    'metadata' => json_encode([
                        'default' => true,
                        'default_score_weight' => $item['score_weight'],
                        'default_risk_level' => $item['risk_level'],
                        'linked_to' => ['rule_sensitive_extension'],
                    ], JSON_UNESCAPED_UNICODE),
                ])
            );
        }

        return count($items);
    }

    public function syncDetectionRules(): int
    {
        $items = [
            ['code' => 'rule_sensitive_extension', 'name' => 'Extension sensible détectée', 'event_type' => 'file_extension_changed', 'risk_level' => 'high', 'score_weight' => 45, 'description' => 'Déclenchée lorsqu’un fichier reçoit une extension sensible configurée.', 'linked_to' => ['sensitive_extensions', 'thresholds', 'policies']],
            ['code' => 'rule_mass_rename', 'name' => 'Renommage massif de fichiers', 'event_type' => 'file_moved', 'risk_level' => 'high', 'score_weight' => 55, 'description' => 'Déclenchée lorsqu’un agent observe beaucoup de renommages sur une courte période.', 'linked_to' => ['thresholds', 'policies']],
            ['code' => 'rule_ransom_note', 'name' => 'Note de rançon détectée', 'event_type' => 'file_created', 'risk_level' => 'critical', 'score_weight' => 90, 'description' => 'Déclenchée si un fichier de type README, RECOVER ou HOW_TO_DECRYPT apparaît.', 'linked_to' => ['thresholds', 'policies']],
            ['code' => 'rule_fast_write_activity', 'name' => 'Activité d’écriture rapide', 'event_type' => 'file_modified', 'risk_level' => 'suspect', 'score_weight' => 30, 'description' => 'Détecte une fréquence anormale de modifications de fichiers.', 'linked_to' => ['thresholds']],
            ['code' => 'rule_simulation_marker', 'name' => 'Événement de simulation contrôlée', 'event_type' => 'simulation', 'risk_level' => 'suspect', 'score_weight' => 20, 'description' => 'Permet de tester le moteur sans déclencher d’action réelle.', 'linked_to' => ['thresholds', 'policies']],
        ];

        foreach ($items as $item) {
            DB::table('detection_rules')->updateOrInsert(
                ['code' => $item['code']],
                $this->withTimestamps('detection_rules', [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'event_type' => $item['event_type'],
                    'risk_level' => $item['risk_level'],
                    'score_weight' => $item['score_weight'],
                    'is_enabled' => true,
                    'description' => $item['description'],
                    'metadata' => json_encode([
                        'default' => true,
                        'default_score_weight' => $item['score_weight'],
                        'default_risk_level' => $item['risk_level'],
                        'linked_to' => $item['linked_to'],
                    ], JSON_UNESCAPED_UNICODE),
                ])
            );
        }

        return count($items);
    }

    public function syncDetectionThresholds(): int
    {
        /*
         * Important :
         * On nettoie les anciennes lignes cassées avec code vide.
         * C’est ce qui causait les doublons et les colonnes vides.
         */
        DB::table('detection_thresholds')
            ->whereNull('code')
            ->orWhere('code', '')
            ->delete();

        $items = [
            ['code' => 'threshold_normal', 'name' => 'Normal', 'risk_level' => 'normal', 'min_score' => 0, 'max_score' => 24],
            ['code' => 'threshold_suspect', 'name' => 'Suspect', 'risk_level' => 'suspect', 'min_score' => 25, 'max_score' => 49],
            ['code' => 'threshold_high', 'name' => 'High', 'risk_level' => 'high', 'min_score' => 50, 'max_score' => 79],
            ['code' => 'threshold_critical', 'name' => 'Critical', 'risk_level' => 'critical', 'min_score' => 80, 'max_score' => null],
        ];

        foreach ($items as $item) {
            DB::table('detection_thresholds')->updateOrInsert(
                ['code' => $item['code']],
                $this->withTimestamps('detection_thresholds', [
                    'code' => $item['code'],
                    'key' => $item['code'],
                    'label' => $item['name'],
                    'name' => $item['name'],
                    'value' => (string) $item['min_score'],
                    'risk_level' => $item['risk_level'],
                    'level' => $item['risk_level'],
                    'severity' => $item['risk_level'],
                    'min_score' => $item['min_score'],
                    'score_min' => $item['min_score'],
                    'max_score' => $item['max_score'],
                    'score_max' => $item['max_score'],
                    'is_enabled' => true,
                    'description' => 'Seuil par défaut RansomShield pour le niveau '.$item['risk_level'].'.',
                    'metadata' => json_encode([
                        'default' => true,
                        'linked_from' => ['detection_rules'],
                        'linked_to' => ['protection_policies'],
                    ], JSON_UNESCAPED_UNICODE),
                ])
            );
        }

        return count($items);
    }

    public function syncProtectionPolicies(): int
    {
        $items = [
            ['code' => 'policy_notify_suspect', 'name' => 'Notifier en cas de comportement suspect', 'scope' => 'agent', 'risk_level' => 'suspect', 'action_type' => 'notify', 'execution_mode' => 'automatic', 'description' => 'Crée une notification UI pour un signal suspect.'],
            ['code' => 'policy_restrict_high_path', 'name' => 'Proposer restriction de chemin en high', 'scope' => 'path', 'risk_level' => 'high', 'action_type' => 'restrict_path', 'execution_mode' => 'approval_required', 'description' => 'Propose une restriction de chemin après confirmation humaine.'],
            ['code' => 'policy_isolate_critical_agent', 'name' => 'Isolation machine critique avec approbation', 'scope' => 'agent', 'risk_level' => 'critical', 'action_type' => 'isolate_agent', 'execution_mode' => 'approval_required', 'description' => 'Propose l’isolation d’une machine uniquement après validation humaine.'],
            ['code' => 'policy_kill_process_manual', 'name' => 'Arrêt processus suspect manuel', 'scope' => 'agent', 'risk_level' => 'critical', 'action_type' => 'kill_process', 'execution_mode' => 'manual', 'description' => 'Action sensible conservée en manuel pour éviter les faux positifs dangereux.'],
        ];

        foreach ($items as $item) {
            DB::table('protection_policies')->updateOrInsert(
                ['code' => $item['code']],
                $this->withTimestamps('protection_policies', [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'scope' => $item['scope'],
                    'risk_level' => $item['risk_level'],
                    'action_type' => $item['action_type'],
                    'execution_mode' => $item['execution_mode'],
                    'is_enabled' => true,
                    'description' => $item['description'],
                    'metadata' => json_encode([
                        'default' => true,
                        'linked_from' => ['detection_thresholds'],
                        'requires_human_validation' => in_array($item['execution_mode'], ['approval_required', 'manual'], true),
                    ], JSON_UNESCAPED_UNICODE),
                ])
            );
        }

        return count($items);
    }

    public function syncSystemSettings(): int
    {
        $items = [
            // ── Protection ────────────────────────────────────────────────────
            ['group' => 'protection', 'key' => 'protection_execution_enabled',                 'label' => 'Exécution des protections',       'value_type' => 'boolean', 'value' => '1',    'description' => "Active la génération et l'exécution des actions de protection."],
            ['group' => 'protection', 'key' => 'enable_real_isolation',                        'label' => 'Isolation réelle',                'value_type' => 'boolean', 'value' => '0',    'description' => "Autorise les actions d'isolation réelle. Désactivé par défaut pour sécurité."],
            ['group' => 'protection', 'key' => 'enable_real_process_kill',                     'label' => 'Arrêt réel de processus',    'value_type' => 'boolean', 'value' => '0',    'description' => "Autorise l'arrêt réel de processus. Désactivé par défaut pour sécurité."],
            ['group' => 'protection', 'key' => 'require_human_approval_for_sensitive_actions', 'label' => 'Approbation humaine obligatoire',      'value_type' => 'boolean', 'value' => '1',    'description' => 'Exige une validation humaine pour les actions sensibles.'],
            ['group' => 'protection', 'key' => 'safe_copy_root',                               'label' => 'Dossier copies sûres',            'value_type' => 'string',  'value' => '',     'description' => "Chemin de stockage des copies sûres ou sauvegardes d'urgence."],

            // ── Détection ────────────────────────────────────────────────────────
            ['group' => 'detection',  'key' => 'min_risk_level_for_incident',                  'label' => 'Niveau minimum pour incident',         'value_type' => 'string',  'value' => 'high', 'description' => "Niveau de risque minimum déclenchant la création automatique d'un incident."],
            ['group' => 'detection',  'key' => 'min_risk_level_for_action',                    'label' => 'Niveau minimum pour action',           'value_type' => 'string',  'value' => 'high', 'description' => "Niveau de risque minimum déclenchant la proposition d'une action de protection."],

            // ── Notifications ─────────────────────────────────────────────────
            ['group' => 'notifications', 'key' => 'notification_ui_enabled',                   'label' => 'Notifications interface',              'value_type' => 'boolean', 'value' => '1',    'description' => "Active les notifications visibles dans l'interface."],
            ['group' => 'notifications', 'key' => 'notification_sound_enabled',                'label' => 'Alarme sonore navigateur',             'value_type' => 'boolean', 'value' => '1',    'description' => "Active l'alarme sonore navigateur pour les alertes importantes."],
            ['group' => 'notifications', 'key' => 'notification_mail_enabled',                 'label' => 'Notifications mail',                  'value_type' => 'boolean', 'value' => '0',    'description' => "Active ou désactive l'envoi de mails d'alerte."],
            ['group' => 'notifications', 'key' => 'notification_mail_recipient',               'label' => 'Destinataire mail alerte',             'value_type' => 'string',  'value' => '',     'description' => "Adresse mail de l'administrateur à notifier."],
            ['group' => 'notifications', 'key' => 'notification_min_risk_level',               'label' => 'Niveau minimum de notification',       'value_type' => 'string',  'value' => 'high', 'description' => 'Niveau minimum de risque déclenchant les notifications importantes.'],
            ['group' => 'notifications', 'key' => 'notification_webhook_enabled',             'label' => 'Notifications webhook',               'value_type' => 'boolean', 'value' => '0',    'description' => "Active l'envoi d'alertes vers un webhook Slack, Teams ou générique."],
            ['group' => 'notifications', 'key' => 'notification_webhook_url',                 'label' => 'URL du webhook',                      'value_type' => 'string',  'value' => '',     'description' => 'URL complète du webhook entrant (Slack, Teams, n8n, Zapier…).'],
            ['group' => 'notifications', 'key' => 'notification_webhook_type',                'label' => 'Type de webhook',                     'value_type' => 'string',  'value' => 'slack','description' => 'Format du payload : slack, teams, ou generic (JSON brut).'],

            // ── Surveillance agents ───────────────────────────────────────────
            ['group' => 'monitoring', 'key' => 'agent_offline_threshold_seconds', 'label' => 'Seuil hors-ligne agent (secondes)', 'value_type' => 'integer', 'value' => '300', 'description' => "Durée en secondes sans heartbeat avant qu'un agent soit considéré hors-ligne et qu'une alerte haute soit déclenchée. Défaut : 300 (5 min)."],

            // ── Interface ─────────────────────────────────────────────────────
            // Corrigé : 'soc_dark' est le thème par défaut réel (soc.blade.php + SystemSettingSeeder).
            // L'ancienne valeur 'light' n'existe pas dans le sélecteur et produisait un thème inconnu.
            ['group' => 'ui',            'key' => 'ui_theme',                                  'label' => 'Thème interface',                 'value_type' => 'string',  'value' => 'soc_dark', 'description' => 'Thème visuel par défaut de la console SOC.'],
        ];

        foreach ($items as $item) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $item['key']],
                $this->withTimestamps('system_settings', [
                    'group'       => $item['group'],
                    'key'         => $item['key'],
                    'label'       => $item['label'],
                    'value_type'  => $item['value_type'],
                    'value'       => $item['value'],
                    'description' => $item['description'],
                    'metadata'    => json_encode([
                        'default'       => true,
                        'default_value' => $item['value'],
                    ], JSON_UNESCAPED_UNICODE),
                ])
            );
        }

        // Invalider le cache SystemSetting — DB::table() contourne l'observer Eloquent
        \App\Models\SystemSetting::clearCache();

        return count($items);
    }

    private function withTimestamps(string $table, array $data): array
    {
        $data = $this->filterColumns($table, $data);

        if (Schema::hasColumn($table, 'created_at')) {
            $data['created_at'] = $data['created_at'] ?? now();
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }

        return $data;
    }

    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }
}
