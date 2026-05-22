@extends('layouts.soc')

@section('title', 'RansomShield — Agents')
@section('page_title', 'Machines surveillées')
@section('page_subtitle', 'Agents enrôlés, pré-enrôlés et machines à risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskClass = fn($r) => match($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        $enrollClass = fn($s) => match($s) {
            'pending'  => 'badge-high',
            'enrolled' => 'badge-normal',
            default    => 'badge-suspect',
        };

        $enrollLabel = fn($s) => match($s) {
            'pending'  => 'À enrôler',
            'enrolled' => 'Enrôlé',
            default    => $s,
        };

        $statusFilters = [
            'all'      => ['label' => 'Tous',       'icon' => 'fa-layer-group'],
            'enrolled' => ['label' => 'Enrôlés',    'icon' => 'fa-circle-check'],
            'pending'  => ['label' => 'À enrôler',  'icon' => 'fa-circle-dot'],
            'critical' => ['label' => 'Critiques',  'icon' => 'fa-circle-exclamation'],
        ];
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .agent-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 14% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 88% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .agent-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .93;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .agent-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 820px;
            margin-top: 14px;
        }

        /* ── FILTER TABS ──────────────────────────────────────────────────── */
        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            align-items: center;
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 14px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            transition: .15s ease;
        }

        .filter-tab:hover {
            background: color-mix(in srgb, var(--accent) 7%, transparent);
            color: var(--accent);
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        .filter-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        /* ── AGENT GRID ───────────────────────────────────────────────────── */
        .agent-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .agent-card {
            border-radius: 26px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .agent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 48px rgba(2, 6, 23, .18);
        }

        .agent-card.risk-critical { border-left: 3px solid #ef4444; }
        .agent-card.risk-high     { border-left: 3px solid #fb923c; }
        .agent-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .agent-card.risk-normal   { border-left: 3px solid #22c55e; }
        .agent-card.risk-pending  { border-left: 3px solid #6366f1; }

        .agent-card-inner {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            padding: 18px 18px 0 0;
        }

        .ag-icon-col {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2px 0 0 14px;
        }

        .ag-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .ag-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .ag-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .ag-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .ag-icon.normal   { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }
        .ag-icon.pending  { background: color-mix(in srgb, #6366f1 12%, transparent); color: #6366f1; }

        .ag-body { min-width: 0; }

        .ag-name {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Online pulse dot */
        .ag-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .ag-dot.online  { background: #22c55e; box-shadow: 0 0 0 3px color-mix(in srgb, #22c55e 22%, transparent); }
        .ag-dot.offline { background: #64748b; }
        .ag-dot.pending { background: #6366f1; box-shadow: 0 0 0 3px color-mix(in srgb, #6366f1 22%, transparent); }
        .ag-dot.isolated{ background: #ef4444; box-shadow: 0 0 0 3px color-mix(in srgb, #ef4444 22%, transparent); }

        .ag-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .ag-meta .mono {
            font-family: monospace;
            font-size: 11px;
            letter-spacing: .01em;
        }

        .ag-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        /* Strip */
        .ag-strip {
            margin-top: 14px;
            padding: 10px 18px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 8px;
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .ag-strip .spacer { flex: 1; }

        .ag-strip .last-seen {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1100px) { .agent-grid { grid-template-columns: 1fr; } }

        @media (max-width: 640px) {
            .agent-card-inner { grid-template-columns: 1fr; }
            .ag-icon-col { padding: 14px 14px 0; justify-content: flex-start; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="agent-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Parc machines RansomShield
            </div>

            <h2>Machines surveillées.</h2>

            <p>
                Un hôte détecté peut être pré-enrôlé. Lorsqu'un agent Python s'installe et appelle l'API,
                la machine passe en enrôlée et commence à transmettre ses événements.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-network-wired"></i> Hôtes détectés
                </a>
                <a href="{{ route('platform.dashboard') }}" class="btn btn-soft">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </div>
        </section>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-display" style="color:var(--accent); margin-right:6px;"></i>
                    Total
                </div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Agents créés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e; margin-right:6px;"></i>
                    Enrôlés
                </div>
                <div class="smart-stat-value" style="{{ $stats['enrolled'] > 0 ? 'color:#22c55e;' : '' }}">
                    {{ $stats['enrolled'] }}
                </div>
                <div class="smart-stat-hint">Machines actives.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-dot" style="color:#6366f1; margin-right:6px;"></i>
                    À enrôler
                </div>
                <div class="smart-stat-value" style="{{ $stats['pending'] > 0 ? 'color:#6366f1;' : '' }}">
                    {{ $stats['pending'] }}
                </div>
                <div class="smart-stat-hint">Installation agent attendue.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-exclamation" style="color:#ef4444; margin-right:6px;"></i>
                    Critiques
                </div>
                <div class="smart-stat-value" style="{{ $stats['critical'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['critical'] }}
                </div>
                <div class="smart-stat-hint">Agents à risque élevé.</div>
            </div>
        </section>

        {{-- ── FILTER TABS ──────────────────────────────────────────────── --}}
        <div class="filter-tabs section-gap">
            @foreach($statusFilters as $key => $filter)
                <a href="{{ route('platform.agents.index', ['status' => $key]) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                </a>
            @endforeach
        </div>

        {{-- ── AGENT LIST ───────────────────────────────────────────────── --}}
        @if($agents->count())
            <div class="agent-grid section-gap">
                @foreach($agents as $agent)
                    @php
                        $risk       = $agent->risk_level ?? 'normal';
                        $enroll     = $agent->enrollment_status ?? 'enrolled';
                        $isIsolated = $agent->is_isolated ?? false;
                        $isOnline   = $enroll === 'enrolled' && $agent->last_seen_at?->gt(now()->subMinutes(10));
                        $isPending  = $enroll === 'pending';

                        $dotClass = match(true) {
                            $isIsolated => 'isolated',
                            $isPending  => 'pending',
                            $isOnline   => 'online',
                            default     => 'offline',
                        };

                        $agIcon = match($agent->host_role ?? '') {
                            'server'      => 'fa-server',
                            'workstation' => 'fa-display',
                            default       => 'fa-laptop',
                        };

                        $iconClass = $isPending ? 'pending' : $risk;
                        $cardRisk  = $isPending ? 'pending' : $risk;
                    @endphp

                    <article class="agent-card risk-{{ $cardRisk }}">
                        <div class="agent-card-inner">
                            <div class="ag-icon-col">
                                <div class="ag-icon {{ $iconClass }}">
                                    @if($isIsolated)
                                        <i class="fa-solid fa-plug-circle-xmark"></i>
                                    @else
                                        <i class="fa-solid {{ $agIcon }}"></i>
                                    @endif
                                </div>
                            </div>

                            <div class="ag-body">
                                <h3 class="ag-name">
                                    <span class="ag-dot {{ $dotClass }}"></span>
                                    {{ $agent->agent_name }}
                                </h3>

                                <div class="ag-meta">
                                    @if($agent->hostname)
                                        <i class="fa-solid fa-tag" style="font-size:9px;"></i>
                                        {{ $agent->hostname }}
                                        &nbsp;—&nbsp;
                                    @endif
                                    <i class="fa-solid fa-ethernet" style="font-size:9px;"></i>
                                    <span class="mono">{{ $agent->ip_address ?? '—' }}</span>
                                    <br>
                                    <i class="fa-solid fa-fingerprint" style="font-size:9px;"></i>
                                    <span class="mono">{{ Str::limit($agent->agent_uuid, 24) }}</span>
                                </div>

                                <div class="ag-badges">
                                    <span class="badge {{ $enrollClass($enroll) }}">
                                        <i class="fa-solid {{ $isPending ? 'fa-circle-dot' : 'fa-circle-check' }}" style="font-size:8px; margin-right:3px;"></i>
                                        {{ $enrollLabel($enroll) }}
                                    </span>
                                    <span class="badge {{ $riskClass($risk) }}">
                                        <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i>
                                        {{ $risk }}
                                    </span>
                                    <span class="badge">Score {{ $agent->risk_score ?? 0 }}</span>
                                    @if($isIsolated)
                                        <span class="badge badge-critical">
                                            <i class="fa-solid fa-plug-circle-xmark" style="font-size:8px; margin-right:3px;"></i>
                                            Isolé
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="ag-strip">
                            <span class="last-seen">
                                <i class="fa-regular fa-clock"></i>
                                {{ $agent->last_seen_at?->diffForHumans() ?? 'Jamais vu' }}
                            </span>
                            <div class="spacer"></div>
                            <a href="{{ route('platform.agents.show', $agent) }}" class="action-btn primary">
                                <i class="fa-solid fa-arrow-right"></i> Ouvrir
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $agents->links() }}</div>

        @else
            <div class="section-gap">
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucun agent pour ce filtre.',
                    'message' => "Va dans Hôtes détectés puis clique sur « Enrôler » pour créer un agent.",
                ])
            </div>
        @endif

    </div>
@endsection
