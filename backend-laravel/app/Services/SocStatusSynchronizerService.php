<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Incident;
use App\Models\ProtectionAction;
use Illuminate\Support\Facades\DB;

class SocStatusSynchronizerService
{
    public function syncAfterAction(ProtectionAction $action): void
    {
        $action->refresh();

        if ($action->incident_id) {
            $incident = Incident::with(['alerts', 'protectionActions'])->find($action->incident_id);

            if ($incident) {
                $this->syncIncidentFromActions($incident);
            }
        }
    }

    public function resolveIncident(Incident $incident, string $reason = 'Incident résolu depuis la console SOC.'): void
    {
        DB::transaction(function () use ($incident, $reason) {
            $incident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'metadata' => array_merge($incident->metadata ?? [], [
                    'resolved_reason' => $reason,
                    'resolved_by_sync' => true,
                ]),
            ]);

            Alert::where('incident_id', $incident->id)
                ->whereNotIn('status', ['resolved', 'false_positive'])
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            ProtectionAction::where('incident_id', $incident->id)
                ->where('approval_status', 'pending')
                ->whereIn('execution_status', ['waiting_approval', 'pending'])
                ->update([
                    'approval_status' => 'cancelled',
                    'execution_status' => 'cancelled',
                    'updated_at' => now(),
                ]);
        });
    }

    public function falsePositiveIncident(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'status' => 'false_positive',
                'resolved_at' => now(),
                'metadata' => array_merge($incident->metadata ?? [], [
                    'closed_as' => 'false_positive',
                ]),
            ]);

            Alert::where('incident_id', $incident->id)
                ->update([
                    'status' => 'false_positive',
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            ProtectionAction::where('incident_id', $incident->id)
                ->where('approval_status', 'pending')
                ->update([
                    'approval_status' => 'rejected',
                    'execution_status' => 'cancelled',
                    'updated_at' => now(),
                ]);
        });
    }

    public function reopenIncident(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'status' => 'reopened',
                'resolved_at' => null,
                'metadata' => array_merge($incident->metadata ?? [], [
                    'reopened_at' => now()->toDateTimeString(),
                ]),
            ]);

            Alert::where('incident_id', $incident->id)
                ->whereIn('status', ['resolved', 'false_positive'])
                ->update([
                    'status' => 'open',
                    'resolved_at' => null,
                    'updated_at' => now(),
                ]);
        });
    }

    public function resolveAlert(Alert $alert): void
    {
        DB::transaction(function () use ($alert) {
            $alert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            if ($alert->incident_id) {
                $incident = Incident::with(['alerts', 'protectionActions'])->find($alert->incident_id);

                if ($incident) {
                    $this->syncIncidentFromAlerts($incident);
                }
            }
        });
    }

    public function falsePositiveAlert(Alert $alert): void
    {
        DB::transaction(function () use ($alert) {
            $alert->update([
                'status' => 'false_positive',
                'resolved_at' => now(),
            ]);

            if ($alert->incident_id) {
                $incident = Incident::with(['alerts', 'protectionActions'])->find($alert->incident_id);

                if ($incident) {
                    $this->syncIncidentFromAlerts($incident);
                }
            }
        });
    }

    public function reopenAlert(Alert $alert): void
    {
        DB::transaction(function () use ($alert) {
            $alert->update([
                'status' => 'open',
                'resolved_at' => null,
            ]);

            if ($alert->incident_id) {
                Incident::where('id', $alert->incident_id)->update([
                    'status' => 'reopened',
                    'resolved_at' => null,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function syncIncidentFromActions(Incident $incident): void
    {
        $incident->loadMissing(['alerts', 'protectionActions']);

        if (in_array($incident->status, ['resolved', 'false_positive'], true)) {
            return;
        }

        $actions = $incident->protectionActions;

        if ($actions->isEmpty()) {
            return;
        }

        $pending = $actions->where('approval_status', 'pending')
            ->whereIn('execution_status', ['waiting_approval', 'pending'])
            ->count();

        $failed = $actions->where('execution_status', 'failed')->count();

        $success = $actions->where('execution_status', 'success')->count();
        $rejected = $actions->where('approval_status', 'rejected')->count();
        $cancelled = $actions->whereIn('execution_status', ['cancelled', 'rolled_back'])->count();

        if ($pending > 0) {
            $incident->update([
                'status' => 'under_review',
            ]);

            return;
        }

        if ($failed > 0) {
            $incident->update([
                'status' => 'investigating',
            ]);

            return;
        }

        if ($success > 0 || ($rejected + $cancelled) === $actions->count()) {
            $this->resolveIncident($incident, 'Toutes les actions liées ont été traitées.');
        }
    }

    public function syncIncidentFromAlerts(Incident $incident): void
    {
        $incident->loadMissing(['alerts', 'protectionActions']);

        if (in_array($incident->status, ['resolved', 'false_positive'], true)) {
            return;
        }

        $alerts = $incident->alerts;

        if ($alerts->isEmpty()) {
            return;
        }

        $openAlerts = $alerts->whereIn('status', ['open', 'acknowledged', 'investigating'])->count();

        if ($openAlerts === 0) {
            $pendingActions = $incident->protectionActions
                ->where('approval_status', 'pending')
                ->whereIn('execution_status', ['waiting_approval', 'pending'])
                ->count();

            if ($pendingActions === 0) {
                $this->resolveIncident($incident, 'Toutes les alertes liées ont été traitées.');
            } else {
                $incident->update([
                    'status' => 'under_review',
                ]);
            }
        }
    }
}
