<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const LIMIT = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => [], 'query' => $q]);
        }

        $like = '%'.$q.'%';

        $incidents = Incident::where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                      ->orWhere('description', 'like', $like)
                      ->orWhere('incident_uuid', 'like', $like);
            })
            ->latest('detected_at')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'risk_level', 'status', 'detected_at'])
            ->map(fn ($i) => [
                'type'    => 'incident',
                'id'      => $i->id,
                'label'   => $i->title,
                'sub'     => strtoupper($i->risk_level).' · '.$i->status,
                'risk'    => $i->risk_level,
                'url'     => route('platform.incidents.show', $i),
                'icon'    => 'fa-triangle-exclamation',
            ]);

        $alerts = Alert::where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                      ->orWhere('message', 'like', $like);
            })
            ->latest('detected_at')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'risk_level', 'status', 'detected_at'])
            ->map(fn ($a) => [
                'type'    => 'alert',
                'id'      => $a->id,
                'label'   => $a->title,
                'sub'     => strtoupper($a->risk_level).' · '.$a->status,
                'risk'    => $a->risk_level,
                'url'     => route('platform.alerts.show', $a),
                'icon'    => 'fa-bell',
            ]);

        $agents = Agent::where(function ($query) use ($like) {
                $query->where('agent_name', 'like', $like)
                      ->orWhere('ip_address', 'like', $like)
                      ->orWhere('hostname', 'like', $like);
            })
            ->limit(self::LIMIT)
            ->get(['id', 'agent_name', 'ip_address', 'status', 'risk_level'])
            ->map(fn ($a) => [
                'type'    => 'agent',
                'id'      => $a->id,
                'label'   => $a->agent_name,
                'sub'     => ($a->ip_address ?? '—').' · '.$a->status,
                'risk'    => $a->risk_level,
                'url'     => route('platform.agents.show', $a),
                'icon'    => 'fa-desktop',
            ]);

        $results = $incidents->concat($alerts)->concat($agents)->values();

        return response()->json([
            'results' => $results,
            'query'   => $q,
            'counts'  => [
                'incidents' => $incidents->count(),
                'alerts'    => $alerts->count(),
                'agents'    => $agents->count(),
            ],
        ]);
    }
}
