@extends('layouts.soc')

@section('title', 'RansomShield — Incidents')
@section('page_title', 'Incidents')
@section('page_subtitle', 'Suivi, investigation et historique des incidents ransomware')

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

        $statusClass = fn($s) => match($s) {
            'resolved'                                   => 'badge-normal',
            'false_positive'                             => 'badge-suspect',
            'under_review', 'investigating', 'reopened' => 'badge-high',
            default                                      => 'badge-critical',
        };

        $statusLabel = fn($s) => match($s) {
            'open'           => 'Ouvert',
            'investigating'  => 'Enquête',
            'under_review'   => 'En révision',
            'reopened'       => 'Réouvert',
            'resolved'       => 'Résolu',
            'false_positive' => 'Faux positif',
            default          => $s,
        };

        $heroTitle = match($activeStatus) {
            'resolved'       => 'Incidents résolus.',
            'false_positive' => 'Faux positifs.',
            'all'            => 'Tous les incidents.',
            default          => 'Incidents actifs.',
        };

        $statusFilters = [
            'active'         => ['label' => 'Actifs',        'icon' => 'fa-fire-flame-curved'],
            'resolved'       => ['label' => 'Résolus',       'icon' => 'fa-circle-check'],
            'false_positive' => ['label' => 'Faux positifs', 'icon' => 'fa-ban'],
            'all'            => ['label' => 'Tous',          'icon' => 'fa-list'],
        ];

        $riskFilters = [
            null       => ['label' => 'Tous risques', 'icon' => 'fa-layer-group'],
            'critical' => ['label' => 'Critical',     'icon' => 'fa-circle-exclamation'],
            'high'     => ['label' => 'High',         'icon' => 'fa-triangle-exclamation'],
            'suspect'  => ['label' => 'Suspect',      'icon' => 'fa-eye'],
            'normal'   => ['label' => 'Normal',       'icon' => 'fa-shield-halved'],
        ];
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .inc-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 14% 18%, color-mix(in srgb, #fb923c 16%, transparent), transparent 28%),
                radial-gradient(circle at 88% 10%, color-mix(in srgb, #ef4444 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .inc-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .93;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .inc-hero p {
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

        .filter-tab.risk-critical.active { background: #ef4444; border-color: #ef4444; }
        .filter-tab.risk-high.active     { background: #fb923c; border-color: #fb923c; }
        .filter-tab.risk-suspect.active  { background: #f59e0b; border-color: #f59e0b; color: #111; }
        .filter-tab.risk-normal.active   { background: #22c55e; border-color: #22c55e; }

        .filter-sep {
            width: 1px;
            height: 24px;
            background: var(--border-soft);
            margin: 0 4px;
            flex-shrink: 0;
        }

        /* ── INCIDENT CARD ────────────────────────────────────────────────── */
        .incident-list { display: grid; gap: 0; }

        .incident-card {
            border-radius: 26px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            margin-bottom: 14px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .incident-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 48px rgba(2, 6, 23, .18);
        }

        .incident-card.risk-critical { border-left: 3px solid #ef4444; }
        .incident-card.risk-high     { border-left: 3px solid #fb923c; }
        .incident-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .incident-card.risk-normal   { border-left: 3px solid #22c55e; }

        .inc-card-inner {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
        }

        .inc-icon-col {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px 0 20px 12px;
        }

        .inc-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .inc-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .inc-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .inc-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .inc-icon.normal   { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }

        .inc-body { padding: 18px 18px 0 12px; }

        .inc-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .inc-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .inc-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        .inc-description {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .inc-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .inc-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Strip */
        .inc-strip {
            margin-top: 14px;
            padding: 10px 18px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .inc-strip .spacer { flex: 1; }

        /* mobile : boutons pleine largeur */

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 760px) {
            .inc-card-inner { grid-template-columns: 1fr; }
            .inc-icon-col   { padding: 14px 14px 0; justify-content: flex-start; }
            .inc-body       { padding: 12px 14px 0; }
        }

        @media (max-width: 540px) {
            .filter-tabs { padding: 10px; }
            .filter-sep  { display: none; }
            .inc-strip .action-btn { width: 100%; justify-content: center; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="inc-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Centre incidents SOC
            </div>

            <h2>{{ $heroTitle }}</h2>

            <p>
                Les incidents regroupent alertes, signaux, actions et décisions SOC.
                Un incident résolu reste consultable dans l'historique complet.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.dashboard') }}" class="btn btn-soft">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="{{ route('platform.approval-queue.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-circle-dot"></i> File d'approbation
                </a>
            </div>
        </section>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-fire-flame-curved" style="color:#fb923c; margin-right:6px;"></i>
                    Actifs
                </div>
                <div class="smart-stat-value" style="{{ $stats['active'] > 0 ? 'color:#fb923c;' : '' }}">
                    {{ $stats['active'] }}
                </div>
                <div class="smart-stat-hint">À investiguer ou contenir.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-exclamation" style="color:#ef4444; margin-right:6px;"></i>
                    Critical
                </div>
                <div class="smart-stat-value" style="{{ $stats['critical'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['critical'] }}
                </div>
                <div class="smart-stat-hint">Menaces de niveau critique.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e; margin-right:6px;"></i>
                    Résolus
                </div>
                <div class="smart-stat-value" style="{{ $stats['resolved'] > 0 ? 'color:#22c55e;' : '' }}">
                    {{ $stats['resolved'] }}
                </div>
                <div class="smart-stat-hint">Incidents traités avec succès.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-layer-group" style="color:var(--accent); margin-right:6px;"></i>
                    Total
                </div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Tous incidents confondus.</div>
            </div>
        </section>

        {{-- ── FILTER TABS ──────────────────────────────────────────────── --}}
        <div class="filter-tabs section-gap">
            @foreach($statusFilters as $key => $filter)
                <a href="{{ route('platform.incidents.index', array_filter(['status' => $key, 'risk' => $activeRisk])) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                </a>
            @endforeach

            <div class="filter-sep"></div>

            @foreach($riskFilters as $riskKey => $filter)
                <a href="{{ route('platform.incidents.index', array_filter(['status' => $activeStatus, 'risk' => $riskKey])) }}"
                   class="filter-tab {{ $activeRisk === $riskKey ? 'active' : '' }} {{ $riskKey ? 'risk-'.$riskKey : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                </a>
            @endforeach
        </div>

        {{-- ── INCIDENT LIST ────────────────────────────────────────────── --}}
        @if($incidents->count())
            <div class="incident-list section-gap">
                @foreach($incidents as $incident)
                    @php
                        $risk   = $incident->risk_level ?? 'normal';
                        $status = $incident->status;
                        $isDone = in_array($status, ['resolved', 'false_positive'], true);

                        $incIcon = match(true) {
                            in_array($risk, ['critical', 'high'], true) => 'fa-fire-flame-curved',
                            $risk === 'suspect'                          => 'fa-triangle-exclamation',
                            default                                      => 'fa-shield-halved',
                        };
                    @endphp

                    <article class="incident-card risk-{{ $risk }}">
                        <div class="inc-card-inner">

                            <div class="inc-icon-col">
                                <div class="inc-icon {{ $risk }}">
                                    <i class="fa-solid {{ $incIcon }}"></i>
                                </div>
                            </div>

                            <div class="inc-body">
                                <div class="inc-head">
                                    <h3 class="inc-title">{{ $incident->title }}</h3>
                                    <div class="inc-badges">
                                        <span class="badge {{ $riskClass($risk) }}">
                                            <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i>
                                            {{ $risk }}
                                        </span>
                                        <span class="badge {{ $statusClass($status) }}">{{ $statusLabel($status) }}</span>
                                        <span class="badge">Score {{ $incident->risk_score }}</span>
                                    </div>
                                </div>

                                @if($incident->description)
                                    <p class="inc-description">{{ Str::limit($incident->description, 120) }}</p>
                                @endif

                                <div class="inc-meta">
                                    <span class="inc-meta-item">
                                        <i class="fa-solid fa-robot"></i>
                                        {{ $incident->agent?->agent_name ?? 'Agent inconnu' }}
                                    </span>
                                    <span class="inc-meta-item">
                                        <i class="fa-regular fa-clock"></i>
                                        {{ $incident->detected_at?->diffForHumans() ?? $incident->created_at?->diffForHumans() ?? '—' }}
                                    </span>
                                    @if($incident->resolved_at)
                                        <span class="inc-meta-item" style="color:#22c55e;">
                                            <i class="fa-solid fa-circle-check"></i>
                                            Résolu {{ $incident->resolved_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="inc-strip">
                            <span style="font-size:11px; color:var(--text-muted);">
                                <i class="fa-solid fa-hashtag" style="font-size:9px;"></i> {{ $incident->id }}
                            </span>

                            <div class="spacer"></div>

                            <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn">
                                <i class="fa-solid fa-magnifying-glass"></i> Voir
                            </a>

                            @if(!$isDone)
                                <form method="POST" action="{{ route('platform.incidents.resolve', $incident) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn success">
                                        <i class="fa-solid fa-circle-check"></i> Résoudre
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('platform.incidents.false-positive', $incident) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn warning">
                                        <i class="fa-solid fa-ban"></i> Faux positif
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('platform.incidents.reopen', $incident) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn primary">
                                        <i class="fa-solid fa-rotate-right"></i> Réouvrir
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $incidents->links() }}</div>

        @else
            <div class="section-gap">
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucun incident pour ce filtre.',
                    'message' => "Change le filtre de statut ou de risque, ou lance un test depuis l'agent.",
                ])
            </div>
        @endif

    </div>
@endsection
