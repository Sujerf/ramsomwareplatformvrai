@extends('layouts.soc')

@section('title', 'RansomShield — Détail événement')
@section('page_title', 'Détail événement')
@section('page_subtitle', $event->event_type)

@section('content')
    @include('platform.partials.page-tools-style')

    @php
        $signals = collect(data_get($event->metadata, 'signals', []));
        $threshold = data_get($event->metadata, 'threshold');
        $matchedPolicies = collect(data_get($event->metadata, 'matched_policies', []));

        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };
    @endphp

    <style>
        .event-show-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #ef4444 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .event-show-hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: .98;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .event-show-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
            word-break: break-word;
        }

        .event-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .signal-grid {
            display: grid;
            gap: 10px;
        }

        .signal-card {
            padding: 13px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--accent) 6%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 14%, transparent);
        }

        .signal-card strong {
            display: block;
            font-size: 13px;
            font-weight: 950;
        }

        .signal-card small {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        @media (max-width: 1100px) {
            .event-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="event-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Événement agent
            </div>

            <h2>{{ $event->event_type }}</h2>

            <p>{{ $event->path ?? 'Aucun chemin associé.' }}</p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="badge {{ $riskClass($event->risk_level) }}">Risque : {{ $event->risk_level }}</span>
                <span class="badge">Score : {{ $event->score }}</span>
                <span class="badge">Extension : {{ $event->file_extension ?? '—' }}</span>
                <span class="badge">Agent : {{ $event->agent?->agent_name ?? '—' }}</span>

                @if($event->is_simulation)
                    <span class="badge badge-suspect">Simulation</span>
                @endif
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.events.index') }}" class="btn btn-soft">← Tous les événements</a>

                @if($event->alert)
                    <a href="{{ route('platform.alerts.show', $event->alert) }}" class="btn btn-primary">Voir alerte liée</a>
                @endif

                @if($event->incident)
                    <a href="{{ route('platform.incidents.show', $event->incident) }}" class="btn btn-soft">Voir incident lié</a>
                @endif
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Signaux</div>
                <div class="smart-stat-value">{{ $signals->count() }}</div>
                <div class="smart-stat-hint">Détectés par le moteur.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Politiques</div>
                <div class="smart-stat-value">{{ $matchedPolicies->count() }}</div>
                <div class="smart-stat-hint">Correspondances trouvées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Seuil</div>
                <div class="smart-stat-value" style="font-size:24px;">
                    {{ data_get($threshold, 'risk_level', $event->risk_level) }}
                </div>
                <div class="smart-stat-hint">{{ data_get($threshold, 'code', '—') }}</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Date</div>
                <div class="smart-stat-value" style="font-size:20px;">
                    {{ $event->observed_at?->format('d/m H:i') ?? $event->created_at?->format('d/m H:i') }}
                </div>
                <div class="smart-stat-hint">Observation.</div>
            </div>
        </section>

        <section class="event-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Signaux du moteur dynamique</h3>
                        <p class="soc-card-subtitle">Règles et extensions qui ont contribué au score.</p>
                    </div>
                </div>

                @if($signals->count())
                    <div class="signal-grid">
                        @foreach($signals as $signal)
                            <div class="signal-card">
                                <strong>{{ data_get($signal, 'label', data_get($signal, 'code', 'Signal')) }}</strong>
                                <small>
                                    Source : {{ data_get($signal, 'source', '—') }}
                                    —
                                    Score : {{ data_get($signal, 'score', 0) }}
                                    —
                                    Risque : {{ data_get($signal, 'risk_level', '—') }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('platform.partials.empty-state', [
                        'title' => 'Aucun signal détaillé.',
                        'message' => 'Cet événement ne contient pas encore les signaux détaillés du moteur.'
                    ])
                @endif
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Politiques correspondantes</h3>
                        <p class="soc-card-subtitle">Actions possibles selon le niveau de risque.</p>
                    </div>
                </div>

                <div class="signal-grid">
                    @forelse($matchedPolicies as $policy)
                        <div class="signal-card">
                            <strong>{{ data_get($policy, 'name', data_get($policy, 'code', 'Politique')) }}</strong>
                            <small>
                                Action : {{ data_get($policy, 'action_type', '—') }}
                                —
                                Mode : {{ data_get($policy, 'execution_mode', '—') }}
                            </small>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune politique.',
                            'message' => "Aucune politique n'a été associée à cet événement."
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Métadonnées complètes</h3>
                    <p class="soc-card-subtitle">Payload technique conservé pour audit.</p>
                </div>
            </div>

            <pre class="json-box">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    </div>
@endsection
