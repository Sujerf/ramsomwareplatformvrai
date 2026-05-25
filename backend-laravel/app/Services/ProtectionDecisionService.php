<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\ProtectionAction;
use App\Models\ProtectionPolicy;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProtectionDecisionService
{
    public function evaluateIncident(Incident $incident): Collection
    {
        if (! $this->settingBool('protection_execution_enabled', true)) {
            return collect();
        }

        $incident->loadMissing('agent');

        $policies = ProtectionPolicy::query()
            ->where('is_enabled', true)
            ->where('risk_level', $incident->risk_level)
            ->get();

        $createdActions = collect();

        foreach ($policies as $policy) {
            foreach ($this->actionTypesFromPolicy($policy) as $actionType) {
                $realExecutionAllowed = $this->realExecutionAllowed($actionType);
                $isSensitiveAction = $this->isSensitiveRealAction($actionType);

                $effectiveDecisionMode = $this->effectiveDecisionMode(
                    policyExecutionMode: $policy->execution_mode,
                    actionType: $actionType,
                    realExecutionAllowed: $realExecutionAllowed
                );

                $shouldAutoExecute = $effectiveDecisionMode === 'automatic'
                    && ! $isSensitiveAction;

                $action = ProtectionAction::firstOrCreate(
                    [
                        'agent_id'             => $incident->agent_id,
                        'incident_id'          => $incident->id,
                        'protection_policy_id' => $policy->id,
                        'action_type'          => $actionType,
                    ],
                    [
                        // action_uuid : identifiant stable utilisé par l'agent Python
                        // pour dépiler les commandes via GET /api/agent/pending-commands
                        'action_uuid'      => (string) Str::uuid(),
                        'decision_mode'    => $effectiveDecisionMode,
                        'execution_status' => $shouldAutoExecute ? 'executed' : 'pending',
                        'approval_status'  => $shouldAutoExecute ? 'approved'  : 'pending',
                        'is_reversible'    => $this->isReversible($actionType),
                        'rollback_available' => false,
                        'description'      => $this->descriptionForAction(
                            actionType: $actionType,
                            riskLevel: $incident->risk_level,
                            realExecutionAllowed: $realExecutionAllowed,
                            effectiveDecisionMode: $effectiveDecisionMode
                        ),
                        'payload' => [
                            'risk_level'               => $incident->risk_level,
                            'risk_score'               => $incident->risk_score,
                            'policy_code'              => $policy->code,
                            'policy_execution_mode'    => $policy->execution_mode,
                            'effective_decision_mode'  => $effectiveDecisionMode,
                            'real_execution_allowed'   => $realExecutionAllowed,
                            'is_sensitive_real_action' => $isSensitiveAction,
                            // F — signaux propagés depuis incident.metadata pour la vue show
                            'signals'                  => data_get($incident->metadata, 'signals', []),
                            // G — champ attendu par la vue show (dérivé de effective_decision_mode)
                            'human_approval_required'  => $effectiveDecisionMode === 'approval_required',
                            'timeline_message'         => $this->timelineMessageForAction($actionType, $effectiveDecisionMode),
                        ],
                        'proposed_at' => now(),
                        'executed_at' => $shouldAutoExecute ? now() : null,
                    ]
                );

                $createdActions->push($action);
            }
        }

        return $createdActions;
    }

    private function actionTypesFromPolicy(ProtectionPolicy $policy): array
    {
        $actions = [];

        if ($policy->alert_only) {
            $actions[] = 'alert_only';
        }

        if ($policy->emergency_backup) {
            $actions[] = 'emergency_backup';
        }

        if ($policy->lock_safe_copy) {
            $actions[] = 'lock_safe_copy';
        }

        if ($policy->isolate_host) {
            $actions[] = 'isolate_host';
        }

        if ($policy->kill_process) {
            $actions[] = 'kill_process';
        }

        if ($policy->restrict_path) {
            $actions[] = 'restrict_path';
        }

        return array_values(array_unique($actions));
    }

    private function effectiveDecisionMode(
        string $policyExecutionMode,
        string $actionType,
        bool $realExecutionAllowed
    ): string {
        $isSensitive = $this->isSensitiveRealAction($actionType);

        // ── Priorité 1 : exécution réelle désactivée → action proposée en manuel ─
        if ($isSensitive && ! $realExecutionAllowed) {
            return 'manual';
        }

        // ── Priorité 2 : approbation humaine requise pour actions sensibles ──────
        // Si require_human_approval_for_sensitive_actions = 1 ET l'action est
        // sensible ET l'exécution réelle est autorisée, on force approval_required
        // même si la politique dit "automatic". Sans ce garde-fou, activer
        // enable_real_isolation sans approbation déclencherait une isolation auto.
        if ($isSensitive && $realExecutionAllowed
            && $this->settingBool('require_human_approval_for_sensitive_actions', true)
        ) {
            return 'approval_required';
        }

        return match ($policyExecutionMode) {
            'automatic' => 'automatic',
            'approval_required' => 'approval_required',
            'manual_only' => 'manual',
            default => 'approval_required',
        };
    }

    private function isSensitiveRealAction(string $actionType): bool
    {
        return in_array($actionType, [
            'isolate_host',
            'kill_process',
        ], true);
    }

    private function realExecutionAllowed(string $actionType): bool
    {
        return match ($actionType) {
            'isolate_host' => $this->settingBool('enable_real_isolation', false),
            'kill_process' => $this->settingBool('enable_real_process_kill', false),
            default => true,
        };
    }

    private function isReversible(string $actionType): bool
    {
        return in_array($actionType, [
            'isolate_host',
            'restrict_path',
            'lock_safe_copy',
        ], true);
    }

    private function descriptionForAction(
        string $actionType,
        string $riskLevel,
        bool $realExecutionAllowed,
        string $effectiveDecisionMode
    ): string {
        $base = match ($actionType) {
            'alert_only' => "Notifier l’administrateur pour un risque {$riskLevel}.",
            'emergency_backup' => "Préparer une sauvegarde d’urgence pour un risque {$riskLevel}.",
            'lock_safe_copy' => "Verrouiller une copie sûre pour un risque {$riskLevel}.",
            'isolate_host' => "Proposer l’isolation de la machine pour un risque {$riskLevel}.",
            'kill_process' => "Proposer l’arrêt d’un processus suspect pour un risque {$riskLevel}.",
            'restrict_path' => "Proposer une restriction du chemin surveillé pour un risque {$riskLevel}.",
            default => "Action de protection proposée pour un risque {$riskLevel}.",
        };

        if ($this->isSensitiveRealAction($actionType) && ! $realExecutionAllowed) {
            return $base . ' Exécution réelle désactivée dans les paramètres système : action conservée comme proposition manuelle.';
        }

        if ($effectiveDecisionMode === 'approval_required') {
            return $base . ' Validation administrateur requise avant exécution.';
        }

        if ($effectiveDecisionMode === 'manual') {
            return $base . ' Exécution manuelle requise.';
        }

        return $base . ' Action automatique autorisée.';
    }

    private function timelineMessageForAction(string $actionType, string $decisionMode): string
    {
        return match ($decisionMode) {
            'automatic' => "Action {$actionType} exécutée automatiquement par politique.",
            'approval_required' => "Action {$actionType} proposée et en attente d’approbation.",
            'manual' => "Action {$actionType} proposée pour exécution manuelle.",
            default => "Action {$actionType} proposée.",
        };
    }

    private function settingBool(string $key, bool $default = false): bool
    {
        $value = SystemSetting::getCached($key) ?? ($default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
