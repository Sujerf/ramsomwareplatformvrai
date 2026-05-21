<?php

namespace App\Services;

use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\ProtectionPolicy;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

class ConfigurationLinkService
{
    public function overview(): array
    {
        $extensions = SensitiveExtension::query()
            ->orderByDesc('score_weight')
            ->orderBy('extension')
            ->get();

        $rules = DetectionRule::query()
            ->orderByDesc('score_weight')
            ->orderBy('code')
            ->get();

        $thresholds = DetectionThreshold::query()
            ->orderBy('min_score')
            ->get();

        $policies = ProtectionPolicy::query()
            ->orderBy('risk_level')
            ->orderBy('code')
            ->get();

        $settings = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return [
            'summary' => $this->summary($extensions, $rules, $thresholds, $policies, $settings),
            'health' => $this->health($extensions, $rules, $thresholds, $policies, $settings),
            'recommendations' => $this->recommendations($extensions, $rules, $thresholds, $policies, $settings),
            'pipeline' => $this->pipeline(),
            'risk_matrix' => $this->riskMatrix($thresholds, $policies),
            'extension_groups' => $this->extensionGroups($extensions),
            'rule_chain' => $this->ruleChain($rules, $thresholds, $policies),
            'safety_settings' => $this->safetySettings($settings),
            'simulation' => $this->simulation($extensions, $rules, $thresholds, $policies, $settings),
        ];
    }

    private function summary(Collection $extensions, Collection $rules, Collection $thresholds, Collection $policies, Collection $settings): array
    {
        return [
            'extensions_total' => $extensions->count(),
            'extensions_enabled' => $extensions->where('is_enabled', true)->count(),
            'extensions_critical' => $extensions->where('is_enabled', true)->where('risk_level', 'critical')->count(),

            'rules_total' => $rules->count(),
            'rules_enabled' => $rules->where('is_enabled', true)->count(),

            'thresholds_total' => $thresholds->count(),
            'thresholds_enabled' => $thresholds->where('is_enabled', true)->count(),

            'policies_total' => $policies->count(),
            'policies_enabled' => $policies->where('is_enabled', true)->count(),
            'approval_policies' => $policies->where('is_enabled', true)->where('execution_mode', 'approval_required')->count(),
            'manual_policies' => $policies->where('is_enabled', true)->where('execution_mode', 'manual')->count(),

            'settings_total' => $settings->count(),
        ];
    }

    private function health(Collection $extensions, Collection $rules, Collection $thresholds, Collection $policies, Collection $settings): array
    {
        $score = 0;

        if ($extensions->where('is_enabled', true)->count() > 0) {
            $score += 20;
        }

        if ($rules->where('is_enabled', true)->count() > 0) {
            $score += 25;
        }

        if ($thresholds->where('is_enabled', true)->count() >= 4) {
            $score += 20;
        }

        if ($policies->where('is_enabled', true)->count() > 0) {
            $score += 20;
        }

        if ($policies->where('is_enabled', true)->whereIn('execution_mode', ['approval_required', 'manual'])->count() > 0) {
            $score += 10;
        }

        if ($this->settingValue($settings, 'require_human_approval_for_sensitive_actions') === '1') {
            $score += 5;
        }

        $level = match (true) {
            $score >= 90 => 'stable',
            $score >= 70 => 'correct',
            $score >= 45 => 'incomplet',
            default => 'critique',
        };

        return [
            'score' => $score,
            'level' => $level,
            'message' => match ($level) {
                'stable' => 'Configuration cohérente : la chaîne détection → seuils → politiques → sécurité est opérationnelle.',
                'correct' => 'Configuration utilisable, mais certains liens peuvent être renforcés.',
                'incomplet' => 'Configuration partielle : certaines décisions peuvent manquer de précision.',
                default => 'Configuration critique : la chaîne de détection n’est pas encore fiable.',
            },
        ];
    }

    private function recommendations(Collection $extensions, Collection $rules, Collection $thresholds, Collection $policies, Collection $settings): array
    {
        $items = [];

        if ($extensions->where('is_enabled', true)->count() === 0) {
            $items[] = 'Active au moins une extension sensible comme .locked, .encrypted ou .crypt.';
        }

        if (! $rules->firstWhere('code', 'rule_sensitive_extension')?->is_enabled) {
            $items[] = 'Active la règle “Extension sensible détectée” pour relier les extensions au score de risque.';
        }

        if ($thresholds->where('is_enabled', true)->count() < 4) {
            $items[] = 'Active les quatre seuils normal, suspect, high et critical pour avoir une classification complète.';
        }

        if ($policies->where('is_enabled', true)->count() === 0) {
            $items[] = 'Active au moins une politique pour que le système propose une réponse après détection.';
        }

        if ($policies->where('is_enabled', true)->whereIn('execution_mode', ['approval_required', 'manual'])->count() === 0) {
            $items[] = 'Garde au moins une politique en approbation humaine ou manuel pour éviter les actions dangereuses automatiques.';
        }

        if ($this->settingValue($settings, 'enable_real_isolation') === '1') {
            $items[] = 'Attention : l’isolation réelle est activée. Pour les tests, garde-la désactivée.';
        }

        if ($this->settingValue($settings, 'require_human_approval_for_sensitive_actions') !== '1') {
            $items[] = 'Active l’approbation humaine obligatoire pour les actions sensibles.';
        }

        if (count($items) === 0) {
            $items[] = 'Configuration cohérente. Tu peux lancer un test contrôlé avec une extension sensible.';
        }

        return $items;
    }

    private function pipeline(): array
    {
        return [
            [
                'step' => '01',
                'title' => 'Extensions sensibles',
                'route' => 'platform.sensitive-extensions.index',
                'description' => 'Exemples : .locked, .encrypted, .crypt, .enc.',
                'impact' => 'Elles augmentent le score lorsqu’un fichier reçoit une extension suspecte.',
            ],
            [
                'step' => '02',
                'title' => 'Règles de détection',
                'route' => 'platform.detection-rules.index',
                'description' => 'Les règles transforment les événements en score.',
                'impact' => 'Chaque règle ajoute un poids au score final.',
            ],
            [
                'step' => '03',
                'title' => 'Seuils',
                'route' => 'platform.detection-thresholds.index',
                'description' => 'Les seuils convertissent le score en niveau de risque.',
                'impact' => 'Normal, suspect, high ou critical.',
            ],
            [
                'step' => '04',
                'title' => 'Politiques',
                'route' => 'platform.protection-policies.index',
                'description' => 'Les politiques décident de la réponse proposée.',
                'impact' => 'Notification, restriction, isolation ou action manuelle.',
            ],
            [
                'step' => '05',
                'title' => 'Paramètres système',
                'route' => 'platform.system-settings.index',
                'description' => 'Ils sécurisent les actions sensibles.',
                'impact' => 'Ils empêchent les actions dangereuses sans validation.',
            ],
        ];
    }

    private function riskMatrix(Collection $thresholds, Collection $policies): array
    {
        return $thresholds->map(function ($threshold) use ($policies) {
            $linkedPolicies = $policies
                ->where('risk_level', $threshold->risk_level)
                ->values()
                ->map(fn ($policy) => [
                    'code' => $policy->code,
                    'name' => $policy->name,
                    'action_type' => $policy->action_type,
                    'execution_mode' => $policy->execution_mode,
                    'is_enabled' => (bool) $policy->is_enabled,
                ]);

            return [
                'code' => $threshold->code,
                'label' => $threshold->label ?? $threshold->name,
                'risk_level' => $threshold->risk_level,
                'min_score' => $threshold->min_score,
                'max_score' => $threshold->max_score,
                'is_enabled' => (bool) $threshold->is_enabled,
                'policies' => $linkedPolicies,
            ];
        })->values()->all();
    }

    private function extensionGroups(Collection $extensions): array
    {
        return [
            'critical' => $extensions->where('risk_level', 'critical')->values(),
            'high' => $extensions->where('risk_level', 'high')->values(),
            'suspect' => $extensions->where('risk_level', 'suspect')->values(),
        ];
    }

    private function ruleChain(Collection $rules, Collection $thresholds, Collection $policies): array
    {
        return $rules->map(function ($rule) use ($thresholds, $policies) {
            $targetThreshold = $thresholds
                ->where('is_enabled', true)
                ->first(fn ($threshold) => $rule->score_weight >= $threshold->min_score && ($threshold->max_score === null || $rule->score_weight <= $threshold->max_score));

            $targetPolicies = $targetThreshold
                ? $policies->where('risk_level', $targetThreshold->risk_level)->values()
                : collect();

            return [
                'code' => $rule->code,
                'name' => $rule->name,
                'risk_level' => $rule->risk_level,
                'score_weight' => $rule->score_weight,
                'is_enabled' => (bool) $rule->is_enabled,
                'threshold' => $targetThreshold ? [
                    'label' => $targetThreshold->label ?? $targetThreshold->name,
                    'risk_level' => $targetThreshold->risk_level,
                    'range' => $targetThreshold->min_score.' - '.($targetThreshold->max_score ?? '∞'),
                ] : null,
                'policies' => $targetPolicies->map(fn ($policy) => [
                    'code' => $policy->code,
                    'action_type' => $policy->action_type,
                    'execution_mode' => $policy->execution_mode,
                ])->all(),
            ];
        })->values()->all();
    }

    private function safetySettings(Collection $settings): array
    {
        return [
            'enable_real_isolation' => $this->settingValue($settings, 'enable_real_isolation', '0'),
            'require_human_approval_for_sensitive_actions' => $this->settingValue($settings, 'require_human_approval_for_sensitive_actions', '1'),
            'min_risk_level_for_incident' => $this->settingValue($settings, 'min_risk_level_for_incident', 'high'),
            'min_risk_level_for_action' => $this->settingValue($settings, 'min_risk_level_for_action', 'high'),
            'notification_ui_enabled' => $this->settingValue($settings, 'notification_ui_enabled', '1'),
            'notification_sound_enabled' => $this->settingValue($settings, 'notification_sound_enabled', '1'),
        ];
    }

    private function simulation(Collection $extensions, Collection $rules, Collection $thresholds, Collection $policies, Collection $settings): array
    {
        $extension = $extensions
            ->where('is_enabled', true)
            ->sortByDesc('score_weight')
            ->first();

        $rule = $rules->firstWhere('code', 'rule_sensitive_extension')
            ?? $rules->where('is_enabled', true)->sortByDesc('score_weight')->first();

        $score = (int) (($extension->score_weight ?? 0) + ($rule->score_weight ?? 0));

        $threshold = $thresholds
            ->where('is_enabled', true)
            ->first(fn ($item) => $score >= $item->min_score && ($item->max_score === null || $score <= $item->max_score));

        $matchedPolicies = $threshold
            ? $policies->where('is_enabled', true)->where('risk_level', $threshold->risk_level)->values()
            : collect();

        return [
            'extension' => $extension ? '.'.$extension->extension : '—',
            'extension_score' => (int) ($extension->score_weight ?? 0),
            'rule' => $rule?->name ?? '—',
            'rule_score' => (int) ($rule->score_weight ?? 0),
            'final_score' => $score,
            'risk_level' => $threshold?->risk_level ?? 'unknown',
            'threshold' => $threshold ? (($threshold->label ?? $threshold->name).' : '.$threshold->min_score.' - '.($threshold->max_score ?? '∞')) : 'Aucun seuil correspondant',
            'policies' => $matchedPolicies->map(fn ($policy) => [
                'name' => $policy->name,
                'action_type' => $policy->action_type,
                'execution_mode' => $policy->execution_mode,
            ])->all(),
            'safety' => [
                'real_isolation' => $this->settingValue($settings, 'enable_real_isolation', '0'),
                'human_approval' => $this->settingValue($settings, 'require_human_approval_for_sensitive_actions', '1'),
            ],
        ];
    }

    private function settingValue(Collection $settings, string $key, ?string $default = null): ?string
    {
        return (string) ($settings->firstWhere('key', $key)?->value ?? $default);
    }
}
