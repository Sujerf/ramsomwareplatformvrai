<?php

namespace App\Services;

use App\Models\DiscoveredHost;
use App\Models\ManagedNetwork;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class InfrastructureInventoryService
{
    // ──────────────────────────────────────────────────────────────────────────
    //  DÉTECTION RÉSEAUX LOCAUX
    // ──────────────────────────────────────────────────────────────────────────

    public function detectLocalNetworks(): Collection
    {
        $interfaces = $this->localInterfaces();

        return collect($interfaces)
            ->map(fn (array $interface) => $this->upsertNetwork($interface))
            ->filter()
            ->values();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  SCAN D'UN RÉSEAU
    // ──────────────────────────────────────────────────────────────────────────

    public function scanNetwork(ManagedNetwork $network): array
    {
        if (! $network->is_monitored) {
            return [
                'network_id'     => $network->id,
                'cidr'           => $network->cidr,
                'hosts_detected' => 0,
                'method'         => 'blocked_retired_network',
                'note'           => 'Réseau retiré de la surveillance.',
            ];
        }

        // ── Étape 1 : découverte active ──────────────────────────────────────
        [$scanMethod, $confirmedIps] = $this->activeScan($network->cidr);

        // ── Étape 2 : construction de la liste d'hôtes ───────────────────────
        //
        // ORDRE IMPORTANT : localHostsForNetwork en PREMIER — ses rôles et
        // hostnames explicites (soc_server, gateway) ont priorité sur les
        // entrées anonymes issues du scan (fping/ARP ne connaissent pas les rôles).
        // unique('ip_address') conserve la PREMIÈRE occurrence → local wins.
        $hosts = collect();

        // Étape 2a : SOC + passerelle avec rôles explicites (priorité maximale)
        foreach ($this->localHostsForNetwork($network) as $host) {
            $hosts->push($host);
        }

        // Étape 2b : hôtes découverts par le scan actif
        // • fping  → liste d'IPs certifiées vivantes (output direct) + lookup MAC dans ARP
        // • autres → table ARP filtrée sur états REACHABLE/DELAY/PROBE/PERMANENT uniquement
        //            (les entrées STALE / FAILED / INCOMPLETE = fantômes, ignorées)
        if ($confirmedIps !== null) {
            $macMap = $this->macFromArpForIps($confirmedIps);

            foreach ($confirmedIps as $ip) {
                $hosts->push([
                    'ip_address'  => $ip,
                    'mac_address' => $macMap[$ip] ?? null,
                    'hostname'    => null,
                    'host_role'   => null,
                    'metadata'    => ['source' => 'fping_direct'],
                ]);
            }
        } else {
            foreach ($this->hostsFromIpNeigh() as $host) {
                $hosts->push($host);
            }
        }

        $hosts = $hosts
            ->filter(fn ($host) => ! empty($host['ip_address']))
            ->filter(fn ($host) => $this->ipInCidr($host['ip_address'], $network->cidr))
            ->unique('ip_address')
            ->values();

        // ── Étape 4 : upsert des hôtes trouvés ──────────────────────────────
        $foundIps = $hosts->pluck('ip_address')->toArray();

        foreach ($hosts as $host) {
            $this->upsertHost($network, $host);
        }

        // ── Étape 5 : retirer les hôtes du réseau qui n'ont PAS été trouvés ─
        //
        // Seuls les hôtes non-enrôlés sont auto-retirés : un agent déployé
        // manuellement ne doit pas disparaître parce que fping l'a raté.
        $retired = DB::table('discovered_hosts')
            ->where('managed_network_id', $network->id)
            ->where('is_monitored', true)
            ->where('enrollment_status', 'not_enrolled')
            ->whereNotIn('ip_address', $foundIps)
            ->update([
                'is_monitored'   => false,
                'retired_at'     => now(),
                'retired_reason' => 'Non détecté lors du scan du '.now()->format('d/m/Y H:i'),
                'updated_at'     => now(),
            ]);

        DB::table('managed_networks')
            ->where('id', $network->id)
            ->update([
                'last_scanned_at' => now(),
                'is_scannable'    => true,
                'metadata'        => json_encode(array_merge($this->asArray($network->metadata), [
                    'last_scan_at'      => now()->toDateTimeString(),
                    'last_scan_method'  => $scanMethod,
                    'last_scan_note'    => $this->scanMethodNote($scanMethod),
                    'last_scan_found'   => count($foundIps),
                    'last_scan_retired' => $retired,
                ]), JSON_UNESCAPED_UNICODE),
                'updated_at'      => now(),
            ]);

        return [
            'network_id'     => $network->id,
            'cidr'           => $network->cidr,
            'hosts_detected' => count($foundIps),
            'hosts_retired'  => $retired,
            'method'         => $scanMethod,
            'note'           => $this->scanMethodNote($scanMethod),
        ];
    }

    /**
     * Supprime définitivement tous les hôtes retirés (is_monitored = false).
     * À appeler depuis un bouton "Purger" dans l'interface.
     */
    public function purgeRetiredDiscoveredHosts(): int
    {
        return DB::table('discovered_hosts')
            ->where('is_monitored', false)
            ->delete();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  UPSERT RÉSEAU / HÔTE
    // ──────────────────────────────────────────────────────────────────────────

    public function upsertNetwork(array $data): ?ManagedNetwork
    {
        if (empty($data['cidr'])) {
            return null;
        }

        $network = ManagedNetwork::query()->where('cidr', $data['cidr'])->first();

        if ($network) {
            // Réseau existant : on met à jour les infos techniques.
            // Si le réseau est "retired" mais qu'il est à nouveau physiquement détecté
            // sur une interface active, on le restaure automatiquement — sa présence
            // sur l'interface prouve que le réseau est de retour.
            $isRetired = $network->status === 'retired' || ! $network->is_monitored;

            $update = [
                'name'           => $data['name'] ?? $network->name,
                'gateway_ip'     => $data['gateway_ip'] ?? $network->gateway_ip,
                'interface_name' => $data['interface_name'] ?? $network->interface_name,
                'is_scannable'   => true,
                'metadata'       => json_encode(array_merge(
                    $this->asArray($network->metadata),
                    $data['metadata'] ?? []
                ), JSON_UNESCAPED_UNICODE),
                'updated_at'     => now(),
            ];

            // Restauration automatique si retiré et physiquement présent
            if ($isRetired) {
                $update['status']        = 'detected';
                $update['is_monitored']  = true;
                $update['retired_at']    = null;
                $update['retired_reason'] = null;
            }

            DB::table('managed_networks')->where('id', $network->id)->update($update);

            return ManagedNetwork::find($network->id);
        }

        // Nouveau réseau : créé actif par défaut
        $payload = [
            'name'           => $data['name'] ?? 'Réseau détecté '.($data['interface_name'] ?? $data['cidr']),
            'cidr'           => $data['cidr'],
            'gateway_ip'     => $data['gateway_ip'] ?? null,
            'interface_name' => $data['interface_name'] ?? null,
            'status'         => 'detected',
            'is_scannable'   => true,
            'is_monitored'   => true,
            'retired_at'     => null,
            'retired_reason' => null,
            'metadata'       => json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        $id = DB::table('managed_networks')->insertGetId($payload);

        return ManagedNetwork::find($id);
    }

    public function upsertHost(ManagedNetwork $network, array $host): ?DiscoveredHost
    {
        $ip = $host['ip_address'] ?? null;

        if (! $ip) {
            return null;
        }

        $existing = DiscoveredHost::query()
            ->where('managed_network_id', $network->id)
            ->where(function ($query) use ($ip, $host) {
                $query->where('ip_address', $ip);

                if (! empty($host['mac_address'])) {
                    $query->orWhere('mac_address', $host['mac_address']);
                }
            })
            ->first();

        $role = $host['host_role'] ?? $this->guessHostRole($network, $ip, $host['hostname'] ?? null);

        $payload = [
            'managed_network_id' => $network->id,
            'ip_address'         => $ip,
            'mac_address'        => $host['mac_address'] ?? null,
            'hostname'           => $host['hostname'] ?? null,
            'host_role'          => $role,
            'discovery_status'   => 'detected',
            'enrollment_status'  => $existing?->enrollment_status ?? 'not_enrolled',
            'is_monitored'       => true,
            'retired_at'         => null,
            'retired_reason'     => null,
            'last_seen_at'       => now(),
            'metadata'           => json_encode(array_merge($host['metadata'] ?? [], [
                'network_id'     => $network->id,
                'network_cidr'   => $network->cidr,
                'redetected_at'  => now()->toDateTimeString(),
            ]), JSON_UNESCAPED_UNICODE),
            'updated_at'         => now(),
        ];

        if ($existing) {
            DB::table('discovered_hosts')->where('id', $existing->id)->update($payload);

            return DiscoveredHost::find($existing->id);
        }

        $payload['created_at'] = now();
        $id = DB::table('discovered_hosts')->insertGetId($payload);

        return DiscoveredHost::find($id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  DÉCOUVERTE ACTIVE — fping / nmap / ping parallèle / aucun
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Lance un scan actif du CIDR.
     * Retourne [$method, $confirmedIps] :
     *   - $confirmedIps : liste d'IPs certifiées vivantes (fping direct), ou null
     *     (les autres méthodes peuplent l'ARP — on filtrera par état ensuite).
     *
     * @return array{0: string, 1: string[]|null}
     */
    private function activeScan(string $cidr): array
    {
        // fping : sortie directe = uniquement les IPs UP, aucun fantôme possible
        if ($this->commandAvailable('fping')) {
            $result = Process::timeout(15)->run("fping -a -g {$cidr} 2>/dev/null || true");
            $output = trim($result->output());

            if ($output !== '') {
                $ips = array_values(array_filter(
                    array_map('trim', explode("\n", $output)),
                    fn (string $line) => filter_var($line, FILTER_VALIDATE_IP) !== false
                ));

                if (! empty($ips)) {
                    return ['fping', $ips];
                }
            }
        }

        // nmap : peuple l'ARP — on lira ensuite uniquement les entrées REACHABLE
        if ($this->commandAvailable('nmap')) {
            Process::timeout(30)->run("nmap -sn {$cidr} -T4 --min-parallelism 256 -oG /dev/null 2>/dev/null || true");

            return ['nmap', null];
        }

        // ping parallèle : uniquement pour les /24 et plus petits
        [, $prefix] = array_pad(explode('/', $cidr), 2, '24');

        if ((int) $prefix >= 24) {
            $parts  = explode('.', explode('/', $cidr)[0]);
            $subnet = $parts[0].'.'.$parts[1].'.'.$parts[2];

            Process::timeout(12)->run(
                "for i in \$(seq 1 254); do ping -c1 -W0.3 {$subnet}.\$i > /dev/null 2>&1 & done; wait"
            );

            return ['ping_sweep', null];
        }

        // Réseau trop grand ou aucun outil : table ARP filtrée uniquement
        return ['ip_neigh_only', null];
    }

    private function commandAvailable(string $cmd): bool
    {
        $result = Process::timeout(2)->run("command -v {$cmd}");

        return $result->successful() && trim($result->output()) !== '';
    }

    private function scanMethodNote(string $method): string
    {
        return match ($method) {
            'fping'         => 'Scan actif via fping — tous les hôtes UP du réseau sont détectés.',
            'nmap'          => 'Scan actif via nmap -sn — tous les hôtes UP du réseau sont détectés.',
            'ping_sweep'    => 'Ping parallèle /24 — table ARP peuplée, hôtes UP détectés.',
            'ip_neigh_only' => 'Table ARP locale uniquement (fping/nmap absents, réseau > /24). Installe fping pour un scan complet.',
            default         => 'Méthode de scan inconnue.',
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  LECTURE TABLE ARP + HÔTES LOCAUX
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Lit la table ARP et ne retourne QUE les entrées dont l'état est confirmé.
     *
     * États acceptés : REACHABLE, DELAY, PROBE, PERMANENT
     * États rejetés  : STALE (hôte disparu depuis un moment),
     *                  FAILED (résolution ARP échouée),
     *                  INCOMPLETE (résolution en cours, pas de MAC),
     *                  NOARP (pas de protocole ARP sur cette interface)
     */
    private function hostsFromIpNeigh(): array
    {
        $result = Process::timeout(3)->run('ip neigh show');

        if (! $result->successful()) {
            return [];
        }

        // États ARP qui garantissent que l'hôte est réellement joignable
        $validStates = ['REACHABLE', 'DELAY', 'PROBE', 'PERMANENT'];

        $hosts = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            // Format : <ip> dev <iface> [lladdr <mac>] <STATE>
            if (! preg_match(
                '/^(\d+\.\d+\.\d+\.\d+)\s+dev\s+(\S+)(?:\s+lladdr\s+([0-9a-fA-F:]+))?\s+(\w+)\s*$/i',
                $line,
                $m
            )) {
                continue;
            }

            $state = strtoupper($m[4]);

            if (! in_array($state, $validStates, true)) {
                continue; // STALE / FAILED / INCOMPLETE → fantôme, ignoré
            }

            $hosts[] = [
                'ip_address'  => $m[1],
                'mac_address' => $m[3] ?: null,
                'hostname'    => null,
                'host_role'   => null,
                'metadata'    => [
                    'source'    => 'ip_neigh',
                    'interface' => $m[2],
                    'arp_state' => $state,
                ],
            ];
        }

        return $hosts;
    }

    /**
     * Retourne une map [ip => mac] pour une liste d'IPs certifiées vivantes
     * (issues de fping). Aucun filtrage par état ARP : si fping dit que l'hôte
     * est UP, on lui fait confiance pour récupérer son MAC même si ARP dit STALE.
     *
     * @param  string[]       $ips
     * @return array<string, string>
     */
    private function macFromArpForIps(array $ips): array
    {
        if (empty($ips)) {
            return [];
        }

        $result = Process::timeout(3)->run('ip neigh show');

        if (! $result->successful()) {
            return [];
        }

        $ipSet  = array_flip($ips);
        $macMap = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            if (! preg_match(
                '/^(\d+\.\d+\.\d+\.\d+)\s+dev\s+\S+\s+lladdr\s+([0-9a-fA-F:]+)/i',
                $line,
                $m
            )) {
                continue;
            }

            if (isset($ipSet[$m[1]])) {
                $macMap[$m[1]] = $m[2];
            }
        }

        return $macMap;
    }

    private function localHostsForNetwork(ManagedNetwork $network): array
    {
        $hosts    = [];
        $metadata = $this->asArray($network->metadata);
        $localIp  = $metadata['ip'] ?? null;

        if ($localIp) {
            $hosts[] = [
                'ip_address'  => $localIp,
                'mac_address' => $metadata['mac_address'] ?? null,
                'hostname'    => gethostname() ?: null,
                'host_role'   => 'soc_server',
                'metadata'    => ['source' => 'local_soc_machine'],
            ];
        }

        // N'ajoute la passerelle que si c'est une machine DIFFÉRENTE du SOC.
        // Sur un bridge KVM (virbr-soc, virbr0), la gateway = IP locale du SOC
        // → déjà enregistrée ci-dessus, pas de doublon.
        if ($network->gateway_ip && $network->gateway_ip !== $localIp) {
            $hosts[] = [
                'ip_address'  => $network->gateway_ip,
                'mac_address' => null,
                'hostname'    => '_gateway',
                'host_role'   => 'gateway',
                'metadata'    => ['source' => 'default_gateway'],
            ];
        }

        return $hosts;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  DÉTECTION DES INTERFACES LOCALES
    // ──────────────────────────────────────────────────────────────────────────

    private function localInterfaces(): array
    {
        $result = Process::timeout(3)->run('ip -o -f inet addr show scope global');

        if (! $result->successful()) {
            return [];
        }

        // Récupère toutes les routes pour détection per-interface
        $routeResult = Process::timeout(2)->run('ip route show');
        $routeOutput = $routeResult->successful() ? $routeResult->output() : '';

        $items = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            if (! preg_match('/^\d+:\s+([^\s]+)\s+inet\s+([0-9.]+)\/(\d+)/', $line, $matches)) {
                continue;
            }

            $interface = $matches[1];
            $ip        = $matches[2];
            $prefix    = (int) $matches[3];

            if ($prefix <= 0 || $prefix > 32) {
                continue;
            }

            $cidr = $this->networkCidr($ip, $prefix);

            // ── Filtrage des interfaces inactives (linkdown) ─────────────────
            // Une interface bridge sans VM connectée affiche "linkdown" dans
            // la table de routage — inutile de la surveiller.
            if (preg_match('/\bdev\s+'.preg_quote($interface, '/').'\b.*\blinkdown\b/i', $routeOutput)) {
                continue;
            }

            // ── Détection de la passerelle propre à cette interface ──────────
            // Cas 1 : route par défaut via cette interface → la gateway est le routeur externe
            // Cas 2 : pas de route par défaut → cette machine EST la gateway (bridge/routeur)
            $gateway = $this->gatewayForInterface($interface, $ip, $routeOutput);

            $items[] = [
                'name'           => 'Réseau détecté '.$interface,
                'cidr'           => $cidr,
                'gateway_ip'     => $gateway,
                'interface_name' => $interface,
                'metadata'       => [
                    'ip'          => $ip,
                    'prefix'      => $prefix,
                    'source'      => 'local_interface_detection',
                    'detected_at' => now()->toDateTimeString(),
                ],
            ];
        }

        return $items;
    }

    /**
     * Retourne la passerelle appropriée pour une interface réseau donnée.
     *
     * • Si une route par défaut passe par cette interface (ex: WiFi → routeur DHCP) :
     *   retourne l'IP du routeur.
     * • Sinon (ex: bridge KVM virbr-soc) : cette machine est elle-même la gateway
     *   du segment → retourne l'IP locale de l'interface.
     */
    private function gatewayForInterface(string $interface, string $localIp, string $routeOutput): string
    {
        // Route par défaut spécifique à cette interface ?
        if (preg_match('/default via ([0-9.]+)\s+dev\s+'.preg_quote($interface, '/').'/i', $routeOutput, $m)) {
            return $m[1];
        }

        // Aucune route par défaut → cette machine est la passerelle (bridge, routeur)
        return $localIp;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  UTILITAIRES
    // ──────────────────────────────────────────────────────────────────────────

    private function guessHostRole(ManagedNetwork $network, string $ip, ?string $hostname): string
    {
        if ($network->gateway_ip === $ip || $hostname === '_gateway') {
            return 'gateway';
        }

        $metadata = $this->asArray($network->metadata);

        if (($metadata['ip'] ?? null) === $ip) {
            return 'soc_server';
        }

        return 'client';
    }

    private function networkCidr(string $ip, int $prefix): string
    {
        $long    = ip2long($ip);
        $mask    = -1 << (32 - $prefix);
        $network = $long & $mask;

        return long2ip($network).'/'.$prefix;
    }

    /**
     * Vérifie qu'une IP appartient bien au CIDR donné.
     * Filtre les entrées ARP d'autres interfaces qui pourraient polluer un scan.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$network, $prefix] = explode('/', $cidr);
        $prefix = (int) $prefix;

        $ipLong  = ip2long($ip);
        $netLong = ip2long($network);
        $mask    = $prefix > 0 ? (-1 << (32 - $prefix)) : 0;

        return ($ipLong & $mask) === ($netLong & $mask);
    }

    private function asArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
