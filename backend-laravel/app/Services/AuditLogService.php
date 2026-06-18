<?php

namespace App\Services;

use App\Models\AuditLog;
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
        $full = $this->withActor($context);
        Log::channel('audit')->info($action, $full);
        $this->persist($action, 'audit', $full);
    }

    public function detection(string $event, array $context = []): void
    {
        Log::channel('soc')->info($event, $context);
        $this->persist($event, 'soc', $context);
    }

    public function warning(string $event, array $context = []): void
    {
        Log::channel('soc')->warning($event, $context);
        $this->persist($event, 'soc', array_merge($context, ['severity' => 'warning']));
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

    // ── Nouvelles actions tracées ────────────────────────────────────────────

    public function userLoggedIn(int $userId, string $email): void
    {
        $this->action('user.login', ['target_user_id' => $userId, 'target_email' => $email]);
    }

    public function userLoggedOut(): void
    {
        $this->action('user.logout', []);
    }

    public function userCreated(int $userId, string $email, string $role): void
    {
        $this->action('user.created', ['target_user_id' => $userId, 'target_email' => $email, 'role' => $role]);
    }

    public function userUpdated(int $userId, string $email): void
    {
        $this->action('user.updated', ['target_user_id' => $userId, 'target_email' => $email]);
    }

    public function userPasswordChanged(int $userId, string $email): void
    {
        $this->action('user.password_changed', ['target_user_id' => $userId, 'target_email' => $email]);
    }

    public function userDeleted(int $userId, string $email): void
    {
        $this->action('user.deleted', ['target_user_id' => $userId, 'target_email' => $email]);
    }

    public function alertResolved(int $alertId, string $resolution): void
    {
        $this->action('alert.'.$resolution, ['alert_id' => $alertId]);
    }

    public function incidentReopened(int $incidentId): void
    {
        $this->action('incident.reopened', ['incident_id' => $incidentId]);
    }

    public function incidentFalsePositive(int $incidentId): void
    {
        $this->action('incident.false_positive', ['incident_id' => $incidentId]);
    }

    public function settingUpdated(string $key, string $oldValue, string $newValue): void
    {
        $this->action('setting.updated', ['key' => $key, 'old' => $oldValue, 'new' => $newValue]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function persist(string $action, string $channel, array $context): void
    {
        try {
            AuditLog::write($action, $channel, $context);
        } catch (\Throwable) {
            // Ne jamais casser l'action principale si l'audit échoue
        }
    }

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
