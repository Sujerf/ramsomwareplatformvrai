<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\SocStatusSynchronizerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $risk = $request->query('risk');

        $query = Alert::with(['agent', 'incident'])
            ->latest('detected_at')
            ->latest();

        if ($status === 'active') {
            $query->whereIn('status', ['open', 'acknowledged', 'investigating']);
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        } elseif ($status === 'false_positive') {
            $query->where('status', 'false_positive');
        }

        if ($risk && in_array($risk, ['normal', 'suspect', 'high', 'critical'], true)) {
            $query->where('risk_level', $risk);
        }

        // Compteurs par statut (pour les onglets de filtre)
        $cntActive   = Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count();
        $cntResolved = Alert::where('status', 'resolved')->count();
        $cntFalsePos = Alert::where('status', 'false_positive')->count();
        $cntTotal    = Alert::count();

        // Compteurs par niveau de risque (parmi les alertes actives)
        $riskCounts = Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])
            ->selectRaw('risk_level, COUNT(*) as cnt')
            ->groupBy('risk_level')
            ->pluck('cnt', 'risk_level')
            ->toArray();

        return view('platform.alerts.index', [
            'alerts'       => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'activeRisk'   => $risk ?? '',   // '' = tous risques (jamais null côté vue)
            'stats'        => [
                'active'        => $cntActive,
                'resolved'      => $cntResolved,
                'false_positive'=> $cntFalsePos,
                'critical'      => Alert::where('risk_level', 'critical')->count(),
                'high'          => Alert::where('risk_level', 'high')->count(),
                'total'         => $cntTotal,
            ],
            'filterCounts' => [
                'status' => [
                    'active'        => $cntActive,
                    'resolved'      => $cntResolved,
                    'false_positive'=> $cntFalsePos,
                    'all'           => $cntTotal,
                ],
                'risk' => [
                    ''         => $cntActive,
                    'critical' => $riskCounts['critical'] ?? 0,
                    'high'     => $riskCounts['high']     ?? 0,
                    'suspect'  => $riskCounts['suspect']  ?? 0,
                    'normal'   => $riskCounts['normal']   ?? 0,
                ],
            ],
        ]);
    }

    public function show(Alert $alert): View
    {
        $alert->load(['agent', 'incident', 'event', 'notifications']);

        return view('platform.alerts.show', [
            'alert' => $alert,
        ]);
    }

    public function resolve(Request $request, Alert $alert, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->resolveAlert($alert);

        return back()->with('success', 'Alerte résolue et incident synchronisé.');
    }

    public function falsePositive(Request $request, Alert $alert, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->falsePositiveAlert($alert);

        return back()->with('success', 'Alerte classée faux positif.');
    }

    public function reopen(Request $request, Alert $alert, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->reopenAlert($alert);

        return back()->with('success', 'Alerte réouverte.');
    }
}
