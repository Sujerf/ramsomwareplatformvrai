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

        // [Amélioration 1] Badge statut — couleurs sémantiques correctes
        $statusClass = fn($s) => match($s) {
            'open'           => 'badge-status-open',
            'investigating'  => 'badge-status-investigating',
            'under_review'   => 'badge-status-review',
            'reopened'       => 'badge-status-reopened',
            'resolved'       => 'badge-status-resolved',
            'false_positive' => 'badge-status-false-positive',
            default          => 'badge',
        };

        $statusIcon = fn($s) => match($s) {
            'open'           => 'fa-circle-dot',
            'investigating'  => 'fa-magnifying-glass',
            'under_review'   => 'fa-eye',
            'reopened'       => 'fa-rotate-right',
            'resolved'       => 'fa-circle-check',
            'false_positive' => 'fa-circle-xmark',
            default          => 'fa-circle',
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

        // [Amélioration 7] Hero gradient adaptatif
        $heroGradient = match($activeStatus) {
            'resolved'       => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #22c55e 16%, transparent), transparent 28%),
                                 radial-gradient(circle at 88% 10%, color-mix(in srgb, #86efac 10%, transparent), transparent 32%)',
            'false_positive' => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #94a3b8 14%, transparent), transparent 28%),
                                 radial-gradient(circle at 88% 10%, color-mix(in srgb, #64748b 8%, transparent), transparent 32%)',
            default          => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #fb923c 16%, transparent), transparent 28%),
                                 radial-gradient(circle at 88% 10%, color-mix(in srgb, #ef4444 12%, transparent), transparent 32%)',
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
            'archived'       => ['label' => 'Archivés',      'icon' => 'fa-box-archive'],
            'all'            => ['label' => 'Tous',          'icon' => 'fa-list'],
        ];

        $riskFilters = [
            ''         => ['label' => 'Tous risques', 'icon' => 'fa-layer-group'],
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
            /* [Amélioration 7] gradient injecté via PHP */
            background:
                {{ $heroGradient }},
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

        /* [Amélioration 3] Compteur dans les onglets */
        .filter-tab .ft-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 900;
            background: color-mix(in srgb, currentColor 15%, transparent);
            line-height: 1;
        }

        .filter-tab.active .ft-count {
            background: rgba(255,255,255,.25);
            color: #fff;
        }

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

        /* ── Meta ligne ─────────────────────────────────────────────────── */
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

        /* [Amélioration 5] IP agent */
        .inc-agent-ip {
            font-size: 11px;
            font-family: monospace;
            color: var(--text-muted);
            opacity: .75;
        }

        /* [Amélioration 4] Âge urgency */
        .inc-meta-item.age-warning  { color: #f59e0b; font-weight: 800; }
        .inc-meta-item.age-critical { color: #ef4444; font-weight: 800; }

        /* ── Score mini-bar [Amélioration 2] ─────────────────────────── */
        .inc-score-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: default;
        }

        .inc-score-num {
            font-size: 12px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--text-main);
            min-width: 24px;
            text-align: right;
        }

        .inc-score-track {
            width: 64px;
            height: 5px;
            border-radius: 3px;
            background: var(--border-soft);
            overflow: hidden;
        }

        .inc-score-fill {
            height: 100%;
            border-radius: 3px;
            transition: width .3s ease;
        }

        .inc-score-fill.critical { background: #ef4444; }
        .inc-score-fill.high     { background: #fb923c; }
        .inc-score-fill.suspect  { background: #f59e0b; }
        .inc-score-fill.normal   { background: #22c55e; }

        /* [Amélioration 1] Badges statut sémantiques */
        .badge-status-open {
            background: color-mix(in srgb, #ef4444 12%, transparent);
            color: #ef4444;
            border: 1px solid color-mix(in srgb, #ef4444 25%, transparent);
        }
        .badge-status-investigating {
            background: color-mix(in srgb, #8b5cf6 12%, transparent);
            color: #8b5cf6;
            border: 1px solid color-mix(in srgb, #8b5cf6 25%, transparent);
        }
        .badge-status-review {
            background: color-mix(in srgb, #3b82f6 12%, transparent);
            color: #3b82f6;
            border: 1px solid color-mix(in srgb, #3b82f6 25%, transparent);
        }
        .badge-status-reopened {
            background: color-mix(in srgb, #fb923c 12%, transparent);
            color: #fb923c;
            border: 1px solid color-mix(in srgb, #fb923c 25%, transparent);
        }
        .badge-status-resolved {
            background: color-mix(in srgb, #22c55e 12%, transparent);
            color: #22c55e;
            border: 1px solid color-mix(in srgb, #22c55e 25%, transparent);
        }
        .badge-status-false-positive {
            background: color-mix(in srgb, #94a3b8 10%, transparent);
            color: #64748b;
            border: 1px solid color-mix(in srgb, #94a3b8 22%, transparent);
        }

        /* [Amélioration 6] Badge compteur alertes liées */
        .badge-alerts-count {
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-variant-numeric: tabular-nums;
        }

        /* ── Strip ──────────────────────────────────────────────────────── */
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
                <a href="{{ route('platform.incidents.export-list', array_filter(['status' => $activeStatus, 'risk' => $activeRisk])) }}" class="btn btn-soft">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
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
                    <i class="fa-solid fa-ban" style="color:#94a3b8; margin-right:6px;"></i>
                    Faux positifs
                </div>
                <div class="smart-stat-value">
                    {{ $stats['false_positive'] }}
                </div>
                <div class="smart-stat-hint">Écartés — non retenus.</div>
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
            {{-- Filtres statut --}}
            @foreach($statusFilters as $key => $filter)
                @php $cnt = $filterCounts['status'][$key] ?? 0; @endphp
                @php
                    $href = route('platform.incidents.index', array_filter(
                        ['status' => $key, 'risk' => $activeRisk],
                        fn($v) => $v !== null && $v !== ''
                    ));
                @endphp
                <a href="{{ $href }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                    @if($cnt > 0)
                        <span class="ft-count">{{ $cnt }}</span>
                    @endif
                </a>
            @endforeach

            <div class="filter-sep"></div>

            {{-- Filtres risque --}}
            @foreach($riskFilters as $riskKey => $filter)
                @php $cnt = $filterCounts['risk'][$riskKey] ?? 0; @endphp
                @php
                    $riskHref = route('platform.incidents.index', array_filter(
                        ['status' => $activeStatus, 'risk' => $riskKey],
                        fn($v) => $v !== null && $v !== ''
                    ));
                @endphp
                <a href="{{ $riskHref }}"
                   class="filter-tab {{ $activeRisk === $riskKey ? 'active' : '' }} {{ $riskKey ? 'risk-'.$riskKey : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                    @if($cnt > 0)
                        <span class="ft-count">{{ $cnt }}</span>
                    @endif
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

                        // [Amélioration 4] Âge urgency
                        $detectedAt = $incident->detected_at ?? $incident->created_at;
                        $ageMinutes = $detectedAt ? $detectedAt->diffInMinutes(now()) : 0;
                        $ageClass   = (!$isDone && $ageMinutes >= 480) ? 'age-critical'
                                    : ((!$isDone && $ageMinutes >= 120) ? 'age-warning' : '');
                        $ageLabel   = $ageMinutes >= 480
                                        ? '⚠ ' . $detectedAt->diffForHumans()
                                        : ($detectedAt?->diffForHumans() ?? '—');

                        // [Amélioration 2] Score mini-bar (max 200pts = 100%)
                        $score         = $incident->risk_score ?? 0;
                        $scoreBarWidth = min(100, round($score / 200 * 100));
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
                                        {{-- Badge risque --}}
                                        <span class="badge {{ $riskClass($risk) }}">
                                            <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i>
                                            {{ $risk }}
                                        </span>

                                        {{-- [Amélioration 1] Badge statut coloré --}}
                                        <span class="badge {{ $statusClass($status) }}">
                                            <i class="fa-solid {{ $statusIcon($status) }}"></i>
                                            {{ $statusLabel($status) }}
                                        </span>

                                        {{-- [Amélioration 2] Score mini-bar --}}
                                        <div class="inc-score-wrap" title="Score de risque : {{ $score }} / 200">
                                            <span class="inc-score-num">{{ $score }}</span>
                                            <div class="inc-score-track">
                                                <div class="inc-score-fill {{ $risk }}" style="width:{{ $scoreBarWidth }}%"></div>
                                            </div>
                                        </div>

                                        {{-- [Amélioration 6] Compteur alertes liées --}}
                                        @if(($incident->alerts_count ?? 0) > 0)
                                            <span class="badge badge-alerts-count">
                                                <i class="fa-solid fa-bell"></i>
                                                {{ $incident->alerts_count }} {{ Str::plural('alerte', $incident->alerts_count) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($incident->description)
                                    <p class="inc-description">{{ Str::limit($incident->description, 120) }}</p>
                                @endif

                                <div class="inc-meta">
                                    {{-- [Amélioration 5] Agent + IP --}}
                                    <span class="inc-meta-item">
                                        <i class="fa-solid fa-robot"></i>
                                        {{ $incident->agent?->agent_name ?? 'Agent inconnu' }}
                                        @if($incident->agent?->ip_address)
                                            <span class="inc-agent-ip">({{ $incident->agent->ip_address }})</span>
                                        @endif
                                    </span>

                                    {{-- [Amélioration 4] Âge coloré --}}
                                    <span class="inc-meta-item {{ $ageClass }}">
                                        <i class="fa-regular fa-clock"></i>
                                        {{ $ageLabel }}
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

                            @if($incident->isArchived())
                                <form method="POST" action="{{ route('platform.incidents.unarchive', $incident) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn">
                                        <i class="fa-solid fa-box-open"></i> Restaurer
                                    </button>
                                </form>
                            @else
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

                                    <form method="POST" action="{{ route('platform.incidents.archive', $incident) }}" style="display:contents;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="action-btn"
                                            style="color:var(--text-muted);border-color:var(--border-color);"
                                            title="Archiver cet incident">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Bouton purge (admin, onglet archivés seulement) --}}
            @if($activeStatus === 'archived' && $filterCounts['status']['archived'] > 0 && auth()->user()->isAdmin())
            <div style="margin-top:16px; padding:14px 18px; background:rgba(239,68,68,.06);
                border:1px solid rgba(239,68,68,.2); border-radius:12px;
                display:flex; align-items:center; gap:12px;">
                <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>
                <span style="font-size:13px; color:var(--text-primary); flex:1;">
                    <strong>{{ $filterCounts['status']['archived'] }} incident(s) archivé(s)</strong>
                    — la suppression est irréversible et effacera toutes les données associées.
                </span>
                <form method="POST" action="{{ route('platform.incidents.purge') }}"
                      onsubmit="return confirm('Supprimer définitivement les {{ $filterCounts['status']['archived'] }} incident(s) archivé(s) ? Cette action est IRRÉVERSIBLE.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-btn"
                        style="background:rgba(239,68,68,.12);color:#ef4444;border-color:rgba(239,68,68,.3);">
                        <i class="fa-solid fa-trash-can"></i>
                        Purger {{ $filterCounts['status']['archived'] }} incident(s)
                    </button>
                </form>
            </div>
            @endif

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
