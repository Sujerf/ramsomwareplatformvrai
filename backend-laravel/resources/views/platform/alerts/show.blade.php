@extends('layouts.soc')

@section('title', 'RansomShield — Alerte')
@section('page_title', 'Détail alerte')
@section('page_subtitle', $alert->title)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskColor = match ($alert->risk_level) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#6366f1',
        };

        $riskIcon = match ($alert->risk_level) {
            'critical' => 'fa-skull-crossbones',
            'high'     => 'fa-triangle-exclamation',
            'suspect'  => 'fa-eye',
            default    => 'fa-bell',
        };

        $statusLabel = match ($alert->status) {
            'open'           => 'Ouverte',
            'acknowledged'   => 'Reconnue',
            'investigating'  => 'En investigation',
            'resolved'       => 'Résolue',
            'false_positive' => 'Faux positif',
            default          => $alert->status,
        };

        $statusColor = match ($alert->status) {
            'resolved'       => '#22c55e',
            'false_positive' => '#eab308',
            'investigating',
            'acknowledged'   => '#f97316',
            default          => '#ef4444',
        };

        $signals    = data_get($alert->metadata, 'signals', []);
        $path       = data_get($alert->metadata, 'path');
        $isSim      = data_get($alert->metadata, 'is_simulation', false);
        $isActive   = !in_array($alert->status, ['resolved', 'false_positive'], true);
    @endphp

    <style>
        /* Hero */
        .al-show-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            background:
                radial-gradient(circle at 8% 20%, color-mix(in srgb, {{ $riskColor }} 14%, transparent), transparent 30%),
                radial-gradient(circle at 90% 5%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 25%),
                var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left: 4px solid {{ $riskColor }};
            box-shadow: var(--shadow-soft);
        }

        .al-show-hero h2 {
            margin: 0;
            font-size: clamp(28px, 4vw, 52px);
            line-height: 1;
            letter-spacing: -.06em;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .al-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: color-mix(in srgb, {{ $riskColor }} 14%, transparent);
            color: {{ $riskColor }};
            flex-shrink: 0;
        }

        .al-show-hero p {
            color: var(--text-muted);
            max-width: 780px;
            line-height: 1.75;
            margin-top: 12px;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        /* Status bar */
        .al-status-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border-soft);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-soft);
        }

        .status-block {
            padding: 16px 20px;
            background: var(--bg-card);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .status-block-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .status-block-value {
            font-size: 15px;
            font-weight: 850;
            letter-spacing: -.02em;
        }

        @media (max-width: 700px) {
            .al-status-bar { grid-template-columns: repeat(2, 1fr); }
        }

        /* Context strip */
        .al-ctx {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .ctx-block {
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
        }

        .ctx-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .ctx-value {
            font-size: 14px;
            font-weight: 750;
            letter-spacing: -.02em;
        }

        @media (max-width: 700px) {
            .al-ctx { grid-template-columns: repeat(2, 1fr); }
        }

        /* Signal cards */
        .signal-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .signal-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border-left-width: 3px;
        }

        .signal-card.risk-critical { border-left-color: #ef4444; }
        .signal-card.risk-high     { border-left-color: #f97316; }
        .signal-card.risk-suspect  { border-left-color: #eab308; }
        .signal-card.risk-normal   { border-left-color: #6366f1; }

        .signal-icon-col {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .signal-body { flex: 1; min-width: 0; }

        .signal-name {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .signal-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Recommendation box */
        .rec-box {
            padding: 18px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--accent) 6%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 16%, transparent);
            font-size: 14px;
            line-height: 1.7;
        }

        .rec-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: -.01em;
            margin-bottom: 8px;
            color: var(--accent);
        }

        /* Metadata accordion */
        .meta-toggle-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .meta-toggle-btn:hover { color: var(--text-main); }

        .meta-body {
            display: none;
            margin-top: 10px;
        }

        .meta-body.open { display: block; }

        .meta-pre {
            background: #0a0f1e;
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            border-radius: 14px;
            padding: 16px;
            font-size: 12px;
            font-family: monospace;
            color: #a5b4fc;
            overflow-x: auto;
            line-height: 1.6;
        }

        /* Path box */
        .path-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #0a0f1e;
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-family: monospace;
            font-size: 13px;
            color: #a5b4fc;
            word-break: break-all;
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="al-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Alerte #{{ $alert->id }}
                @if($isSim)
                    &nbsp;·&nbsp;<span style="color:#6366f1">Simulation contrôlée</span>
                @endif
            </div>

            <h2>
                <span class="al-hero-icon"><i class="fa-solid {{ $riskIcon }}"></i></span>
                {{ $alert->title }}
            </h2>

            @if($alert->message)
                <p>{{ $alert->message }}</p>
            @endif

            <div class="btn-row">
                <a href="{{ route('platform.alerts.index') }}" class="action-btn">
                    <i class="fa-solid fa-arrow-left"></i> Retour alertes
                </a>
                @if($alert->incident)
                    <a href="{{ route('platform.incidents.show', $alert->incident) }}" class="action-btn primary">
                        <i class="fa-solid fa-shield-halved"></i> Ouvrir incident
                    </a>
                @endif

                @if($isActive)
                    <form method="POST" action="{{ route('platform.alerts.resolve', $alert) }}" style="display:contents">
                        @csrf @method('PATCH')
                        <button class="action-btn lg success" type="submit">
                            <i class="fa-solid fa-check"></i> Résoudre
                        </button>
                    </form>
                    <form method="POST" action="{{ route('platform.alerts.false-positive', $alert) }}" style="display:contents">
                        @csrf @method('PATCH')
                        <button class="action-btn lg warning" type="submit">
                            <i class="fa-solid fa-xmark"></i> Faux positif
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.alerts.reopen', $alert) }}" style="display:contents">
                        @csrf @method('PATCH')
                        <button class="action-btn lg" type="submit">
                            <i class="fa-solid fa-rotate-left"></i> Réouvrir
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Status bar --}}
        <div class="al-status-bar section-gap">
            <div class="status-block">
                <div class="status-block-label">Risque</div>
                <div class="status-block-value" style="color:{{ $riskColor }}">{{ ucfirst($alert->risk_level) }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Score</div>
                <div class="status-block-value">{{ $alert->score }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Statut</div>
                <div class="status-block-value" style="color:{{ $statusColor }}">{{ $statusLabel }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Mode</div>
                <div class="status-block-value" style="color:{{ $isSim ? '#6366f1' : 'var(--text-main)' }}">
                    {{ $isSim ? 'Simulation' : 'Réel' }}
                </div>
            </div>
        </div>

        {{-- Context strip --}}
        <div class="al-ctx section-gap">
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-microchip" style="margin-right:5px"></i>Agent</div>
                <div class="ctx-value">{{ $alert->agent?->agent_name ?? '—' }}</div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-shield-halved" style="margin-right:5px"></i>Incident</div>
                <div class="ctx-value">
                    @if($alert->incident)
                        <a href="{{ route('platform.incidents.show', $alert->incident) }}" style="color:var(--accent)">
                            {{ Str::limit($alert->incident->title, 30) }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-bolt" style="margin-right:5px"></i>Événement</div>
                <div class="ctx-value">{{ $alert->event?->event_type ?? '—' }}</div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-regular fa-clock" style="margin-right:5px"></i>Détectée</div>
                <div class="ctx-value">{{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        </div>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid {{ $riskIcon }}"></i></div>
                <div class="smart-stat-label">Risque</div>
                <div class="smart-stat-value" style="color:{{ $riskColor }}">{{ ucfirst($alert->risk_level) }}</div>
                <div class="smart-stat-hint">Niveau attribué par le moteur.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-chart-bar"></i></div>
                <div class="smart-stat-label">Score</div>
                <div class="smart-stat-value">{{ $alert->score }}</div>
                <div class="smart-stat-hint">Score de dangerosité calculé.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-bell"></i></div>
                <div class="smart-stat-label">Notifications</div>
                <div class="smart-stat-value">{{ $alert->notifications->count() }}</div>
                <div class="smart-stat-hint">Alertes UI / son / email.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-list-check"></i></div>
                <div class="smart-stat-label">Signaux</div>
                <div class="smart-stat-value">{{ count($signals) }}</div>
                <div class="smart-stat-hint">Règles de détection déclenchées.</div>
            </div>
        </section>

        {{-- Signals + Recommendation --}}
        <div class="grid grid-2 section-gap">
            {{-- Signals --}}
            <div class="soc-card">
                <h3 class="soc-card-title">Signaux déclencheurs</h3>
                <p class="soc-card-subtitle">Règles activées par le moteur pour cette alerte.</p>

                <div class="signal-list section-gap">
                    @forelse($signals as $sig)
                        @php
                            $sl = data_get($sig, 'risk_level', 'normal');
                            $sc = match($sl) {
                                'critical' => '#ef4444',
                                'high'     => '#f97316',
                                'suspect'  => '#eab308',
                                default    => '#6366f1',
                            };
                        @endphp
                        <div class="signal-card risk-{{ $sl }}">
                            <div class="signal-icon-col" style="background:color-mix(in srgb, {{ $sc }} 12%, transparent); color:{{ $sc }}">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <div class="signal-body">
                                <div class="signal-name">{{ data_get($sig, 'rule_name') ?? data_get($sig, 'rule_code', 'Règle inconnue') }}</div>
                                <div class="signal-meta">
                                    Risque : {{ $sl }}
                                    @if(data_get($sig, 'score_weight'))
                                        &nbsp;·&nbsp; Poids : {{ $sig['score_weight'] }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--text-muted); font-size:13px">
                            Aucun signal détaillé disponible. L'alerte existe mais ses signaux ne sont pas encore enrichis.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Recommendation --}}
            <div class="soc-card">
                <h3 class="soc-card-title">Recommandation SOC</h3>
                <p class="soc-card-subtitle">Marche à suivre recommandée pour cette alerte.</p>

                <div class="rec-box section-gap">
                    @if($alert->risk_level === 'critical')
                        <div class="rec-title"><i class="fa-solid fa-skull-crossbones" style="margin-right:6px"></i>Alerte critique — action immédiate</div>
                        Ouvre l'incident lié et confirme si l'événement est réel ou une simulation.
                        Traite les actions de protection proposées avant de résoudre.
                    @elseif($alert->risk_level === 'high')
                        <div class="rec-title"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px"></i>Risque élevé</div>
                        Vérifie l'agent concerné, le chemin impliqué et les répétitions d'événements similaires.
                    @else
                        <div class="rec-title"><i class="fa-solid fa-eye" style="margin-right:6px"></i>Alerte à surveiller</div>
                        Consulte le contexte complet avant de résoudre ou classer en faux positif.
                    @endif

                    @if($path)
                        <div style="margin-top:14px">
                            <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:6px">CHEMIN CONCERNÉ</div>
                            <div class="path-box">
                                <i class="fa-solid fa-folder-open" style="color:#6366f1"></i>
                                {{ $path }}
                            </div>
                        </div>
                    @endif

                    @if($alert->resolved_at)
                        <div style="margin-top:14px; font-size:12px; color:#22c55e">
                            <i class="fa-solid fa-circle-check" style="margin-right:5px"></i>
                            Résolue le {{ $alert->resolved_at->format('d/m/Y à H:i') }}
                            @if($alert->resolvedBy)
                                par {{ $alert->resolvedBy->name }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Metadata accordion --}}
        <div class="soc-card section-gap">
            <button class="meta-toggle-btn" onclick="document.getElementById('metaBody').classList.toggle('open')">
                <i class="fa-solid fa-code"></i>
                Métadonnées brutes
                <i class="fa-solid fa-chevron-down" style="font-size:10px"></i>
            </button>
            <div id="metaBody" class="meta-body">
                <pre class="meta-pre">{{ json_encode($alert->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

    </div>
@endsection
