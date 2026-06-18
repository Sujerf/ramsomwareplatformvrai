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
     * Régénère un token d'enrôlement valable 48h.
     *
     * Comportement selon l'état de l'agent :
     *  - pending   : régénère le token + short code, conserve le statut pending.
     *  - enrolled  : régénère un token de RE-enrôlement. L'agent actuel continue
     *                de fonctionner avec sa clé API existante jusqu'à ce que le
     *                nouveau bootstrap soit exécuté. Le statut reste 'enrolled'
     *                pour ne pas interrompre la surveillance en cours.
     */
    public function regenerateToken(Agent $agent): RedirectResponse
    {
        $token     = Str::random(48);
        $expiresAt = now()->addHours(48);

        // Régénère aussi le short code pour cette session d'enrôlement
        $shortCode = $this->generateUniqueShortCode($agent->id);

        // Ne PAS rétrograder un agent déjà enrôlé — il continue de surveiller
        // pendant que le nouveau token est préparé pour une réinstallation.
        $isEnrolled = $agent->enrollment_status === 'enrolled';

        \Illuminate\Support\Facades\DB::table('agents')
            ->where('id', $agent->id)
            ->update(array_filter([
                'enrollment_token'            => $token,
                'enrollment_token_expires_at' => $expiresAt,
                'enrollment_short_code'       => $shortCode,
                // Seulement si l'agent n'est pas encore enrôlé
                'enrollment_status'           => $isEnrolled ? 'enrolled' : 'pending',
                'status'                      => $isEnrolled ? $agent->status : 'pending_enrollment',
                'updated_at'                  => now(),
            ]));

        $msg = $isEnrolled
            ? 'Token de ré-enrôlement généré — valable 48h. L\'agent actuel continue de fonctionner jusqu\'à la réinstallation.'
            : 'Nouveau token généré — valable 48h. Lance le script bootstrap sur la machine cible.';

        return redirect()
            ->route('platform.agents.show', $agent)
            ->with('success', $msg);
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
            'action_type' => ['required', 'string', 'in:isolate_host,kill_process,rollback_isolation,update_agent,force_scan'],
            'pid'         => ['nullable', 'integer', 'min:1'],
            'scan_type'   => ['nullable', 'string', 'in:quick,full'],
            'scan_paths'  => ['nullable', 'string', 'max:1000'],
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
            $socUrl = config('app.soc_url', config('app.url'));
            $socIp  = gethostbyname(parse_url($socUrl, PHP_URL_HOST) ?? '127.0.0.1');
            $payload['soc_ip'] = $socIp;
        }

        if ($validated['action_type'] === 'force_scan') {
            $payload['scan_type'] = $validated['scan_type'] ?? 'quick';
            if (! empty($validated['scan_paths'])) {
                $payload['paths'] = array_values(array_filter(
                    array_map('trim', explode("\n", $validated['scan_paths']))
                ));
            }
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
     * Désinscrire un agent enrôlé : efface la clé API et le token, remet en pending.
     * L'agent conserve son historique (events, alertes, incidents).
     * Utile pour forcer un re-enrôlement sans supprimer les données.
     */
    public function unenroll(Agent $agent): RedirectResponse
    {
        \Illuminate\Support\Facades\DB::table('agents')
            ->where('id', $agent->id)
            ->update([
                'enrollment_status'           => 'pending',
                'status'                      => 'pending_enrollment',
                'agent_api_key'               => null,
                'enrollment_token'            => null,
                'enrollment_token_expires_at' => null,
                'enrolled_at'                 => null,
                'updated_at'                  => now(),
            ]);

        return redirect()
            ->route('platform.agents.show', $agent)
            ->with('success', "Agent « {$agent->agent_name} » désinscrit. Générez un nouveau token pour le ré-enrôler.");
    }

    /**
     * Supprimer définitivement un agent et toutes ses données liées.
     * Action irréversible — demande confirmation côté vue.
     */
    public function destroy(Agent $agent): RedirectResponse
    {
        $name = $agent->agent_name;

        // Suppression en cascade via la base (FK ou manual)
        \Illuminate\Support\Facades\DB::transaction(function () use ($agent) {
            $agent->protectionActions()->delete();
            $agent->alerts()->delete();
            $agent->incidents()->delete();
            $agent->events()->delete();
            $agent->riskSnapshots()->delete();
            $agent->delete();
        });

        return redirect()
            ->route('platform.agents.index')
            ->with('success', "Agent « {$name} » supprimé définitivement avec toutes ses données.");
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
     * L'URL SOC est calculée automatiquement depuis le réseau de l'agent
     * (managed_networks.metadata.ip) — le port provient de RANSHIELD_SOC_URL.
     * Ainsi chaque agent obtient l'IP SOC joignable depuis SON réseau, sans
     * configuration manuelle : VMs sur 10.20.0.x → 10.20.0.1, WiFi → 192.168.1.194,
     * prod sur un seul réseau → l'IP de l'interface de ce réseau.
     */
    private function agentInstallInfo(Agent $agent): array
    {
        // URL externe du SOC, accessible depuis les machines cibles
        $socUrl = $this->resolveSocUrlForAgent($agent);
        $apiUrl = $socUrl.'/api';
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

        // Réseau associé (pour affichage dans la vue)
        $network     = $agent->discoveredHost?->managedNetwork;
        $networkName = $network?->name ?? null;
        $networkCidr = $network?->cidr ?? null;

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
            'network_name'         => $networkName,
            'network_cidr'         => $networkCidr,
        ];
    }

    /**
     * Calcule l'URL SOC joignable depuis le réseau de l'agent.
     *
     * Stratégie :
     *   1. Lit managed_networks.metadata.ip → IP du SOC sur l'interface de ce réseau
     *   2. Conserve le port extrait de RANSHIELD_SOC_URL (configuré une fois dans .env)
     *   3. Fallback sur RANSHIELD_SOC_URL complet si pas de réseau associé
     *
     * Exemples :
     *   agent sur 10.20.0.x  → http://10.20.0.1:8081   (virbr-soc)
     *   agent sur 192.168.1.x → http://192.168.1.194:8081 (wlp58s0)
     *   prod réseau unique    → IP de l'interface unique du SOC
     *   RANSHIELD_SOC_URL = https://soc.company.com → utilisé tel quel (pas d'IP)
     */
    private function resolveSocUrlForAgent(Agent $agent): string
    {
        $configuredUrl = rtrim(config('app.soc_url', config('app.url')), '/');

        // Si la SOC_URL est configurée avec un nom de domaine (non IP),
        // on la respecte telle quelle — l'admin a fait un choix explicite.
        $configuredHost = parse_url($configuredUrl, PHP_URL_HOST) ?? '';
        $isIpUrl        = filter_var($configuredHost, FILTER_VALIDATE_IP) !== false;

        if (! $isIpUrl) {
            return $configuredUrl;
        }

        // ── Étape 1 : réseau via la relation discovered_host → managedNetwork ─
        $network = $agent->discoveredHost?->managedNetwork;

        // ── Étape 2 : fallback — chercher le réseau par CIDR contenant l'IP ──
        // Utile quand managed_network_id est NULL sur le discovered_host
        // (agents enrôlés avant le scan ou sans découverte préalable).
        if (! $network && $agent->ip_address) {
            $agentIp   = $agent->ip_address;
            $networks  = \App\Models\ManagedNetwork::all();
            foreach ($networks as $candidate) {
                if ($this->ipInCidr($agentIp, $candidate->cidr)) {
                    $network = $candidate;
                    break;
                }
            }
        }

        $socIpOnNetwork = data_get($network?->metadata, 'ip');

        if (! $socIpOnNetwork) {
            // Aucun réseau correspondant → SOC_URL configuré dans .env
            return $configuredUrl;
        }

        // Conserver le schéma et le port de la SOC_URL configurée
        $scheme  = parse_url($configuredUrl, PHP_URL_SCHEME) ?? 'http';
        $port    = parse_url($configuredUrl, PHP_URL_PORT);

        $baseUrl = $scheme.'://'.$socIpOnNetwork;
        if ($port) {
            $baseUrl .= ':'.$port;
        }

        return $baseUrl;
    }

    /**
     * Vérifie si une IP est dans un CIDR donné (ex: 10.20.0.72 dans 10.20.0.0/24).
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits >= 32 ? -1 : ~((1 << (32 - (int) $bits)) - 1);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
