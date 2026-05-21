<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\SocStatusSynchronizerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $risk = $request->query('risk');

        $query = Incident::with(['agent', 'attackProfile'])
            ->latest('detected_at')
            ->latest();

        if ($status === 'active') {
            $query->whereIn('status', ['open', 'investigating', 'under_review', 'reopened']);
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        } elseif ($status === 'false_positive') {
            $query->where('status', 'false_positive');
        }

        if ($risk && in_array($risk, ['normal', 'suspect', 'high', 'critical'], true)) {
            $query->where('risk_level', $risk);
        }

        return view('platform.incidents.index', [
            'incidents' => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'activeRisk' => $risk,
            'stats' => [
                'active' => Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count(),
                'resolved' => Incident::where('status', 'resolved')->count(),
                'false_positive' => Incident::where('status', 'false_positive')->count(),
                'critical' => Incident::where('risk_level', 'critical')->count(),
                'high' => Incident::where('risk_level', 'high')->count(),
                'total' => Incident::count(),
            ],
        ]);
    }

    public function show(Incident $incident): View
    {
        $incident->load([
            'agent',
            'attackProfile',
            'alerts.event',
            'events',
            'protectionActions.protectionPolicy',
            'protectionActions.decisions.user',
            'notifications',
        ]);

        return view('platform.incidents.show', [
            'incident' => $incident,
        ]);
    }

    public function resolve(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->resolveIncident($incident, 'Incident résolu manuellement depuis la console SOC.');

        return back()->with('success', 'Incident résolu. Les alertes liées ont été clôturées.');
    }

    public function falsePositive(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->falsePositiveIncident($incident);

        return back()->with('success', 'Incident classé faux positif. Alertes et actions liées synchronisées.');
    }

    public function reopen(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->reopenIncident($incident);

        return back()->with('success', 'Incident réouvert.');
    }
}
