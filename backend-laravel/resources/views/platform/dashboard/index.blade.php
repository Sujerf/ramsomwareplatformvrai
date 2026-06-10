@extends('layouts.soc')

@section('title', 'RansomShield — Dashboard SOC')
@section('page_title', 'Dashboard SOC')
@section('page_subtitle', 'Centre opérationnel de surveillance, détection et réponse')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskTotal = max(1, array_sum($riskDistribution));

        $criticalPercent = round(($riskDistribution['critical'] / $riskTotal) * 100);
        $highPercent     = round(($riskDistribution['high']     / $riskTotal) * 100);
        $suspectPercent  = round(($riskDistribution['suspect']  / $riskTotal) * 100);
        $normalPercent   = round(($riskDistribution['normal']   / $riskTotal) * 100);

        $globalLevel = match (true) {
            $stats['active_incidents'] > 0 || $stats['critical_agents'] > 0 => 'critical',
            $stats['active_alerts']    > 0 || $stats['pending_actions'] > 0 => 'high',
            $stats['agents_total']     > 0                                  => 'normal',
            default                                                          => 'suspect',
        };

        $globalLabel = match ($globalLevel) {
            'critical' => 'Attention critique',
            'high'     => 'Surveillance renforcée',
            'normal'   => 'Situation maîtrisée',
            default    => 'En attente de données',
        };

        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high'     => 'badge-high',
                'suspect'  => 'badge-suspect',
                default    => 'badge-normal',
            };
        };

        $dashboardCharts = $chartData ?? [
            'labels'           => [],
            'alerts'           => [],
            'incidents'        => [],
            'actions'          => [],
            'risk_by_alert'    => [],
            'actions_by_status'=> [],
        ];

        $ss = $surveillanceSettings ?? collect();

        $boolSetting = function ($key) use ($ss) {
            $s = $ss->get($key);
            return $s && $s->value === '1';
        };

        $strSetting = function ($key, $default = 'high') use ($ss) {
            $s = $ss->get($key);
            return $s ? $s->value : $default;
        };

        $settingId = function ($key) use ($ss) {
            $s = $ss->get($key);
            return $s ? $s->id : null;
        };
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .dashboard-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 20%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 28%),
                radial-gradient(circle at 80% 10%, color-mix(in srgb, var(--accent-2) 14%, transparent), transparent 30%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .dashboard-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, .6fr);
            gap: 24px;
            align-items: center;
        }

        .dashboard-hero h2 {
            margin: 0;
            font-size: clamp(40px, 5vw, 72px);
            line-height: 1.08;
            letter-spacing: -.06em;
            font-weight: 950;
        }

        .dashboard-hero p {
            margin: 16px 0 0;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 720px;
        }

        /* ── STATUS ORB ───────────────────────────────────────────────────── */
        .status-orb {
            position: relative;
            width: min(100%, 260px);
            aspect-ratio: 1;
            margin: auto;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: conic-gradient(
                color-mix(in srgb, var(--accent)   80%, transparent)   0   35%,
                color-mix(in srgb, #ef4444         80%, transparent)  35%  55%,
                color-mix(in srgb, #f59e0b         80%, transparent)  55%  72%,
                color-mix(in srgb, var(--accent-2) 80%, transparent)  72% 100%
            );
            box-shadow: 0 24px 70px rgba(2, 6, 23, .22);
            animation: rsPulse 4s ease-in-out infinite;
        }

        .status-orb::before {
            content: "";
            position: absolute;
            inset: 18px;
            border-radius: inherit;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
        }

        .status-orb-content {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 20px;
        }

        .status-orb-content strong {
            display: block;
            font-size: 36px;
            line-height: 1;
            letter-spacing: -.06em;
        }

        .status-orb-content span {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        @keyframes rsPulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.022); }
        }

        /* ── SURVEILLANCE PANEL ───────────────────────────────────────────── */
        .surveillance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .surv-card {
            padding: 16px;
            border-radius: 22px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: border-color .22s;
        }

        .surv-card.active-card {
            border-color: color-mix(in srgb, var(--accent-2) 35%, transparent);
            background:
                radial-gradient(circle at 90% 10%, color-mix(in srgb, var(--accent-2) 8%, transparent), transparent 50%),
                var(--bg-card);
        }

        .surv-card.danger-card {
            border-color: color-mix(in srgb, #ef4444 30%, transparent);
            background:
                radial-gradient(circle at 90% 10%, color-mix(in srgb, #ef4444 7%, transparent), transparent 50%),
                var(--bg-card);
        }

        .surv-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            font-size: 18px;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            color: var(--accent);
        }

        .surv-icon.green  { background: color-mix(in srgb, var(--accent-2) 12%, transparent); color: var(--accent-2); }
        .surv-icon.red    { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .surv-icon.orange { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .surv-icon.purple { background: color-mix(in srgb, #a855f7 12%, transparent); color: #a855f7; }

        .surv-body {
            flex: 1;
            min-width: 0;
        }

        .surv-label {
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
        }

        .surv-title {
            margin: 3px 0 0;
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .surv-desc {
            margin-top: 4px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .surv-control {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        /* Toggle switch */
        .toggle-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 52px;
            height: 28px;
            flex-shrink: 0;
            cursor: pointer;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-track {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: color-mix(in srgb, var(--text-muted) 18%, transparent);
            border: 1px solid var(--border-soft);
            transition: .22s ease;
        }

        .toggle-track::before {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            left: 3px;
            top: 3px;
            border-radius: 999px;
            background: var(--text-muted);
            transition: .22s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,.3);
        }

        .toggle-switch input:checked + .toggle-track {
            background: var(--accent-2);
            border-color: var(--accent-2);
        }

        .toggle-switch input:checked + .toggle-track::before {
            transform: translateX(24px);
            background: white;
        }

        .toggle-switch input:focus + .toggle-track {
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 25%, transparent);
        }

        .toggle-status {
            font-size: 12px;
            font-weight: 850;
            color: var(--text-muted);
        }

        .toggle-status.on { color: var(--accent-2); }

        /* Risk select */
        .risk-select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--bg-panel-soft) 80%, transparent);
            border: 1px solid var(--border-soft);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 850;
            cursor: pointer;
            outline: none;
            transition: border-color .18s;
            margin-top: 10px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238ea2bd' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 32px;
        }

        .risk-select:focus {
            border-color: var(--accent);
        }

        .risk-select option {
            background: #0d1b2e;
            color: var(--text-main);
        }

        /* ── QUICK ACTIONS ────────────────────────────────────────────────── */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .quick-action {
            padding: 20px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            text-decoration: none;
            color: var(--text-main);
            transition: .2s ease;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .quick-action:hover {
            transform: translateY(-3px);
            border-color: color-mix(in srgb, var(--accent) 35%, transparent);
            box-shadow: 0 16px 48px rgba(2, 6, 23, .18);
        }

        .qa-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 22px;
            margin-bottom: 14px;
            transition: transform .2s;
        }

        .quick-action:hover .qa-icon-wrap {
            transform: scale(1.08);
        }

        .qa-icon-wrap.blue   { background: color-mix(in srgb, var(--accent)   14%, transparent); color: var(--accent); }
        .qa-icon-wrap.green  { background: color-mix(in srgb, var(--accent-2) 14%, transparent); color: var(--accent-2); }
        .qa-icon-wrap.orange { background: color-mix(in srgb, #f59e0b 14%, transparent); color: #f59e0b; }
        .qa-icon-wrap.purple { background: color-mix(in srgb, #a855f7 14%, transparent); color: #a855f7; }
        .qa-icon-wrap.red    { background: color-mix(in srgb, #ef4444 14%, transparent); color: #ef4444; }
        .qa-icon-wrap.cyan   { background: color-mix(in srgb, #22d3ee 14%, transparent); color: #22d3ee; }

        .quick-action strong {
            display: block;
            font-size: 15px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .quick-action small {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .qa-arrow {
            margin-top: 14px;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0;
            transform: translateX(-4px);
            transition: .2s ease;
        }

        .quick-action:hover .qa-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        /* ── CHARTS ───────────────────────────────────────────────────────── */
        .chart-panel {
            position: relative;
            min-height: 340px;
            padding: 18px;
            border-radius: 28px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .chart-panel::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 160px;
            height: 160px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 7%, transparent);
            pointer-events: none;
        }

        .chart-head {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 16px;
        }

        .period-filter {
            display: flex;
            gap: 3px;
            background: color-mix(in srgb, var(--bg-panel-soft) 80%, transparent);
            border: 1px solid var(--border-soft);
            border-radius: 999px;
            padding: 4px;
            flex-shrink: 0;
        }

        .period-btn {
            padding: 5px 13px;
            border: none;
            border-radius: 999px;
            background: transparent;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.18s ease;
            font-family: inherit;
        }

        .period-btn:hover:not(.period-btn-active) {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 12%, transparent);
        }

        .period-btn-active {
            background: var(--accent) !important;
            color: var(--accent-contrast) !important;
        }

        .chart-title {
            margin: 0;
            font-size: 17px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .chart-subtitle {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .chart-box {
            position: relative;
            z-index: 1;
            height: 250px;
        }

        .chart-box canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .chart-grid-premium {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(300px, .7fr);
            gap: 16px;
        }

        .chart-grid-equal {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        /* ── DASHBOARD GRID ───────────────────────────────────────────────── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        /* ── RISK BARS ────────────────────────────────────────────────────── */
        .risk-bars {
            display: grid;
            gap: 14px;
        }

        .risk-row { display: grid; gap: 7px; }

        .risk-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 850;
        }

        .risk-track {
            height: 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--text-muted) 10%, transparent);
            overflow: hidden;
        }

        .risk-fill {
            height: 100%;
            border-radius: inherit;
        }

        /* ── ACTIVITY ITEMS ───────────────────────────────────────────────── */
        .activity-list { display: grid; gap: 10px; }

        .activity-item {
            display: grid;
            grid-template-columns: 42px 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            border: 1px solid var(--border-soft);
            transition: border-color .18s;
        }

        .activity-item:hover {
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        .activity-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            font-size: 16px;
        }

        .activity-icon.red    { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .activity-icon.orange { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .activity-icon.yellow { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .activity-icon.green  { background: color-mix(in srgb, var(--accent-2) 12%, transparent); color: var(--accent-2); }
        .activity-icon.blue   { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); }
        .activity-icon.purple { background: color-mix(in srgb, #a855f7 12%, transparent); color: #a855f7; }

        .activity-title {
            margin: 0;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .activity-subtitle {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 12px;
        }

        /* ── SECTION HEADING ──────────────────────────────────────────────── */
        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .section-heading-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 15px;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            color: var(--accent);
        }

        .section-heading-text h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .section-heading-text p {
            margin: 3px 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* ── TOAST NOTIFICATION ───────────────────────────────────────────── */
        .surv-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            padding: 12px 20px;
            border-radius: 18px;
            background: var(--bg-panel);
            border: 1px solid var(--border-soft);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 850;
            box-shadow: 0 12px 40px rgba(2, 6, 23, .30);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(20px);
            opacity: 0;
            transition: .28s ease;
            pointer-events: none;
        }

        .surv-toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .surv-toast i { color: var(--accent-2); }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1300px) {
            .surveillance-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 1200px) {
            .dashboard-hero-grid,
            .dashboard-grid,
            .chart-grid-premium,
            .chart-grid-equal { grid-template-columns: 1fr; }

            .quick-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 800px) {
            .surveillance-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .dashboard-hero { padding: 20px; border-radius: 24px; }
            .quick-actions  { grid-template-columns: 1fr; }

            .activity-item { grid-template-columns: 42px 1fr; }

            .activity-item > a,
            .activity-item > span:last-child { grid-column: 1 / -1; }
        }
    </style>

    {{-- Toast notification --}}
    <div id="survToast" class="surv-toast">
        <i class="fa-solid fa-check-circle"></i>
        <span id="survToastMsg">Paramètre mis à jour.</span>
    </div>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="dashboard-hero">
            <div class="dashboard-hero-grid">
                <div>
                    <div class="analysis-kicker">
                        <span class="analysis-dot"></span>
                        Centre opérationnel SOC
                    </div>

                    <h2>Surveiller.<br>Détecter.<br>Décider.</h2>

                    <p>
                        RansomShield centralise les signaux des agents, alertes, incidents et actions
                        de protection. Le moteur agit automatiquement ; vous approuvez ou rejetez.
                    </p>

                    <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge {{ $riskClass($globalLevel) }}">{{ $globalLabel }}</span>
                        <span class="badge">
                            <i class="fa-solid fa-microchip" style="font-size:11px; margin-right:4px;"></i>
                            {{ $stats['agents_total'] }} agent(s)
                        </span>
                        <span class="badge">
                            <i class="fa-solid fa-diagram-project" style="font-size:11px; margin-right:4px;"></i>
                            {{ $stats['monitored_networks'] }} réseau(x)
                        </span>
                        <span class="badge">
                            <i class="fa-solid fa-desktop" style="font-size:11px; margin-right:4px;"></i>
                            {{ $stats['monitored_hosts'] }} hôte(s)
                        </span>
                    </div>

                    <div class="btn-row">
                        <a href="{{ route('platform.configuration.index') }}" class="btn btn-primary">
                            <i class="fa-solid fa-table-cells-large"></i> Configuration
                        </a>
                        <a href="{{ route('platform.alerts.index') }}" class="btn btn-soft">
                            <i class="fa-solid fa-triangle-exclamation"></i> Alertes actives
                        </a>
                        <a href="{{ route('platform.approval-queue.index') }}" class="btn btn-soft">
                            <i class="fa-solid fa-clipboard-check"></i> Approbation
                        </a>
                    </div>
                </div>

                <div class="status-orb">
                    <div class="status-orb-content">
                        <strong>{{ $stats['active_incidents'] }}</strong>
                        <span>incident(s) actif(s)</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444;box-shadow:0 0 0 1px rgba(239,68,68,.2);">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="smart-stat-label">Alertes actives</div>
                <div class="smart-stat-value" style="{{ $stats['active_alerts'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['active_alerts'] }}
                </div>
                <div class="smart-stat-hint">Ouvertes, reconnues ou en cours.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-icon" style="background:rgba(251,146,60,.12);color:#fb923c;box-shadow:0 0 0 1px rgba(251,146,60,.2);">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div class="smart-stat-label">Incidents actifs</div>
                <div class="smart-stat-value" style="{{ $stats['active_incidents'] > 0 ? 'color:#fb923c;' : '' }}">
                    {{ $stats['active_incidents'] }}
                </div>
                <div class="smart-stat-hint">Ouverts, analysés ou réouverts.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;box-shadow:0 0 0 1px rgba(245,158,11,.2);">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="smart-stat-label">Actions en attente</div>
                <div class="smart-stat-value" style="{{ $stats['pending_actions'] > 0 ? 'color:#f59e0b;' : '' }}">
                    {{ $stats['pending_actions'] }}
                </div>
                <div class="smart-stat-hint">Décisions SOC à approuver.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444;box-shadow:0 0 0 1px rgba(239,68,68,.2);">
                    <i class="fa-solid fa-skull-crossbones"></i>
                </div>
                <div class="smart-stat-label">Agents critiques</div>
                <div class="smart-stat-value" style="{{ $stats['critical_agents'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['critical_agents'] }}
                </div>
                <div class="smart-stat-hint">Machines à risque critique.</div>
            </div>
        </section>

        {{-- ── SURVEILLANCE CONTROL PANEL — admin uniquement ─────────── --}}
        @if(auth()->user()->isAdmin())
        <section class="section-gap">
            <div class="section-heading">
                <div class="section-heading-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="section-heading-text">
                    <h3>Panneau de surveillance</h3>
                    <p>Paramètres opérationnels du moteur — modifiables en temps réel.</p>
                </div>
            </div>

            <div class="surveillance-grid">

                {{-- Protection Engine --}}
                @if($ss->has('protection_execution_enabled'))
                @php $s = $ss->get('protection_execution_enabled'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'active-card' : '' }}" id="card-protection_execution_enabled">
                    <div class="surv-icon {{ $on ? 'green' : '' }}">
                        <i class="fa-solid fa-power-off"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Moteur</div>
                        <div class="surv-title">Exécution des protections</div>
                        <div class="surv-desc">Active la génération et le déclenchement des actions de protection.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Human Approval --}}
                @if($ss->has('require_human_approval_for_sensitive_actions'))
                @php $s = $ss->get('require_human_approval_for_sensitive_actions'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'active-card' : '' }}" id="card-require_human_approval_for_sensitive_actions">
                    <div class="surv-icon {{ $on ? 'blue' : '' }}">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Sécurité</div>
                        <div class="surv-title">Approbation humaine</div>
                        <div class="surv-desc">Exige une validation manuelle avant toute action sensible.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Requise' : 'Non requise' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Real Isolation --}}
                @if($ss->has('enable_real_isolation'))
                @php $s = $ss->get('enable_real_isolation'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'danger-card' : '' }}" id="card-enable_real_isolation">
                    <div class="surv-icon {{ $on ? 'red' : '' }}">
                        <i class="fa-solid fa-plug-circle-xmark"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Réponse</div>
                        <div class="surv-title">Isolation réseau réelle</div>
                        <div class="surv-desc">Autorise l'isolation physique des machines suspectes.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Autorisée' : 'Désactivée' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Real Process Kill --}}
                @if($ss->has('enable_real_process_kill'))
                @php $s = $ss->get('enable_real_process_kill'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'danger-card' : '' }}" id="card-enable_real_process_kill">
                    <div class="surv-icon {{ $on ? 'red' : '' }}">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Réponse</div>
                        <div class="surv-title">Arrêt processus réel</div>
                        <div class="surv-desc">Autorise l'arrêt forcé de processus identifiés comme malveillants.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Autorisé' : 'Désactivé' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Min risk level for incident --}}
                @if($ss->has('min_risk_level_for_incident'))
                @php $s = $ss->get('min_risk_level_for_incident'); @endphp
                <div class="surv-card" id="card-min_risk_level_for_incident">
                    <div class="surv-icon orange">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Détection</div>
                        <div class="surv-title">Seuil de création d'incident</div>
                        <div class="surv-desc">Niveau minimum déclenchant un incident automatique.</div>
                        <select class="risk-select surv-select"
                                data-setting-id="{{ $s->id }}"
                                data-setting-key="{{ $s->key }}"
                                data-set-url="{{ route('platform.system-settings.set-value', $s) }}">
                            @foreach(['normal','suspect','high','critical'] as $level)
                                <option value="{{ $level }}" {{ $s->value === $level ? 'selected' : '' }}>
                                    {{ ucfirst($level) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                {{-- Min risk level for action --}}
                @if($ss->has('min_risk_level_for_action'))
                @php $s = $ss->get('min_risk_level_for_action'); @endphp
                <div class="surv-card" id="card-min_risk_level_for_action">
                    <div class="surv-icon purple">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Réponse</div>
                        <div class="surv-title">Seuil de proposition d'action</div>
                        <div class="surv-desc">Niveau minimum déclenchant la proposition d'une action.</div>
                        <select class="risk-select surv-select"
                                data-setting-id="{{ $s->id }}"
                                data-setting-key="{{ $s->key }}"
                                data-set-url="{{ route('platform.system-settings.set-value', $s) }}">
                            @foreach(['normal','suspect','high','critical'] as $level)
                                <option value="{{ $level }}" {{ $s->value === $level ? 'selected' : '' }}>
                                    {{ ucfirst($level) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                {{-- Notification UI --}}
                @if($ss->has('notification_ui_enabled'))
                @php $s = $ss->get('notification_ui_enabled'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'active-card' : '' }}" id="card-notification_ui_enabled">
                    <div class="surv-icon {{ $on ? 'blue' : '' }}">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Notifications</div>
                        <div class="surv-title">Alertes interface</div>
                        <div class="surv-desc">Affiche les pop-ups de notification dans l'interface SOC.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Actives' : 'Inactives' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Notification son --}}
                @if($ss->has('notification_sound_enabled'))
                @php $s = $ss->get('notification_sound_enabled'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'active-card' : '' }}" id="card-notification_sound_enabled">
                    <div class="surv-icon {{ $on ? 'orange' : '' }}">
                        <i class="fa-solid fa-volume-high"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Notifications</div>
                        <div class="surv-title">Alarme sonore</div>
                        <div class="surv-desc">Joue un bip d'alerte navigateur à chaque nouvelle alerte.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Activée' : 'Désactivée' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Notification mail --}}
                @if($ss->has('notification_mail_enabled'))
                @php $s = $ss->get('notification_mail_enabled'); $on = $s->value === '1'; @endphp
                <div class="surv-card {{ $on ? 'active-card' : '' }}" id="card-notification_mail_enabled">
                    <div class="surv-icon {{ $on ? 'green' : '' }}">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div class="surv-body">
                        <div class="surv-label">Notifications</div>
                        <div class="surv-title">Alertes par e-mail</div>
                        <div class="surv-desc">Envoie un e-mail à l'opérateur SOC à chaque nouvelle alerte.</div>
                        <div class="surv-control">
                            <label class="toggle-switch" title="{{ $s->label }}">
                                <input type="checkbox"
                                       {{ $on ? 'checked' : '' }}
                                       data-setting-id="{{ $s->id }}"
                                       data-setting-key="{{ $s->key }}"
                                       data-toggle-url="{{ route('platform.system-settings.toggle', $s) }}"
                                       class="surv-toggle">
                                <span class="toggle-track"></span>
                            </label>
                            <span class="toggle-status {{ $on ? 'on' : '' }}" id="status-{{ $s->key }}">
                                {{ $on ? 'Activées' : 'Désactivées' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </section>
        @endif {{-- end @if(auth()->user()->isAdmin()) surveillance panel --}}

        {{-- ── CHARTS ───────────────────────────────────────────────────── --}}
        <section class="chart-grid-premium section-gap">
            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">
                            <i class="fa-solid fa-wave-square" style="color:var(--accent); margin-right:8px; font-size:15px;"></i>
                            Activité SOC — <span id="activityPeriodLabel">7 jours</span>
                        </h3>
                        <p class="chart-subtitle">Alertes, incidents et actions déclenchées par le moteur.</p>
                    </div>
                    <div class="period-filter">
                        <button class="period-btn" data-period="24h">24h</button>
                        <button class="period-btn period-btn-active" data-period="week">7j</button>
                        <button class="period-btn" data-period="month">30j</button>
                    </div>
                </div>
                <div class="chart-box" style="position:relative;">
                    <canvas id="socActivityChart"></canvas>
                    <div id="chartLoader" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; background:color-mix(in srgb, var(--bg-panel) 80%, transparent); border-radius:12px;">
                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size:22px; color:var(--accent);"></i>
                    </div>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">
                            <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444; margin-right:8px; font-size:15px;"></i>
                            Risques des alertes
                        </h3>
                        <p class="chart-subtitle">Répartition globale par niveau de risque.</p>
                    </div>
                    <span class="badge">Analyse</span>
                </div>
                <div class="chart-box">
                    <canvas id="alertRiskChart"></canvas>
                </div>
            </div>
        </section>

        <section class="chart-grid-equal section-gap">
            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">
                            <i class="fa-solid fa-shield-halved" style="color:var(--accent-2); margin-right:8px; font-size:15px;"></i>
                            Actions par statut
                        </h3>
                        <p class="chart-subtitle">Pending, approved, rejected, cancelled.</p>
                    </div>
                    <span class="badge">Réponse</span>
                </div>
                <div class="chart-box">
                    <canvas id="actionStatusChart"></canvas>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">
                            <i class="fa-solid fa-microchip" style="color:var(--accent); margin-right:8px; font-size:15px;"></i>
                            Distribution agents
                        </h3>
                        <p class="chart-subtitle">Répartition par niveau de risque actuel.</p>
                    </div>
                    <span class="badge">Agents</span>
                </div>
                <div class="chart-box">
                    <canvas id="agentRiskChart"></canvas>
                </div>
            </div>
        </section>

        {{-- ── QUICK ACTIONS ────────────────────────────────────────────── --}}
        <section class="quick-actions section-gap">
            <a class="quick-action" href="{{ route('platform.networks.index') }}">
                <div class="qa-icon-wrap blue">
                    <i class="fa-solid fa-diagram-project"></i>
                </div>
                <strong>Infrastructure</strong>
                <small>Réseaux et hôtes surveillés.</small>
                <div class="qa-arrow">
                    <i class="fa-solid fa-arrow-right"></i> Accéder
                </div>
            </a>

            <a class="quick-action" href="{{ route('platform.detection-rules.index') }}">
                <div class="qa-icon-wrap orange">
                    <i class="fa-solid fa-crosshairs"></i>
                </div>
                <strong>Détection</strong>
                <small>Règles, seuils et extensions sensibles.</small>
                <div class="qa-arrow">
                    <i class="fa-solid fa-arrow-right"></i> Accéder
                </div>
            </a>

            <a class="quick-action" href="{{ route('platform.protection-actions.index') }}">
                <div class="qa-icon-wrap green">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <strong>Réponse SOC</strong>
                <small>Actions et file d'approbation.</small>
                <div class="qa-arrow">
                    <i class="fa-solid fa-arrow-right"></i> Accéder
                </div>
            </a>

            <a class="quick-action" href="{{ route('platform.system-settings.index') }}">
                <div class="qa-icon-wrap purple">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
                <strong>Paramètres</strong>
                <small>Configuration complète du système.</small>
                <div class="qa-arrow">
                    <i class="fa-solid fa-arrow-right"></i> Accéder
                </div>
            </a>
        </section>

        {{-- ── RISK BARS + NETWORK STATE ────────────────────────────────── --}}
        <section class="dashboard-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-chart-pie" style="color:var(--accent); margin-right:8px; font-size:14px;"></i>
                            Répartition des risques agents
                        </h3>
                        <p class="soc-card-subtitle">État actuel de toutes les machines enrôlées.</p>
                    </div>
                </div>

                <div class="risk-bars">
                    <div class="risk-row">
                        <div class="risk-meta">
                            <span><i class="fa-solid fa-circle" style="color:#ef4444; font-size:9px; margin-right:6px;"></i>Critical</span>
                            <strong>{{ $riskDistribution['critical'] }}</strong>
                        </div>
                        <div class="risk-track">
                            <div class="risk-fill" style="width: {{ $criticalPercent }}%; background:#ef4444;"></div>
                        </div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta">
                            <span><i class="fa-solid fa-circle" style="color:#fb923c; font-size:9px; margin-right:6px;"></i>High</span>
                            <strong>{{ $riskDistribution['high'] }}</strong>
                        </div>
                        <div class="risk-track">
                            <div class="risk-fill" style="width: {{ $highPercent }}%; background:#fb923c;"></div>
                        </div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta">
                            <span><i class="fa-solid fa-circle" style="color:#f59e0b; font-size:9px; margin-right:6px;"></i>Suspect</span>
                            <strong>{{ $riskDistribution['suspect'] }}</strong>
                        </div>
                        <div class="risk-track">
                            <div class="risk-fill" style="width: {{ $suspectPercent }}%; background:#f59e0b;"></div>
                        </div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta">
                            <span><i class="fa-solid fa-circle" style="color:#22c55e; font-size:9px; margin-right:6px;"></i>Normal</span>
                            <strong>{{ $riskDistribution['normal'] }}</strong>
                        </div>
                        <div class="risk-track">
                            <div class="risk-fill" style="width: {{ $normalPercent }}%; background:#22c55e;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-diagram-project" style="color:var(--accent); margin-right:8px; font-size:14px;"></i>
                            État réseau
                        </h3>
                        <p class="soc-card-subtitle">Derniers réseaux enregistrés.</p>
                    </div>
                    <a href="{{ route('platform.networks.index') }}" class="action-btn primary">Voir</a>
                </div>

                <div class="activity-list">
                    @forelse($networks as $network)
                        <div class="activity-item">
                            <div class="activity-icon {{ $network->is_monitored ? 'green' : 'blue' }}">
                                <i class="fa-solid fa-diagram-project"></i>
                            </div>
                            <div>
                                <p class="activity-title">{{ $network->name }}</p>
                                <div class="activity-subtitle">
                                    {{ $network->cidr }} — {{ $network->discovered_hosts_count }} hôte(s)
                                </div>
                            </div>
                            <span class="badge {{ $network->is_monitored ? 'badge-normal' : 'badge-suspect' }}">
                                {{ $network->is_monitored ? 'surveillé' : 'retiré' }}
                            </span>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun réseau.',
                            'message' => 'Lance une détection réseau pour alimenter cette section.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── RECENT ALERTS + INCIDENTS ────────────────────────────────── --}}
        <section class="dashboard-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444; margin-right:8px; font-size:14px;"></i>
                            Dernières alertes
                        </h3>
                        <p class="soc-card-subtitle">Alertes récentes, actives ou historiques.</p>
                    </div>
                    <a href="{{ route('platform.alerts.index', ['status' => 'all']) }}" class="action-btn primary">Historique</a>
                </div>

                <div class="activity-list">
                    @forelse($recentAlerts as $alert)
                        @php
                            $aIconClass = match($alert->risk_level) {
                                'critical' => 'red',
                                'high'     => 'orange',
                                'suspect'  => 'yellow',
                                default    => 'green',
                            };
                        @endphp
                        <div class="activity-item">
                            <div class="activity-icon {{ $aIconClass }}">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <p class="activity-title">{{ $alert->title }}</p>
                                <div class="activity-subtitle">
                                    {{ $alert->agent?->agent_name ?? 'Agent inconnu' }} —
                                    {{ $alert->detected_at?->format('d/m H:i') ?? $alert->created_at?->format('d/m H:i') }}
                                </div>
                            </div>
                            <span class="badge {{ $riskClass($alert->risk_level) }}">{{ $alert->risk_level }}</span>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune alerte.',
                            'message' => "Les alertes apparaîtront après les événements agents."
                        ])
                    @endforelse
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-fire" style="color:#fb923c; margin-right:8px; font-size:14px;"></i>
                            Incidents récents
                        </h3>
                        <p class="soc-card-subtitle">Derniers incidents créés par le moteur.</p>
                    </div>
                    <a href="{{ route('platform.incidents.index', ['status' => 'all']) }}" class="action-btn primary">Historique</a>
                </div>

                <div class="activity-list">
                    @forelse($recentIncidents as $incident)
                        @php
                            $iIconClass = match($incident->risk_level) {
                                'critical' => 'red',
                                'high'     => 'orange',
                                'suspect'  => 'yellow',
                                default    => 'green',
                            };
                        @endphp
                        <div class="activity-item">
                            <div class="activity-icon {{ $iIconClass }}">
                                <i class="fa-solid fa-fire"></i>
                            </div>
                            <div>
                                <p class="activity-title">{{ $incident->title }}</p>
                                <div class="activity-subtitle">
                                    {{ $incident->agent?->agent_name ?? 'Agent inconnu' }} —
                                    statut : {{ $incident->status }}
                                </div>
                            </div>
                            <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn">Voir</a>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun incident.',
                            'message' => "Les incidents apparaîtront après une alerte high ou critical."
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── RECENT ACTIONS ───────────────────────────────────────────── --}}
        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">
                        <i class="fa-solid fa-lock" style="color:var(--accent-2); margin-right:8px; font-size:14px;"></i>
                        Décisions SOC récentes
                    </h3>
                    <p class="soc-card-subtitle">Actions proposées, exécutées, rejetées ou en attente.</p>
                </div>
                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="action-btn primary">Toutes les actions</a>
            </div>

            <div class="activity-list">
                @forelse($recentActions as $action)
                    @php
                        $acIconClass = match($action->approval_status) {
                            'approved' => 'green',
                            'rejected', 'cancelled' => 'red',
                            'pending'  => 'yellow',
                            default    => 'blue',
                        };
                    @endphp
                    <div class="activity-item">
                        <div class="activity-icon {{ $acIconClass }}">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <p class="activity-title">{{ $action->action_type }}</p>
                            <div class="activity-subtitle">
                                {{ $action->agent?->agent_name ?? 'Agent inconnu' }}
                                — {{ $action->approval_status }} / {{ $action->execution_status }}
                            </div>
                        </div>
                        <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn">Ouvrir</a>
                    </div>
                @empty
                    @include('platform.partials.empty-state', [
                        'title' => 'Aucune action.',
                        'message' => "Les actions seront proposées selon les politiques de protection."
                    ])
                @endforelse
            </div>
        </section>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* ── Charts ──────────────────────────────────────────────────────── */
        const chartData            = @json($dashboardCharts);
        const agentRiskDistribution = @json($riskDistribution);

        const css        = getComputedStyle(document.documentElement);
        const mutedColor = css.getPropertyValue('--text-muted').trim() || '#64748b';
        const accent     = css.getPropertyValue('--accent').trim()     || '#38bdf8';
        const accent2    = css.getPropertyValue('--accent-2').trim()   || '#22c55e';
        const gridColor  = 'rgba(148, 163, 184, .14)';

        const colors = {
            normal  : '#22c55e',
            suspect : '#f59e0b',
            high    : '#fb923c',
            critical: '#ef4444',
            blue    : accent,
            green   : accent2,
            slate   : '#64748b'
        };

        const baseOpts = {
            responsive          : true,
            maintainAspectRatio : false,
            plugins: {
                legend: {
                    labels: {
                        color        : mutedColor,
                        usePointStyle: true,
                        boxWidth     : 8,
                        font         : { weight: '700' }
                    }
                }
            },
            scales: {
                x: { ticks: { color: mutedColor }, grid: { color: gridColor } },
                y: { beginAtZero: true, ticks: { color: mutedColor, precision: 0 }, grid: { color: gridColor } }
            }
        };

        let socActivityChart = null;

        (function makeLineChart() {
            const el = document.getElementById('socActivityChart');
            if (!el) return;
            socActivityChart = new Chart(el, {
                type: 'line',
                data: {
                    labels  : chartData.labels || [],
                    datasets: [
                        { label: 'Alertes',   data: chartData.alerts    || [], borderColor: colors.critical, backgroundColor: 'rgba(239,68,68,.10)',    tension: .38, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Incidents', data: chartData.incidents  || [], borderColor: colors.high,    backgroundColor: 'rgba(251,146,60,.10)',   tension: .38, fill: true, pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Actions',   data: chartData.actions    || [], borderColor: colors.blue,    backgroundColor: 'rgba(56,189,248,.10)',   tension: .38, fill: true, pointRadius: 4, pointHoverRadius: 6 }
                    ]
                },
                options: baseOpts
            });
        })();

        /* ── Period filter ───────────────────────────────────────────────── */
        const periodLabels = { '24h': '24 heures', 'week': '7 jours', 'month': '30 jours' };
        const loader       = document.getElementById('chartLoader');

        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.classList.contains('period-btn-active')) return;

                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('period-btn-active'));
                this.classList.add('period-btn-active');

                const period = this.dataset.period;
                document.getElementById('activityPeriodLabel').textContent = periodLabels[period] || period;

                if (loader) loader.style.display = 'flex';

                fetch('{{ route("platform.dashboard.chart-data") }}?period=' + period, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (!socActivityChart) return;
                    socActivityChart.data.labels           = data.labels    || [];
                    socActivityChart.data.datasets[0].data = data.alerts    || [];
                    socActivityChart.data.datasets[1].data = data.incidents || [];
                    socActivityChart.data.datasets[2].data = data.actions   || [];
                    socActivityChart.update('active');
                })
                .catch(() => { /* réseau indisponible — chart conserve ses données */ })
                .finally(() => { if (loader) loader.style.display = 'none'; });
            });
        });

        (function makeDoughnutCharts() {
            function doughnut(id, labels, data, palette) {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, {
                    type: 'doughnut',
                    data: { labels, datasets: [{ data, backgroundColor: palette, borderWidth: 0, hoverOffset: 8 }] },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '68%',
                        plugins: { legend: { position: 'bottom', labels: { color: mutedColor, usePointStyle: true, boxWidth: 8, font: { weight: '700' } } } }
                    }
                });
            }

            const alertRisk = chartData.risk_by_alert || {};
            doughnut('alertRiskChart',
                ['Normal','Suspect','High','Critical'],
                [alertRisk.normal||0, alertRisk.suspect||0, alertRisk.high||0, alertRisk.critical||0],
                [colors.normal, colors.suspect, colors.high, colors.critical]
            );

            doughnut('agentRiskChart',
                ['Normal','Suspect','High','Critical'],
                [agentRiskDistribution.normal||0, agentRiskDistribution.suspect||0, agentRiskDistribution.high||0, agentRiskDistribution.critical||0],
                [colors.normal, colors.suspect, colors.high, colors.critical]
            );
        })();

        (function makeBarChart() {
            const el = document.getElementById('actionStatusChart');
            if (!el) return;
            const s = chartData.actions_by_status || {};
            new Chart(el, {
                type: 'bar',
                data: {
                    labels  : ['Pending','Approved','Rejected','Cancelled'],
                    datasets: [{
                        label          : 'Actions',
                        data           : [s.pending||0, s.approved||0, s.rejected||0, s.cancelled||0],
                        backgroundColor: [colors.suspect, colors.normal, colors.critical, colors.slate],
                        borderRadius   : 12,
                        maxBarThickness: 52
                    }]
                },
                options: baseOpts
            });
        })();

        /* ── Surveillance toggles ─────────────────────────────────────────── */
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function showToast(msg) {
            const toast = document.getElementById('survToast');
            const msgEl = document.getElementById('survToastMsg');
            if (!toast || !msgEl) return;
            msgEl.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2800);
        }

        document.querySelectorAll('.surv-toggle').forEach(function (checkbox) {
            checkbox.addEventListener('change', async function () {
                const url    = this.dataset.toggleUrl;
                const key    = this.dataset.settingKey;
                const active = this.checked;

                try {
                    const resp = await fetch(url, {
                        method : 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept'      : 'application/json',
                            'Content-Type': 'application/json',
                        }
                    });

                    if (!resp.ok) throw new Error('Erreur réseau');

                    const data = await resp.json();

                    const card       = document.getElementById('card-' + key);
                    const statusSpan = document.getElementById('status-' + key);
                    const icon       = card?.querySelector('.surv-icon');

                    if (card) {
                        card.classList.remove('active-card', 'danger-card');
                        if (data.active) {
                            const isDanger = ['enable_real_isolation','enable_real_process_kill'].includes(key);
                            card.classList.add(isDanger ? 'danger-card' : 'active-card');
                        }
                    }

                    if (icon) {
                        const isGreen  = ['protection_execution_enabled','notification_mail_enabled'].includes(key);
                        const isBlue   = ['require_human_approval_for_sensitive_actions','notification_ui_enabled'].includes(key);
                        const isOrange = ['notification_sound_enabled'].includes(key);
                        const isDanger = ['enable_real_isolation','enable_real_process_kill'].includes(key);
                        icon.className = 'surv-icon';
                        if (data.active) {
                            if (isDanger)  icon.classList.add('red');
                            else if (isBlue)   icon.classList.add('blue');
                            else if (isGreen)  icon.classList.add('green');
                            else if (isOrange) icon.classList.add('orange');
                        }
                    }

                    if (statusSpan) {
                        statusSpan.className = 'toggle-status' + (data.active ? ' on' : '');
                        const labels = {
                            protection_execution_enabled                 : ['Inactif','Actif'],
                            require_human_approval_for_sensitive_actions : ['Non requise','Requise'],
                            enable_real_isolation                        : ['Désactivée','Autorisée'],
                            enable_real_process_kill                     : ['Désactivé','Autorisé'],
                            notification_ui_enabled                      : ['Inactives','Actives'],
                            notification_sound_enabled                   : ['Désactivée','Activée'],
                            notification_mail_enabled                    : ['Désactivées','Activées'],
                        };
                        const pair = labels[key] || ['Off','On'];
                        statusSpan.textContent = data.active ? pair[1] : pair[0];
                    }

                    showToast((data.active ? 'Activé : ' : 'Désactivé : ') + key.replace(/_/g,' '));

                } catch (e) {
                    this.checked = !active;
                    showToast('Erreur de mise à jour.');
                }
            });
        });

        document.querySelectorAll('.surv-select').forEach(function (select) {
            select.addEventListener('change', async function () {
                const url   = this.dataset.setUrl;
                const key   = this.dataset.settingKey;
                const value = this.value;

                try {
                    const resp = await fetch(url, {
                        method : 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept'      : 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ value })
                    });

                    if (!resp.ok) throw new Error('Erreur réseau');

                    showToast('Seuil mis à jour : ' + value);

                } catch (e) {
                    showToast('Erreur de mise à jour.');
                }
            });
        });

    });
    </script>

@endsection
