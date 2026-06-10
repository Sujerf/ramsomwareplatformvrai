<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Centralise la piste d'audit des actions sensibles dans le SOC.
 *
 * Chaque entrée contient : qui, quoi, sur quoi, depuis quelle IP, quand.
 * Le canal 'audit' écrit dans storage/logs/audit-YYYY-MM-DD.log (rotation 90j).
 * Le canal 'soc' reçoit les événements de détection (incidents, alertes).
 */
class AuditLogService
{
    public function action(string $action, array $context = []): void
    {
        Log::channel('audit')->info($action, $this->withActor($context));
    }

    public function detection(string $event, array $context = []): void
    {
        Log::channel('soc')->info($event, $context);
    }

    public function warning(string $event, array $context = []): void
    {
        Log::channel('soc')->warning($event, $context);
    }

    // ── Actions admin tracées ─────────────────────────────────────────────────

    public function incidentCreated(int $incidentId, string $agentUuid, string $riskLevel): void
    {
        $this->detection('incident.created', [
            'incident_id' => $incidentId,
            'agent_uuid'  => $agentUuid,
            'risk_level'  => $riskLevel,
        ]);
    }

    public function incidentResolved(int $incidentId, string $resolution, ?string $reason = null): void
    {
        $this->action('incident.resolved', [
            'incident_id' => $incidentId,
            'resolution'  => $resolution,
            'reason'      => $reason,
        ]);
    }

    public function protectionActionApproved(int $actionId, string $actionType, int $incidentId): void
    {
        $this->action('protection.approved', [
            'action_id'   => $actionId,
            'action_type' => $actionType,
            'incident_id' => $incidentId,
        ]);
    }

    public function protectionActionExecuted(int $actionId, string $actionType, bool $success): void
    {
        $this->action('protection.executed', [
            'action_id'   => $actionId,
            'action_type' => $actionType,
            'success'     => $success,
        ]);
    }

    public function protectionActionRolledBack(int $actionId, string $actionType): void
    {
        $this->action('protection.rolled_back', [
            'action_id'   => $actionId,
            'action_type' => $actionType,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function withActor(array $context): array
    {
        return array_merge([
            'user_id'    => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip'         => request()?->ip(),
            'at'         => now()->toIso8601String(),
        ], $context);
    }
}
