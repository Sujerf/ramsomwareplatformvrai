<?php

namespace App\Services;

use App\Models\ManagedNetwork;
use Illuminate\Support\Collection;

class NetworkDiscoveryService
{
    public function __construct(
        private readonly LocalHostDetectionService $localHostDetectionService,
    ) {
    }

    public function detectAndStoreNetworks(): Collection
    {
        $detectedNetworks = collect($this->localHostDetectionService->detectedNetworks());

        return $detectedNetworks->map(function (array $network) {
            return ManagedNetwork::updateOrCreate(
                ['cidr' => $network['cidr']],
                [
                    'name' => $network['name'],
                    'gateway_ip' => $network['gateway_ip'],
                    'interface_name' => $network['interface_name'],
                    'status' => 'detected',
                    'is_scannable' => true,
                    'metadata' => $network['metadata'],
                ]
            );
        });
    }

    public function localHostInfo(): array
    {
        return $this->localHostDetectionService->detect();
    }
}
