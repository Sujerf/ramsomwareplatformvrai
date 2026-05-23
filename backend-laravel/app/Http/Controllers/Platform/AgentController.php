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
            'agent'       => $agent,
            'installInfo' => $this->agentInstallInfo($agent, $baseUrl),
        ]);
    }

    /**
     * Retourne les informations nécessaires à l'installation manuelle de l'agent.
     * Plus de fausse commande curl : on guide l'opérateur étape par étape.
     */
    private function agentInstallInfo(Agent $agent, string $baseUrl): array
    {
        $apiSecret = config('app.agent_api_secret', '');

        $envContent = implode("\n", [
            "RANSHIELD_API_URL={$baseUrl}/api",
            "RANSHIELD_API_SECRET={$apiSecret}",
            "RANSHIELD_AGENT_UUID={$agent->agent_uuid}",
            "RANSHIELD_AGENT_NAME={$agent->agent_name}",
            "RANSHIELD_HOST_ROLE={$agent->host_role}",
            "RANSHIELD_MONITOR_MODE=host",
        ]);

        return [
            'api_url'          => $baseUrl.'/api',
            'api_secret'       => $apiSecret,
            'agent_uuid'       => $agent->agent_uuid,
            'agent_name'       => $agent->agent_name,
            'host_role'        => $agent->host_role ?? 'client',
            'enrollment_token' => $agent->enrollment_token,
            'env_content'      => $envContent,
            'install_cmd'      => 'sudo bash install.sh',
            'service_name'     => 'ransomshield-agent',
        ];
    }
}
