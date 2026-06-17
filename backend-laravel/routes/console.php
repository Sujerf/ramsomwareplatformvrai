<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ransomshield:reset-defaults', function () {
    $defaults = app(\App\Services\RansomShieldDefaultConfigurationService::class);

    $results = $defaults->resetAll();

    foreach ($results as $key => $count) {
        $this->info($key.' : '.$count.' élément(s) synchronisé(s).');
    }

    $this->info('Configuration RansomShield réinitialisée avec succès.');

    return 0;
})->purpose('Réinitialise les configurations RansomShield vers les valeurs par défaut.');


/**
 * Scan actif de tous les réseaux surveillés.
 *
 * Usage :
 *   php artisan ransomshield:scan-networks              → scanne tous les réseaux surveillés
 *   php artisan ransomshield:scan-networks --cidr=10.20.0.0/24  → scanne un réseau spécifique
 *
 * Planifié automatiquement toutes les 5 minutes via le scheduler Laravel.
 */
Artisan::command('ransomshield:scan-networks {--cidr= : Scanner uniquement ce CIDR}', function () {
    $inventory = app(\App\Services\InfrastructureInventoryService::class);

    $query = \App\Models\ManagedNetwork::where('is_monitored', true)
        ->where('is_scannable', true);

    $cidr = $this->option('cidr');

    if ($cidr) {
        $query->where('cidr', $cidr);
    }

    $networks = $query->get();

    if ($networks->isEmpty()) {
        $this->warn('Aucun réseau à scanner' . ($cidr ? " pour le CIDR {$cidr}" : '') . '.');
        return 0;
    }

    $this->info("Scan de {$networks->count()} réseau(x) surveillé(s)...");
    $this->newLine();

    $totalHosts   = 0;
    $totalRetired = 0;

    foreach ($networks as $network) {
        $this->line("  → Scan de <comment>{$network->cidr}</comment> ({$network->name})...");
        $start  = microtime(true);
        $result = $inventory->scanNetwork($network);
        $ms     = round((microtime(true) - $start) * 1000);

        $hosts   = $result['hosts_detected'] ?? 0;
        $retired = $result['hosts_retired'] ?? 0;
        $method  = $result['method'] ?? '?';
        $ips     = implode(', ', array_slice($result['discovered_ips'] ?? [], 0, 8));
        $more    = count($result['discovered_ips'] ?? []) > 8
                    ? ' (+' . (count($result['discovered_ips']) - 8) . ')' : '';

        $this->info("     ✓ {$hosts} hôte(s) détecté(s) — {$retired} retiré(s) — méthode: {$method} — {$ms}ms");

        if ($ips) {
            $this->line("       IPs: <fg=cyan>{$ips}{$more}</>");
        }

        $totalHosts   += $hosts;
        $totalRetired += $retired;
    }

    $this->newLine();
    $this->info("Terminé — {$totalHosts} hôte(s) au total, {$totalRetired} retiré(s).");

    return 0;
})->purpose('Scanne activement tous les réseaux surveillés et met à jour les hôtes découverts.');


/**
 * Surveillance de la santé des agents.
 *
 * Détecte les agents silencieux (plus de heartbeat depuis N secondes) et génère
 * une alerte haute. Gère aussi la récupération automatique quand un agent repulse.
 *
 * Usage :
 *   php artisan ransomshield:check-offline-agents
 *
 * Planifié automatiquement toutes les 5 minutes via le scheduler Laravel.
 */
Artisan::command('ransomshield:check-offline-agents', function () {
    $threshold = (int) (\App\Models\SystemSetting::getCached('agent_offline_threshold_seconds') ?? 300);
    $cutoff    = now()->subSeconds($threshold);

    // ── Récupération : agents offline revenus en ligne ────────────────────────
    $recovered = \App\Models\Agent::where('status', 'offline')
        ->whereNotNull('last_seen_at')
        ->where('last_seen_at', '>=', $cutoff)
        ->get();

    foreach ($recovered as $agent) {
        $agent->update([
            'status'   => 'active',
            'metadata' => array_merge($agent->metadata ?? [], [
                'recovered_at' => now()->toDateTimeString(),
            ]),
        ]);
        $this->line("  ↑ <info>{$agent->agent_name}</info> revenu en ligne.");
    }

    // ── Détection : agents actifs silencieux depuis trop longtemps ────────────
    $gone = \App\Models\Agent::whereIn('status', ['active', 'compromised'])
        ->whereNotNull('last_seen_at')
        ->where('last_seen_at', '<', $cutoff)
        ->get();

    foreach ($gone as $agent) {
        // Transition active → offline (la clé anti-doublon : on n'alerte que lors
        // de ce changement de statut, pas à chaque run tant qu'il reste offline)
        $agent->update(['status' => 'offline']);

        $silenceMin = (int) $agent->last_seen_at->diffInMinutes(now());
        $agentIp    = $agent->ip_address ?? 'IP inconnue';
        $lastSeen   = $agent->last_seen_at->format('d/m/Y H:i:s');

        $alert = \App\Models\Alert::create([
            'alert_uuid'  => (string) \Illuminate\Support\Str::uuid(),
            'agent_id'    => $agent->id,
            'incident_id' => null,
            'event_id'    => null,
            'title'       => "Agent hors-ligne : {$agent->agent_name}",
            'message'     => "L'agent {$agent->agent_name} ({$agentIp}) n'a pas envoyé de heartbeat depuis {$silenceMin} min. Dernier contact : {$lastSeen}.",
            'status'      => 'open',
            'risk_level'  => 'high',
            'score'       => 75,
            'detected_at' => now(),
            'metadata'    => [
                'alert_type'        => 'agent_offline',
                'last_seen_at'      => $agent->last_seen_at->toDateTimeString(),
                'silence_minutes'   => $silenceMin,
                'threshold_seconds' => $threshold,
                'timeline_message'  => 'Agent détecté hors-ligne — heartbeat manquant.',
            ],
        ]);

        app(\App\Services\NotificationService::class)->notifyAlert($alert);

        $this->warn("  ✗ {$agent->agent_name} hors-ligne depuis {$silenceMin} min.");
    }

    if ($recovered->isEmpty() && $gone->isEmpty()) {
        $this->info('  ✓ Tous les agents actifs sont en ligne.');
    }

    return 0;
})->purpose('Vérifie les agents silencieux et génère des alertes hautes pour ceux hors-ligne.');


Artisan::command('ransomshield:reactivate-infrastructure', function () {
    $networks = \App\Models\ManagedNetwork::query()
        ->where('status', 'retired')
        ->orWhere('is_monitored', false)
        ->get();

    foreach ($networks as $network) {
        \Illuminate\Support\Facades\DB::table('managed_networks')
            ->where('id', $network->id)
            ->update([
                'status' => 'detected',
                'is_monitored' => true,
                'is_scannable' => true,
                'retired_at' => null,
                'retired_reason' => null,
                'updated_at' => now(),
            ]);
    }

    $hosts = \App\Models\DiscoveredHost::query()
        ->where('discovery_status', 'retired')
        ->orWhere('is_monitored', false)
        ->get();

    foreach ($hosts as $host) {
        \Illuminate\Support\Facades\DB::table('discovered_hosts')
            ->where('id', $host->id)
            ->update([
                'discovery_status' => 'detected',
                'is_monitored' => true,
                'retired_at' => null,
                'retired_reason' => null,
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);
    }

    $this->info($networks->count().' réseau(x) réactivé(s).');
    $this->info($hosts->count().' hôte(s) réactivé(s).');

    return 0;
})->purpose('Réactive les réseaux et hôtes retirés lorsqu’ils doivent être remis sous surveillance.');

