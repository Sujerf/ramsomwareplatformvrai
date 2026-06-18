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


Artisan::command('ransomshield:executive-report {--period=auto}', function () {
    $setting = fn(string $k, mixed $d = null) => \App\Models\SystemSetting::getCached($k) ?? $d;

    if (! in_array((string) $setting('report_executive_enabled', '0'), ['1', 'true'], true)) {
        $this->info('Rapport exécutif désactivé (report_executive_enabled=0). Rien à faire.');
        return 0;
    }

    $recipient = trim((string) $setting('report_executive_recipient', ''));
    if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $this->error('Aucun destinataire valide configuré (report_executive_recipient).');
        return 1;
    }

    $frequency = $setting('report_executive_frequency', 'weekly');
    $period    = $this->option('period') === 'auto' ? $frequency : $this->option('period');

    if ($period === 'monthly') {
        $start = now()->subDays(29)->startOfDay();
        $end   = now()->endOfDay();
        $label = now()->subDays(14)->format('F Y');
    } else {
        $start = now()->subDays(6)->startOfDay();
        $end   = now()->endOfDay();
        $label = 'Semaine du '.$start->format('d/m').' au '.$end->format('d/m/Y');
    }

    $this->info("Génération du rapport « {$label }» ...");

    // ── Collecte des données ───────────────────────────────────────────────
    $incidents = \App\Models\Incident::whereBetween('detected_at', [$start, $end]);
    $alerts    = \App\Models\Alert::whereBetween('detected_at', [$start, $end]);
    $actions   = \App\Models\ProtectionAction::whereBetween('created_at', [$start, $end]);
    $auditLogs = \App\Models\AuditLog::whereBetween('created_at', [$start, $end]);

    $mttr = \App\Models\Incident::whereBetween('detected_at', [$start, $end])
        ->whereNotNull('resolved_at')
        ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, detected_at, resolved_at)) as avg_hours')
        ->value('avg_hours');

    $offlineAgents = \App\Models\Agent::whereIn('status', ['offline', 'compromised'])->get()
        ->map(fn($a) => [
            'name'      => $a->agent_name,
            'ip'        => $a->ip_address ?? '—',
            'last_seen' => $a->last_seen_at?->format('d/m/Y H:i') ?? 'jamais',
        ])->take(10)->values()->toArray();

    $data = [
        'incidents' => [
            'total'         => (clone $incidents)->count(),
            'critical'      => (clone $incidents)->where('risk_level', 'critical')->count(),
            'high'          => (clone $incidents)->where('risk_level', 'high')->count(),
            'suspect'       => (clone $incidents)->where('risk_level', 'suspect')->count(),
            'normal'        => (clone $incidents)->where('risk_level', 'normal')->count(),
            'resolved'      => (clone $incidents)->where('status', 'resolved')->count(),
            'open'          => (clone $incidents)->whereIn('status', ['open','investigating','under_review','reopened'])->count(),
            'false_positive'=> (clone $incidents)->where('status', 'false_positive')->count(),
            'mttr_hours'    => $mttr ? round($mttr, 1) : null,
            'recent'        => (clone $incidents)->with('agent')->latest('detected_at')->limit(8)->get()
                ->map(fn($i) => ['title' => $i->title, 'risk_level' => $i->risk_level, 'status' => $i->status, 'detected_at' => $i->detected_at?->format('d/m/Y H:i')])
                ->toArray(),
        ],
        'alerts' => [
            'total'         => (clone $alerts)->count(),
            'critical'      => (clone $alerts)->where('risk_level', 'critical')->count(),
            'high'          => (clone $alerts)->where('risk_level', 'high')->count(),
            'suspect'       => (clone $alerts)->where('risk_level', 'suspect')->count(),
            'normal'        => (clone $alerts)->where('risk_level', 'normal')->count(),
            'resolved'      => (clone $alerts)->where('status', 'resolved')->count(),
            'active'        => (clone $alerts)->whereIn('status', ['open','acknowledged','investigating'])->count(),
            'false_positive'=> (clone $alerts)->where('status', 'false_positive')->count(),
        ],
        'agents' => [
            'total'        => \App\Models\Agent::count(),
            'online'       => \App\Models\Agent::where('status', 'active')->count(),
            'offline'      => \App\Models\Agent::where('status', 'offline')->count(),
            'compromised'  => \App\Models\Agent::where('status', 'compromised')->count(),
            'risk_critical'=> \App\Models\Agent::where('risk_level', 'critical')->count(),
            'risk_high'    => \App\Models\Agent::where('risk_level', 'high')->count(),
            'risk_normal'  => \App\Models\Agent::whereIn('risk_level', ['normal','suspect'])->count(),
            'offline_list' => $offlineAgents,
        ],
        'actions' => [
            'total'    => (clone $actions)->count(),
            'approved' => (clone $actions)->where('approval_status', 'approved')->count(),
            'executed' => (clone $actions)->where('execution_status', 'executed')->count(),
            'rejected' => (clone $actions)->where('approval_status', 'rejected')->count(),
            'pending'  => (clone $actions)->where('approval_status', 'pending')->count(),
        ],
        'audit' => [
            'total'           => (clone $auditLogs)->count(),
            'logins'          => (clone $auditLogs)->where('action', 'user.login')->count(),
            'operator_actions'=> (clone $auditLogs)->where('channel', 'audit')->count(),
            'settings_changes'=> (clone $auditLogs)->where('action', 'setting.updated')->count(),
            'active_users'    => (clone $auditLogs)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ],
    ];

    // ── Génération PDF ─────────────────────────────────────────────────────
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('platform.reports.executive-pdf', [
        'data'        => $data,
        'periodLabel' => $label,
        'frequency'   => $frequency,
        'start'       => $start,
        'end'         => $end,
    ])->setPaper('a4', 'portrait');

    $filename  = 'rapport-soc-'.now()->format('Ymd-His').'.pdf';
    $storagePath = storage_path('app/reports/'.$filename);
    file_put_contents($storagePath, $pdf->output());
    $this->info("PDF généré : {$storagePath}");

    // ── Envoi e-mail ───────────────────────────────────────────────────────
    $summary = [
        'incidents_total'    => $data['incidents']['total'],
        'incidents_critical' => $data['incidents']['critical'],
        'alerts_total'       => $data['alerts']['total'],
        'agents_online'      => $data['agents']['online'],
        'actions_executed'   => $data['actions']['executed'],
    ];

    \Illuminate\Support\Facades\Mail::to($recipient)
        ->send(new \App\Mail\ExecutiveReportMail($storagePath, $label, $summary));

    $this->info("Rapport envoyé à : {$recipient}");

    // ── Audit ──────────────────────────────────────────────────────────────
    \App\Models\AuditLog::write('report.executive_generated', 'audit', [
        'period'    => $label,
        'frequency' => $frequency,
        'recipient' => $recipient,
        'incidents' => $data['incidents']['total'],
        'file'      => $filename,
    ]);

    return 0;
})->purpose('Génère et envoie par e-mail le rapport exécutif SOC (hebdomadaire ou mensuel).');
