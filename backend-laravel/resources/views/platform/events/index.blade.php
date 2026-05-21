@extends('layouts.soc')

@section('title', 'RansomShield — Événements')
@section('page_title', 'Événements techniques')
@section('page_subtitle', 'Flux brut enrichi envoyé par les agents RansomShield')

@section('content')
    @include('platform.partials.page-tools-style')

    @php
        $riskFilters = [
            'all' => 'Tous',
            'normal' => 'Normal',
            'suspect' => 'Suspect',
            'high' => 'High',
            'critical' => 'Critical',
        ];

        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $eventIcon = fn ($type) => match ($type) {
            'file_created' => '📄',
            'file_modified' => '✏️',
            'file_moved' => '🔁',
            'file_encrypted_extension' => '🔐',
            'mass_rename_detected' => '⚠️',
            'ransom_note_created' => '📝',
            'suspicious_process_detected' => '🧪',
            default => '📌',
        };
    @endphp

    <style>
        .event-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #ef4444 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .event-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .event-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
        }

        .event-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .event-filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: end;
            margin-top: 18px;
        }

        .event-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 950;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .event-grid {
            display: grid;
            gap: 14px;
        }

        .event-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 16px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .event-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-size: 24px;
        }

        .event-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .event-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
            word-break: break-word;
        }

        .event-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
        }

        .event-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        @media (max-width: 1100px) {
            .event-filter-form {
                grid-template-columns: 1fr;
            }

            .event-card {
                grid-template-columns: 52px 1fr;
            }

            .event-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: 1fr;
            }

            .event-actions .action-btn {
                width: 100%;
            }
        }
    </style>

    <div class="animated-page">
        <section class="event-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Journal technique agents
            </div>

            <h2>Observer le flux réel des agents.</h2>

            <p>
                Cette page affiche les événements reçus depuis les agents hôtes :
                fichiers modifiés, extensions suspectes, renommages, processus suspects et signaux enrichis.
            </p>

            <div class="event-filter-row">
                @foreach($riskFilters as $key => $label)
                    <a href="{{ route('platform.events.index', array_filter(['risk' => $key, 'type' => $activeType, 'agent_id' => $activeAgentId])) }}"
                       class="action-btn {{ $activeRisk === $key ? 'primary' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('platform.events.index') }}" class="event-filter-form">
                <input type="hidden" name="risk" value="{{ $activeRisk }}">

                <div class="event-field">
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

                <div class="event-field">
                    <label>Type événement</label>
                    <select name="type" class="form-control">
                        <option value="">Tous les types</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" @selected($activeType === $type)>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="action-btn primary" type="submit">Filtrer</button>
            </form>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Événements reçus.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Critical</div>
                <div class="smart-stat-value">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Événements critiques.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">High</div>
                <div class="smart-stat-value">{{ $stats['high'] }}</div>
                <div class="smart-stat-hint">Événements élevés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Simulation</div>
                <div class="smart-stat-value">{{ $stats['simulation'] }}</div>
                <div class="smart-stat-hint">Tests contrôlés.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Flux des événements</h3>
                    <p class="soc-card-subtitle">Derniers événements enregistrés par les agents.</p>
                </div>

                <a href="{{ route('platform.dashboard') }}" class="action-btn primary">Dashboard</a>
            </div>

            @if($events->count())
                <div class="event-grid">
                    @foreach($events as $event)
                        @php
                            $signals = collect(data_get($event->metadata, 'signals', []));
                            $threshold = data_get($event->metadata, 'threshold');
                        @endphp

                        <article class="event-card">
                            <div class="event-icon">{{ $eventIcon($event->event_type) }}</div>

                            <div>
                                <h3 class="event-title">{{ $event->event_type }}</h3>

                                <div class="event-meta">
                                    Agent : {{ $event->agent?->agent_name ?? '—' }}
                                    —
                                    Date : {{ $event->observed_at?->format('d/m/Y H:i:s') ?? $event->created_at?->format('d/m/Y H:i:s') }}
                                    <br>
                                    Chemin : <span class="mono">{{ $event->path ?? '—' }}</span>
                                </div>

                                <div class="event-badges">
                                    <span class="badge {{ $riskClass($event->risk_level) }}">{{ $event->risk_level }}</span>
                                    <span class="badge">Score : {{ $event->score }}</span>
                                    <span class="badge">Extension : {{ $event->file_extension ?? '—' }}</span>
                                    <span class="badge">Signaux : {{ $signals->count() }}</span>

                                    @if($event->is_simulation)
                                        <span class="badge badge-suspect">Simulation</span>
                                    @endif

                                    @if($threshold)
                                        <span class="badge">Seuil : {{ data_get($threshold, 'code', '—') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="event-actions">
                                <a href="{{ route('platform.events.show', $event) }}" class="action-btn primary">Détail</a>

                                @if($event->alert)
                                    <a href="{{ route('platform.alerts.show', $event->alert) }}" class="action-btn">Alerte</a>
                                @endif

                                @if($event->incident)
                                    <a href="{{ route('platform.incidents.show', $event->incident) }}" class="action-btn">Incident</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $events->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun événement.',
                    'message' => 'Démarre l'agent Python hôte complet pour alimenter ce journal.'
                ])
            @endif
        </section>
    </div>
@endsection
