<?php

namespace App\Services;

use App\Models\DiscoveredHost;
use App\Models\ManagedNetwork;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class InfrastructureInventoryService
{
    public function detectLocalNetworks(): Collection
    {
        $interfaces = $this->localInterfaces();

        return collect($interfaces)
            ->map(fn (array $interface) => $this->upsertNetwork($interface))
            ->filter()
            ->values();
    }

    public function scanNetwork(ManagedNetwork $network): array
    {
        if (! $network->is_monitored) {
            return [
                'network_id' => $network->id,
                'cidr' => $network->cidr,
                'hosts_detected' => 0,
                'method' => 'blocked_retired_network',
                'note' => 'Réseau retiré de la surveillance.',
            ];
        }

        $hosts = collect();

        foreach ($this->hostsFromIpNeigh() as $host) {
            $hosts->push($host);
        }

        foreach ($this->localHostsForNetwork($network) as $host) {
            $hosts->push($host);
        }

        $hosts = $hosts
            ->filter(fn ($host) => ! empty($host['ip_address']))
            ->unique(fn ($host) => $host['ip_address'].'|'.($host['mac_address'] ?? ''))
            ->values();

        foreach ($hosts as $host) {
            $this->upsertHost($network, $host);
        }

        DB::table('managed_networks')
            ->where('id', $network->id)
            ->update([
                'last_scanned_at' => now(),
                'is_monitored' => true,
                'is_scannable' => true,
                'status' => 'approved',
                'retired_at' => null,
                'retired_reason' => null,
                'metadata' => json_encode(array_merge($this->asArray($network->metadata), [
                    'last_scan_at' => now()->toDateTimeString(),
                    'last_scan_method' => 'safe_ip_neigh_plus_local_host',
                    'last_scan_note' => 'Scan sécurisé : ip neigh + hôte local + passerelle. Aucun ping massif.',
                ]), JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        return [
            'network_id' => $network->id,
            'cidr' => $network->cidr,
            'hosts_detected' => $hosts->count(),
            'method' => 'safe_ip_neigh_plus_local_host',
            'note' => 'Les hôtes détectés ont été enregistrés ou réactivés dans discovered_hosts.',
        ];
    }

    public function upsertNetwork(array $data): ?ManagedNetwork
    {
        if (empty($data['cidr'])) {
            return null;
        }

        $network = ManagedNetwork::query()
            ->where('cidr', $data['cidr'])
            ->first();

        $payload = [
            'name' => $data['name'] ?? 'Réseau détecté '.($data['interface_name'] ?? $data['cidr']),
            'cidr' => $data['cidr'],
            'gateway_ip' => $data['gateway_ip'] ?? null,
            'interface_name' => $data['interface_name'] ?? null,
            'status' => 'detected',
            'is_scannable' => true,
            'is_monitored' => true,
            'retired_at' => null,
            'retired_reason' => null,
            'metadata' => json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        if ($network) {
            DB::table('managed_networks')
                ->where('id', $network->id)
                ->update($payload);

            return ManagedNetwork::find($network->id);
        }

        $payload['created_at'] = now();

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
            'ip_address' => $ip,
            'mac_address' => $host['mac_address'] ?? null,
            'hostname' => $host['hostname'] ?? null,
            'host_role' => $role,
            'discovery_status' => 'detected',
            'enrollment_status' => $existing?->enrollment_status ?? 'not_enrolled',
            'is_monitored' => true,
            'retired_at' => null,
            'retired_reason' => null,
            'last_seen_at' => now(),
            'metadata' => json_encode(array_merge($host['metadata'] ?? [], [
                'network_id' => $network->id,
                'network_cidr' => $network->cidr,
                'redetected_at' => now()->toDateTimeString(),
            ]), JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('discovered_hosts')
                ->where('id', $existing->id)
                ->update($payload);

            return DiscoveredHost::find($existing->id);
        }

        $payload['created_at'] = now();

        $id = DB::table('discovered_hosts')->insertGetId($payload);

        return DiscoveredHost::find($id);
    }

    private function localInterfaces(): array
    {
        $result = Process::timeout(3)->run('ip -o -f inet addr show scope global');

        if (! $result->successful()) {
            return [];
        }

        $gateway = $this->defaultGateway();
        $items = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            if (! preg_match('/^\d+:\s+([^\s]+)\s+inet\s+([0-9.]+)\/(\d+)/', $line, $matches)) {
                continue;
            }

            $interface = $matches[1];
            $ip = $matches[2];
            $prefix = (int) $matches[3];

            if ($prefix <= 0 || $prefix > 32) {
                continue;
            }

            $cidr = $this->networkCidr($ip, $prefix);

            $items[] = [
                'name' => 'Réseau détecté '.$interface,
                'cidr' => $cidr,
                'gateway_ip' => $gateway,
                'interface_name' => $interface,
                'metadata' => [
                    'ip' => $ip,
                    'prefix' => $prefix,
                    'source' => 'local_interface_detection',
                    'detected_at' => now()->toDateTimeString(),
                ],
            ];
        }

        return $items;
    }

    private function hostsFromIpNeigh(): array
    {
        $result = Process::timeout(3)->run('ip neigh show');

        if (! $result->successful()) {
            return [];
        }

        $hosts = [];

        foreach (explode("\n", trim($result->output())) as $line) {
            if (! preg_match('/^([0-9.]+)\s+dev\s+([^\s]+)(?:\s+lladdr\s+([0-9a-fA-F:]+))?/i', $line, $matches)) {
                continue;
            }

            $ip = $matches[1];
            $interface = $matches[2];
            $mac = $matches[3] ?? null;

            $hosts[] = [
                'ip_address' => $ip,
                'mac_address' => $mac,
                'hostname' => $this->safeHostname($ip),
                'host_role' => null,
                'metadata' => [
                    'source' => 'ip_neigh',
                    'interface' => $interface,
                    'raw' => $line,
                ],
            ];
        }

        return $hosts;
    }

    private function localHostsForNetwork(ManagedNetwork $network): array
    {
        $hosts = [];

        $metadata = $this->asArray($network->metadata);
        $localIp = $metadata['ip'] ?? null;

        if ($localIp) {
            $hosts[] = [
                'ip_address' => $localIp,
                'mac_address' => $metadata['mac_address'] ?? null,
                'hostname' => gethostname() ?: null,
                'host_role' => 'soc_server',
                'metadata' => [
                    'source' => 'local_soc_machine',
                ],
            ];
        }

        if ($network->gateway_ip) {
            $hosts[] = [
                'ip_address' => $network->gateway_ip,
                'mac_address' => null,
                'hostname' => '_gateway',
                'host_role' => 'gateway',
                'metadata' => [
                    'source' => 'default_gateway',
                ],
            ];
        }

        return $hosts;
    }

    private function defaultGateway(): ?string
    {
        $result = Process::timeout(2)->run('ip route show default');

        if (! $result->successful()) {
            return null;
        }

        if (preg_match('/default via ([0-9.]+)/', $result->output(), $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function safeHostname(string $ip): ?string
    {
        // Pas de DNS bloquant : on évite getent hosts qui a déjà causé des timeouts.
        if ($ip === $this->defaultGateway()) {
            return '_gateway';
        }

        return null;
    }

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
        $long = ip2long($ip);
        $mask = -1 << (32 - $prefix);
        $network = $long & $mask;

        return long2ip($network).'/'.$prefix;
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
