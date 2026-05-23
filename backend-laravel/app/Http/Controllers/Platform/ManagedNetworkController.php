<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ManagedNetwork;
use App\Services\InfrastructureInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagedNetworkController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'monitored');

        $query = ManagedNetwork::query()
            ->withCount('discoveredHosts')
            ->latest();

        if ($status === 'monitored') {
            $query->where('is_monitored', true);
        } elseif ($status === 'retired') {
            $query->where('is_monitored', false);
        }

        return view('platform.networks.index', [
            'networks'     => $query->paginate(20)->withQueryString(),
            'activeStatus' => $status,
            'stats'        => [
                'total'     => ManagedNetwork::count(),
                'monitored' => ManagedNetwork::where('is_monitored', true)->count(),
                'retired'   => ManagedNetwork::where('is_monitored', false)->count(),
                'approved'  => ManagedNetwork::where('status', 'approved')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'cidr'           => ['required', 'string', 'max:50'],
            'gateway_ip'     => ['nullable', 'ip'],
            'interface_name' => ['nullable', 'string', 'max:100'],
        ]);

        ManagedNetwork::create(array_merge($validated, [
            'status'       => 'detected',
            'is_scannable' => true,
            'is_monitored' => true,
        ]));

        return back()->with('success', 'Réseau ajouté.');
    }

    /**
     * Détecte les réseaux locaux et scanne chacun.
     * Retourne JSON si appelé via AJAX (Accept: application/json).
     */
    public function detect(Request $request, InfrastructureInventoryService $inventory): RedirectResponse|JsonResponse
    {
        $networks = $inventory->detectLocalNetworks();

        $results = [];
        foreach ($networks as $network) {
            $results[] = $inventory->scanNetwork($network);
        }

        if ($request->expectsJson()) {
            $totalHosts = collect($results)->sum('hosts_detected');

            return response()->json([
                'success'       => true,
                'networks_found' => $networks->count(),
                'hosts_detected' => $totalHosts,
                'results'       => $results,
                'message'       => $networks->count().' réseau(x) détecté(s) — '.$totalHosts.' hôte(s) trouvé(s).',
            ]);
        }

        return back()->with('success', $networks->count().' réseau(x) détecté(s). Les hôtes visibles ont été enregistrés.');
    }

    /**
     * Scanne un réseau spécifique.
     * Retourne JSON si appelé via AJAX (Accept: application/json).
     */
    public function scan(Request $request, ManagedNetwork $managedNetwork, InfrastructureInventoryService $inventory): RedirectResponse|JsonResponse
    {
        $result = $inventory->scanNetwork($managedNetwork);

        if ($request->expectsJson()) {
            return response()->json([
                'success'        => true,
                'network_id'     => $managedNetwork->id,
                'hosts_detected' => $result['hosts_detected'] ?? 0,
                'hosts_retired'  => $result['hosts_retired'] ?? 0,
                'method'         => $result['method'] ?? 'unknown',
                'note'           => $result['note'] ?? '',
                'discovered_ips' => $result['discovered_ips'] ?? [],
                'last_scanned_at'=> $result['last_scanned_at'] ?? now()->toDateTimeString(),
                'message'        => 'Scan terminé : '.($result['hosts_detected'] ?? 0).' hôte(s) détecté(s).',
            ]);
        }

        return back()->with('success', 'Scan terminé : '.($result['hosts_detected'] ?? 0).' hôte(s) enregistré(s).');
    }

    public function approve(ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::table('managed_networks')
            ->where('id', $managedNetwork->id)
            ->update([
                'status'        => 'approved',
                'is_monitored'  => true,
                'is_scannable'  => true,
                'retired_at'    => null,
                'retired_reason'=> null,
                'updated_at'    => now(),
            ]);

        return back()->with('success', 'Réseau approuvé et surveillé.');
    }

    public function retire(Request $request, ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::transaction(function () use ($request, $managedNetwork) {
            DB::table('managed_networks')
                ->where('id', $managedNetwork->id)
                ->update([
                    'is_monitored'  => false,
                    'status'        => 'retired',
                    'retired_at'    => now(),
                    'retired_reason'=> $request->input('retired_reason', 'Retiré de la surveillance depuis la console SOC.'),
                    'is_scannable'  => false,
                    'updated_at'    => now(),
                ]);

            DB::table('discovered_hosts')
                ->where('managed_network_id', $managedNetwork->id)
                ->update([
                    'is_monitored'     => false,
                    'discovery_status' => 'retired',
                    'retired_at'       => now(),
                    'retired_reason'   => 'Retiré car le réseau parent a été retiré de la surveillance.',
                    'updated_at'       => now(),
                ]);
        });

        return back()->with('success', 'Réseau et hôtes liés retirés de la surveillance.');
    }

    public function restore(ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::table('managed_networks')
            ->where('id', $managedNetwork->id)
            ->update([
                'is_monitored'  => true,
                'status'        => 'approved',
                'retired_at'    => null,
                'retired_reason'=> null,
                'is_scannable'  => true,
                'updated_at'    => now(),
            ]);

        return back()->with('success', 'Réseau réactivé.');
    }
}
