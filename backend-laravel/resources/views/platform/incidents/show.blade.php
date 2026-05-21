@extends('layouts.soc')

@section('title', 'RansomShield — Fiche incident')
@section('page_title', 'Fiche incident')
@section('page_subtitle', $incident->title)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high' => 'badge-high',
                'suspect' => 'badge-suspect',
                default => 'badge-normal',
            };
        };

        $statusClass = function ($status) {
            return match ($status) {
                'resolved' => 'badge-normal',
                'false_positive' => 'badge-suspect',
                'under_review', 'investigating', 'reopened' => 'badge-high',
                default => 'badge-critical',
            };
        };

        $signals = collect(data_get($incident->metadata, 'signals', []));
        $threshold = data_get($incident->metadata, 'threshold');
    @endphp

    <style>
        .incident-show-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 15%, color-mix(in srgb, #ef4444 16%, transparent), transparent 28%),
                radial-gradient(circle at 85% 12%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .incident-show-hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: .98;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .incident-show-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
            margin-top: 14px;
        }

        .incident-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .timeline {
            display: grid;
            gap: 12px;
        }

        .timeline-item {
            position: relative;
            padding: 14px 14px 14px 46px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            border: 1px solid var(--border-soft);
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 18px;
            top: 18px;
            width: 13px;
            height: 13px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 5px color-mix(in srgb, var(--accent) 14%, transparent);
        }

        .timeline-title {
            margin: 0;
            font-weight: 950;
            font-size: 14px;
            letter-spacing: -.02em;
        }

        .timeline-meta {
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
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

        .action-list {
            display: grid;
            gap: 10px;
        }

        .linked-action {
            padding: 13px;
            border-radius: 18px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
        }

        @media (max-width: 1100px) {
            .incident-detail-grid {
                grid-template-columns: 1fr;
            }

            .linked-action {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="incident-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Investigation incident
            </div>

            <h2>{{ $incident->title }}</h2>

            <p>
                {{ $incident->description ?? 'Incident généré par le moteur dynamique de détection RansomShield.' }}
            </p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="badge {{ $riskClass($incident->risk_level) }}">Risque : {{ $incident->risk_level }}</span>
                <span class="badge">Score : {{ $incident->risk_score }}</span>
                <span class="badge {{ $statusClass($incident->status) }}">Statut : {{ $incident->status }}</span>
                <span class="badge">Agent : {{ $incident->agent?->agent_name ?? '—' }}</span>
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.incidents.index', ['status' => 'all']) }}" class="btn btn-soft">← Historique incidents</a>

                @if(!in_array($incident->status, ['resolved', 'false_positive'], true))
                    <form method="POST" action="{{ route('platform.incidents.resolve', $incident) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" type="submit">Résoudre incident</button>
                    </form>

                    <form method="POST" action="{{ route('platform.incidents.false-positive', $incident) }}">
                        @csrf
                        @method('PATCH')
                        <button class="action-btn warning" type="submit">Classer faux positif</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.incidents.reopen', $incident) }}">
                        @csrf
                        @method('PATCH')
                        <button class="action-btn" type="submit">Réouvrir incident</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Alertes liées</div>
                <div class="smart-stat-value">{{ $incident->alerts->count() }}</div>
                <div class="smart-stat-hint">Signaux rattachés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Événements</div>
                <div class="smart-stat-value">{{ $incident->events->count() }}</div>
                <div class="smart-stat-hint">Événements techniques.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Actions</div>
                <div class="smart-stat-value">{{ $incident->protectionActions->count() }}</div>
                <div class="smart-stat-hint">Réponses proposées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Seuil</div>
                <div class="smart-stat-value">{{ data_get($threshold, 'risk_level', $incident->risk_level) }}</div>
                <div class="smart-stat-hint">Classification moteur.</div>
            </div>
        </section>

        <section class="incident-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Signaux détectés</h3>
                        <p class="soc-card-subtitle">Signaux provenant du moteur dynamique.</p>
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
                        'message' => 'Les prochains incidents générés par le moteur dynamique stockeront leurs signaux ici.'
                    ])
                @endif
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Actions de protection</h3>
                        <p class="soc-card-subtitle">Réponses proposées ou déjà traitées.</p>
                    </div>
                </div>

                <div class="action-list">
                    @forelse($incident->protectionActions as $action)
                        <div class="linked-action">
                            <div>
                                <strong>{{ $action->action_type }}</strong>
                                <div class="timeline-meta">
                                    {{ $action->approval_status }} / {{ $action->execution_status }}
                                    —
                                    politique : {{ $action->protectionPolicy?->code ?? '—' }}
                                </div>
                            </div>

                            <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn primary">Ouvrir</a>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune action.',
                            'message' => 'Aucune politique n’a proposé d’action pour cet incident.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="incident-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Alertes liées</h3>
                        <p class="soc-card-subtitle">Alertes rattachées à cet incident.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($incident->alerts as $alert)
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $alert->title }}</h4>
                            <div class="timeline-meta">
                                {{ $alert->status }} —
                                {{ $alert->risk_level }} —
                                {{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}
                            </div>
                            <div class="section-gap">
                                <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn">Voir alerte</a>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune alerte liée.',
                            'message' => 'Cet incident ne contient pas encore d’alerte associée.'
                        ])
                    @endforelse
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Événements techniques</h3>
                        <p class="soc-card-subtitle">Événements rattachés à l’incident.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($incident->events as $event)
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $event->event_type }}</h4>
                            <div class="timeline-meta">
                                {{ $event->path ?? '—' }}
                                <br>
                                Score {{ $event->score }} —
                                {{ $event->risk_level }} —
                                {{ $event->observed_at?->format('d/m/Y H:i') ?? $event->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun événement lié.',
                            'message' => 'Les événements techniques apparaîtront ici selon les relations disponibles.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
