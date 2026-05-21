<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ManagedNetwork;
use App\Services\InfrastructureInventoryService;
use Illuminate\Contracts\View\View;
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
            'networks' => $query->paginate(20)->withQueryString(),
            'activeStatus' => $status,
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
            'status'      => 'detected',
            'is_scannable' => true,
            'is_monitored' => true,
        ]));

        return back()->with('success', 'Réseau ajouté.');
    }

    public function ignore(ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::table('managed_networks')
            ->where('id', $managedNetwork->id)
            ->update([
                'is_monitored'  => false,
                'status'        => 'retired',
                'is_scannable'  => false,
                'retired_at'    => now(),
                'retired_reason' => 'Réseau ignoré depuis la console SOC.',
                'updated_at'    => now(),
            ]);

        return back()->with('success', 'Réseau ignoré.');
    }

    public function detect(InfrastructureInventoryService $inventory): RedirectResponse
    {
        $networks = $inventory->detectLocalNetworks();

        foreach ($networks as $network) {
            $inventory->scanNetwork($network);
        }

        return back()->with('success', $networks->count().' réseau(x) détecté(s). Les hôtes visibles ont été enregistrés.');
    }

    public function scan(ManagedNetwork $managedNetwork, InfrastructureInventoryService $inventory): RedirectResponse
    {
        $result = $inventory->scanNetwork($managedNetwork);

        return back()->with('success', 'Scan terminé : '.($result['hosts_detected'] ?? 0).' hôte(s) enregistré(s).');
    }

    public function approve(ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::table('managed_networks')
            ->where('id', $managedNetwork->id)
            ->update([
                'status' => 'approved',
                'is_monitored' => true,
                'is_scannable' => true,
                'retired_at' => null,
                'retired_reason' => null,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Réseau approuvé et surveillé.');
    }

    public function retire(Request $request, ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::transaction(function () use ($request, $managedNetwork) {
            DB::table('managed_networks')
                ->where('id', $managedNetwork->id)
                ->update([
                    'is_monitored' => false,
                    'status' => 'retired',
                    'retired_at' => now(),
                    'retired_reason' => $request->input('retired_reason', 'Retiré de la surveillance depuis la console SOC.'),
                    'is_scannable' => false,
                    'updated_at' => now(),
                ]);

            DB::table('discovered_hosts')
                ->where('managed_network_id', $managedNetwork->id)
                ->update([
                    'is_monitored' => false,
                    'discovery_status' => 'retired',
                    'retired_at' => now(),
                    'retired_reason' => 'Retiré car le réseau parent a été retiré de la surveillance.',
                    'updated_at' => now(),
                ]);
        });

        return back()->with('success', 'Réseau et hôtes liés retirés de la surveillance.');
    }

    public function restore(ManagedNetwork $managedNetwork): RedirectResponse
    {
        DB::table('managed_networks')
            ->where('id', $managedNetwork->id)
            ->update([
                'is_monitored' => true,
                'status' => 'approved',
                'retired_at' => null,
                'retired_reason' => null,
                'is_scannable' => true,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Réseau réactivé.');
    }
}
