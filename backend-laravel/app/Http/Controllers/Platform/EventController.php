<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $risk       = $request->query('risk', 'all');
        $type       = $request->query('type');
        $agentId    = $request->query('agent_id');
        $simulation = $request->query('simulation');   // '1' = simu seulement, '0' = réels seulement

        $query = Event::query()
            ->with(['agent', 'alert', 'incident'])
            ->latest('observed_at')
            ->latest();

        if ($risk !== 'all' && in_array($risk, ['normal', 'suspect', 'high', 'critical'], true)) {
            $query->where('risk_level', $risk);
        }

        if ($type) {
            $query->where('event_type', $type);
        }

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        if ($simulation === '1') {
            $query->where('is_simulation', true);
        } elseif ($simulation === '0') {
            $query->where('is_simulation', false);
        }

        return view('platform.events.index', [
            'events' => $query->paginate(30)->withQueryString(),
            'agents' => Agent::query()->orderBy('agent_name')->get(),
            'eventTypes' => Event::query()
                ->select('event_type')
                ->distinct()
                ->orderBy('event_type')
                ->pluck('event_type'),
            'activeRisk'     => $risk,
            'activeType'     => $type,
            'activeAgentId'  => $agentId,
            'activeSimulation' => $simulation,
            'stats' => [
                'total'      => Event::count(),
                'normal'     => Event::where('risk_level', 'normal')->count(),
                'suspect'    => Event::where('risk_level', 'suspect')->count(),
                'high'       => Event::where('risk_level', 'high')->count(),
                'critical'   => Event::where('risk_level', 'critical')->count(),
                'simulation' => Event::where('is_simulation', true)->count(),
                'real'       => Event::where('is_simulation', false)->count(),
            ],
        ]);
    }

    public function show(Event $event): View
    {
        $event->load(['agent', 'alert', 'incident']);

        return view('platform.events.show', [
            'event' => $event,
        ]);
    }
}
