@extends('layouts.soc')

@section('title', 'RansomShield — Fiche incident')
@section('page_title', 'Fiche incident')
@section('page_subtitle', $incident->title)

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

        $approvalLabel = fn($s) => match($s) {
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'pending'  => 'En attente',
            default    => $s,
        };

        $execLabel = fn($s) => match($s) {
            'executed', 'success' => 'Exécutée',
            'executing'           => 'En cours…',
            'pending'             => 'En attente',
            'waiting_approval'    => 'Attente appro.',
            'cancelled'           => 'Annulée',
            'failed'              => 'Échec',
            'rolled_back'         => 'Rollback',
            default               => $s,
        };

        $signals   = collect(data_get($incident->metadata, 'signals', []));
        $threshold = data_get($incident->metadata, 'threshold');

        $risk   = $incident->risk_level ?? 'normal';
        $isDone = in_array($incident->status, ['resolved', 'false_positive'], true);
    @endphp

    <style>
        /* ── SHOW HERO ────────────────────────────────────────────────────── */
        .inc-show-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 16%, color-mix(in srgb, #ef4444 14%, transparent), transparent 28%),
                radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .inc-show-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(32px, 4vw, 56px);
            line-height: .96;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .inc-show-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
            margin-top: 12px;
        }

        /* ── CONTEXT STRIP ────────────────────────────────────────────────── */
        .ctx-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .ctx-field {
            padding: 14px 16px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .ctx-field-label {
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
        }

        .ctx-field-value {
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── DETAIL GRID ──────────────────────────────────────────────────── */
        .inc-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        /* ── SIGNAL CARD ──────────────────────────────────────────────────── */
        .signal-grid { display: grid; gap: 10px; }

        .signal-card {
            padding: 14px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: 1px solid var(--border-soft);
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
        }

        .signal-card.risk-critical { border-left: 3px solid #ef4444; }
        .signal-card.risk-high     { border-left: 3px solid #fb923c; }
        .signal-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .signal-card.risk-normal   { border-left: 3px solid #22c55e; }

        .signal-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 15px;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
        }

        .signal-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .signal-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .signal-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }

        .signal-label {
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .signal-meta {
            margin-top: 4px;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ── ACTION ITEM ──────────────────────────────────────────────────── */
        .action-list { display: grid; gap: 10px; }

        .action-item {
            padding: 14px;
            border-radius: 18px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
        }

        .action-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 16px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            flex-shrink: 0;
        }

        .action-item-icon.approved { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }
        .action-item-icon.rejected { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .action-item-icon.pending  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }

        .action-item-title {
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .action-item-meta {
            margin-top: 4px;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── TIMELINE ─────────────────────────────────────────────────────── */
        .timeline { display: grid; gap: 10px; }

        .timeline-item {
            position: relative;
            padding: 14px 14px 14px 48px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 17px;
            top: 19px;
            width: 13px;
            height: 13px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 16%, transparent);
        }

        .timeline-item.risk-critical::before { background: #ef4444; box-shadow: 0 0 0 4px color-mix(in srgb, #ef4444 16%, transparent); }
        .timeline-item.risk-high::before     { background: #fb923c; box-shadow: 0 0 0 4px color-mix(in srgb, #fb923c 16%, transparent); }
        .timeline-item.risk-suspect::before  { background: #f59e0b; box-shadow: 0 0 0 4px color-mix(in srgb, #f59e0b 16%, transparent); }
        .timeline-item.risk-normal::before   { background: #22c55e; box-shadow: 0 0 0 4px color-mix(in srgb, #22c55e 16%, transparent); }

        .timeline-title {
            margin: 0;
            font-weight: 950;
            font-size: 13px;
            letter-spacing: -.02em;
        }

        .timeline-meta {
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.5;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .timeline-badges {
            margin-top: 8px;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── METADATA ACCORDION ───────────────────────────────────────────── */
        .meta-accordion {
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            overflow: hidden;
        }

        .meta-accordion-toggle {
            width: 100%;
            padding: 14px 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 850;
            color: var(--text-muted);
            text-align: left;
        }

        .meta-accordion-toggle:hover { color: var(--text-main); }
        .meta-accordion-toggle i.chevron { margin-left: auto; transition: .2s ease; }
        .meta-accordion-toggle.open i.chevron { transform: rotate(180deg); }

        .meta-accordion-body { display: none; padding: 0 18px 18px; }
        .meta-accordion-body.open { display: block; }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .inc-detail-grid { grid-template-columns: 1fr; }
            .ctx-strip { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 700px) {
            .ctx-strip { grid-template-columns: 1fr 1fr; }
            .action-item { grid-template-columns: 40px 1fr; }
            .action-item a { grid-column: span 2; }
        }

        @media (max-width: 480px) {
            .ctx-strip { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="inc-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Investigation incident #{{ $incident->id }}
            </div>

            <h2>{{ $incident->title }}</h2>

            <p>{{ $incident->description ?? 'Incident généré par le moteur dynamique de détection RansomShield.' }}</p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <span class="badge {{ $riskClass($risk) }}">
                    <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i> {{ $risk }}
                </span>
                <span class="badge">Score {{ $incident->risk_score }}</span>
                <span class="badge {{ $statusClass($incident->status) }}">{{ $statusLabel($incident->status) }}</span>
                @if($incident->agent)
                    <span class="badge">
                        <i class="fa-solid fa-robot" style="margin-right:4px;"></i>
                        {{ $incident->agent->agent_name }}
                    </span>
                @endif
                @if($threshold)
                    <span class="badge">
                        <i class="fa-solid fa-sliders" style="margin-right:4px;"></i>
                        Seuil : {{ data_get($threshold, 'risk_level', '—') }}
                    </span>
                @endif
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.incidents.index', ['status' => 'all']) }}" class="btn btn-soft">
                    <i class="fa-solid fa-arrow-left"></i> Historique incidents
                </a>

                @if(!$isDone)
                    <form method="POST" action="{{ route('platform.incidents.resolve', $incident) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-circle-check"></i> Résoudre incident
                        </button>
                    </form>

                    <form method="POST" action="{{ route('platform.incidents.false-positive', $incident) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="action-btn warning">
                            <i class="fa-solid fa-ban"></i> Classer faux positif
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.incidents.reopen', $incident) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="action-btn">
                            <i class="fa-solid fa-rotate-right"></i> Réouvrir
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- ── CONTEXT STRIP ────────────────────────────────────────────── --}}
        <div class="ctx-strip section-gap">
            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-regular fa-clock"></i> Détecté
                </div>
                <div class="ctx-field-value">
                    {{ $incident->detected_at?->format('d/m/Y H:i') ?? $incident->created_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-shield-halved"></i> Contenu
                </div>
                <div class="ctx-field-value">
                    {{ $incident->contained_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-circle-check"></i> Résolu
                </div>
                <div class="ctx-field-value" style="{{ $incident->resolved_at ? 'color:#22c55e;' : '' }}">
                    {{ $incident->resolved_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-robot"></i> Agent
                </div>
                <div class="ctx-field-value" title="{{ $incident->agent?->agent_name ?? 'Inconnu' }}">
                    {{ $incident->agent?->agent_name ?? '—' }}
                </div>
            </div>
        </div>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-bell" style="color:#f59e0b; margin-right:6px;"></i>
                    Alertes liées
                </div>
                <div class="smart-stat-value" style="{{ $incident->alerts->count() > 0 ? 'color:#f59e0b;' : '' }}">
                    {{ $incident->alerts->count() }}
                </div>
                <div class="smart-stat-hint">Signaux rattachés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:6px;"></i>
                    Événements
                </div>
                <div class="smart-stat-value" style="{{ $incident->events->count() > 0 ? 'color:var(--accent);' : '' }}">
                    {{ $incident->events->count() }}
                </div>
                <div class="smart-stat-hint">Événements techniques.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-shield-virus" style="color:#fb923c; margin-right:6px;"></i>
                    Actions
                </div>
                <div class="smart-stat-value" style="{{ $incident->protectionActions->count() > 0 ? 'color:#fb923c;' : '' }}">
                    {{ $incident->protectionActions->count() }}
                </div>
                <div class="smart-stat-hint">Réponses proposées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-wave-square" style="color:#22c55e; margin-right:6px;"></i>
                    Signaux
                </div>
                <div class="smart-stat-value" style="{{ $signals->count() > 0 ? 'color:var(--accent);' : '' }}">
                    {{ $signals->count() }}
                </div>
                <div class="smart-stat-hint">Du moteur dynamique.</div>
            </div>
        </section>

        {{-- ── SIGNAUX / ACTIONS ────────────────────────────────────────── --}}
        <section class="inc-detail-grid section-gap">

            {{-- Signaux --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:8px;"></i>
                            Signaux détectés
                        </h3>
                        <p class="soc-card-subtitle">Signaux provenant du moteur dynamique.</p>
                    </div>
                </div>

                @if($signals->count())
                    <div class="signal-grid">
                        @foreach($signals as $signal)
                            @php
                                $sigRisk = data_get($signal, 'risk_level', 'normal');
                                $sigIcon = match($sigRisk) {
                                    'critical' => 'fa-circle-exclamation',
                                    'high'     => 'fa-triangle-exclamation',
                                    'suspect'  => 'fa-eye',
                                    default    => 'fa-bolt',
                                };
                            @endphp
                            <div class="signal-card risk-{{ $sigRisk }}">
                                <div class="signal-icon {{ $sigRisk }}">
                                    <i class="fa-solid {{ $sigIcon }}"></i>
                                </div>
                                <div>
                                    <div class="signal-label">
                                        {{ data_get($signal, 'label', data_get($signal, 'code', 'Signal')) }}
                                    </div>
                                    <div class="signal-meta">
                                        @if(data_get($signal, 'source'))
                                            <span><i class="fa-solid fa-location-dot"></i> {{ data_get($signal, 'source') }}</span>
                                        @endif
                                        <span><i class="fa-solid fa-chart-simple"></i> Score {{ data_get($signal, 'score', 0) }}</span>
                                        @if($sigRisk !== 'normal')
                                            <span class="badge {{ $riskClass($sigRisk) }}" style="font-size:10px; padding:2px 7px;">{{ $sigRisk }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('platform.partials.empty-state', [
                        'title'   => 'Aucun signal détaillé.',
                        'message' => 'Les prochains incidents générés par le moteur dynamique stockeront leurs signaux ici.',
                    ])
                @endif
            </div>

            {{-- Actions de protection --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-shield-virus" style="color:#fb923c; margin-right:8px;"></i>
                            Actions de protection
                        </h3>
                        <p class="soc-card-subtitle">Réponses proposées ou déjà traitées.</p>
                    </div>
                </div>

                <div class="action-list">
                    @forelse($incident->protectionActions as $action)
                        @php
                            $aIcon = match(true) {
                                str_contains($action->action_type, 'isolat')  => 'fa-plug-circle-xmark',
                                str_contains($action->action_type, 'kill')    => 'fa-ban',
                                str_contains($action->action_type, 'backup')  => 'fa-cloud-arrow-up',
                                str_contains($action->action_type, 'block')   => 'fa-shield-halved',
                                str_contains($action->action_type, 'alert')   => 'fa-bell',
                                str_contains($action->action_type, 'quarant') => 'fa-box',
                                default                                        => 'fa-shield-virus',
                            };
                            $aClass = match($action->approval_status) {
                                'approved' => 'approved',
                                'rejected' => 'rejected',
                                default    => 'pending',
                            };
                        @endphp
                        <div class="action-item">
                            <div class="action-item-icon {{ $aClass }}">
                                <i class="fa-solid {{ $aIcon }}"></i>
                            </div>

                            <div>
                                <div class="action-item-title">{{ $action->action_type }}</div>
                                <div class="action-item-meta">
                                    <span class="badge {{ $action->approval_status === 'approved' ? 'badge-normal' : ($action->approval_status === 'rejected' ? 'badge-critical' : 'badge-high') }}" style="font-size:10px; padding:2px 7px;">
                                        {{ $approvalLabel($action->approval_status) }}
                                    </span>
                                    <span style="color:var(--text-muted);">{{ $execLabel($action->execution_status) }}</span>
                                    @if($action->protectionPolicy)
                                        <span><i class="fa-solid fa-scroll" style="font-size:9px;"></i> {{ $action->protectionPolicy->code }}</span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('platform.protection-actions.show', $action) }}"
                               class="action-btn" style="white-space:nowrap; align-self:center;">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucune action.',
                            'message' => "Aucune politique n'a proposé d'action pour cet incident.",
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── ALERTES / ÉVÉNEMENTS ─────────────────────────────────────── --}}
        <section class="inc-detail-grid section-gap">

            {{-- Alertes --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bell" style="color:#f59e0b; margin-right:8px;"></i>
                            Alertes liées
                        </h3>
                        <p class="soc-card-subtitle">Alertes rattachées à cet incident.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($incident->alerts as $alert)
                        <div class="timeline-item risk-{{ $alert->risk_level ?? 'normal' }}">
                            <h4 class="timeline-title">{{ $alert->title }}</h4>
                            <div class="timeline-meta">
                                <span><i class="fa-regular fa-clock"></i>
                                    {{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="timeline-badges">
                                <span class="badge {{ $riskClass($alert->risk_level ?? 'normal') }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $alert->risk_level ?? 'normal' }}
                                </span>
                                <span class="badge" style="font-size:10px; padding:2px 7px;">{{ $alert->status }}</span>
                                <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn" style="padding:3px 10px; font-size:11px;">
                                    <i class="fa-solid fa-arrow-right"></i> Voir
                                </a>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucune alerte liée.',
                            'message' => "Cet incident ne contient pas encore d'alerte associée.",
                        ])
                    @endforelse
                </div>
            </div>

            {{-- Événements --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:8px;"></i>
                            Événements techniques
                        </h3>
                        <p class="soc-card-subtitle">Événements rattachés à l'incident.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($incident->events as $event)
                        <div class="timeline-item risk-{{ $event->risk_level ?? 'normal' }}">
                            <h4 class="timeline-title">{{ $event->event_type }}</h4>
                            @if($event->path)
                                <div class="timeline-meta">
                                    <span style="font-family:monospace; font-size:11px; word-break:break-all;">
                                        {{ Str::limit($event->path, 60) }}
                                    </span>
                                </div>
                            @endif
                            <div class="timeline-badges">
                                <span class="badge {{ $riskClass($event->risk_level ?? 'normal') }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $event->risk_level ?? 'normal' }}
                                </span>
                                <span class="badge" style="font-size:10px; padding:2px 7px;">Score {{ $event->score ?? 0 }}</span>
                                <span style="font-size:11px; color:var(--text-muted);">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ $event->observed_at?->format('d/m/Y H:i') ?? $event->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucun événement lié.',
                            'message' => 'Les événements techniques apparaîtront ici selon les relations disponibles.',
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── METADATA ─────────────────────────────────────────────────── --}}
        @if($incident->metadata)
            <div class="section-gap">
                <div class="meta-accordion">
                    <button type="button" class="meta-accordion-toggle" id="metaToggle">
                        <i class="fa-solid fa-code" style="color:var(--accent);"></i>
                        Métadonnées brutes (JSON)
                        <i class="fa-solid fa-chevron-down chevron"></i>
                    </button>
                    <div class="meta-accordion-body" id="metaBody">
                        <pre class="json-box" style="margin:0;">{{ json_encode($incident->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <script>
    document.getElementById('metaToggle')?.addEventListener('click', function () {
        this.classList.toggle('open');
        document.getElementById('metaBody').classList.toggle('open');
    });
    </script>
@endsection
