<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\DiscoveredHost;
use App\Models\Incident;
use App\Models\ManagedNetwork;
use App\Models\ProtectionAction;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeAlerts = Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count();

        $activeIncidents = Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count();

        $pendingActions = ProtectionAction::where('approval_status', 'pending')
            ->whereIn('execution_status', ['waiting_approval', 'pending'])
            ->count();

        $agentsTotal = Agent::count();

        $criticalAgents = Agent::where('risk_level', 'critical')->count();

        $monitoredNetworks = ManagedNetwork::where('is_monitored', true)->count();

        $monitoredHosts = DiscoveredHost::where('is_monitored', true)->count();

        $riskDistribution = Agent::select('risk_level', DB::raw('COUNT(*) as total'))
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level')
            ->toArray();

        $recentAlerts = Alert::with(['agent', 'incident'])
            ->latest('detected_at')
            ->latest()
            ->limit(6)
            ->get();

        $recentIncidents = Incident::with(['agent'])
            ->latest('detected_at')
            ->latest()
            ->limit(6)
            ->get();

        $recentActions = ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
            ->latest('proposed_at')
            ->latest()
            ->limit(6)
            ->get();

        $networks = ManagedNetwork::withCount('discoveredHosts')
            ->latest()
            ->limit(5)
            ->get();

        $chartData = $this->buildCharts();

        $surveillanceSettings = SystemSetting::whereIn('key', [
            'protection_execution_enabled',
            'require_human_approval_for_sensitive_actions',
            'enable_real_isolation',
            'enable_real_process_kill',
            'min_risk_level_for_incident',
            'min_risk_level_for_action',
        ])->get()->keyBy('key');

        return view('platform.dashboard.index', [
            'stats' => [
                'active_alerts' => $activeAlerts,
                'active_incidents' => $activeIncidents,
                'pending_actions' => $pendingActions,
                'agents_total' => $agentsTotal,
                'critical_agents' => $criticalAgents,
                'monitored_networks' => $monitoredNetworks,
                'monitored_hosts' => $monitoredHosts,
            ],
            'riskDistribution' => [
                'normal' => $riskDistribution['normal'] ?? 0,
                'suspect' => $riskDistribution['suspect'] ?? 0,
                'high' => $riskDistribution['high'] ?? 0,
                'critical' => $riskDistribution['critical'] ?? 0,
            ],
            'recentAlerts' => $recentAlerts,
            'recentIncidents' => $recentIncidents,
            'recentActions' => $recentActions,
            'networks' => $networks,
            'chartData' => $chartData,
            'surveillanceSettings' => $surveillanceSettings,
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $period = in_array($request->input('period'), ['24h', 'week', 'month'])
            ? $request->input('period')
            : 'week';

        return response()->json($this->buildCharts($period));
    }

    private function buildCharts(string $period = 'week'): array
    {
        if ($period === '24h') {
            $points = collect(range(23, 0))->map(fn ($h) => now()->subHours($h)->startOfHour());
            $labels = $points->map(fn (Carbon $h) => $h->format('H\h'))->values();
            $groupFn = fn (Carbon $p) => [
                $p->copy()->startOfHour(),
                $p->copy()->endOfHour(),
            ];
        } elseif ($period === 'month') {
            $points = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->startOfDay());
            $labels = $points->map(fn (Carbon $d) => $d->format('d/m'))->values();
            $groupFn = fn (Carbon $p) => [
                $p->copy()->startOfDay(),
                $p->copy()->endOfDay(),
            ];
        } else {
            $points = collect(range(6, 0))->map(fn ($d) => now()->subDays($d)->startOfDay());
            $labels = $points->map(fn (Carbon $d) => $d->isoFormat('ddd'))->values();
            $groupFn = fn (Carbon $p) => [
                $p->copy()->startOfDay(),
                $p->copy()->endOfDay(),
            ];
        }

        $alertSeries = $points->map(fn ($p) => Alert::whereBetween('created_at', $groupFn($p))->count())->values();
        $incidentSeries = $points->map(fn ($p) => Incident::whereBetween('created_at', $groupFn($p))->count())->values();
        $actionSeries = $points->map(fn ($p) => ProtectionAction::whereBetween('created_at', $groupFn($p))->count())->values();

        return [
            'labels'           => $labels,
            'alerts'           => $alertSeries,
            'incidents'        => $incidentSeries,
            'actions'          => $actionSeries,
            'risk_by_alert'    => [
                'normal'   => Alert::where('risk_level', 'normal')->count(),
                'suspect'  => Alert::where('risk_level', 'suspect')->count(),
                'high'     => Alert::where('risk_level', 'high')->count(),
                'critical' => Alert::where('risk_level', 'critical')->count(),
            ],
            'actions_by_status' => [
                'pending'   => ProtectionAction::where('approval_status', 'pending')->count(),
                'approved'  => ProtectionAction::where('approval_status', 'approved')->count(),
                'rejected'  => ProtectionAction::where('approval_status', 'rejected')->count(),
                'cancelled' => ProtectionAction::where('approval_status', 'cancelled')->count(),
            ],
        ];
    }
}
