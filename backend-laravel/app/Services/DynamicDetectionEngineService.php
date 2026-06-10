<?php

namespace App\Services;

use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\ProtectionPolicy;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DynamicDetectionEngineService
{
    public function analyze(array $payload): array
    {
        $eventType = $payload['event_type'] ?? 'unknown';
        $path = $payload['path'] ?? '';
        $extension = $this->extractExtension($payload);

        $signals = collect();
        $score = 0;

        $extensionSignal = $this->analyzeSensitiveExtension($extension);

        if ($extensionSignal) {
            $signals->push($extensionSignal);
            $score += $extensionSignal['score'];
        }

        foreach ($this->activeRules() as $rule) {
            $signal = $this->matchRule($rule, $payload, $eventType, $path, $extension);

            if ($signal) {
                $signals->push($signal);
                $score += $signal['score'];
            }
        }

        $threshold = $this->matchThreshold($score);
        $riskLevel = $threshold['risk_level'] ?? 'normal';

        $policies = $this->matchPolicies($riskLevel);
        $settings = $this->safetySettings();

        return [
            'score' => $score,
            'risk_level' => $riskLevel,
            'threshold' => $threshold,
            'signals' => $signals->values()->all(),
            'policies' => $policies->values()->all(),
            'settings' => $settings,
            'should_create_alert' => $riskLevel !== 'normal',
            'should_create_incident' => $this->levelIsAtLeast($riskLevel, $settings['min_risk_level_for_incident']),
            'should_propose_action' => $this->levelIsAtLeast($riskLevel, $settings['min_risk_level_for_action']),
        ];
    }

    private function analyzeSensitiveExtension(?string $extension): ?array
    {
        if (! $extension) {
            return null;
        }

        $extension = ltrim(strtolower($extension), '.');
        $sensitive = $this->cachedSensitiveExtensions()[$extension] ?? null;

        if (! $sensitive) {
            return null;
        }

        return [
            'type' => 'sensitive_extension',
            'code' => 'sensitive_extension_'.$extension,
            'label' => 'Extension sensible détectée : .'.$extension,
            'risk_level' => $sensitive['risk_level'],
            'score' => (int) $sensitive['score_weight'],
            'source' => 'sensitive_extensions',
            'metadata' => [
                'extension' => $extension,
                'description' => $sensitive['description'],
            ],
        ];
    }

    private function activeRules(): Collection
    {
        $rows = Cache::remember('detection:active_rules', 60, function () {
            return DetectionRule::query()
                ->where('is_enabled', true)
                ->orderByDesc('score_weight')
                ->get()
                ->map->toArray()
                ->values()
                ->all();
        });

        return collect($rows)->map(fn ($attrs) => (object) $attrs);
    }

    private function cachedSensitiveExtensions(): array
    {
        return Cache::remember('detection:sensitive_extensions', 60, function () {
            return SensitiveExtension::query()
                ->where('is_enabled', true)
                ->get(['extension', 'risk_level', 'score_weight', 'description'])
                ->keyBy('extension')
                ->map(fn ($e) => [
                    'risk_level'   => $e->risk_level,
                    'score_weight' => $e->score_weight,
                    'description'  => $e->description,
                ])
                ->all();
        });
    }

    private function matchRule(
        object $rule,
        array $payload,
        string $eventType,
        string $path,
        ?string $extension
    ): ?array {
        $conditions = is_array($rule->conditions) ? $rule->conditions : [];

        if (! $this->evaluateConditions($conditions, $rule, $payload, $eventType, $path)) {
            return null;
        }

        return [
            'type' => 'detection_rule',
            'code' => $rule->code,
            'label' => $rule->name,
            'risk_level' => $rule->risk_level,
            'score' => (int) $rule->score_weight,
            'source' => 'detection_rules',
            'metadata' => [
                'event_type' => $eventType,
                'path' => $path,
            ],
        ];
    }

    /**
     * Évalue les conditions d'une règle de façon générique.
     *
     * Les conditions sont stockées en JSON dans detection_rules.conditions.
     * Champs supportés :
     *   event_types[]           — l'event_type doit être dans cette liste
     *   filename_keywords[]     — au moins un mot-clé doit apparaître dans le nom de fichier
     *   path_excludes[]         — "browser_or_system" exclus via isBrowserOrSystemPath()
     *   require_simulation_flag — déclenche uniquement si is_simulation=true
     *   path_contains           — le chemin doit contenir cette sous-chaîne
     *
     * Rétro-compat : si conditions est vide, on se replie sur event_type et path_contains
     * issus du record DB (comportement d'origine de genericRuleMatch).
     */
    private function evaluateConditions(
        array $conditions,
        object $rule,
        array $payload,
        string $eventType,
        string $path
    ): bool {
        // ── Filtre sur le type d'événement ────────────────────────────────────
        if (isset($conditions['event_types'])) {
            if (! in_array($eventType, $conditions['event_types'], true)) {
                return false;
            }
        } elseif ($rule->event_type) {
            // Rétro-compat : règles sans conditions mais avec event_type en colonne
            if ($rule->event_type !== $eventType) {
                return false;
            }
        }

        // ── Mots-clés dans le nom de fichier ──────────────────────────────────
        if (! empty($conditions['filename_keywords'])) {
            $filename  = strtolower(basename($path));
            $hasKeyword = false;

            foreach ($conditions['filename_keywords'] as $kw) {
                if (str_contains($filename, strtolower((string) $kw))) {
                    $hasKeyword = true;
                    break;
                }
            }

            if (! $hasKeyword) {
                return false;
            }
        }

        // ── Exclusions de chemin ──────────────────────────────────────────────
        foreach ($conditions['path_excludes'] ?? [] as $exclude) {
            if ($exclude === 'browser_or_system' && $this->isBrowserOrSystemPath($path)) {
                return false;
            }
        }

        // ── Drapeau simulation ────────────────────────────────────────────────
        if (! empty($conditions['require_simulation_flag'])) {
            if (! (bool) ($payload['is_simulation'] ?? false)) {
                return false;
            }
        }

        // ── Sous-chaîne dans le chemin (rétro-compat) ─────────────────────────
        if (! empty($conditions['path_contains'])) {
            if (! Str::contains(strtolower($path), strtolower((string) $conditions['path_contains']))) {
                return false;
            }
        }

        // Si aucune condition n'a rejeté et qu'il n'y avait aucun filtre actif,
        // on refuse quand même les règles sans critères discriminants pour éviter
        // les faux positifs globaux sur des règles partiellement configurées.
        $hasActiveCriteria = isset($conditions['event_types'])
            || ! empty($conditions['filename_keywords'])
            || ! empty($conditions['require_simulation_flag'])
            || $rule->event_type;

        return $hasActiveCriteria;
    }

    /**
     * Retourne true si le chemin appartient à un navigateur, un cache système
     * ou une application connue générant de l'I/O légitime intense.
     *
     * Ces chemins ne doivent PAS déclencher rule_fast_write_activity car ils
     * produisent des centaines de file_modified légitimes par heure.
     */
    private function isBrowserOrSystemPath(string $path): bool
    {
        if (! $path) {
            return false;
        }

        $normalised = str_replace('\\', '/', strtolower($path));

        $browserPrefixes = [
            // Navigateurs Windows (AppData)
            'appdata/local/google/chrome',
            'appdata/local/chromium',
            'appdata/roaming/mozilla/firefox',
            'appdata/local/mozilla',
            'appdata/roaming/opera software',
            'appdata/local/opera software',
            'appdata/local/microsoft/edge',
            'appdata/local/bravesoftware',
            // Applications Windows Store (WhatsApp, Teams, Outlook…)
            'appdata/local/packages',
            // Caches Office / OneDrive
            'appdata/local/microsoft/office',
            'appdata/roaming/microsoft/office',
            // Windows Update
            'c:/windows/softwaredistribution',
            // Navigateurs macOS
            'library/caches',
            'library/application support/google/chrome',
            'library/application support/firefox',
            'library/application support/opera',
            'library/application support/microsoft edge',
            // Linux – caches navigateurs
            '.config/google-chrome',
            '.config/chromium',
            '.mozilla/firefox',
            '.config/opera',
            // Temporaires génériques
            'appdata/local/temp',
        ];

        foreach ($browserPrefixes as $prefix) {
            if (str_contains($normalised, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchThreshold(int $score): array
    {
        $thresholds = Cache::remember('detection:thresholds', 60, function () {
            return DetectionThreshold::query()
                ->where('is_enabled', true)
                ->orderByDesc('min_score')
                ->get()
                ->map(fn ($t) => [
                    'code'       => $t->code,
                    'label'      => $t->label ?? $t->name,
                    'risk_level' => $t->risk_level,
                    'min_score'  => $t->min_score,
                    'max_score'  => $t->max_score,
                ])
                ->values()
                ->all();
        });

        $threshold = collect($thresholds)->first(function ($t) use ($score) {
            return $t['min_score'] <= $score
                && ($t['max_score'] === null || $t['max_score'] >= $score);
        });

        return $threshold ?? [
            'code'       => 'threshold_normal_fallback',
            'label'      => 'Normal fallback',
            'risk_level' => 'normal',
            'min_score'  => 0,
            'max_score'  => null,
        ];
    }

    private function matchPolicies(string $riskLevel): Collection
    {
        $policies = Cache::remember('detection:policies', 60, function () {
            return ProtectionPolicy::query()
                ->where('is_enabled', true)
                ->get()
                ->map(fn ($p) => [
                    'id'                        => $p->id,
                    'code'                      => $p->code,
                    'name'                      => $p->name,
                    'scope'                     => $p->scope,
                    'risk_level'                => $p->risk_level,
                    'action_type'               => $p->action_type,
                    'execution_mode'            => $p->execution_mode,
                    'requires_human_validation' => in_array($p->execution_mode, ['approval_required', 'manual'], true),
                ])
                ->values()
                ->all();
        });

        return collect($policies)->where('risk_level', $riskLevel)->values();
    }

    private function safetySettings(): array
    {
        return [
            'protection_execution_enabled' => $this->settingValue('protection_execution_enabled', '1'),
            'enable_real_isolation' => $this->settingValue('enable_real_isolation', '0'),
            'enable_real_process_kill' => $this->settingValue('enable_real_process_kill', '0'),
            'require_human_approval_for_sensitive_actions' => $this->settingValue('require_human_approval_for_sensitive_actions', '1'),
            'min_risk_level_for_incident' => $this->settingValue('min_risk_level_for_incident', 'high'),
            'min_risk_level_for_action' => $this->settingValue('min_risk_level_for_action', 'high'),
            'notification_ui_enabled' => $this->settingValue('notification_ui_enabled', '1'),
            'notification_sound_enabled' => $this->settingValue('notification_sound_enabled', '1'),
        ];
    }

    private function settingValue(string $key, string $default): string
    {
        return (string) (SystemSetting::getCached($key) ?? $default);
    }

    private function levelIsAtLeast(string $actual, string $minimum): bool
    {
        $rank = [
            'normal' => 0,
            'suspect' => 1,
            'high' => 2,
            'critical' => 3,
        ];

        return ($rank[$actual] ?? 0) >= ($rank[$minimum] ?? 0);
    }

    private function extractExtension(array $payload): ?string
    {
        if (! empty($payload['file_extension'])) {
            return ltrim(strtolower((string) $payload['file_extension']), '.');
        }

        $path = $payload['path'] ?? null;

        if (! $path) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension ? strtolower($extension) : null;
    }
}
