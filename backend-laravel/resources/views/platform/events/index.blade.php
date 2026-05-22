@extends('layouts.soc')

@section('title', 'RansomShield — Événements')
@section('page_title', 'Événements techniques')
@section('page_subtitle', 'Flux brut enrichi envoyé par les agents RansomShield')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $eventIconClass = function ($type) {
            return match (true) {
                str_contains($type, 'encrypt')   => 'fa-lock',
                str_contains($type, 'rename')    => 'fa-arrows-rotate',
                str_contains($type, 'ransom')    => 'fa-file-circle-exclamation',
                str_contains($type, 'process')   => 'fa-gears',
                str_contains($type, 'created')   => 'fa-file-circle-plus',
                str_contains($type, 'modified')  => 'fa-file-pen',
                str_contains($type, 'moved')     => 'fa-file-export',
                str_contains($type, 'deleted')   => 'fa-file-circle-minus',
                default                          => 'fa-bolt',
            };
        };

        $riskColor = function ($risk) {
            return match ($risk) {
                'critical' => '#ef4444',
                'high'     => '#f97316',
                'suspect'  => '#eab308',
                default    => '#6366f1',
            };
        };
    @endphp

    <style>
        .ev-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 18%, color-mix(in srgb, var(--accent) 14%, transparent), transparent 28%),
                radial-gradient(circle at 88% 8%, color-mix(in srgb, #f97316 10%, transparent), transparent 30%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .ev-hero h2 {
            margin: 0;
            font-size: clamp(36px, 5vw, 64px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .ev-hero p {
            margin-top: 12px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 20px;
        }

        .filter-sep {
            width: 1px;
            background: var(--border-soft);
            margin: 0 4px;
            align-self: stretch;
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: .15s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .filter-tab:hover {
            border-color: color-mix(in srgb, var(--accent) 35%, transparent);
            color: var(--text-main);
        }

        .filter-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: color-mix(in srgb, var(--accent) 60%, transparent);
        }

        /* Advanced filter form */
        .ev-filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: end;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-soft);
        }

        .ev-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        /* Event cards */
        .event-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .event-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left-width: 4px;
            box-shadow: var(--shadow-soft);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }

        .event-card.risk-critical { border-left-color: #ef4444; }
        .event-card.risk-high     { border-left-color: #f97316; }
        .event-card.risk-suspect  { border-left-color: #eab308; }
        .event-card.risk-normal   { border-left-color: #6366f1; }

        .ev-icon-col {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .ev-body {
            flex: 1;
            min-width: 0;
        }

        .ev-title {
            margin: 0 0 4px;
            font-size: 15px;
            font-weight: 850;
            letter-spacing: -.02em;
            font-family: monospace;
        }

        .ev-path {
            font-size: 12px;
            color: var(--text-muted);
            font-family: monospace;
            margin-bottom: 6px;
            word-break: break-all;
        }

        .ev-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .ev-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .ev-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        @media (max-width: 800px) {
            .event-card { flex-direction: column; }
            .ev-filter-form { grid-template-columns: 1fr; }
            .ev-strip { width: 100%; }
            .ev-strip .action-btn { flex: 1 1 auto; justify-content: center; }
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="ev-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Journal technique agents
            </div>

            <h2>Observer le flux réel des agents.</h2>

            <p>Événements reçus depuis les agents hôtes : fichiers modifiés, extensions suspectes, renommages en masse,
                processus suspects et signaux enrichis par le moteur de détection.</p>

            {{-- Risk filter tabs --}}
            @php
                $riskFilters = ['all' => 'Tous', 'normal' => 'Normal', 'suspect' => 'Suspect', 'high' => 'High', 'critical' => 'Critical'];
            @endphp
            <div class="filter-tabs">
                @foreach($riskFilters as $key => $label)
                    <a class="filter-tab {{ $activeRisk === $key ? 'active' : '' }}"
                       href="{{ route('platform.events.index', array_filter(['risk' => $key, 'type' => $activeType, 'agent_id' => $activeAgentId], fn($v) => $v !== '' && $v !== null)) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Advanced filters --}}
            <form method="GET" action="{{ route('platform.events.index') }}" class="ev-filter-form">
                <input type="hidden" name="risk" value="{{ $activeRisk }}">

                <div class="ev-field">
                    <label>Agent</label>
                    <select name="agent_id" class="form-control">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) $activeAgentId === (string) $agent->id)>
                                {{ $agent->agent_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ev-field">
                    <label>Type d'événement</label>
                    <select name="type" class="form-control">
                        <option value="">Tous les types</option>
                        @foreach($eventTypes as $evType)
                            <option value="{{ $evType }}" @selected($activeType === $evType)>{{ $evType }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="action-btn primary" type="submit">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
            </form>
        </section>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-database"></i></div>
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Événements reçus.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-skull-crossbones"></i></div>
                <div class="smart-stat-label">Critical</div>
                <div class="smart-stat-value" style="{{ $stats['critical'] > 0 ? 'color:#ef4444' : '' }}">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Événements critiques.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="smart-stat-label">High</div>
                <div class="smart-stat-value" style="{{ $stats['high'] > 0 ? 'color:#f97316' : '' }}">{{ $stats['high'] }}</div>
                <div class="smart-stat-hint">Événements élevés.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-flask"></i></div>
                <div class="smart-stat-label">Simulation</div>
                <div class="smart-stat-value" style="color:#6366f1">{{ $stats['simulation'] }}</div>
                <div class="smart-stat-hint">Tests contrôlés.</div>
            </div>
        </section>

        {{-- Event list --}}
        @if($events->count())
            <div class="event-list section-gap">
                @foreach($events as $event)
                    @php
                        $ic  = $eventIconClass($event->event_type);
                        $col = $riskColor($event->risk_level);
                        $signals = collect(data_get($event->metadata, 'signals', []));
                    @endphp

                    <article class="event-card risk-{{ $event->risk_level }}">
                        <div class="ev-icon-col" style="background:color-mix(in srgb, {{ $col }} 12%, transparent); color:{{ $col }}">
                            <i class="fa-solid {{ $ic }}"></i>
                        </div>

                        <div class="ev-body">
                            <h4 class="ev-title">{{ $event->event_type }}</h4>
                            @if($event->path)
                                <div class="ev-path"><i class="fa-solid fa-folder-open" style="margin-right:4px"></i>{{ Str::limit($event->path, 80) }}</div>
                            @endif
                            <div class="ev-meta">
                                <i class="fa-solid fa-microchip" style="margin-right:4px"></i>{{ $event->agent?->agent_name ?? 'Agent inconnu' }}
                                &nbsp;·&nbsp;
                                <i class="fa-regular fa-clock" style="margin-right:4px"></i>{{ $event->observed_at?->diffForHumans() ?? $event->created_at?->diffForHumans() }}
                            </div>
                            <div class="ev-tags">
                                <span class="badge badge-{{ $event->risk_level === 'critical' ? 'critical' : ($event->risk_level === 'high' ? 'high' : ($event->risk_level === 'suspect' ? 'suspect' : 'normal')) }}">
                                    {{ $event->risk_level }}
                                </span>
                                <span class="badge">Score : {{ $event->score }}</span>
                                @if($event->file_extension)
                                    <span class="badge">{{ $event->file_extension }}</span>
                                @endif
                                @if($signals->count())
                                    <span class="badge">{{ $signals->count() }} signal{{ $signals->count() > 1 ? 's' : '' }}</span>
                                @endif
                                @if($event->is_simulation)
                                    <span class="badge" style="color:#6366f1; border-color:rgba(99,102,241,.3)">Simulation</span>
                                @endif
                            </div>
                        </div>

                        <div class="ev-strip">
                            <a href="{{ route('platform.events.show', $event) }}" class="action-btn primary">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir
                            </a>
                            @if($event->alert)
                                <a href="{{ route('platform.alerts.show', $event->alert) }}" class="action-btn warning">
                                    <i class="fa-solid fa-bell"></i> Alerte
                                </a>
                            @endif
                            @if($event->incident)
                                <a href="{{ route('platform.incidents.show', $event->incident) }}" class="action-btn danger">
                                    <i class="fa-solid fa-shield-halved"></i> Incident
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">
                {{ $events->links() }}
            </div>
        @else
            @include('platform.partials.empty-state', [
                'title'   => 'Aucun événement.',
                'message' => "Démarre l'agent Python hôte pour alimenter ce journal."
            ])
        @endif

    </div>
@endsection
