@extends('layouts.soc')

@section('title', "RansomShield — File d'approbation")
@section('page_title', "File d'approbation")
@section('page_subtitle', 'Actions sensibles en attente de validation humaine')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .aq-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 20%, color-mix(in srgb, #f59e0b 18%, transparent), transparent 28%),
                radial-gradient(circle at 85% 10%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .aq-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(36px, 5vw, 66px);
            line-height: .93;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .aq-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 820px;
            margin-top: 14px;
        }

        /* ── URGENCY BAR ──────────────────────────────────────────────────── */
        .urgency-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 20px;
            border: 1px solid color-mix(in srgb, #f59e0b 30%, transparent);
            background: color-mix(in srgb, #f59e0b 6%, transparent);
            margin-top: 18px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .urgency-bar.all-clear {
            border-color: color-mix(in srgb, var(--accent-2) 30%, transparent);
            background: color-mix(in srgb, var(--accent-2) 6%, transparent);
        }

        .urgency-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 16px;
            background: color-mix(in srgb, #f59e0b 14%, transparent);
            color: #f59e0b;
            flex-shrink: 0;
        }

        .urgency-icon.green { background: color-mix(in srgb, var(--accent-2) 14%, transparent); color: var(--accent-2); }

        .urgency-text {
            flex: 1;
            font-size: 14px;
            font-weight: 850;
        }

        .urgency-text span { color: var(--text-muted); font-weight: 700; font-size: 13px; }

        .urgency-counts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── SECTION HEADERS (amélioration 6) ────────────────────────────── */
        .aq-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 6px 10px;
            margin-top: 8px;
        }

        .aqsh-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .aqsh-title {
            font-size: 12px;
            font-weight: 950;
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .aqsh-sep { flex: 1; height: 1px; background: var(--border-soft); }

        .aqsh-count {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 850;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: 1px solid var(--border-soft);
            border-radius: 999px;
            padding: 2px 10px;
        }

        .aq-section-header.aqsh-critical .aqsh-icon  { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .aq-section-header.aqsh-critical .aqsh-title { color: #ef4444; }
        .aq-section-header.aqsh-high .aqsh-icon      { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .aq-section-header.aqsh-high .aqsh-title     { color: #fb923c; }
        .aq-section-header.aqsh-suspect .aqsh-icon   { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .aq-section-header.aqsh-suspect .aqsh-title  { color: #f59e0b; }
        .aq-section-header.aqsh-normal .aqsh-icon    { background: color-mix(in srgb, #6b7280 12%, transparent); color: #6b7280; }
        .aq-section-header.aqsh-normal .aqsh-title   { color: #6b7280; }

        /* ── APPROVAL CARD ────────────────────────────────────────────────── */
        .approval-list {
            display: grid;
            gap: 0;
        }

        .approval-card {
            border-radius: 26px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            max-height: 500px;
            margin-bottom: 16px;
            transition: transform .18s ease, box-shadow .18s ease,
                        opacity .32s ease, max-height .42s ease, margin-bottom .42s ease;
        }

        .approval-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 48px rgba(2, 6, 23, .18);
        }

        .approval-card.removing {
            opacity: 0;
            transform: translateX(56px) scale(.97);
            max-height: 0 !important;
            margin-bottom: 0 !important;
            pointer-events: none;
        }

        /* Left accent border by risk */
        .approval-card.risk-critical { border-left: 3px solid #ef4444; }
        .approval-card.risk-high     { border-left: 3px solid #fb923c; }
        .approval-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .approval-card.risk-normal   { border-left: 3px solid #6b7280; }

        .approval-card-inner {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            gap: 0;
        }

        /* Left icon column — amélioration 1 : icône XL */
        .ac-icon-col {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 22px 0 22px 14px;
        }

        .ac-icon {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            font-size: 26px;
            flex-shrink: 0;
        }

        .ac-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .ac-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .ac-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .ac-icon.normal   { background: color-mix(in srgb, #6b7280 12%, transparent); color: #6b7280; }

        /* Main content */
        .ac-body {
            padding: 20px 20px 0 14px;
        }

        .ac-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* amélioration 1 : label lisible sous le type */
        .ac-type-wrap { display: flex; flex-direction: column; gap: 2px; }

        .ac-type {
            margin: 0;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        /* amélioration 2 : description */
        .ac-description {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.55;
            font-style: italic;
            max-width: 640px;
        }

        .ac-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* amélioration 7 : badge statut neutre */
        .badge-pending {
            background: color-mix(in srgb, #94a3b8 10%, transparent);
            color: #64748b;
            border: 1px solid color-mix(in srgb, #94a3b8 22%, transparent);
        }

        /* amélioration 4 : score mini-bar */
        .ac-score-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px 4px 8px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: 1px solid var(--border-soft);
        }

        .ac-score-num {
            font-size: 12px;
            font-weight: 950;
            letter-spacing: -.02em;
            min-width: 24px;
            text-align: right;
        }

        .ac-score-track {
            width: 48px;
            height: 4px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--border-soft) 100%, transparent);
            overflow: hidden;
            flex-shrink: 0;
        }

        .ac-score-fill { height: 100%; border-radius: 999px; }
        .ac-score-fill.critical { background: #ef4444; }
        .ac-score-fill.high     { background: #fb923c; }
        .ac-score-fill.suspect  { background: #f59e0b; }
        .ac-score-fill.normal   { background: #6b7280; }

        /* Context grid */
        .ac-context {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 14px;
        }

        .ac-ctx-item {
            padding: 10px 12px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }

        .ac-ctx-label {
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ac-ctx-value {
            margin-top: 4px;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* amélioration 3 : IP sous le nom de machine */
        .ac-ctx-sub {
            margin-top: 2px;
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 700;
            font-family: 'Courier New', monospace;
            letter-spacing: .03em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Signals strip */
        .ac-signals {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .signal-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 16%, transparent);
            font-size: 11px;
            font-weight: 850;
            color: var(--accent);
        }

        .signal-chip .chip-score {
            color: var(--text-muted);
            font-size: 10px;
        }

        /* Decision strip */
        .ac-decision-strip {
            margin-top: 16px;
            padding: 14px 20px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .ac-decision-strip .spacer { flex: 1; }

        .action-group {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-btn.loading {
            opacity: .55;
            pointer-events: none;
        }

        /* amélioration 5 : proposed_at coloré selon âge */
        .ac-proposed {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ac-proposed.age-warning { color: #f59e0b; font-weight: 850; }
        .ac-proposed.age-critical { color: #ef4444; font-weight: 850; }

        /* ── ALL CLEAR STATE ──────────────────────────────────────────────── */
        .all-clear-state {
            padding: 60px 28px;
            border-radius: 28px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            text-align: center;
        }

        .all-clear-icon {
            width: 80px;
            height: 80px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent-2) 12%, transparent);
            color: var(--accent-2);
            font-size: 32px;
            display: grid;
            place-items: center;
            margin: 0 auto 20px;
        }

        .all-clear-state h3 {
            margin: 0;
            font-size: 26px;
            font-weight: 950;
            letter-spacing: -.05em;
        }

        .all-clear-state p {
            margin-top: 10px;
            color: var(--text-muted);
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.65;
        }

        /* ── TOAST ────────────────────────────────────────────────────────── */
        .aq-toast {
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
            max-width: 340px;
        }

        .aq-toast.show   { transform: translateY(0); opacity: 1; }
        .aq-toast.green i { color: var(--accent-2); }
        .aq-toast.red   i { color: #ef4444; }

        /* ── PENDING COUNTER ──────────────────────────────────────────────── */
        #aq-counter {
            font-weight: 950;
            font-variant-numeric: tabular-nums;
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1000px) {
            .ac-context { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 760px) {
            .approval-card-inner { grid-template-columns: 1fr; }
            .ac-icon-col { padding: 16px 16px 0; justify-content: flex-start; }
            .ac-body { padding: 12px 16px 0; }
            .ac-context { grid-template-columns: 1fr 1fr; }
            .ac-decision-strip { padding: 12px 16px; }
        }

        @media (max-width: 540px) {
            .ac-context { grid-template-columns: 1fr; }
            .ac-decision-strip .spacer { display: none; }
            .action-group { flex-direction: column; width: 100%; }
            .action-group .action-btn { width: 100%; justify-content: center; }
            .action-btn.detail-link { width: 100%; justify-content: center; }
        }
    </style>

    {{-- Toast --}}
    <div id="aqToast" class="aq-toast">
        <i id="aqToastIcon" class="fa-solid fa-check-circle"></i>
        <span id="aqToastMsg">Décision enregistrée.</span>
    </div>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="aq-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Validation humaine
            </div>

            <h2>Vous décidez.<br>Le moteur attend.</h2>

            <p>
                Chaque action listée ici a été proposée automatiquement par le moteur de détection.
                Elle ne sera exécutée que si vous l'approuvez. Prenez le temps d'examiner le contexte.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="btn btn-soft">
                    <i class="fa-solid fa-clock-rotate-left"></i> Historique complet
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
                    <i class="fa-solid fa-hourglass-half" style="color:#f59e0b; margin-right:6px;"></i>
                    En attente
                </div>
                <div class="smart-stat-value" id="aq-counter"
                     style="{{ $stats['total'] > 0 ? 'color:#f59e0b;' : '' }}">
                    {{ $stats['total'] }}
                </div>
                <div class="smart-stat-hint">Décisions à traiter.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-exclamation" style="color:#ef4444; margin-right:6px;"></i>
                    Critiques
                </div>
                <div class="smart-stat-value" style="{{ $stats['critical'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['critical'] }}
                </div>
                <div class="smart-stat-hint">Urgence maximale.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#fb923c; margin-right:6px;"></i>
                    Élevées
                </div>
                <div class="smart-stat-value" style="{{ $stats['high'] > 0 ? 'color:#fb923c;' : '' }}">
                    {{ $stats['high'] }}
                </div>
                <div class="smart-stat-hint">Risque élevé.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-shield-halved" style="color:var(--accent); margin-right:6px;"></i>
                    Urgentes
                </div>
                <div class="smart-stat-value" style="{{ $stats['urgent'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['urgent'] }}
                </div>
                <div class="smart-stat-hint">Critical + High.</div>
            </div>
        </section>

        {{-- ── URGENCY BAR ──────────────────────────────────────────────── --}}
        @if($stats['total'] > 0)
            <div class="{{ $stats['urgent'] === 0 ? 'urgency-bar all-clear' : 'urgency-bar' }}">
                <div class="{{ $stats['urgent'] === 0 ? 'urgency-icon green' : 'urgency-icon' }}">
                    @if($stats['urgent'] > 0)
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    @else
                        <i class="fa-solid fa-shield-halved"></i>
                    @endif
                </div>
                <div class="urgency-text">
                    @if($stats['urgent'] > 0)
                        <strong>{{ $stats['urgent'] }} action(s) urgente(s)</strong>
                        <span> nécessitent une décision immédiate (risque critical ou high).</span>
                    @else
                        <strong>Toutes les actions en attente sont de faible urgence.</strong>
                        <span> Aucune action critical ou high dans la file.</span>
                    @endif
                </div>
                <div class="urgency-counts">
                    @if($stats['critical'] > 0)
                        <span class="badge badge-critical">{{ $stats['critical'] }} critical</span>
                    @endif
                    @if($stats['high'] > 0)
                        <span class="badge badge-high">{{ $stats['high'] }} high</span>
                    @endif
                    @if($stats['suspect'] > 0)
                        <span class="badge badge-suspect">{{ $stats['suspect'] }} suspect</span>
                    @endif
                    @if($stats['normal'] > 0)
                        <span class="badge badge-normal">{{ $stats['normal'] }} normal</span>
                    @endif
                </div>
            </div>
        @endif

        {{-- ── APPROVAL LIST ────────────────────────────────────────────── --}}
        @if($actions->count())
            <div class="approval-list section-gap" id="aqList">

                {{-- Amélioration 6 : groupement par section --}}
                @php $currentSection = null; @endphp

                @foreach($actions as $action)
                    @php
                        $riskLevel  = data_get($action->payload, 'risk_level', $action->incident?->risk_level ?? 'normal');
                        $riskScore  = (int) data_get($action->payload, 'risk_score', $action->incident?->risk_score ?? 0);
                        $policyCode = data_get($action->payload, 'policy_code', $action->protectionPolicy?->code ?? null);
                        $signals    = collect(data_get($action->payload, 'signals', []))->take(4);

                        // Amélioration 5 : âge de l'action
                        $proposedAt  = $action->proposed_at ?? $action->created_at;
                        $ageMinutes  = $proposedAt ? $proposedAt->diffInMinutes(now()) : 0;
                        $ageClass    = $ageMinutes >= 30 ? 'age-critical' : ($ageMinutes >= 10 ? 'age-warning' : '');

                        // Amélioration 4 : largeur mini-bar (max 200 pts = 100%)
                        $scoreBarWidth = min(100, round($riskScore / 200 * 100));

                        $riskBadge = match($riskLevel) {
                            'critical' => 'badge-critical',
                            'high'     => 'badge-high',
                            'suspect'  => 'badge-suspect',
                            default    => 'badge-normal',
                        };

                        // Amélioration 1 : label lisible + icône enrichie
                        $actionLabel = match($action->action_type) {
                            'isolate_host'       => 'Isolation réseau',
                            'kill_process'       => 'Arrêt de processus',
                            'alert_only'         => 'Notification SOC',
                            'emergency_backup'   => 'Sauvegarde urgence',
                            'lock_safe_copy'     => 'Copie sécurisée',
                            'restrict_path'      => 'Restriction chemin',
                            'rollback_isolation' => 'Lever isolation',
                            default              => ucwords(str_replace('_', ' ', $action->action_type)),
                        };

                        $actionIcon = match(true) {
                            str_contains($action->action_type, 'rollback')  => 'fa-plug-circle-check',
                            str_contains($action->action_type, 'isolat')    => 'fa-plug-circle-xmark',
                            str_contains($action->action_type, 'kill')      => 'fa-ban',
                            str_contains($action->action_type, 'backup')    => 'fa-cloud-arrow-up',
                            str_contains($action->action_type, 'copy')      => 'fa-lock',
                            str_contains($action->action_type, 'restrict')  => 'fa-folder-minus',
                            str_contains($action->action_type, 'alert')     => 'fa-bell',
                            str_contains($action->action_type, 'notify')    => 'fa-envelope',
                            default                                          => 'fa-shield-virus',
                        };

                        $sectionIcon = match($riskLevel) {
                            'critical' => 'fa-radiation',
                            'high'     => 'fa-shield-halved',
                            'suspect'  => 'fa-eye',
                            default    => 'fa-circle-dot',
                        };

                        $sectionLabel = match($riskLevel) {
                            'critical' => 'CRITIQUE',
                            'high'     => 'ÉLEVÉ',
                            'suspect'  => 'SUSPECT',
                            default    => 'NORMAL',
                        };

                        $sectionCount = match($riskLevel) {
                            'critical' => $stats['critical'],
                            'high'     => $stats['high'],
                            'suspect'  => $stats['suspect'],
                            default    => $stats['normal'],
                        };
                    @endphp

                    {{-- ── Section header quand le niveau change (amélioration 6) --}}
                    @if($riskLevel !== $currentSection)
                        @php $currentSection = $riskLevel; @endphp
                        <div class="aq-section-header aqsh-{{ $riskLevel }}">
                            <div class="aqsh-icon">
                                <i class="fa-solid {{ $sectionIcon }}"></i>
                            </div>
                            <span class="aqsh-title">{{ $sectionLabel }}</span>
                            <div class="aqsh-sep"></div>
                            <span class="aqsh-count">{{ $sectionCount }} en attente</span>
                        </div>
                    @endif

                    <article class="approval-card risk-{{ $riskLevel }}"
                             id="ac-{{ $action->id }}"
                             data-id="{{ $action->id }}">
                        <div class="approval-card-inner">

                            {{-- Icône XL (amélioration 1) --}}
                            <div class="ac-icon-col">
                                <div class="ac-icon {{ $riskLevel }}">
                                    <i class="fa-solid {{ $actionIcon }}"></i>
                                </div>
                            </div>

                            {{-- Body --}}
                            <div class="ac-body">
                                <div class="ac-head">

                                    <div class="ac-type-wrap">
                                        {{-- Amélioration 1 : label lisible --}}
                                        <h3 class="ac-type">{{ $actionLabel }}</h3>
                                        {{-- Amélioration 2 : description --}}
                                        @if($action->description)
                                            <p class="ac-description">{{ $action->description }}</p>
                                        @endif
                                    </div>

                                    <div class="ac-badges">
                                        <span class="badge {{ $riskBadge }}">
                                            <i class="fa-solid fa-circle" style="font-size:8px; margin-right:4px;"></i>
                                            {{ $riskLevel }}
                                        </span>

                                        {{-- Amélioration 4 : score mini-bar --}}
                                        <div class="ac-score-wrap" title="Score de risque : {{ $riskScore }} / 200">
                                            <span class="ac-score-num">{{ $riskScore }}</span>
                                            <div class="ac-score-track">
                                                <div class="ac-score-fill {{ $riskLevel }}"
                                                     style="width:{{ $scoreBarWidth }}%"></div>
                                            </div>
                                        </div>

                                        {{-- Amélioration 7 : badge pending neutre --}}
                                        <span class="badge badge-pending">
                                            <i class="fa-solid fa-hourglass-half" style="font-size:10px; margin-right:3px;"></i>
                                            en attente
                                        </span>
                                    </div>
                                </div>

                                {{-- Context grid --}}
                                <div class="ac-context">

                                    {{-- Amélioration 3 : Machine avec IP --}}
                                    <div class="ac-ctx-item">
                                        <div class="ac-ctx-label">
                                            <i class="fa-solid fa-robot"></i> Machine
                                        </div>
                                        <div class="ac-ctx-value" title="{{ $action->agent?->hostname ?? $action->agent?->agent_name ?? 'Inconnu' }}">
                                            {{ $action->agent?->agent_name ?? '—' }}
                                        </div>
                                        @if($action->agent?->ip_address)
                                            <div class="ac-ctx-sub">{{ $action->agent->ip_address }}</div>
                                        @endif
                                    </div>

                                    <div class="ac-ctx-item">
                                        <div class="ac-ctx-label">
                                            <i class="fa-solid fa-fire-flame-curved"></i> Incident
                                        </div>
                                        <div class="ac-ctx-value" title="{{ $action->incident?->title ?? 'Aucun' }}">
                                            @if($action->incident)
                                                #{{ $action->incident_id }}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </div>

                                    <div class="ac-ctx-item">
                                        <div class="ac-ctx-label">
                                            <i class="fa-solid fa-scroll"></i> Politique
                                        </div>
                                        <div class="ac-ctx-value" title="{{ $policyCode ?? 'Aucune' }}">
                                            {{ $policyCode ?? '—' }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Signals --}}
                                @if($signals->count())
                                    <div class="ac-signals">
                                        @foreach($signals as $signal)
                                            <span class="signal-chip">
                                                <i class="fa-solid fa-bolt" style="font-size:9px;"></i>
                                                {{ data_get($signal, 'label', data_get($signal, 'code', 'signal')) }}
                                                <span class="chip-score">+{{ data_get($signal, 'score', 0) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Decision strip --}}
                        <div class="ac-decision-strip">

                            {{-- Amélioration 5 : âge coloré --}}
                            <span class="ac-proposed {{ $ageClass }}"
                                  title="{{ $proposedAt?->format('d/m/Y H:i:s') }}">
                                <i class="fa-{{ $ageClass === 'age-critical' ? 'solid' : 'regular' }} fa-clock"></i>
                                {{ $proposedAt?->diffForHumans() ?? '—' }}
                                @if($ageClass === 'age-critical')
                                    <span style="font-size:10px; opacity:.8;">— urgent</span>
                                @elseif($ageClass === 'age-warning')
                                    <span style="font-size:10px; opacity:.8;">— en attente</span>
                                @endif
                            </span>

                            <div class="spacer"></div>

                            <a href="{{ route('platform.protection-actions.show', $action) }}"
                               class="action-btn">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Voir détails
                            </a>

                            <div class="action-group">
                                <button type="button" class="action-btn lg danger"
                                        data-action-id="{{ $action->id }}"
                                        data-url="{{ route('platform.protection-actions.reject', $action) }}"
                                        data-decision="reject">
                                    <i class="fa-solid fa-xmark"></i>
                                    Rejeter
                                </button>

                                <button type="button" class="action-btn lg success"
                                        data-action-id="{{ $action->id }}"
                                        data-url="{{ route('platform.protection-actions.approve', $action) }}"
                                        data-decision="approve">
                                    <i class="fa-solid fa-check"></i>
                                    Approuver
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap" style="margin-top:24px;">
                {{ $actions->links() }}
            </div>

        @else
            {{-- All clear state --}}
            <div class="all-clear-state">
                <div class="all-clear-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>File vide — tout est traité.</h3>
                <p>
                    Aucune action sensible en attente de validation.
                    Le moteur proposera de nouvelles décisions dès la prochaine détection.
                </p>
                <div class="btn-row" style="justify-content:center; margin-top:20px;">
                    <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="btn btn-soft">
                        <i class="fa-solid fa-clock-rotate-left"></i> Voir historique
                    </a>
                    <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                </div>
            </div>
        @endif

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let pendingCount = {{ $stats['total'] }};
        const counter    = document.getElementById('aq-counter');

        function showToast(msg, type) {
            const toast  = document.getElementById('aqToast');
            const msgEl  = document.getElementById('aqToastMsg');
            const iconEl = document.getElementById('aqToastIcon');
            if (!toast) return;
            msgEl.textContent = msg;
            toast.className   = 'aq-toast show ' + (type || 'green');
            iconEl.className  = type === 'red'
                ? 'fa-solid fa-xmark-circle'
                : 'fa-solid fa-check-circle';
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function updateCounter(delta) {
            pendingCount = Math.max(0, pendingCount + delta);
            if (counter) {
                counter.textContent = pendingCount;
                counter.style.color = pendingCount > 0 ? '#f59e0b' : '';
            }
        }

        function removeCard(id) {
            const card = document.getElementById('ac-' + id);
            if (!card) return;
            card.classList.add('removing');
            setTimeout(() => {
                card.remove();

                // Supprimer les section-headers vides (plus aucune card de ce niveau)
                document.querySelectorAll('.aq-section-header').forEach(header => {
                    const next = header.nextElementSibling;
                    if (!next || next.classList.contains('aq-section-header')) {
                        header.remove();
                    }
                });

                const list = document.getElementById('aqList');
                if (list && list.querySelectorAll('.approval-card:not(.removing)').length === 0) {
                    list.insertAdjacentHTML('afterend',
                        '<div class="all-clear-state section-gap">' +
                        '<div class="all-clear-icon"><i class="fa-solid fa-shield-halved"></i></div>' +
                        '<h3>File vide — tout est traité.</h3>' +
                        '<p>Toutes les actions ont été traitées dans cette session.</p>' +
                        '</div>'
                    );
                    list.remove();
                }
            }, 450);
        }

        document.querySelectorAll('.action-btn[data-decision]').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const id       = this.dataset.actionId;
                const url      = this.dataset.url;
                const decision = this.dataset.decision;

                const card = document.getElementById('ac-' + id);
                const btns = card?.querySelectorAll('.action-btn[data-decision]');
                btns?.forEach(b => b.classList.add('loading'));

                try {
                    const resp = await fetch(url, {
                        method : 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept'      : 'application/json',
                            'Content-Type': 'application/json',
                        }
                    });

                    if (!resp.ok) throw new Error('Erreur serveur');

                    updateCounter(-1);
                    removeCard(id);

                    showToast(
                        decision === 'approve'
                            ? 'Action approuvée — elle sera exécutée.'
                            : 'Action rejetée — annulée.',
                        decision === 'approve' ? 'green' : 'red'
                    );

                } catch (e) {
                    btns?.forEach(b => b.classList.remove('loading'));
                    showToast("Erreur lors de l'enregistrement.", 'red');
                }
            });
        });
    });
    </script>

@endsection
