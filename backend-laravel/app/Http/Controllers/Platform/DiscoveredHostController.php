<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\DiscoveredHost;
use App\Services\HostEnrollmentService;
use App\Services\InfrastructureInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscoveredHostController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'monitored');
        $search = trim($request->query('search', ''));

        $query = DiscoveredHost::query()
            ->with(['managedNetwork', 'agent'])
            ->latest('last_seen_at')
            ->latest();

        if ($status === 'monitored') {
            $query->where('is_monitored', true);
        } elseif ($status === 'retired') {
            $query->where('is_monitored', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('hostname',   'like', "%{$search}%")
                  ->orWhere('ip_address',  'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }

        // ── URL SOC par réseau ───────────────────────────────────────────────
        // Permet d'afficher la bonne commande d'enrôlement directement sur la carte.
        $configuredUrl = rtrim(config('app.soc_url', config('app.url')), '/');
        $scheme = parse_url($configuredUrl, PHP_URL_SCHEME) ?? 'http';
        $port   = parse_url($configuredUrl, PHP_URL_PORT);

        $networkSocUrls = \App\Models\ManagedNetwork::all()
            ->mapWithKeys(function ($network) use ($scheme, $port, $configuredUrl) {
                $ip  = data_get($network->metadata, 'ip');
                $url = $ip
                    ? $scheme.'://'.$ip.($port ? ':'.$port : '')
                    : $configuredUrl;
                return [$network->id => $url];
            })
            ->toArray();

        $filterCounts = [
            'monitored' => DiscoveredHost::where('is_monitored', true)->count(),
            'retired'   => DiscoveredHost::where('is_monitored', false)->count(),
            'all'       => DiscoveredHost::count(),
        ];

        return view('platform.discovered-hosts.index', [
            'hosts'          => $query->paginate(30)->withQueryString(),
            'activeStatus'   => $status,
            'search'         => $search,
            'filterCounts'   => $filterCounts,
            'networkSocUrls' => $networkSocUrls,
            'fallbackSocUrl' => $configuredUrl,
            'stats'          => [
                'total'    => DiscoveredHost::count(),
                'monitored'=> DiscoveredHost::where('is_monitored', true)->count(),
                'enrolled' => DiscoveredHost::where('enrollment_status', 'enrolled')->count(),
                'pending'  => DiscoveredHost::where('enrollment_status', 'pending')->count(),
                'retired'  => DiscoveredHost::where('is_monitored', false)->count(),
            ],
        ]);
    }

    public function validateHost(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['discovery_status' => 'approved', 'updated_at' => now()]);

        return back()->with('success', 'Hôte validé.');
    }

    public function reset(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['discovery_status' => 'detected', 'updated_at' => now()]);

        return back()->with('success', 'Statut de l\'hôte réinitialisé.');
    }

    public function markClient(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['host_role' => 'client', 'updated_at' => now()]);

        return back()->with('success', 'Hôte marqué comme client.');
    }

    public function markFileServer(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['host_role' => 'file_server', 'updated_at' => now()]);

        return back()->with('success', 'Hôte marqué comme serveur de fichiers.');
    }

    public function markSocServer(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['host_role' => 'soc_server', 'updated_at' => now()]);

        return back()->with('success', 'Hôte marqué comme serveur SOC.');
    }

    public function markAttackerDemo(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['host_role' => 'attacker_demo', 'updated_at' => now()]);

        return back()->with('success', 'Hôte marqué comme attaquant démo.');
    }

    public function markMobile(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update(['host_role' => 'mobile_device', 'updated_at' => now()]);

        return back()->with('success', 'Hôte marqué comme mobile / tablette.');
    }

    public function enroll(DiscoveredHost $discoveredHost, HostEnrollmentService $enrollment): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update([
                'is_monitored' => true,
                'discovery_status' => 'detected',
                'retired_at' => null,
                'retired_reason' => null,
                'updated_at' => now(),
            ]);

        $agent = $enrollment->preEnroll($discoveredHost->refresh());

        return redirect()
            ->route('platform.agents.show', $agent)
            ->with('success', 'Hôte pré-enrôlé. Installe l’agent sur cette machine pour finaliser l’enrôlement.');
    }

    public function retire(Request $request, DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update([
                'is_monitored' => false,
                'discovery_status' => 'retired',
                'retired_at' => now(),
                'retired_reason' => $request->input('retired_reason', 'Hôte retiré de la surveillance depuis la console SOC.'),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Hôte retiré de la surveillance.');
    }

    public function restore(DiscoveredHost $discoveredHost): RedirectResponse
    {
        DB::table('discovered_hosts')
            ->where('id', $discoveredHost->id)
            ->update([
                'is_monitored' => true,
                'discovery_status' => 'detected',
                'retired_at' => null,
                'retired_reason' => null,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Hôte réactivé.');
    }

    /**
     * Supprime définitivement tous les hôtes retirés (is_monitored = false).
     * Utile pour nettoyer les fantômes accumulés lors des scans précédents.
     */
    public function purgeRetired(InfrastructureInventoryService $inventory): RedirectResponse
    {
        $deleted = $inventory->purgeRetiredDiscoveredHosts();

        return redirect()
            ->route('platform.discovered-hosts.index')
            ->with('success', "{$deleted} hôte(s) retiré(s) supprimé(s) définitivement.");
    }
}
