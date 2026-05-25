@extends('layouts.soc')

@section('title', 'RansomShield — Hôtes découverts')
@section('page_title', 'Hôtes découverts')
@section('page_subtitle', 'Machines détectées sur les réseaux surveillés')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus   = $activeStatus   ?? 'monitored';
        $search         = $search         ?? '';
        $filterCounts   = $filterCounts   ?? [];
        $networkSocUrls = $networkSocUrls ?? [];
        $fallbackSocUrl = $fallbackSocUrl ?? config('app.soc_url', config('app.url'));

        $roleIcon = fn($r) => match($r) {
            'soc_server'    => 'fa-server',
            'gateway'       => 'fa-network-wired',
            'file_server'   => 'fa-hard-drive',
            'attacker_demo' => 'fa-skull-crossbones',
            'mobile_device' => 'fa-mobile-screen',
            default         => 'fa-desktop',
        };

        $roleLabel = fn($r) => match($r) {
            'soc_server'    => 'Serveur SOC',
            'gateway'       => 'Passerelle',
            'file_server'   => 'Serveur fichiers',
            'attacker_demo' => 'Attaquant démo',
            'mobile_device' => 'Mobile / Tablette',
            default         => 'Client',
        };

        $roleColor = fn($r) => match($r) {
            'soc_server'    => '#6366f1',
            'gateway'       => '#22c55e',
            'file_server'   => '#f97316',
            'attacker_demo' => '#ef4444',
            'mobile_device' => '#ec4899',
            default         => '#64748b',
        };

        $enrollLabel = fn($s) => match($s) {
            'enrolled'     => 'Enrôlé',
            'pre_enrolled' => 'En attente',
            default        => 'Non enrôlé',
        };

        $enrollColor = fn($s) => match($s) {
            'enrolled'     => '#22c55e',
            'pre_enrolled' => '#f59e0b',
            default        => '#64748b',
        };

        $riskColor = fn($r) => match($r) {
            'critical' => '#ef4444',
            'high'     => '#fb923c',
            'suspect'  => '#f59e0b',
            default    => '#22c55e',
        };

        $statusFilters = [
            'monitored' => ['label' => 'Surveillés',  'icon' => 'fa-eye'],
            'retired'   => ['label' => 'Retirés',     'icon' => 'fa-ban'],
            'all'       => ['label' => 'Tous',        'icon' => 'fa-list'],
        ];
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .dh-hero {
            position: relative;
            overflow: hidden;
            padding: 28px 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 10% 20%, color-mix(in srgb, #22c55e 10%, transparent), transparent 28%),
                radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 28%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .dh-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(34px, 5vw, 60px);
            line-height: .93;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .dh-hero p {
            margin-top: 12px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 860px;
        }

        /* ── TOOLBAR ──────────────────────────────────────────────────────── */
        .dh-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 10px 14px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 14px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            transition: .15s ease;
            white-space: nowrap;
        }

        .filter-tab:hover { background: color-mix(in srgb, var(--accent) 7%, transparent); color: var(--text-body); }
        .filter-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }

        .tab-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px;
            border-radius: 999px; font-size: 10px; font-weight: 900;
            background: color-mix(in srgb, currentColor 18%, transparent);
        }

        .filter-tab.active .tab-count { background: rgba(255,255,255,.25); }

        .toolbar-sep { width: 1px; height: 22px; background: var(--border-soft); margin: 0 4px; }

        .dh-search {
            flex: 1; min-width: 180px;
            display: flex; align-items: center; gap: 8px;
            padding: 7px 12px;
            border-radius: 12px;
            border: 1px solid var(--border-soft);
            background: var(--bg-panel-soft);
            font-size: 13px;
            color: var(--text-body);
        }

        .dh-search input {
            flex: 1; border: none; background: transparent;
            color: var(--text-body); font-size: 13px; outline: none;
        }

        .dh-search input::placeholder { color: var(--text-muted); }

        /* ── STATS ────────────────────────────────────────────────────────── */
        .dh-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        @media (max-width: 900px) { .dh-stats { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 560px) { .dh-stats { grid-template-columns: repeat(2, 1fr); } }

        /* ── HOST CARD ────────────────────────────────────────────────────── */
        .host-list { display: flex; flex-direction: column; gap: 12px; }

        .host-card {
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left-width: 4px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .host-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(0,0,0,.15);
        }

        .host-card-top {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            gap: 0;
            padding: 18px 20px 0 16px;
            align-items: flex-start;
        }

        .host-icon {
            width: 44px; height: 44px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
            margin-top: 2px;
        }

        .host-info { min-width: 0; }

        .host-name {
            margin: 0; font-size: 15px; font-weight: 950; letter-spacing: -.03em;
            font-family: monospace;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .host-sub {
            font-size: 12px; color: var(--text-muted);
            margin-top: 3px; display: flex; gap: 12px; flex-wrap: wrap;
            align-items: center;
        }

        .host-sub-item { display: flex; align-items: center; gap: 4px; }

        .host-badges {
            display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px;
            align-items: center;
        }

        /* ── ENROLLMENT COMMAND ─────────────────────────────────────────── */
        .enroll-cmd-block {
            margin: 12px 16px 0;
            padding: 12px 14px;
            border-radius: 14px;
            background: color-mix(in srgb, #6366f1 8%, transparent);
            border: 1px solid color-mix(in srgb, #6366f1 22%, transparent);
        }

        .enroll-cmd-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px; margin-bottom: 8px; flex-wrap: wrap;
        }

        .enroll-cmd-title {
            font-size: 11px; font-weight: 850; letter-spacing: .06em;
            text-transform: uppercase; color: #818cf8;
            display: flex; align-items: center; gap: 5px;
        }

        .enroll-cmd-soc {
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; gap: 4px;
        }

        .enroll-cmd-soc code {
            background: color-mix(in srgb, #6366f1 18%, transparent);
            color: #a5b4fc; padding: 1px 6px; border-radius: 5px;
        }

        .os-tabs {
            display: flex; gap: 4px; margin-bottom: 8px;
        }

        .os-tab {
            padding: 3px 10px; border-radius: 8px; font-size: 11px; font-weight: 800;
            border: 1px solid var(--border-soft); background: transparent;
            color: var(--text-muted); cursor: pointer; transition: .12s ease;
        }

        .os-tab.active, .os-tab:hover {
            background: color-mix(in srgb, #6366f1 15%, transparent);
            border-color: color-mix(in srgb, #6366f1 40%, transparent);
            color: #a5b4fc;
        }

        .cmd-line {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg-panel-soft);
            border-radius: 10px; padding: 7px 10px;
            border: 1px solid var(--border-soft);
        }

        .cmd-line code {
            flex: 1; font-size: 11.5px; font-family: monospace;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            color: #e2e8f0;
        }

        .cmd-copy-btn {
            padding: 3px 8px; border-radius: 7px;
            background: color-mix(in srgb, #6366f1 18%, transparent);
            border: 1px solid color-mix(in srgb, #6366f1 35%, transparent);
            color: #a5b4fc; font-size: 11px; cursor: pointer;
            white-space: nowrap; transition: .12s ease; flex-shrink: 0;
        }

        .cmd-copy-btn:hover { background: color-mix(in srgb, #6366f1 28%, transparent); }

        /* ── AGENT LINK BLOCK ─────────────────────────────────────────────── */
        .agent-link-block {
            margin: 12px 16px 0;
            padding: 10px 14px;
            border-radius: 14px;
            background: color-mix(in srgb, #22c55e 7%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 22%, transparent);
            display: flex; align-items: center; gap: 10px;
        }

        /* ── ROLE PICKER ──────────────────────────────────────────────────── */
        .role-picker {
            display: flex; flex-wrap: wrap; gap: 4px;
            margin: 10px 16px 0;
            padding-top: 10px;
            border-top: 1px solid var(--border-soft);
        }

        .role-picker-label {
            width: 100%; font-size: 10px; font-weight: 850; letter-spacing: .06em;
            text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px;
        }

        .role-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 9px; border-radius: 8px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            color: var(--text-muted); font-size: 11px; font-weight: 700;
            cursor: pointer; transition: .12s ease; white-space: nowrap;
        }

        .role-btn:hover {
            border-color: color-mix(in srgb, var(--accent) 40%, transparent);
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 8%, transparent);
        }

        .role-btn.active {
            border-color: var(--accent); color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
        }

        /* ── STRIP ────────────────────────────────────────────────────────── */
        .host-strip {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 10px 20px;
            margin-top: 12px;
            border-top: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .host-strip .spacer { flex: 1; }

        .host-strip .last-seen {
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; gap: 4px;
        }

        /* ── RETIRED OVERLAY ──────────────────────────────────────────────── */
        .host-card.retired { opacity: .65; }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 700px) {
            .host-card-top { grid-template-columns: 48px 1fr; }
            .host-strip-actions { display: contents; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="dh-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Découverte machines — LAN
            </div>
            <h2>Hôtes découverts.</h2>
            <p>Machines détectées lors des scans réseau. Clique sur <strong>Enrôler</strong> pour déployer
               l'agent — la commande d'installation est générée automatiquement avec l'URL SOC correcte
               pour le réseau de cette machine.</p>

            <div class="btn-row" style="margin-top:18px;">
                <a href="{{ route('platform.networks.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-network-wired"></i> Réseaux
                </a>
                <a href="{{ route('platform.agents.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-microchip"></i> Agents
                </a>
                <a href="{{ route('platform.local-host.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-server"></i> Machine SOC
                </a>
                @if(($stats['retired'] ?? 0) > 0)
                    <form method="POST" action="{{ route('platform.discovered-hosts.purge-retired') }}"
                          data-confirm="Supprimer définitivement {{ $stats['retired'] }} hôte(s) retiré(s) ? Action irréversible."
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-soft" style="border-color:#ef4444;color:#ef4444;">
                            <i class="fa-solid fa-trash-can"></i> Purger {{ $stats['retired'] }} fantôme(s)
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- ── STATS ────────────────────────────────────────────────────── --}}
        <section class="dh-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-desktop" style="color:var(--accent); margin-right:6px;"></i> Total
                </div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Machines enregistrées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-eye" style="color:#22c55e; margin-right:6px;"></i> Surveillés
                </div>
                <div class="smart-stat-value" style="{{ $stats['monitored'] > 0 ? 'color:#22c55e' : '' }}">
                    {{ $stats['monitored'] }}
                </div>
                <div class="smart-stat-hint">Actifs en surveillance.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-microchip" style="color:var(--accent); margin-right:6px;"></i> Enrôlés
                </div>
                <div class="smart-stat-value" style="color:var(--accent)">{{ $stats['enrolled'] }}</div>
                <div class="smart-stat-hint">Agent installé et actif.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-hourglass-half" style="color:#f59e0b; margin-right:6px;"></i> En attente
                </div>
                <div class="smart-stat-value" style="{{ ($stats['pending'] ?? 0) > 0 ? 'color:#f59e0b' : '' }}">
                    {{ $stats['pending'] ?? 0 }}
                </div>
                <div class="smart-stat-hint">Enrôlement en cours.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-ban" style="color:var(--text-muted); margin-right:6px;"></i> Retirés
                </div>
                <div class="smart-stat-value" style="color:var(--text-muted)">{{ $stats['retired'] }}</div>
                <div class="smart-stat-hint">Hors surveillance.</div>
            </div>
        </section>

        {{-- ── TOOLBAR : filtres + recherche ───────────────────────────── --}}
        <div class="dh-toolbar section-gap">
            @foreach($statusFilters as $key => $filter)
                @php $count = $filterCounts[$key] ?? 0; @endphp
                <a href="{{ route('platform.discovered-hosts.index', array_filter(['status' => $key, 'search' => $search ?: null])) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                    @if($count > 0)
                        <span class="tab-count">{{ $count }}</span>
                    @endif
                </a>
            @endforeach

            <div class="toolbar-sep"></div>

            <form method="GET" action="{{ route('platform.discovered-hosts.index') }}"
                  style="display:contents">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                <label class="dh-search">
                    <i class="fa-solid fa-magnifying-glass" style="color:var(--text-muted); font-size:12px; flex-shrink:0;"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Rechercher hostname, IP, MAC…"
                           autocomplete="off">
                    @if($search)
                        <a href="{{ route('platform.discovered-hosts.index', ['status' => $activeStatus]) }}"
                           style="color:var(--text-muted); font-size:12px; flex-shrink:0; text-decoration:none;">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </label>
            </form>
        </div>

        {{-- ── HOST LIST ────────────────────────────────────────────────── --}}
        @if($hosts->count())
            <div class="host-list section-gap">
                @foreach($hosts as $host)
                    @php
                        $ri  = $roleIcon($host->host_role ?? 'client');
                        $rc  = $roleColor($host->host_role ?? 'client');
                        $es  = $host->enrollment_status ?? 'not_enrolled';
                        $ec  = $enrollColor($es);
                        $isEnrolled   = $es === 'enrolled';
                        $isPreEnrolled = $es === 'pre_enrolled';

                        // URL SOC pour ce réseau
                        $socUrl = $networkSocUrls[$host->managed_network_id] ?? $fallbackSocUrl;
                        $networkName = $host->managedNetwork?->cidr ?? '—';

                        // Commande d'enrôlement (si agent avec token valide)
                        $agent       = $host->agent;
                        $hasToken    = $agent && $agent->enrollment_token
                                       && (! $agent->enrollment_token_expires_at || now()->lt($agent->enrollment_token_expires_at));
                        $shortCode   = $agent?->enrollment_short_code;
                        $shortUrl    = ($hasToken && $shortCode) ? $socUrl.'/e/'.$shortCode : null;

                        // Couleur de la bordure gauche
                        $borderColor = $isEnrolled ? '#6366f1'
                                     : ($isPreEnrolled ? '#f59e0b'
                                     : ($host->is_monitored ? '#22c55e' : '#6b7280'));
                    @endphp

                    <article class="host-card {{ !$host->is_monitored ? 'retired' : '' }}"
                             style="border-left-color:{{ $borderColor }}">

                        {{-- ── TOP : icône + infos + badges ───────────── --}}
                        <div class="host-card-top">
                            {{-- Icône rôle --}}
                            <div class="host-icon"
                                 style="background:color-mix(in srgb, {{ $rc }} 12%, transparent); color:{{ $rc }}">
                                <i class="fa-solid {{ $ri }}"></i>
                            </div>

                            {{-- Infos principales --}}
                            <div class="host-info" style="padding: 0 14px;">
                                <h4 class="host-name">{{ $host->hostname ?: $host->ip_address }}</h4>
                                <div class="host-sub">
                                    <span class="host-sub-item">
                                        <i class="fa-solid fa-ethernet" style="color:var(--accent); font-size:10px;"></i>
                                        <code style="font-size:12px;">{{ $host->ip_address }}</code>
                                    </span>
                                    @if($host->mac_address)
                                        <span class="host-sub-item">
                                            <i class="fa-solid fa-id-card" style="font-size:10px;"></i>
                                            <code style="font-size:11px;">{{ $host->mac_address }}</code>
                                        </span>
                                    @endif
                                    <span class="host-sub-item">
                                        <i class="fa-solid fa-network-wired" style="font-size:10px;"></i>
                                        {{ $networkName }}
                                    </span>
                                    @if($host->last_seen_at)
                                        <span class="host-sub-item">
                                            <i class="fa-regular fa-clock" style="font-size:10px;"></i>
                                            {{ $host->last_seen_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Badges --}}
                                <div class="host-badges">
                                    {{-- Statut surveillance --}}
                                    <span class="badge" style="color:{{ $host->is_monitored ? '#22c55e' : '#6b7280' }}; border-color:color-mix(in srgb, {{ $host->is_monitored ? '#22c55e' : '#6b7280' }} 28%, transparent)">
                                        <i class="fa-solid {{ $host->is_monitored ? 'fa-eye' : 'fa-eye-slash' }}" style="font-size:9px;"></i>
                                        {{ $host->is_monitored ? 'Surveillé' : 'Retiré' }}
                                    </span>
                                    {{-- Rôle --}}
                                    <span class="badge" style="color:{{ $rc }}; border-color:color-mix(in srgb, {{ $rc }} 28%, transparent)">
                                        <i class="fa-solid {{ $ri }}" style="font-size:9px;"></i>
                                        {{ $roleLabel($host->host_role ?? 'client') }}
                                    </span>
                                    {{-- Enrôlement --}}
                                    <span class="badge" style="color:{{ $ec }}; border-color:color-mix(in srgb, {{ $ec }} 28%, transparent)">
                                        <i class="fa-solid {{ $isEnrolled ? 'fa-microchip' : ($isPreEnrolled ? 'fa-hourglass-half' : 'fa-circle-xmark') }}" style="font-size:9px;"></i>
                                        {{ $enrollLabel($es) }}
                                    </span>
                                    {{-- Fabricant --}}
                                    @if($host->device_vendor)
                                        @php $catColor = match($host->device_category) {
                                            'mobile' => '#ec4899', 'apple_device' => '#6366f1',
                                            'router' => '#22c55e', 'iot' => '#f59e0b',
                                            'printer' => '#64748b', 'computer' => '#3b82f6',
                                            default => '#64748b',
                                        }; @endphp
                                        <span class="badge" style="color:{{ $catColor }}; border-color:color-mix(in srgb, {{ $catColor }} 28%, transparent)">
                                            {{ $host->device_icon }} {{ $host->device_vendor }}
                                        </span>
                                    @endif
                                    {{-- Statut découverte (discovery_status) --}}
                                    @php $ds = $host->discovery_status ?? 'detected'; @endphp
                                    @if($ds === 'approved')
                                        <span class="badge" style="color:#22c55e; border-color:color-mix(in srgb,#22c55e 28%,transparent);">
                                            <i class="fa-solid fa-circle-check" style="font-size:9px;"></i> Validé
                                        </span>
                                    @elseif($ds === 'detected')
                                        <span class="badge" style="color:#f59e0b; border-color:color-mix(in srgb,#f59e0b 28%,transparent);">
                                            <i class="fa-solid fa-magnifying-glass" style="font-size:9px;"></i> Détecté
                                        </span>
                                    @endif
                                    {{-- Agent lien (si enrôlé) --}}
                                    @if($isEnrolled && $agent)
                                        <a href="{{ route('platform.agents.show', $agent) }}"
                                           class="badge"
                                           style="color:var(--accent); border-color:color-mix(in srgb,var(--accent) 28%,transparent); text-decoration:none;">
                                            <i class="fa-solid fa-microchip" style="font-size:9px;"></i>
                                            {{ $agent->agent_name }}
                                            @if($agent->risk_level && $agent->risk_level !== 'normal')
                                                <span style="color:{{ $riskColor($agent->risk_level) }}; margin-left:3px;">
                                                    <i class="fa-solid fa-circle" style="font-size:6px;"></i>
                                                    {{ ucfirst($agent->risk_level) }}
                                                </span>
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions rapides (col droite) — admin uniquement --}}
                            @if(auth()->user()->isAdmin())
                            <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0; padding-top:2px;">
                                @if($host->is_monitored)
                                    @if($isEnrolled && $agent)
                                        <a href="{{ route('platform.agents.show', $agent) }}" class="action-btn primary" style="white-space:nowrap;">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Console
                                        </a>
                                    @elseif(!$isEnrolled && !$isPreEnrolled)
                                        <form method="POST" action="{{ route('platform.discovered-hosts.enroll', $host) }}">
                                            @csrf
                                            <button type="submit" class="action-btn primary" style="white-space:nowrap; width:100%">
                                                <i class="fa-solid fa-plug"></i> Enrôler
                                            </button>
                                        </form>
                                    @elseif($isPreEnrolled && $agent)
                                        <a href="{{ route('platform.agents.show', $agent) }}" class="action-btn" style="white-space:nowrap; border-color:#f59e0b; color:#f59e0b;">
                                            <i class="fa-solid fa-terminal"></i> Installer
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('platform.discovered-hosts.retire', $host) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="action-btn danger" style="width:100%; white-space:nowrap;">
                                            <i class="fa-solid fa-ban"></i> Retirer
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.discovered-hosts.restore', $host) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="action-btn success" style="white-space:nowrap; width:100%">
                                            <i class="fa-solid fa-rotate-left"></i> Réactiver
                                        </button>
                                    </form>
                                @endif
                            </div>
                            @endif {{-- end @if(auth()->user()->isAdmin()) actions rapides --}}
                        </div>

                        {{-- ── COMMANDE D'ENRÔLEMENT ────────────────────── --}}
                        @if($shortUrl && !$isEnrolled)
                            <div class="enroll-cmd-block">
                                <div class="enroll-cmd-header">
                                    <div class="enroll-cmd-title">
                                        <i class="fa-solid fa-terminal"></i>
                                        Commande d'installation — copie sur la machine cible
                                    </div>
                                    <div class="enroll-cmd-soc">
                                        <i class="fa-solid fa-satellite-dish" style="font-size:10px;"></i>
                                        SOC auto-détecté : <code>{{ $socUrl }}</code>
                                    </div>
                                </div>

                                {{-- Tabs OS --}}
                                <div class="os-tabs" id="ostabs-{{ $host->id }}">
                                    <button type="button" class="os-tab active"
                                            onclick="switchOs({{ $host->id }}, 'linux', this)">
                                        <i class="fa-brands fa-linux"></i> Linux
                                    </button>
                                    <button type="button" class="os-tab"
                                            onclick="switchOs({{ $host->id }}, 'macos', this)">
                                        <i class="fa-brands fa-apple"></i> macOS
                                    </button>
                                    <button type="button" class="os-tab"
                                            onclick="switchOs({{ $host->id }}, 'windows', this)">
                                        <i class="fa-brands fa-windows"></i> Windows
                                    </button>
                                </div>

                                {{-- Linux --}}
                                <div id="cmd-linux-{{ $host->id }}" class="cmd-line">
                                    <code>curl {{ $shortUrl }} | sudo bash</code>
                                    <button type="button" class="cmd-copy-btn"
                                            onclick="copyCmd(this, 'curl {{ $shortUrl }} | sudo bash')">
                                        <i class="fa-regular fa-copy"></i> Copier
                                    </button>
                                </div>
                                {{-- macOS --}}
                                <div id="cmd-macos-{{ $host->id }}" class="cmd-line" style="display:none;">
                                    <code>curl "{{ $shortUrl }}?os=macos" | sudo bash</code>
                                    <button type="button" class="cmd-copy-btn"
                                            onclick="copyCmd(this, 'curl &quot;{{ $shortUrl }}?os=macos&quot; | sudo bash')">
                                        <i class="fa-regular fa-copy"></i> Copier
                                    </button>
                                </div>
                                {{-- Windows --}}
                                <div id="cmd-windows-{{ $host->id }}" class="cmd-line" style="display:none;">
                                    <code>powershell -ExecutionPolicy Bypass -Command "iwr '{{ $shortUrl }}?os=windows' -UseBasicParsing | iex"</code>
                                    <button type="button" class="cmd-copy-btn"
                                            onclick="copyCmd(this, `powershell -ExecutionPolicy Bypass -Command \"iwr '{{ $shortUrl }}?os=windows' -UseBasicParsing | iex\"`)">
                                        <i class="fa-regular fa-copy"></i> Copier
                                    </button>
                                </div>

                                @if($agent?->enrollment_token_expires_at)
                                    <div style="margin-top:6px; font-size:10.5px; color:var(--text-muted);">
                                        <i class="fa-regular fa-clock" style="margin-right:3px;"></i>
                                        Token expire {{ $agent->enrollment_token_expires_at->diffForHumans() }}
                                        · Code court : <code style="background:color-mix(in srgb,#6366f1 16%,transparent); color:#a5b4fc; padding:1px 5px; border-radius:4px;">{{ $shortCode }}</code>
                                    </div>
                                @endif
                            </div>
                        @elseif($isPreEnrolled && $agent && !$hasToken)
                            {{-- Agent créé mais token expiré --}}
                            <div style="margin: 10px 16px 0; padding:10px 14px; border-radius:12px;
                                        background:color-mix(in srgb,#f59e0b 7%,transparent);
                                        border:1px solid color-mix(in srgb,#f59e0b 22%,transparent);
                                        font-size:12px; color:#f59e0b; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Token expiré —
                                <a href="{{ route('platform.agents.show', $agent) }}"
                                   style="color:#f59e0b; font-weight:800;">Régénérer depuis la console agent</a>
                            </div>
                        @endif

                        {{-- ── AGENT LINK (enrôlé) ──────────────────────── --}}
                        @if($isEnrolled && $agent)
                            <div class="agent-link-block">
                                <div style="width:32px; height:32px; border-radius:10px;
                                            background:color-mix(in srgb,#22c55e 15%,transparent);
                                            color:#22c55e; display:flex; align-items:center;
                                            justify-content:center; flex-shrink:0; font-size:14px;">
                                    <i class="fa-solid fa-microchip"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12px; font-weight:900; color:#22c55e;">Agent actif</div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:1px;">
                                        {{ $agent->agent_name }}
                                        @if($agent->last_seen_at)
                                            · vu {{ $agent->last_seen_at->diffForHumans() }}
                                        @endif
                                        @if($agent->risk_level && $agent->risk_level !== 'normal')
                                            · <span style="color:{{ $riskColor($agent->risk_level) }}; font-weight:900;">
                                                risque {{ $agent->risk_level }}
                                              </span>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('platform.agents.show', $agent) }}"
                                   class="action-btn" style="flex-shrink:0;">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir
                                </a>
                            </div>
                        @endif

                        {{-- ── ROLE PICKER ──────────────────────────────── --}}
                        @if($host->is_monitored)
                            <div class="role-picker">
                                <div class="role-picker-label">
                                    <i class="fa-solid fa-tag" style="margin-right:4px;"></i>Qualifier le rôle
                                </div>
                                @foreach([
                                    'client'        => ['fa-desktop',          'Client',            route('platform.discovered-hosts.mark-client',       $host)],
                                    'mobile_device' => ['fa-mobile-screen',    'Mobile',            route('platform.discovered-hosts.mark-mobile',        $host)],
                                    'file_server'   => ['fa-hard-drive',       'Serveur fichiers',  route('platform.discovered-hosts.mark-file-server',   $host)],
                                    'soc_server'    => ['fa-server',           'Serveur SOC',       route('platform.discovered-hosts.mark-soc-server',    $host)],
                                    'attacker_demo' => ['fa-skull-crossbones', 'Attaquant démo',    route('platform.discovered-hosts.mark-attacker-demo', $host)],
                                ] as $roleKey => [$icon, $label, $action])
                                    <form method="POST" action="{{ $action }}" style="display:contents">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="role-btn {{ ($host->host_role ?? 'client') === $roleKey ? 'active' : '' }}"
                                                title="{{ $label }}">
                                            <i class="fa-solid {{ $icon }}"></i> {{ $label }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif

                        {{-- ── STRIP INFOS ──────────────────────────────── --}}
                        <div class="host-strip">
                            <span class="last-seen">
                                @if($host->last_seen_at)
                                    <i class="fa-regular fa-clock"></i> Vu {{ $host->last_seen_at->diffForHumans() }}
                                @else
                                    <i class="fa-solid fa-question-circle"></i> Jamais vu
                                @endif
                            </span>
                            @if($host->retired_at)
                                <span class="last-seen" style="color:#ef4444;">
                                    <i class="fa-solid fa-ban"></i> Retiré {{ $host->retired_at->diffForHumans() }}
                                    @if($host->retired_reason)
                                        · {{ $host->retired_reason }}
                                    @endif
                                </span>
                            @endif

                            {{-- Boutons Valider / Réinitialiser — admin uniquement --}}
                            @if(auth()->user()->isAdmin() && $host->is_monitored)
                                @if($ds === 'detected')
                                    <form method="POST" action="{{ route('platform.discovered-hosts.validate', $host) }}" style="display:contents">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="action-btn success"
                                                style="font-size:11px; padding:3px 10px; border-radius:8px; white-space:nowrap; height:auto; line-height:1.6;"
                                                title="Marquer cet hôte comme validé par l'opérateur">
                                            <i class="fa-solid fa-circle-check"></i> Valider
                                        </button>
                                    </form>
                                @elseif($ds === 'approved')
                                    <form method="POST" action="{{ route('platform.discovered-hosts.reset', $host) }}" style="display:contents">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="action-btn"
                                                style="font-size:11px; padding:3px 10px; border-radius:8px; white-space:nowrap; height:auto; line-height:1.6; color:var(--text-muted);"
                                                title="Remettre en statut détecté">
                                            <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                                        </button>
                                    </form>
                                @endif
                            @endif

                            <div class="spacer"></div>
                            <span style="font-size:11px; color:var(--text-muted); font-family:monospace;">
                                #{{ $host->id }}
                            </span>
                        </div>

                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">{{ $hosts->links() }}</div>

        @else
            <div class="section-gap">
                @include('platform.partials.empty-state', [
                    'title'   => $search ? "Aucun résultat pour « {$search} »." : 'Aucun hôte pour ce filtre.',
                    'message' => $search ? 'Essaie une autre IP, hostname ou MAC.' : 'Lance un scan réseau depuis la page Réseaux ou change le filtre.',
                ])
            </div>
        @endif
    </div>

    <script>
    function switchOs(hostId, os, btn) {
        ['linux','macos','windows'].forEach(o => {
            const el = document.getElementById('cmd-' + o + '-' + hostId);
            if (el) el.style.display = (o === os) ? 'flex' : 'none';
        });
        btn.closest('.os-tabs').querySelectorAll('.os-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function copyCmd(btn, text) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié';
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        }).catch(() => {
            const ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy');
            document.body.removeChild(ta);
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié';
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        });
    }
    </script>
@endsection
