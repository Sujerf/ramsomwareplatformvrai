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

        return view('platform.alerts.index', [
            'alerts' => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'activeRisk' => $risk,
            'stats' => [
                'active' => Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count(),
                'resolved' => Alert::where('status', 'resolved')->count(),
                'false_positive' => Alert::where('status', 'false_positive')->count(),
                'critical' => Alert::where('risk_level', 'critical')->count(),
                'high' => Alert::where('risk_level', 'high')->count(),
                'total' => Alert::count(),
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
