<?php

namespace App\Services;

use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\ProtectionPolicy;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
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

        $sensitive = SensitiveExtension::query()
            ->where('extension', $extension)
            ->where('is_enabled', true)
            ->first();

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
        return DetectionRule::query()
            ->where('is_enabled', true)
            ->orderByDesc('score_weight')
            ->get();
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
            'rule_sensitive_extension' => $extension && $this->isSensitiveExtension($extension),
            'rule_mass_rename' => in_array($eventType, ['file_moved', 'file_renamed', 'moved', 'renamed'], true),
            'rule_ransom_note' => $this->looksLikeRansomNote($path),
            'rule_fast_write_activity' => in_array($eventType, ['file_modified', 'modified', 'file_created', 'created'], true),
            'rule_simulation_marker' => (bool) ($payload['is_simulation'] ?? false),
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

    private function isSensitiveExtension(string $extension): bool
    {
        return SensitiveExtension::query()
            ->where('extension', ltrim(strtolower($extension), '.'))
            ->where('is_enabled', true)
            ->exists();
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
        $threshold = DetectionThreshold::query()
            ->where('is_enabled', true)
            ->where('min_score', '<=', $score)
            ->where(function ($query) use ($score) {
                $query->whereNull('max_score')
                    ->orWhere('max_score', '>=', $score);
            })
            ->orderByDesc('min_score')
            ->first();

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
        return ProtectionPolicy::query()
            ->where('is_enabled', true)
            ->where('risk_level', $riskLevel)
            ->get()
            ->map(fn (ProtectionPolicy $policy) => [
                'id' => $policy->id,
                'code' => $policy->code,
                'name' => $policy->name,
                'scope' => $policy->scope,
                'risk_level' => $policy->risk_level,
                'action_type' => $policy->action_type,
                'execution_mode' => $policy->execution_mode,
                'requires_human_validation' => in_array($policy->execution_mode, ['approval_required', 'manual'], true),
            ]);
    }

    private function safetySettings(): array
    {
        return [
            'enable_real_isolation' => $this->settingValue('enable_real_isolation', '0'),
            'require_human_approval_for_sensitive_actions' => $this->settingValue('require_human_approval_for_sensitive_actions', '1'),
            'min_risk_level_for_incident' => $this->settingValue('min_risk_level_for_incident', 'high'),
            'min_risk_level_for_action' => $this->settingValue('min_risk_level_for_action', 'high'),
            'notification_ui_enabled' => $this->settingValue('notification_ui_enabled', '1'),
            'notification_sound_enabled' => $this->settingValue('notification_sound_enabled', '1'),
        ];
    }

    private function settingValue(string $key, string $default): string
    {
        return (string) (
            SystemSetting::query()
                ->where('key', $key)
                ->value('value')
            ?? $default
        );
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
