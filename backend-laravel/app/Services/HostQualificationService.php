<?php

namespace App\Services;

class HostQualificationService
{
    public function qualify(array $host, ?string $socIp = null): array
    {
        $ip = $host['ip_address'] ?? null;
        $hostname = strtolower((string) ($host['hostname'] ?? ''));
        $ports = $host['open_ports'] ?? [];

        $role = 'unknown';
        $confidence = 30;
        $reasons = [];

        if ($socIp && $ip === $socIp) {
            $role = 'soc_server';
            $confidence = 100;
            $reasons[] = 'Adresse IP identique à celle du serveur SOC.';
        } elseif (str_contains($hostname, 'kali') || str_contains($hostname, 'attacker') || str_contains($hostname, 'attack')) {
            $role = 'attacker_demo';
            $confidence = 85;
            $reasons[] = 'Hostname associé à une machine attaquante ou de démonstration.';
        } elseif (str_contains($hostname, 'server') || str_contains($hostname, 'srv') || in_array(445, $ports, true) || in_array(139, $ports, true)) {
            $role = 'file_server';
            $confidence = 75;
            $reasons[] = 'Indice serveur ou partage réseau détecté.';
        } elseif ($hostname !== '') {
            $role = 'client';
            $confidence = 55;
            $reasons[] = 'Hostname détecté, rôle client probable.';
        } else {
            $role = 'unknown';
            $confidence = 30;
            $reasons[] = 'Informations insuffisantes pour qualifier automatiquement.';
        }

        return [
            'host_role' => $role,
            'confidence' => $confidence,
            'reasons' => $reasons,
        ];
    }
}
