@extends('layouts.soc')

@section('title', 'RansomShield — Détail événement')
@section('page_title', 'Détail événement')
@section('page_subtitle', $event->event_type)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $signals        = collect(data_get($event->metadata, 'signals', []));
        $threshold      = data_get($event->metadata, 'threshold');
        $matchedPolicies = collect(data_get($event->metadata, 'matched_policies', []));

        $riskColor = match ($event->risk_level) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#6366f1',
        };

        $riskIcon = match (true) {
            str_contains($event->event_type, 'encrypt')  => 'fa-lock',
            str_contains($event->event_type, 'rename')   => 'fa-arrows-rotate',
            str_contains($event->event_type, 'ransom')   => 'fa-file-circle-exclamation',
            str_contains($event->event_type, 'process')  => 'fa-gears',
            str_contains($event->event_type, 'created')  => 'fa-file-circle-plus',
            str_contains($event->event_type, 'modified') => 'fa-file-pen',
            str_contains($event->event_type, 'moved')    => 'fa-file-export',
            str_contains($event->event_type, 'deleted')  => 'fa-file-circle-minus',
            default                                       => 'fa-bolt',
        };
    @endphp

    <style>
        .ev-show-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            border-left: 4px solid {{ $riskColor }};
            background:
                radial-gradient(circle at 8% 20%, color-mix(in srgb, {{ $riskColor }} 12%, transparent), transparent 30%),
                radial-gradient(circle at 90% 5%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 25%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .ev-show-hero h2 {
            margin: 0;
            font-size: clamp(26px, 4vw, 50px);
            line-height: 1;
            letter-spacing: -.06em;
            font-weight: 950;
            font-family: monospace;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .ev-hero-icon {
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
            font-family: sans-serif;
        }

        .ev-show-hero .ev-path-display {
            margin-top: 12px;
            font-size: 13px;
            font-family: monospace;
            color: var(--text-muted);
            word-break: break-all;
            line-height: 1.5;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        /* Status bar */
        .ev-status-bar {
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
            .ev-status-bar { grid-template-columns: repeat(2, 1fr); }
        }

        /* Context strip */
        .ev-ctx {
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
            .ev-ctx { grid-template-columns: repeat(2, 1fr); }
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
            font-size: 14px;
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

        /* Policy cards */
        .policy-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--accent) 5%, transparent);
            border-left: 3px solid var(--accent);
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

        .meta-body { display: none; margin-top: 10px; }
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
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="ev-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Événement #{{ $event->id }}
                @if($event->is_simulation)
                    &nbsp;·&nbsp;<span style="color:#6366f1">Simulation contrôlée</span>
                @endif
            </div>

            <h2>
                <span class="ev-hero-icon"><i class="fa-solid {{ $riskIcon }}"></i></span>
                {{ $event->event_type }}
            </h2>

            @if($event->path)
                <div class="ev-path-display">
                    <i class="fa-solid fa-folder-open" style="margin-right:6px; color:{{ $riskColor }}"></i>{{ $event->path }}
                </div>
            @endif

            <div class="btn-row">
                <a href="{{ route('platform.events.index') }}" class="action-btn">
                    <i class="fa-solid fa-arrow-left"></i> Tous les événements
                </a>
                @if($event->alert)
                    <a href="{{ route('platform.alerts.show', $event->alert) }}" class="action-btn warning">
                        <i class="fa-solid fa-bell"></i> Voir alerte liée
                    </a>
                @endif
                @if($event->incident)
                    <a href="{{ route('platform.incidents.show', $event->incident) }}" class="action-btn danger">
                        <i class="fa-solid fa-shield-halved"></i> Voir incident lié
                    </a>
                @endif
            </div>
        </section>

        {{-- Status bar --}}
        <div class="ev-status-bar section-gap">
            <div class="status-block">
                <div class="status-block-label">Risque</div>
                <div class="status-block-value" style="color:{{ $riskColor }}">{{ ucfirst($event->risk_level) }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Score</div>
                <div class="status-block-value">{{ $event->score }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Extension</div>
                <div class="status-block-value" style="font-family:monospace">{{ $event->file_extension ?? '—' }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Mode</div>
                <div class="status-block-value" style="color:{{ $event->is_simulation ? '#6366f1' : 'var(--text-main)' }}">
                    {{ $event->is_simulation ? 'Simulation' : 'Réel' }}
                </div>
            </div>
        </div>

        {{-- Context strip --}}
        <div class="ev-ctx section-gap">
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-microchip" style="margin-right:5px"></i>Agent</div>
                <div class="ctx-value">{{ $event->agent?->agent_name ?? '—' }}</div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-bell" style="margin-right:5px"></i>Alerte</div>
                <div class="ctx-value">
                    @if($event->alert)
                        <a href="{{ route('platform.alerts.show', $event->alert) }}" style="color:var(--accent)">
                            {{ Str::limit($event->alert->title, 28) }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-solid fa-shield-halved" style="margin-right:5px"></i>Incident</div>
                <div class="ctx-value">
                    @if($event->incident)
                        <a href="{{ route('platform.incidents.show', $event->incident) }}" style="color:#ef4444">
                            {{ Str::limit($event->incident->title, 28) }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="ctx-block">
                <div class="ctx-label"><i class="fa-regular fa-clock" style="margin-right:5px"></i>Observé</div>
                <div class="ctx-value">{{ $event->observed_at?->format('d/m/Y H:i') ?? $event->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        </div>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-list-check"></i></div>
                <div class="smart-stat-label">Signaux</div>
                <div class="smart-stat-value">{{ $signals->count() }}</div>
                <div class="smart-stat-hint">Règles déclenchées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="smart-stat-label">Politiques</div>
                <div class="smart-stat-value">{{ $matchedPolicies->count() }}</div>
                <div class="smart-stat-hint">Correspondances trouvées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-gauge-high"></i></div>
                <div class="smart-stat-label">Seuil</div>
                <div class="smart-stat-value" style="font-size:18px; color:{{ $riskColor }}">
                    {{ data_get($threshold, 'risk_level', $event->risk_level) }}
                </div>
                <div class="smart-stat-hint">{{ data_get($threshold, 'code', 'Aucun seuil') }}</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-chart-bar"></i></div>
                <div class="smart-stat-label">Score</div>
                <div class="smart-stat-value">{{ $event->score }}</div>
                <div class="smart-stat-hint">Score de dangerosité.</div>
            </div>
        </section>

        {{-- Signals + Policies --}}
        <div class="grid grid-2 section-gap">
            <div class="soc-card">
                <h3 class="soc-card-title">Signaux du moteur</h3>
                <p class="soc-card-subtitle">Règles et extensions qui ont contribué au score.</p>

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
                                <div class="signal-name">{{ data_get($sig, 'label') ?? data_get($sig, 'code', 'Signal') }}</div>
                                <div class="signal-meta">
                                    Source : {{ data_get($sig, 'source', '—') }}
                                    &nbsp;·&nbsp; Score : {{ data_get($sig, 'score', 0) }}
                                    &nbsp;·&nbsp; Risque : {{ $sl }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--text-muted); font-size:13px">Aucun signal détaillé pour cet événement.</p>
                    @endforelse
                </div>
            </div>

            <div class="soc-card">
                <h3 class="soc-card-title">Politiques correspondantes</h3>
                <p class="soc-card-subtitle">Actions possibles selon le niveau de risque.</p>

                <div class="signal-list section-gap">
                    @forelse($matchedPolicies as $policy)
                        <div class="policy-card">
                            <div class="signal-icon-col" style="background:color-mix(in srgb, var(--accent) 12%, transparent); color:var(--accent)">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="signal-body">
                                <div class="signal-name">{{ data_get($policy, 'name') ?? data_get($policy, 'code', 'Politique') }}</div>
                                <div class="signal-meta">
                                    Action : {{ data_get($policy, 'action_type', '—') }}
                                    &nbsp;·&nbsp; Mode : {{ data_get($policy, 'execution_mode', '—') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--text-muted); font-size:13px">Aucune politique associée à cet événement.</p>
                    @endforelse
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
                <pre class="meta-pre">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

    </div>
@endsection
