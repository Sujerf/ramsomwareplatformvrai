<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class LocalHostDetectionService
{
    public function detect(): array
    {
        $hostname = gethostname() ?: 'unknown-host';
        $os = PHP_OS_FAMILY;
        $interfaces = $this->detectInterfaces();
        $routes = $this->detectRoutes();

        $primaryInterface = collect($interfaces)
            ->first(fn (array $interface) => $interface['is_active'] && count($interface['ipv4_addresses']) > 0);

        return [
            'hostname' => $hostname,
            'os' => $os,
            'primary_ip' => $primaryInterface['ipv4_addresses'][0]['ip'] ?? null,
            'primary_interface' => $primaryInterface['name'] ?? null,
            'primary_mac' => $primaryInterface['mac_address'] ?? null,
            'interfaces' => $interfaces,
            'routes' => $routes,
            'detected_at' => now()->toDateTimeString(),
        ];
    }

    public function detectInterfaces(): array
    {
        $result = Process::run('ip -j addr');

        if (! $result->successful()) {
            return [];
        }

        $items = json_decode($result->output(), true);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function (array $item) {
                $ipv4Addresses = collect($item['addr_info'] ?? [])
                    ->filter(fn (array $addr) => ($addr['family'] ?? null) === 'inet')
                    ->map(function (array $addr) {
                        $ip = $addr['local'] ?? null;
                        $prefix = (int) ($addr['prefixlen'] ?? 24);

                        return [
                            'ip' => $ip,
                            'prefix' => $prefix,
                            'cidr' => $ip ? $this->ipv4NetworkCidr($ip, $prefix) : null,
                            'scope' => $addr['scope'] ?? null,
                        ];
                    })
                    ->filter(fn (array $addr) => $addr['ip'] !== null)
                    ->values()
                    ->all();

                return [
                    'name' => $item['ifname'] ?? null,
                    'index' => $item['ifindex'] ?? null,
                    'mac_address' => $item['address'] ?? null,
                    'state' => $item['operstate'] ?? 'UNKNOWN',
                    'is_active' => ($item['operstate'] ?? null) === 'UP',
                    'ipv4_addresses' => $ipv4Addresses,
                ];
            })
            ->filter(fn (array $interface) => $interface['name'] !== 'lo')
            ->values()
            ->all();
    }

    public function detectRoutes(): array
    {
        $result = Process::run('ip -j route');

        if (! $result->successful()) {
            return [];
        }

        $items = json_decode($result->output(), true);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (array $route) => [
                'destination' => $route['dst'] ?? 'default',
                'gateway' => $route['gateway'] ?? null,
                'interface' => $route['dev'] ?? null,
                'preferred_source' => $route['prefsrc'] ?? null,
                'protocol' => $route['protocol'] ?? null,
            ])
            ->values()
            ->all();
    }

    public function detectedNetworks(): array
    {
        $routes = collect($this->detectRoutes());
        $interfaces = collect($this->detectInterfaces());

        $networksFromInterfaces = $interfaces
            ->flatMap(function (array $interface) use ($routes) {
                return collect($interface['ipv4_addresses'])
                    ->map(function (array $address) use ($interface, $routes) {
                        $gateway = $routes
                            ->first(fn (array $route) => ($route['interface'] ?? null) === $interface['name'] && ($route['destination'] ?? null) === 'default');

                        return [
                            'name' => 'Réseau détecté ' . ($interface['name'] ?? 'interface'),
                            'cidr' => $address['cidr'],
                            'gateway_ip' => $gateway['gateway'] ?? null,
                            'interface_name' => $interface['name'],
                            'status' => 'detected',
                            'is_scannable' => true,
                            'metadata' => [
                                'source' => 'local_host_detection',
                                'ip' => $address['ip'],
                                'prefix' => $address['prefix'],
                                'mac_address' => $interface['mac_address'] ?? null,
                                'interface_state' => $interface['state'] ?? null,
                                'detected_at' => now()->toDateTimeString(),
                            ],
                        ];
                    });
            })
            ->filter(fn (array $network) => ! empty($network['cidr']))
            ->unique('cidr')
            ->values()
            ->all();

        return $networksFromInterfaces;
    }

    private function ipv4NetworkCidr(string $ip, int $prefix): ?string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        if ($prefix < 0 || $prefix > 32) {
            return null;
        }

        $ipLong = ip2long($ip);

        if ($ipLong === false) {
            return null;
        }

        $mask = -1 << (32 - $prefix);
        $network = $ipLong & $mask;

        return long2ip($network) . '/' . $prefix;
    }
}
