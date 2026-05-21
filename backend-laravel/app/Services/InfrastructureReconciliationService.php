<?php

namespace App\Services;

use App\Models\DiscoveredHost;
use App\Models\ManagedNetwork;
use Illuminate\Support\Facades\DB;

class InfrastructureReconciliationService
{
    public function reactivateNetworkByCidr(string $cidr, array $data = []): ManagedNetwork
    {
        $network = ManagedNetwork::query()
            ->where('cidr', $cidr)
            ->first();

        $payload = [
            'name' => $data['name'] ?? 'Réseau détecté '.$cidr,
            'cidr' => $cidr,
            'gateway_ip' => $data['gateway_ip'] ?? null,
            'interface_name' => $data['interface_name'] ?? null,
            'status' => 'detected',
            'is_scannable' => true,
            'is_monitored' => true,
            'retired_at' => null,
            'retired_reason' => null,
            'metadata' => json_encode(array_merge($data['metadata'] ?? [], [
                'redetected_at' => now()->toDateTimeString(),
                'reactivated_by_detection' => true,
            ]), JSON_UNESCAPED_UNICODE),
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

    public function reactivateHost(ManagedNetwork $network, array $data): DiscoveredHost
    {
        $ip = $data['ip_address'] ?? null;

        if (! $ip) {
            throw new \InvalidArgumentException('ip_address obligatoire pour réactiver un hôte.');
        }

        $query = DiscoveredHost::query()
            ->where('managed_network_id', $network->id)
            ->where('ip_address', $ip);

        if (! empty($data['mac_address'])) {
            $query->orWhere(function ($q) use ($network, $data) {
                $q->where('managed_network_id', $network->id)
                    ->where('mac_address', $data['mac_address']);
            });
        }

        $host = $query->first();

        $payload = [
            'managed_network_id' => $network->id,
            'ip_address' => $ip,
            'mac_address' => $data['mac_address'] ?? null,
            'hostname' => $data['hostname'] ?? null,
            'host_role' => $data['host_role'] ?? 'client',
            'discovery_status' => 'detected',
            'is_monitored' => true,
            'retired_at' => null,
            'retired_reason' => null,
            'last_seen_at' => now(),
            'metadata' => json_encode(array_merge($data['metadata'] ?? [], [
                'redetected_at' => now()->toDateTimeString(),
                'reactivated_by_scan' => true,
            ]), JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        if ($host) {
            DB::table('discovered_hosts')
                ->where('id', $host->id)
                ->update($payload);

            return DiscoveredHost::find($host->id);
        }

        $payload['created_at'] = now();

        $id = DB::table('discovered_hosts')->insertGetId($payload);

        return DiscoveredHost::find($id);
    }
}
