<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ProtectionAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
     * Régénère un token d'enrôlement valable 48h pour un agent pending.
     * Permet de re-déclencher le bootstrap sans repasser par "Hôtes découverts".
     */
    public function regenerateToken(Agent $agent): RedirectResponse
    {
        $token     = Str::random(48);
        $expiresAt = now()->addHours(48);

        // Régénère aussi le short code pour cette session d'enrôlement
        $shortCode = $this->generateUniqueShortCode($agent->id);

        \Illuminate\Support\Facades\DB::table('agents')
            ->where('id', $agent->id)
            ->update([
                'enrollment_token'            => $token,
                'enrollment_token_expires_at' => $expiresAt,
                'enrollment_status'           => 'pending',
                'status'                      => 'pending_enrollment',
                'enrollment_short_code'       => $shortCode,
                'updated_at'                  => now(),
            ]);

        return redirect()
            ->route('platform.agents.show', $agent)
            ->with('success', 'Nouveau token généré — valable 48h. Lance le script bootstrap sur la machine cible.');
    }

    /**
     * Crée et envoie une commande manuelle à un agent.
     *
     * Actions supportées :
     *   isolate_host  — bloque tout le trafic sauf SOC (netsh / iptables)
     *   kill_process  — tue un processus par PID (payload.pid requis)
     *   rollback_isolation — retire l'isolation réseau
     */
    public function sendCommand(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'action_type' => ['required', 'string', 'in:isolate_host,kill_process,rollback_isolation'],
            'pid'         => ['nullable', 'integer', 'min:1'],
            'note'        => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [];
        if ($validated['action_type'] === 'kill_process') {
            if (empty($validated['pid'])) {
                return back()->withErrors(['pid' => 'Le PID est requis pour kill_process.']);
            }
            $payload['pid'] = (int) $validated['pid'];
        }

        if ($validated['action_type'] === 'isolate_host') {
            // Fournir l'IP du SOC pour que l'agent garde la communication ouverte
            $socUrl = config('app.soc_url', config('app.url'));
            $socIp  = gethostbyname(parse_url($socUrl, PHP_URL_HOST) ?? '127.0.0.1');
            $payload['soc_ip'] = $socIp;
        }

        $action = ProtectionAction::create([
            'agent_id'         => $agent->id,
            'action_uuid'      => Str::uuid()->toString(),
            'action_type'      => $validated['action_type'],
            'decision_mode'    => 'manual',
            'execution_status' => 'pending',
            'approval_status'  => 'approved',  // manuel = déjà approuvé par l'opérateur
            'is_reversible'    => $validated['action_type'] === 'isolate_host',
            'rollback_available' => false,
            'description'      => $validated['note'] ?? "Commande manuelle : {$validated['action_type']} (opérateur: ".auth()->user()->name.')',
            'payload'          => $payload,
            'proposed_at'      => now(),
        ]);

        return redirect()
            ->route('platform.protection-actions.show', $action)
            ->with('success', "Commande « {$validated['action_type']} » envoyée à l'agent. Elle sera exécutée lors du prochain poll (≤ 30 s).");
    }

    /**
     * Génère un short code unique sur 8 chars alphanumériques pour un agent.
     * Essaie d'abord les 8 premiers chars de l'UUID (sans tirets), puis fallback random.
     */
    private function generateUniqueShortCode(?int $excludeAgentId = null): string
    {
        do {
            $code   = strtolower(Str::random(8));
            $exists = \Illuminate\Support\Facades\DB::table('agents')
                ->where('enrollment_short_code', $code)
                ->when($excludeAgentId, fn ($q) => $q->where('id', '!=', $excludeAgentId))
                ->exists();
        } while ($exists);

        return $code;
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

        // URL courte pour KVM — copier-coller-free
        $shortCode      = $agent->enrollment_short_code;
        $shortEnrollUrl = ($hasValidToken && $shortCode)
            ? $socUrl.'/e/'.$shortCode
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
            'short_enroll_url'     => $shortEnrollUrl,
            'short_code'           => $shortCode,
            'agent_source_path'    => $agentSourcePath,
            'service_name'         => 'ransomshield-agent',
        ];
    }
}
