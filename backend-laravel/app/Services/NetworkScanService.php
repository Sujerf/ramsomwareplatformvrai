<?php

namespace App\Services;

use App\Models\DiscoveredHost;
use App\Models\ManagedNetwork;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class NetworkScanService
{
    public function __construct(
        private readonly LocalHostDetectionService $localHostDetectionService,
        private readonly HostQualificationService $hostQualificationService,
    ) {
    }

    public function scan(ManagedNetwork $network): array
    {
        /*
         * Scan WEB-SAFE :
         * - aucun ping ;
         * - aucune résolution hostname getent ;
         * - aucune boucle 1..254 ;
         * - uniquement ip neigh + machine SOC locale.
         *
         * Le scan complet sera fait plus tard via commande Artisan/job.
         */
        return $this->webSafeScan($network);
    }

    public function webSafeScan(ManagedNetwork $network): array
    {
        $localInfo = $this->localHostDetectionService->detect();
        $socIp = $localInfo['primary_ip'] ?? null;

        $neighbors = collect($this->readIpNeighbors())
            ->filter(fn (array $host) => $this->ipBelongsToCidr($host['ip_address'], $network->cidr))
            ->values();

        if ($socIp && $this->ipBelongsToCidr($socIp, $network->cidr)) {
            $neighbors->push([
                'ip_address' => $socIp,
                'mac_address' => $localInfo['primary_mac'] ?? null,
                'hostname' => $localInfo['hostname'] ?? null,
                'interface' => $localInfo['primary_interface'] ?? null,
                'source' => 'local_soc_host',
                'open_ports' => [],
                'detected_services' => [],
            ]);
        }

        $createdOrUpdated = $this->storeHosts(
            network: $network,
            hosts: $neighbors->unique('ip_address')->values(),
            socIp: $socIp,
            scanMethod: 'web_safe_ip_neigh'
        );

        $network->update([
            'last_scanned_at' => now(),
            'metadata' => array_merge($network->metadata ?? [], [
                'last_scan_method' => 'web_safe_ip_neigh',
                'last_scan_at' => now()->toDateTimeString(),
                'last_scan_note' => 'Scan web sécurisé : ip neigh + machine SOC locale uniquement. Aucun ping massif, aucune résolution DNS bloquante.',
            ]),
        ]);

        return [
            'network_id' => $network->id,
            'cidr' => $network->cidr,
            'hosts_detected' => $createdOrUpdated,
            'method' => 'web_safe_ip_neigh',
            'note' => 'Scan web sécurisé terminé instantanément. Pour découvrir plus d’hôtes, un scan Artisan non bloquant sera ajouté plus tard.',
        ];
    }

    public function passiveScan(ManagedNetwork $network): array
    {
        return $this->webSafeScan($network);
    }

    private function storeHosts(
        ManagedNetwork $network,
        Collection $hosts,
        ?string $socIp,
        string $scanMethod
    ): int {
        $createdOrUpdated = 0;

        foreach ($hosts as $host) {
            /*
             * Très important :
             * Pas de getent ici. Le scan web ne doit jamais résoudre les hostnames.
             * Si hostname est inconnu, on garde null.
             */
            $hostname = $host['hostname'] ?? null;

            $qualification = $this->hostQualificationService->qualify($host, $socIp);

            DiscoveredHost::updateOrCreate(
                [
                    'managed_network_id' => $network->id,
                    'ip_address' => $host['ip_address'],
                ],
                [
                    'mac_address' => $host['mac_address'] ?? null,
                    'hostname' => $hostname,
                    'host_role' => $qualification['host_role'],
                    'discovery_status' => 'detected',
                    'open_ports' => $host['open_ports'] ?? [],
                    'detected_services' => $host['detected_services'] ?? [],
                    'metadata' => [
                        'source' => $host['source'] ?? $scanMethod,
                        'interface' => $host['interface'] ?? $network->interface_name,
                        'qualification_confidence' => $qualification['confidence'],
                        'qualification_reasons' => $qualification['reasons'],
                        'scan_method' => $scanMethod,
                        'scanned_at' => now()->toDateTimeString(),
                        'web_safe' => true,
                    ],
                    'last_seen_at' => now(),
                ]
            );

            $createdOrUpdated++;
        }

        return $createdOrUpdated;
    }

    private function readIpNeighbors(): array
    {
        $result = Process::timeout(3)->run('ip neigh show');

        if (! $result->successful()) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($result->output()));
        $hosts = [];

        foreach ($lines as $line) {
            if (! $line) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            $ip = $parts[0] ?? null;

            if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }

            $mac = null;
            $dev = null;
            $state = end($parts) ?: null;

            foreach ($parts as $index => $part) {
                if ($part === 'lladdr') {
                    $mac = $parts[$index + 1] ?? null;
                }

                if ($part === 'dev') {
                    $dev = $parts[$index + 1] ?? null;
                }
            }

            $hosts[] = [
                'ip_address' => $ip,
                'mac_address' => $mac,
                'hostname' => null,
                'interface' => $dev,
                'state' => $state,
                'source' => 'ip_neigh',
                'open_ports' => [],
                'detected_services' => [],
            ];
        }

        return $hosts;
    }

    private function ipBelongsToCidr(string $ip, string $cidr): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        [$network, $prefix] = array_pad(explode('/', $cidr), 2, null);

        if (! $network || $prefix === null) {
            return false;
        }

        $prefix = (int) $prefix;

        if (! filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);

        if ($ipLong === false || $networkLong === false) {
            return false;
        }

        $mask = -1 << (32 - $prefix);

        return ($ipLong & $mask) === ($networkLong & $mask);
    }
}
