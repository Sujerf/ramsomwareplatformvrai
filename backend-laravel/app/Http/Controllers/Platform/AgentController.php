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

        return view('platform.agents.show', [
            'agent'       => $agent,
            'installInfo' => $this->agentInstallInfo($agent),
        ]);
    }

    /**
     * Retourne les informations d'installation de l'agent.
     *
     * RANSHIELD_SOC_URL (dans .env) = URL du SOC accessible depuis les VMs.
     * Différente de APP_URL (localhost dev) → l'opérateur la configure une fois.
     */
    private function agentInstallInfo(Agent $agent): array
    {
        // URL externe du SOC, accessible depuis les machines cibles
        $socUrl    = rtrim(config('app.soc_url', config('app.url')), '/');
        $apiUrl    = $socUrl.'/api';
        $apiSecret = config('app.agent_api_secret', '');

        $enrollmentToken   = $agent->enrollment_token;
        $tokenExpiresAt    = $agent->enrollment_token_expires_at;
        $tokenIsExpired    = $tokenExpiresAt && now()->gt($tokenExpiresAt);
        $tokenExpiresLabel = $tokenExpiresAt
            ? ($tokenIsExpired ? 'EXPIRÉ' : $tokenExpiresAt->diffForHumans())
            : null;

        $hasValidToken = $enrollmentToken && ! $tokenIsExpired;

        // .env complet généré pour copier-coller ou pour le script bootstrap
        $hostRole = $agent->host_role ?? 'client';

        $envLines = [
            "RANSHIELD_API_URL={$apiUrl}",
            "RANSHIELD_API_SECRET={$apiSecret}",
            "RANSHIELD_AGENT_UUID={$agent->agent_uuid}",
            "RANSHIELD_AGENT_NAME={$agent->agent_name}",
            "RANSHIELD_HOST_ROLE={$hostRole}",
            "RANSHIELD_MONITOR_MODE=host",
            "RANSHIELD_HEARTBEAT_INTERVAL=30",
            "RANSHIELD_SCAN_INTERVAL=5",
        ];

        if ($hasValidToken) {
            $envLines[] = "RANSHIELD_ENROLLMENT_TOKEN={$enrollmentToken}";
        }

        // Commande one-liner (uniquement si token valide)
        $bootstrapUrl = $hasValidToken
            ? $socUrl.'/api/agent/bootstrap/'.$agent->agent_uuid
            : null;

        // Chemin réel vers les fichiers agent pour rsync
        $agentSourcePath = base_path('../agent-python/');

        return [
            'soc_url'              => $socUrl,
            'api_url'              => $apiUrl,
            'api_secret'           => $apiSecret,
            'agent_uuid'           => $agent->agent_uuid,
            'agent_name'           => $agent->agent_name,
            'host_role'            => $hostRole,
            'enrollment_token'     => $enrollmentToken,
            'token_expires_at'     => $tokenExpiresAt,
            'token_expires_label'  => $tokenExpiresLabel,
            'token_is_expired'     => $tokenIsExpired,
            'has_valid_token'      => $hasValidToken,
            'env_content'          => implode("\n", $envLines),
            'bootstrap_url'        => $bootstrapUrl,
            'agent_source_path'    => $agentSourcePath,
            'service_name'         => 'ransomshield-agent',
        ];
    }
}
