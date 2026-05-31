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
            'notification_ui_enabled',
            'notification_sound_enabled',
            'notification_mail_enabled',
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

    private function dateGroupExpr(string $format): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('{$format}', created_at) as period"
            : "DATE_FORMAT(created_at, '{$format}') as period";
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
            $start     = now()->subHours(23)->startOfHour();
            $end       = now()->endOfHour();
            $points    = collect(range(23, 0))->map(fn ($h) => now()->subHours($h)->startOfHour());
            $labels    = $points->map(fn (Carbon $h) => $h->format('H\h'))->values();
            $sqlFormat = '%Y-%m-%d %H:00:00';
            $phpFormat = 'Y-m-d H:00:00';
        } elseif ($period === 'month') {
            $start     = now()->subDays(29)->startOfDay();
            $end       = now()->endOfDay();
            $points    = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->startOfDay());
            $labels    = $points->map(fn (Carbon $d) => $d->format('d/m'))->values();
            $sqlFormat = '%Y-%m-%d';
            $phpFormat = 'Y-m-d';
        } else {
            $start     = now()->subDays(6)->startOfDay();
            $end       = now()->endOfDay();
            $points    = collect(range(6, 0))->map(fn ($d) => now()->subDays($d)->startOfDay());
            $labels    = $points->map(fn (Carbon $d) => $d->isoFormat('ddd'))->values();
            $sqlFormat = '%Y-%m-%d';
            $phpFormat = 'Y-m-d';
        }

        $groupExpr = $this->dateGroupExpr($sqlFormat);

        // 3 requêtes groupées au lieu de N×3 requêtes par point de série
        $alertCounts   = DB::table('alerts')
            ->selectRaw("{$groupExpr}, COUNT(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('total', 'period');

        $incidentCounts = DB::table('incidents')
            ->selectRaw("{$groupExpr}, COUNT(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('total', 'period');

        $actionCounts  = DB::table('protection_actions')
            ->selectRaw("{$groupExpr}, COUNT(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('total', 'period');

        $alertSeries    = $points->map(fn (Carbon $p) => (int) ($alertCounts[$p->format($phpFormat)]   ?? 0))->values();
        $incidentSeries = $points->map(fn (Carbon $p) => (int) ($incidentCounts[$p->format($phpFormat)] ?? 0))->values();
        $actionSeries   = $points->map(fn (Carbon $p) => (int) ($actionCounts[$p->format($phpFormat)]  ?? 0))->values();

        // 2 requêtes groupées pour les distributions (au lieu de 8 counts séparés)
        $riskByAlert = Alert::query()
            ->selectRaw('risk_level, COUNT(*) as total')
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level')
            ->toArray();

        $actionsByStatus = ProtectionAction::query()
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status')
            ->toArray();

        return [
            'labels'            => $labels,
            'alerts'            => $alertSeries,
            'incidents'         => $incidentSeries,
            'actions'           => $actionSeries,
            'risk_by_alert'     => [
                'normal'   => (int) ($riskByAlert['normal']   ?? 0),
                'suspect'  => (int) ($riskByAlert['suspect']  ?? 0),
                'high'     => (int) ($riskByAlert['high']     ?? 0),
                'critical' => (int) ($riskByAlert['critical'] ?? 0),
            ],
            'actions_by_status' => [
                'pending'   => (int) ($actionsByStatus['pending']   ?? 0),
                'approved'  => (int) ($actionsByStatus['approved']  ?? 0),
                'rejected'  => (int) ($actionsByStatus['rejected']  ?? 0),
                'cancelled' => (int) ($actionsByStatus['cancelled'] ?? 0),
            ],
        ];
    }
}
