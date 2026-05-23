<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\InfrastructureInventoryService;
use App\Services\LocalHostDetectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LocalHostController extends Controller
{
    public function __construct(
        private readonly LocalHostDetectionService $localHostDetectionService,
        private readonly InfrastructureInventoryService $inventory,
    ) {
    }

    public function index(): View
    {
        $localHost = session('local_host_detection') ?? $this->localHostDetectionService->detect();

        return view('platform.local-host.index', [
            'hostname' => $localHost['hostname'] ?? null,
            'serverIp' => $localHost['primary_ip'] ?? null,
            'phpOs' => $localHost['os'] ?? PHP_OS_FAMILY,
            'localHost' => $localHost,
        ]);
    }

    public function detect(): RedirectResponse
    {
        $localHost = $this->localHostDetectionService->detect();

        session()->flash('local_host_detection', $localHost);

        return back()->with('success', 'Machine SOC locale détectée avec succès.');
    }

    /**
     * Détecte les réseaux locaux, les persiste dans managed_networks,
     * scanne chaque réseau pour découvrir les hôtes, puis redirige vers
     * la page Réseaux surveillés.
     */
    public function pushToNetworks(): RedirectResponse
    {
        $networks = $this->inventory->detectLocalNetworks();

        $hostsTotal = 0;

        foreach ($networks as $network) {
            $result      = $this->inventory->scanNetwork($network);
            $hostsTotal += $result['hosts_detected'] ?? 0;
        }

        $networkCount = $networks->count();

        if ($networkCount === 0) {
            return redirect()
                ->route('platform.networks.index')
                ->with('error', 'Aucun réseau détectable sur cette machine. Vérifie les interfaces réseau.');
        }

        return redirect()
            ->route('platform.networks.index')
            ->with('success', "{$networkCount} réseau(x) ajouté(s) à la surveillance · {$hostsTotal} hôte(s) découvert(s).");
    }
}
