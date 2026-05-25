@extends('layouts.soc')

@section('title', 'RansomShield — Réseaux surveillés')
@section('page_title', 'Réseaux surveillés')
@section('page_subtitle', 'Détection, scan et gestion des réseaux sous surveillance')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus = $activeStatus ?? 'monitored';
        $filterCounts = $filterCounts ?? [];

        $statusColor = fn($n) => !$n->is_monitored ? '#6b7280' : match($n->status) {
            'approved' => '#22c55e',
            'detected' => '#f97316',
            default    => '#6366f1',
        };

        $statusLabel = fn($n) => !$n->is_monitored ? 'Retiré' : match($n->status) {
            'approved' => 'Approuvé',
            'detected' => 'Détecté',
            'retired'  => 'Retiré',
            default    => ucfirst($n->status),
        };

        $methodLabel = fn($m) => match($m) {
            'fping'       => 'fping (ICMP+ARP)',
            'nmap'        => 'nmap',
            'ping_sweep'  => 'ping sweep',
            'arp_only'    => 'ARP uniquement',
            default       => $m ?? '—',
        };

        $statusFilters = [
            'monitored' => ['label' => 'Surveillés', 'icon' => 'fa-eye'],
            'retired'   => ['label' => 'Retirés',    'icon' => 'fa-ban'],
            'all'       => ['label' => 'Tous',       'icon' => 'fa-list'],
        ];
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .net-hero {
            position: relative; overflow: hidden;
            padding: 28px 32px; border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 18%, color-mix(in srgb, #22c55e 10%, transparent), transparent 28%),
                radial-gradient(circle at 88% 8%,  color-mix(in srgb, var(--accent) 10%, transparent), transparent 28%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .net-hero h2 {
            margin: 10px 0 0; font-size: clamp(34px, 5vw, 60px);
            line-height: .93; letter-spacing: -.08em; font-weight: 950;
        }

        .net-hero p {
            margin-top: 12px; color: var(--text-muted);
            line-height: 1.75; max-width: 860px;
        }

        /* ── STATS ────────────────────────────────────────────────────────── */
        .net-stats {
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;
        }
        @media (max-width: 1050px) { .net-stats { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 600px)  { .net-stats { grid-template-columns: repeat(2, 1fr); } }

        /* ── TOOLBAR ──────────────────────────────────────────────────────── */
        .net-toolbar {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 10px 14px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
        }

        .filter-tab {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 14px;
            border: 1px solid transparent; background: transparent;
            color: var(--text-muted); font-size: 13px; font-weight: 850;
            text-decoration: none; transition: .15s ease; white-space: nowrap;
        }
        .filter-tab:hover { background: color-mix(in srgb,var(--accent) 7%,transparent); color: var(--text-body); }
        .filter-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }

        .tab-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
            font-size: 10px; font-weight: 900;
            background: color-mix(in srgb, currentColor 18%, transparent);
        }
        .filter-tab.active .tab-count { background: rgba(255,255,255,.25); }

        .toolbar-sep { width: 1px; height: 22px; background: var(--border-soft); margin: 0 4px; }

        /* ── NETWORK CARD ─────────────────────────────────────────────────── */
        .net-list { display: flex; flex-direction: column; gap: 12px; }

        .net-card {
            border-radius: 24px; background: var(--bg-card);
            border: 1px solid var(--border-soft); border-left-width: 4px;
            box-shadow: var(--shadow-soft); overflow: hidden;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .net-card:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(0,0,0,.15); }
        .net-card.retired { opacity: .65; }

        .net-card-top {
            display: grid;
            grid-template-columns: 60px 1fr auto;
            padding: 18px 20px 0 16px; gap: 0; align-items: flex-start;
        }

        .net-icon {
            width: 46px; height: 46px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0; margin-top: 2px;
        }

        .net-info { min-width: 0; padding: 0 14px; }

        .net-name {
            margin: 0; font-size: 16px; font-weight: 950; letter-spacing: -.04em;
        }

        .net-sub {
            font-size: 12px; color: var(--text-muted); margin-top: 4px;
            display: flex; gap: 14px; flex-wrap: wrap; align-items: center;
        }

        .net-sub-item { display: flex; align-items: center; gap: 5px; }

        /* ── INFO GRID ────────────────────────────────────────────────────── */
        .net-info-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px; margin-top: 12px;
        }
        @media (max-width: 900px) { .net-info-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 600px) { .net-info-grid { grid-template-columns: repeat(2, 1fr); } }

        .net-info-box {
            padding: 8px 10px; border-radius: 12px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }
        .net-info-box-label {
            font-size: 10px; font-weight: 850; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-muted);
            display: flex; align-items: center; gap: 4px;
        }
        .net-info-box-val {
            margin-top: 3px; font-size: 12px; font-weight: 950;
            font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        /* ── HOSTS BAR ────────────────────────────────────────────────────── */
        .hosts-bar-wrap { margin-top: 12px; }

        .hosts-bar-labels {
            display: flex; justify-content: space-between;
            font-size: 11px; color: var(--text-muted); margin-bottom: 4px;
        }

        .hosts-bar-track {
            height: 6px; border-radius: 6px;
            background: color-mix(in srgb, var(--border-soft) 70%, transparent);
            overflow: hidden; position: relative;
        }

        .hosts-bar-monitored {
            height: 100%; border-radius: 6px;
            background: linear-gradient(90deg, #22c55e, #4ade80);
            transition: width .5s ease;
            position: absolute; left: 0; top: 0;
        }

        .hosts-bar-enrolled {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #818cf8);
            transition: width .5s ease;
            position: absolute; left: 0; top: 0;
        }

        /* ── SOC IP BADGE ─────────────────────────────────────────────────── */
        .soc-ip-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 8px;
            background: color-mix(in srgb, #6366f1 12%, transparent);
            border: 1px solid color-mix(in srgb, #6366f1 28%, transparent);
            font-size: 11px; font-weight: 800; color: #a5b4fc;
        }

        /* ── SCAN RESULT ──────────────────────────────────────────────────── */
        .scan-result {
            display: none; margin: 12px 16px 0;
            padding: 12px 14px; border-radius: 14px;
            background: color-mix(in srgb, #22c55e 8%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 22%, transparent);
            font-size: 12px; animation: fadeIn .2s ease;
        }

        .scan-result.error {
            background: color-mix(in srgb, #ef4444 8%, transparent);
            border-color: color-mix(in srgb, #ef4444 22%, transparent);
        }

        .scan-result-title {
            font-size: 13px; font-weight: 850; color: #22c55e;
            margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
        }
        .scan-result.error .scan-result-title { color: #ef4444; }

        .scan-result-ips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }

        .scan-ip-tag {
            padding: 2px 8px; border-radius: 6px; font-size: 11px;
            font-weight: 700; font-family: monospace;
            background: color-mix(in srgb, #22c55e 14%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 22%, transparent);
            color: #22c55e;
        }

        /* ── STRIP ────────────────────────────────────────────────────────── */
        .net-strip {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 10px 20px; margin-top: 12px;
            border-top: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .net-strip .spacer { flex: 1; }

        .net-strip .last-scan {
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; gap: 4px;
        }

        /* ── ADD FORM ─────────────────────────────────────────────────────── */
        .net-add-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: 10px; align-items: end;
        }

        .net-field label {
            display: block; font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--text-muted); margin-bottom: 6px;
        }

        @keyframes spin    { to { transform: rotate(360deg); } }
        @keyframes fadeIn  { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
        .btn-spinner { animation: spin .7s linear infinite; display: inline-block; }

        @media (max-width: 900px) {
            .net-card-top { grid-template-columns: 52px 1fr; }
            .net-strip .net-strip-actions { width: 100%; display: flex; gap: 6px; flex-wrap: wrap; }
            .net-strip .action-btn { flex: 1 1 auto; justify-content: center; }
            .net-add-form { grid-template-columns: 1fr 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="net-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Infrastructure réseau SOC
            </div>
            <h2>Réseaux surveillés.</h2>
            <p>Réseaux détectés sur les interfaces du SOC. Lance un scan pour découvrir les hôtes actifs
               — l'IP du SOC sur chaque réseau est calculée automatiquement et utilisée dans les commandes
               d'enrôlement.</p>

            <div class="btn-row" style="margin-top:18px;">
                <button type="button" id="btn-detect-all" class="btn btn-primary"
                        data-url="{{ route('platform.networks.detect') }}"
                        data-csrf="{{ csrf_token() }}">
                    <i class="fa-solid fa-magnifying-glass-location" id="detect-icon"></i>
                    <span id="detect-label">Détecter réseaux locaux</span>
                </button>
                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-desktop"></i> Hôtes découverts
                </a>
                <a href="{{ route('platform.local-host.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-server"></i> Machine SOC
                </a>
            </div>

            <div id="detect-banner" style="display:none; margin-top:14px; padding:12px 16px;
                 border-radius:14px; background:color-mix(in srgb,var(--accent) 8%,transparent);
                 border:1px solid color-mix(in srgb,var(--accent) 25%,transparent);
                 font-size:13px; font-weight:750; color:var(--accent);
                 align-items:center; gap:10px; animation:fadeIn .2s ease;">
                <i class="fa-solid fa-circle-check"></i>
                <span id="detect-banner-text"></span>
            </div>
        </section>

        {{-- ── STATS ────────────────────────────────────────────────────── --}}
        <section class="net-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-network-wired" style="color:var(--accent); margin-right:6px;"></i>Total
                </div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Réseaux enregistrés.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-eye" style="color:#22c55e; margin-right:6px;"></i>Surveillés
                </div>
                <div class="smart-stat-value" style="{{ $stats['monitored'] > 0 ? 'color:#22c55e' : '' }}">
                    {{ $stats['monitored'] }}
                </div>
                <div class="smart-stat-hint">Actifs en ce moment.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e; margin-right:6px;"></i>Approuvés
                </div>
                <div class="smart-stat-value">{{ $stats['approved'] }}</div>
                <div class="smart-stat-hint">Validés par l'opérateur.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-ban" style="color:var(--text-muted); margin-right:6px;"></i>Retirés
                </div>
                <div class="smart-stat-value" style="color:var(--text-muted)">{{ $stats['retired'] }}</div>
                <div class="smart-stat-hint">Hors surveillance.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-desktop" style="color:#38bdf8; margin-right:6px;"></i>Hôtes actifs
                </div>
                <div class="smart-stat-value" style="{{ ($stats['total_hosts'] ?? 0) > 0 ? 'color:#38bdf8' : '' }}">
                    {{ $stats['total_hosts'] ?? 0 }}
                </div>
                <div class="smart-stat-hint">Machines surveillées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-microchip" style="color:#a78bfa; margin-right:6px;"></i>Agents enrôlés
                </div>
                <div class="smart-stat-value" style="{{ ($stats['total_enrolled'] ?? 0) > 0 ? 'color:#a78bfa' : '' }}">
                    {{ $stats['total_enrolled'] ?? 0 }}
                </div>
                <div class="smart-stat-hint">Agents actifs.</div>
            </div>
        </section>

        {{-- ── TOOLBAR ──────────────────────────────────────────────────── --}}
        <div class="net-toolbar section-gap">
            @foreach($statusFilters as $key => $filter)
                @php $count = $filterCounts[$key] ?? 0; @endphp
                <a href="{{ route('platform.networks.index', ['status' => $key]) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                    @if($count > 0)
                        <span class="tab-count">{{ $count }}</span>
                    @endif
                </a>
            @endforeach
            <div class="toolbar-sep"></div>
            <span style="font-size:12px; color:var(--text-muted); margin-left:2px;">
                <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                L'IP SOC sur chaque réseau est auto-détectée — utilisée dans les commandes d'enrôlement.
            </span>
        </div>

        {{-- ── NETWORK LIST ─────────────────────────────────────────────── --}}
        @if($networks->count())
            <div class="net-list section-gap">
                @foreach($networks as $network)
                    @php
                        $sc = $statusColor($network);
                        $sl = $statusLabel($network);

                        // IP du SOC sur ce réseau (depuis metadata)
                        $socIp       = data_get($network->metadata, 'ip');
                        $lastScanAt  = data_get($network->metadata, 'last_scan_at');
                        $lastMethod  = data_get($network->metadata, 'last_scan_method');
                        $lastFound   = data_get($network->metadata, 'last_scan_found', 0);
                        $lastNote    = data_get($network->metadata, 'last_scan_note');

                        $totalHosts    = $network->discovered_hosts_count ?? 0;
                        $monitoredH    = $network->monitored_hosts_count   ?? 0;
                        $enrolledH     = $network->enrolled_hosts_count    ?? 0;
                        $monitoredPct  = $totalHosts > 0 ? round($monitoredH / $totalHosts * 100) : 0;
                        $enrolledPct   = $totalHosts > 0 ? round($enrolledH  / $totalHosts * 100) : 0;
                    @endphp

                    <article class="net-card {{ !$network->is_monitored ? 'retired' : '' }}"
                             id="net-card-{{ $network->id }}"
                             style="border-left-color:{{ $sc }}">

                        {{-- ── TOP ─────────────────────────────────── --}}
                        <div class="net-card-top">
                            <div class="net-icon"
                                 style="background:color-mix(in srgb,{{ $sc }} 12%,transparent); color:{{ $sc }}">
                                <i class="fa-solid fa-network-wired"></i>
                            </div>

                            <div class="net-info">
                                <h4 class="net-name">{{ $network->name }}</h4>
                                <div class="net-sub">
                                    <span class="net-sub-item">
                                        <i class="fa-solid fa-ethernet" style="color:var(--accent); font-size:10px;"></i>
                                        <code style="font-size:12px; font-weight:900;">{{ $network->cidr }}</code>
                                    </span>
                                    @if($network->gateway_ip)
                                        <span class="net-sub-item">
                                            <i class="fa-solid fa-route" style="font-size:10px;"></i>
                                            <code style="font-size:11px;">{{ $network->gateway_ip }}</code>
                                        </span>
                                    @endif
                                    @if($network->interface_name)
                                        <span class="net-sub-item">
                                            <i class="fa-solid fa-plug" style="font-size:10px;"></i>
                                            <code style="font-size:11px;">{{ $network->interface_name }}</code>
                                        </span>
                                    @endif
                                    {{-- Badge statut --}}
                                    <span class="badge" style="color:{{ $sc }}; border-color:color-mix(in srgb,{{ $sc }} 28%,transparent)">
                                        {{ $sl }}
                                    </span>
                                    {{-- SOC IP badge --}}
                                    @if($socIp)
                                        <span class="soc-ip-badge">
                                            <i class="fa-solid fa-server" style="font-size:9px;"></i>
                                            SOC : {{ $socIp }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Info grid --}}
                                <div class="net-info-grid">
                                    <div class="net-info-box">
                                        <div class="net-info-box-label">
                                            <i class="fa-solid fa-desktop"></i> Hôtes
                                        </div>
                                        <div class="net-info-box-val" id="host-count-{{ $network->id }}"
                                             style="font-family:sans-serif; font-size:14px;">
                                            {{ $monitoredH }}
                                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">
                                                / {{ $totalHosts }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="net-info-box">
                                        <div class="net-info-box-label">
                                            <i class="fa-solid fa-microchip"></i> Enrôlés
                                        </div>
                                        <div class="net-info-box-val" style="font-family:sans-serif; font-size:14px; color:#a78bfa;">
                                            {{ $enrolledH }}
                                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">
                                                / {{ $monitoredH }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="net-info-box">
                                        <div class="net-info-box-label">
                                            <i class="fa-solid fa-satellite-dish"></i> Dernier scan
                                        </div>
                                        <div class="net-info-box-val" id="last-scan-{{ $network->id }}"
                                             style="font-family:sans-serif; font-size:11px;">
                                            @if($lastScanAt)
                                                {{ \Carbon\Carbon::parse($lastScanAt)->diffForHumans() }}
                                            @elseif($network->last_scanned_at)
                                                {{ $network->last_scanned_at->diffForHumans() }}
                                            @else
                                                Jamais
                                            @endif
                                        </div>
                                    </div>
                                    <div class="net-info-box">
                                        <div class="net-info-box-label">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i> Méthode
                                        </div>
                                        <div class="net-info-box-val" style="font-family:sans-serif; font-size:11px;">
                                            {{ $methodLabel($lastMethod) }}
                                        </div>
                                    </div>
                                    <div class="net-info-box">
                                        <div class="net-info-box-label">
                                            <i class="fa-solid fa-magnifying-glass"></i> Trouvés
                                        </div>
                                        <div class="net-info-box-val" style="font-family:sans-serif; font-size:14px;" id="found-count-{{ $network->id }}">
                                            {{ $lastFound }}
                                            <span style="font-size:10px; color:var(--text-muted); font-weight:700;">hôtes</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Barre coverage --}}
                                @if($totalHosts > 0)
                                    <div class="hosts-bar-wrap">
                                        <div class="hosts-bar-labels">
                                            <span>
                                                <i class="fa-solid fa-circle" style="color:#22c55e; font-size:8px; margin-right:3px;"></i>
                                                Surveillés {{ $monitoredPct }}%
                                            </span>
                                            <span>
                                                <i class="fa-solid fa-circle" style="color:#6366f1; font-size:8px; margin-right:3px;"></i>
                                                Enrôlés {{ $enrolledPct }}%
                                            </span>
                                        </div>
                                        <div class="hosts-bar-track">
                                            <div class="hosts-bar-monitored" style="width:{{ $monitoredPct }}%;"></div>
                                            <div class="hosts-bar-enrolled"  style="width:{{ $enrolledPct }}%;"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions col — admin uniquement --}}
                            @if(auth()->user()->isAdmin())
                            <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0; padding-top:2px;">
                                @if($network->is_monitored)
                                    <button type="button"
                                            class="action-btn primary btn-scan"
                                            id="btn-scan-{{ $network->id }}"
                                            data-network-id="{{ $network->id }}"
                                            data-url="{{ route('platform.networks.scan', $network) }}"
                                            data-csrf="{{ csrf_token() }}"
                                            data-name="{{ $network->name }}"
                                            style="white-space:nowrap;">
                                        <i class="fa-solid fa-satellite-dish" id="scan-icon-{{ $network->id }}"></i>
                                        <span id="scan-label-{{ $network->id }}">Scanner</span>
                                    </button>

                                    @if($network->status !== 'approved')
                                        <form method="POST" action="{{ route('platform.networks.approve', $network) }}" style="display:contents;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="action-btn success" style="white-space:nowrap;">
                                                <i class="fa-solid fa-circle-check"></i> Approuver
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('platform.discovered-hosts.index', ['status' => 'monitored', 'search' => explode('/', $network->cidr)[0]]) }}"
                                       class="action-btn" style="white-space:nowrap;">
                                        <i class="fa-solid fa-desktop"></i> Hôtes
                                    </a>

                                    <form method="POST" action="{{ route('platform.networks.retire', $network) }}" style="display:contents;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="action-btn danger" style="white-space:nowrap;">
                                            <i class="fa-solid fa-ban"></i> Retirer
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.networks.restore', $network) }}" style="display:contents;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="action-btn success" style="white-space:nowrap;">
                                            <i class="fa-solid fa-rotate-left"></i> Réactiver
                                        </button>
                                    </form>
                                @endif
                            </div>
                            @endif {{-- end @if(auth()->user()->isAdmin()) actions col --}}
                        </div>

                        {{-- ── SCAN RESULT ────────────────────────────── --}}
                        <div class="scan-result" id="scan-result-{{ $network->id }}">
                            <div class="scan-result-title">
                                <i class="fa-solid fa-circle-check"></i>
                                <span class="scan-result-msg"></span>
                            </div>
                            <div class="scan-method-note" style="font-size:11px; color:var(--text-muted);"></div>
                            <div class="scan-result-ips"></div>
                        </div>

                        {{-- ── STRIP ───────────────────────────────────── --}}
                        <div class="net-strip">
                            @if($lastNote)
                                <span class="last-scan">
                                    <i class="fa-solid fa-circle-info" style="font-size:10px;"></i>
                                    {{ $lastNote }}
                                </span>
                            @endif
                            @if($network->retired_at)
                                <span class="last-scan" style="color:#ef4444;">
                                    <i class="fa-solid fa-ban"></i>
                                    Retiré {{ $network->retired_at->diffForHumans() }}
                                    @if($network->retired_reason)
                                        · {{ Str::limit($network->retired_reason, 60) }}
                                    @endif
                                </span>
                            @endif
                            <div class="spacer"></div>
                            <span style="font-size:11px; color:var(--text-muted); font-family:monospace;">
                                #{{ $network->id }}
                            </span>
                        </div>

                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">{{ $networks->links() }}</div>

        @else
            <div class="section-gap">
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucun réseau pour ce filtre.',
                    'message' => 'Lance une détection réseau ou change le filtre.',
                ])
            </div>
        @endif

        {{-- ── ADD NETWORK — admin uniquement ──────────────────────────── --}}
        @if(auth()->user()->isAdmin())
        <div class="soc-card section-gap">
            <h3 class="soc-card-title">
                <i class="fa-solid fa-plus" style="color:var(--accent); margin-right:8px;"></i>
                Ajouter un réseau manuellement
            </h3>
            <p class="soc-card-subtitle">Pour les réseaux non détectés automatiquement — VPN, VLAN, réseau distant.</p>

            <form method="POST" action="{{ route('platform.networks.store') }}"
                  class="net-add-form" style="margin-top:16px;">
                @csrf
                <div class="net-field">
                    <label>Nom</label>
                    <input class="form-control" type="text" name="name" placeholder="LAN Bureau" required>
                </div>
                <div class="net-field">
                    <label>CIDR</label>
                    <input class="form-control" type="text" name="cidr" placeholder="192.168.1.0/24" required>
                </div>
                <div class="net-field">
                    <label>Passerelle</label>
                    <input class="form-control" type="text" name="gateway_ip" placeholder="192.168.1.1">
                </div>
                <div class="net-field">
                    <label>Interface</label>
                    <input class="form-control" type="text" name="interface_name" placeholder="eth0">
                </div>
                <button class="action-btn primary" type="submit" style="align-self:end;">
                    <i class="fa-solid fa-plus"></i> Ajouter
                </button>
            </form>
        </div>
        @endif {{-- end @if(auth()->user()->isAdmin()) add network --}}

    </div>

    <script>
    // ── Helpers ───────────────────────────────────────────────────────────────
    function setScanning(id, scanning) {
        const btn   = document.getElementById('btn-scan-' + id);
        const icon  = document.getElementById('scan-icon-' + id);
        const label = document.getElementById('scan-label-' + id);
        if (!btn) return;
        btn.disabled = scanning;
        icon.className  = scanning ? 'fa-solid fa-circle-notch btn-spinner' : 'fa-solid fa-satellite-dish';
        label.textContent = scanning ? 'Scan…' : 'Scanner';
    }

    function showScanResult(id, data) {
        const box    = document.getElementById('scan-result-' + id);
        if (!box) return;
        box.classList.remove('error');
        box.querySelector('.scan-result-title i').className = 'fa-solid fa-circle-check';
        box.querySelector('.scan-result-msg').textContent   = data.message;
        box.querySelector('.scan-method-note').textContent  = data.note || '';

        const ipsEl = box.querySelector('.scan-result-ips');
        ipsEl.innerHTML = '';
        (data.discovered_ips || []).slice(0, 24).forEach(ip => {
            const tag = document.createElement('span');
            tag.className = 'scan-ip-tag'; tag.textContent = ip;
            ipsEl.appendChild(tag);
        });
        if ((data.discovered_ips || []).length > 24) {
            const more = document.createElement('span');
            more.className = 'scan-ip-tag';
            more.textContent = '+' + (data.discovered_ips.length - 24) + ' autres';
            ipsEl.appendChild(more);
        }

        box.style.display = 'block';

        // Mise à jour des compteurs live
        const ls = document.getElementById('last-scan-' + id);
        const hc = document.getElementById('host-count-' + id);
        const fc = document.getElementById('found-count-' + id);
        if (ls) ls.textContent = 'À l\'instant';
        if (hc) hc.innerHTML  = (data.hosts_detected || 0) + ' <span style="font-size:11px;color:var(--text-muted);font-weight:700;">/ ' + (data.hosts_detected || 0) + '</span>';
        if (fc) fc.innerHTML  = (data.hosts_detected || 0) + ' <span style="font-size:10px;color:var(--text-muted);font-weight:700;">hôtes</span>';
    }

    function showScanError(id, message) {
        const box = document.getElementById('scan-result-' + id);
        if (!box) return;
        box.classList.add('error');
        box.querySelector('.scan-result-title i').className = 'fa-solid fa-triangle-exclamation';
        box.querySelector('.scan-result-msg').textContent   = message;
        box.querySelector('.scan-method-note').textContent  = '';
        box.querySelector('.scan-result-ips').innerHTML     = '';
        box.style.display = 'block';
    }

    // ── Scan individuel ───────────────────────────────────────────────────────
    document.querySelectorAll('.btn-scan').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id   = this.dataset.networkId;
            const url  = this.dataset.url;
            const csrf = this.dataset.csrf;
            setScanning(id, true);
            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({}),
                });
                const data = await resp.json();
                (resp.ok && data.success) ? showScanResult(id, data) : showScanError(id, data.message || 'Erreur lors du scan.');
            } catch (err) {
                showScanError(id, 'Erreur réseau : ' + err.message);
            } finally {
                setScanning(id, false);
            }
        });
    });

    // ── Détection globale ─────────────────────────────────────────────────────
    document.getElementById('btn-detect-all')?.addEventListener('click', async function() {
        const url  = this.dataset.url;
        const csrf = this.dataset.csrf;
        const icon  = document.getElementById('detect-icon');
        const label = document.getElementById('detect-label');
        const banner = document.getElementById('detect-banner');
        const bannerText = document.getElementById('detect-banner-text');

        this.disabled = true;
        icon.className = 'fa-solid fa-circle-notch btn-spinner';
        label.textContent = 'Détection en cours…';
        banner.style.display = 'none';

        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({}),
            });
            const data = await resp.json();
            bannerText.textContent = resp.ok && data.success
                ? data.message
                : (data.message || 'Erreur lors de la détection.');
            banner.style.display = 'flex';
            if (resp.ok && data.success) {
                setTimeout(() => window.location.reload(), 2000);
            } else {
                banner.style.color = '#ef4444';
            }
        } catch (err) {
            bannerText.textContent = 'Erreur réseau : ' + err.message;
            banner.style.display = 'flex';
        } finally {
            this.disabled = false;
            icon.className = 'fa-solid fa-magnifying-glass-location';
            label.textContent = 'Détecter réseaux locaux';
        }
    });
    </script>
@endsection
