<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $query = Agent::query()
            ->with('discoveredHost')
            ->latest('last_seen_at')
            ->latest();

        if ($status === 'pending') {
            $query->where('enrollment_status', 'pending');
        } elseif ($status === 'enrolled') {
            $query->where('enrollment_status', 'enrolled');
        } elseif ($status === 'critical') {
            $query->where('risk_level', 'critical');
        }

        return view('platform.agents.index', [
            'agents' => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'stats' => [
                'total' => Agent::count(),
                'pending' => Agent::where('enrollment_status', 'pending')->count(),
                'enrolled' => Agent::where('enrollment_status', 'enrolled')->count(),
                'critical' => Agent::where('risk_level', 'critical')->count(),
            ],
        ]);
    }

    public function show(Agent $agent): View
    {
        $agent->load([
            'discoveredHost.managedNetwork',
            'events' => fn ($query) => $query->latest()->limit(12),
            'alerts' => fn ($query) => $query->latest()->limit(8),
            'incidents' => fn ($query) => $query->latest()->limit(8),
            'protectionActions' => fn ($query) => $query->latest()->limit(8),
        ]);

        $baseUrl = rtrim(config('app.url') ?: url('/'), '/');

        return view('platform.agents.show', [
            'agent' => $agent,
            'installCommand' => $this->installCommand($agent, $baseUrl),
        ]);
    }

    private function installCommand(Agent $agent, string $baseUrl): string
    {
        $token = $agent->enrollment_token ?: 'TOKEN_DEJA_UTILISE_OU_NON_REQUIS';

        return "curl -fsSL {$baseUrl}/agent/install.sh | bash -s -- --server={$baseUrl} --token={$token} --agent-uuid={$agent->agent_uuid}";
    }
}
