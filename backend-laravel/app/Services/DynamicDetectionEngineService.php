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
        $sensitive = $this->cachedSensitiveExtensions()->get($extension);

        if (! $sensitive) {
            return null;
        }

        return [
            'type' => 'sensitive_extension',
            'code' => 'sensitive_extension_'.$extension,
            'label' => 'Extension sensible détectée : .'.$extension,
            'risk_level' => $sensitive->risk_level,
            'score' => (int) $sensitive->score_weight,
            'source' => 'sensitive_extensions',
            'metadata' => [
                'extension' => $extension,
                'description' => $sensitive->description,
            ],
        ];
    }

    private function activeRules(): Collection
    {
        return Cache::remember('detection:active_rules', 60, function () {
            return DetectionRule::query()
                ->where('is_enabled', true)
                ->orderByDesc('score_weight')
                ->get();
        });
    }

    private function cachedSensitiveExtensions(): Collection
    {
        return Cache::remember('detection:sensitive_extensions', 60, function () {
            return SensitiveExtension::query()
                ->where('is_enabled', true)
                ->get()
                ->keyBy('extension');
        });
    }

    private function matchRule(
        DetectionRule $rule,
        array $payload,
        string $eventType,
        string $path,
        ?string $extension
    ): ?array {
        $code = $rule->code;

        $matched = match ($code) {
            // NOTE : rule_sensitive_extension est intentionnellement absent ici.
            // analyzeSensitiveExtension() gère déjà le scoring par extension avec
            // des poids granulaires depuis la table sensitive_extensions.
            // Un case hardcodé ici provoquerait un double comptage sur chaque
            // événement portant une extension sensible.

            // Bug G : 'mass_rename_detected' ajouté — l'agent l'envoie après ≥10
            // renommages en 30 s (track_rename). C'est le signal de dernier recours
            // quand les événements individuels sont étouffés par le rate-limiter.
            //
            // Bug J : 'file_encrypted_extension' ajouté — l'agent envoie ce type
            // quand un fichier est renommé vers une extension sensible (.locked,
            // .encrypted…). C'est bien un renommage → rule_mass_rename doit s'appliquer.
            'rule_mass_rename'        => in_array($eventType, [
                'file_moved', 'file_renamed', 'moved', 'renamed',
                'file_encrypted_extension',  // Bug J — rename vers ext sensible
                'mass_rename_detected',      // Bug G — burst ≥10 renames/30s
            ], true)
                && ! $this->isBrowserOrSystemPath($path),
            'rule_ransom_note'        => $this->looksLikeRansomNote($path),

            // Bug N fix — 'file_created' et 'created' retirés.
            // Avant : tout file_created (+30) dépassait seul le seuil suspect (25)
            // → chaque création de .py/.json/.txt générait une fausse alerte.
            // La création de fichiers chiffrés est couverte par analyzeSensitiveExtension()
            // (extension scoring) et par rule_mass_rename (file_encrypted_extension).
            // On conserve uniquement file_modified / modified, mais en excluant les
            // chemins de navigateurs et caches système (I/O légitime à haute fréquence).
            'rule_fast_write_activity'=> in_array($eventType, ['file_modified', 'modified'], true)
                && ! $this->isBrowserOrSystemPath($path),
            'rule_simulation_marker'  => (bool) ($payload['is_simulation'] ?? false),
            // Processus suspects (openssl, gpg, cryptsetup, rclone…) — scorés via
            // la règle rule_suspicious_process en base, capturés par genericRuleMatch.
            default => $this->genericRuleMatch($rule, $payload, $eventType, $path),
        };

        if (! $matched) {
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

    private function genericRuleMatch(DetectionRule $rule, array $payload, string $eventType, string $path): bool
    {
        if ($rule->event_type && $rule->event_type === $eventType) {
            return true;
        }

        $metadata = $rule->metadata ?? [];

        $contains = data_get($metadata, 'path_contains');

        if ($contains && Str::contains(strtolower($path), strtolower($contains))) {
            return true;
        }

        return false;
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

    private function looksLikeRansomNote(string $path): bool
    {
        $name = strtolower(basename($path));

        return Str::contains($name, [
            'readme',
            'recover',
            'decrypt',
            'how_to_decrypt',
            'ransom',
            'restore_files',
            'instructions',
        ]);
    }

    private function matchThreshold(int $score): array
    {
        $threshold = Cache::remember('detection:thresholds', 60, function () {
            return DetectionThreshold::query()
                ->where('is_enabled', true)
                ->orderByDesc('min_score')
                ->get();
        })->first(function ($t) use ($score) {
            return $t->min_score <= $score
                && ($t->max_score === null || $t->max_score >= $score);
        });

        if (! $threshold) {
            return [
                'code' => 'threshold_normal_fallback',
                'label' => 'Normal fallback',
                'risk_level' => 'normal',
                'min_score' => 0,
                'max_score' => null,
            ];
        }

        return [
            'code' => $threshold->code,
            'label' => $threshold->label ?? $threshold->name,
            'risk_level' => $threshold->risk_level,
            'min_score' => $threshold->min_score,
            'max_score' => $threshold->max_score,
        ];
    }

    private function matchPolicies(string $riskLevel): Collection
    {
        return Cache::remember('detection:policies', 60, function () {
            return ProtectionPolicy::query()
                ->where('is_enabled', true)
                ->get();
        })->where('risk_level', $riskLevel)
            ->map(fn (ProtectionPolicy $policy) => [
                'id' => $policy->id,
                'code' => $policy->code,
                'name' => $policy->name,
                'scope' => $policy->scope,
                'risk_level' => $policy->risk_level,
                'action_type' => $policy->action_type,
                'execution_mode' => $policy->execution_mode,
                'requires_human_validation' => in_array($policy->execution_mode, ['approval_required', 'manual'], true),
            ])
            ->values();
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
