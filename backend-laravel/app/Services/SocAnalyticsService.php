<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Alert;
use App\Models\DiscoveredHost;
use App\Models\Event;
use App\Models\Incident;
use App\Models\ManagedNetwork;
use App\Models\ProtectionAction;
use App\Models\RiskSnapshot;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SocAnalyticsService
{
    public function dashboard(): array
    {
        return [
            'summary' => $this->summary(),
            'events_by_day' => $this->eventsByDay(7),
            'alerts_by_risk_level' => $this->alertsByRiskLevel(),
            'incidents_by_status' => $this->incidentsByStatus(),
            'actions_by_status' => $this->actionsByStatus(),
            'risk_score_trend' => $this->riskScoreTrend(7),
            'hosts_by_network' => $this->hostsByNetwork(),
            'top_risky_agents' => $this->topRiskyAgents(),
            'recent_alerts' => $this->recentAlerts(),
            'recent_incidents' => $this->recentIncidents(),
            'recent_events' => $this->recentEvents(),
            'recent_actions' => $this->recentActions(),
            'recommendation' => $this->recommendation(),
        ];
    }

    private function summary(): array
    {
        return [
            'agents_total' => Agent::count(),
            'agents_active' => Agent::where('status', 'active')->count(),
            'agents_compromised' => Agent::where('status', 'compromised')->count(),
            'agents_isolated' => Agent::where('is_isolated', true)->count(),

            'events_24h' => Event::where('created_at', '>=', now()->subDay())->count(),

            'alerts_open' => Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count(),
            'alerts_critical' => Alert::where('risk_level', 'critical')->whereIn('status', ['open', 'acknowledged', 'investigating'])->count(),

            'incidents_open' => Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count(),
            'incidents_critical' => Incident::where('risk_level', 'critical')->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count(),

            'actions_pending' => ProtectionAction::where('approval_status', 'pending')->whereIn('execution_status', ['waiting_approval', 'pending'])->count(),

            'networks_total' => ManagedNetwork::count(),
            'networks_approved' => ManagedNetwork::where('status', 'approved')->count(),
            'hosts_total' => DiscoveredHost::count(),
        ];
    }

    private function eventsByDay(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $rows = Event::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        return $this->fillDateSeries($days, $rows, 'total');
    }

    private function riskScoreTrend(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $rows = RiskSnapshot::query()
            ->selectRaw('DATE(calculated_at) as day, ROUND(AVG(score), 0) as total')
            ->whereBetween('calculated_at', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        return $this->fillDateSeries($days, $rows, 'total');
    }

    private function alertsByRiskLevel(): array
    {
        return $this->countByField(Alert::class, 'risk_level', ['normal', 'suspect', 'high', 'critical']);
    }

    private function incidentsByStatus(): array
    {
        return $this->countByField(Incident::class, 'status', [
            'open',
            'investigating',
            'under_review',
            'resolved',
            'false_positive',
            'reopened',
        ]);
    }

    private function actionsByStatus(): array
    {
        return $this->countByField(ProtectionAction::class, 'approval_status', [
            'pending',
            'approved',
            'rejected',
            'executed',
        ]);
    }

    private function hostsByNetwork(): array
    {
        $networks = ManagedNetwork::withCount('discoveredHosts')
            ->latest()
            ->limit(8)
            ->get();

        return [
            'labels' => $networks->map(fn ($network) => $network->cidr)->values()->all(),
            'values' => $networks->map(fn ($network) => (int) $network->discovered_hosts_count)->values()->all(),
        ];
    }

    private function topRiskyAgents(): Collection
    {
        return Agent::query()
            ->where(function ($query) {
                $query->where('risk_score', '>', 0)
                    ->orWhereIn('risk_level', ['suspect', 'high', 'critical']);
            })
            ->orderByDesc('risk_score')
            ->latest('last_seen_at')
            ->limit(6)
            ->get();
    }

    private function recentAlerts(): Collection
    {
        return Alert::with(['agent', 'incident'])
            ->latest('detected_at')
            ->latest()
            ->limit(6)
            ->get();
    }

    private function recentIncidents(): Collection
    {
        return Incident::with(['agent', 'attackProfile'])
            ->latest('detected_at')
            ->latest()
            ->limit(6)
            ->get();
    }

    private function recentEvents(): Collection
    {
        return Event::with('agent')
            ->latest('observed_at')
            ->latest()
            ->limit(6)
            ->get();
    }

    private function recentActions(): Collection
    {
        return ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
            ->latest('proposed_at')
            ->latest()
            ->limit(6)
            ->get();
    }

    private function recommendation(): array
    {
        $criticalIncidents = Incident::where('risk_level', 'critical')
            ->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
            ->count();

        $criticalAlerts = Alert::where('risk_level', 'critical')
            ->whereIn('status', ['open', 'acknowledged', 'investigating'])
            ->count();

        $pendingActions = ProtectionAction::where('approval_status', 'pending')->whereIn('execution_status', ['waiting_approval', 'pending'])->count();

        $silentAgents = Agent::where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subMinutes(10));
            })
            ->count();

        if ($criticalIncidents > 0) {
            return [
                'level' => 'critical',
                'title' => 'Incident critique actif',
                'message' => 'Priorise les incidents critiques, vérifie les alertes liées et traite les actions de protection en attente.',
            ];
        }

        if ($criticalAlerts > 0) {
            return [
                'level' => 'critical',
                'title' => 'Alerte critique ouverte',
                'message' => 'Analyse immédiatement les alertes critiques et confirme si elles sont liées à un comportement ransomware.',
            ];
        }

        if ($pendingActions > 0) {
            return [
                'level' => 'high',
                'title' => 'Actions en attente',
                'message' => 'Des actions de protection attendent une décision humaine. Vérifie la file d’approbation.',
            ];
        }

        if ($silentAgents > 0) {
            return [
                'level' => 'suspect',
                'title' => 'Agents silencieux',
                'message' => 'Certains agents n’ont pas envoyé de heartbeat récent. Vérifie si les machines sont connectées.',
            ];
        }

        return [
            'level' => 'normal',
            'title' => 'État SOC stable',
            'message' => 'Aucun signal critique actif. Continue la surveillance des événements et des hôtes découverts.',
        ];
    }

    private function countByField(string $modelClass, string $field, array $expectedKeys): array
    {
        $rows = $modelClass::query()
            ->selectRaw($field . ' as label, COUNT(*) as total')
            ->groupBy($field)
            ->pluck('total', 'label');

        return [
            'labels' => $expectedKeys,
            'values' => collect($expectedKeys)
                ->map(fn ($key) => (int) ($rows[$key] ?? 0))
                ->values()
                ->all(),
        ];
    }

    private function fillDateSeries(int $days, Collection $rows, string $valueKey): array
    {
        $period = CarbonPeriod::create(
            now()->subDays($days - 1)->startOfDay(),
            now()->startOfDay()
        );

        $labels = [];
        $values = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');

            $labels[] = Carbon::parse($key)->format('d/m');
            $values[] = (int) ($rows[$key]->{$valueKey} ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
