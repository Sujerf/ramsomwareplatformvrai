@extends('layouts.soc')

@section('title', 'RansomShield — Fiche agent')
@section('page_title', 'Fiche agent')
@section('page_subtitle', $agent->agent_name)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $enrollClass = fn ($status) => match ($status) {
            'pending' => 'badge-high',
            'enrolled' => 'badge-normal',
            default => 'badge-suspect',
        };
    @endphp

    <style>
        .agent-show-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .agent-show-hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: .98;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .agent-show-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
        }

        .agent-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .command-box {
            position: relative;
            padding: 16px;
            border-radius: 20px;
            background: #020617;
            color: #dbeafe;
            border: 1px solid rgba(148, 163, 184, .25);
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.7;
        }

        .timeline {
            display: grid;
            gap: 12px;
        }

        .timeline-item {
            padding: 14px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            border: 1px solid var(--border-soft);
        }

        .timeline-title {
            margin: 0;
            font-size: 14px;
            font-weight: 950;
        }

        .timeline-meta {
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 1100px) {
            .agent-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="agent-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Machine surveillée
            </div>

            <h2>{{ $agent->agent_name }}</h2>

            <p>
                Cette fiche montre l’état d’enrôlement, l’hôte réseau lié, la commande d’installation
                et les dernières données de sécurité de la machine.
            </p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="badge {{ $enrollClass($agent->enrollment_status) }}">
                    Enrôlement : {{ $agent->enrollment_status ?? 'enrolled' }}
                </span>
                <span class="badge {{ $riskClass($agent->risk_level) }}">Risque : {{ $agent->risk_level ?? 'normal' }}</span>
                <span class="badge">Score : {{ $agent->risk_score ?? 0 }}</span>
                <span class="badge">Statut : {{ $agent->status }}</span>
                <span class="badge">IP : {{ $agent->ip_address ?? '—' }}</span>
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.agents.index') }}" class="btn btn-soft">← Tous les agents</a>
                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-primary">Hôtes détectés</a>
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Événements</div>
                <div class="smart-stat-value">{{ $agent->events->count() }}</div>
                <div class="smart-stat-hint">Derniers événements chargés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Alertes</div>
                <div class="smart-stat-value">{{ $agent->alerts->count() }}</div>
                <div class="smart-stat-hint">Alertes récentes.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Incidents</div>
                <div class="smart-stat-value">{{ $agent->incidents->count() }}</div>
                <div class="smart-stat-hint">Incidents récents.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Actions</div>
                <div class="smart-stat-value">{{ $agent->protectionActions->count() }}</div>
                <div class="smart-stat-hint">Réponses proposées.</div>
            </div>
        </section>

        @if(($agent->enrollment_status ?? 'enrolled') !== 'enrolled')
            <section class="soc-card section-gap">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Commande d’installation / enrôlement</h3>
                        <p class="soc-card-subtitle">
                            À exécuter sur la machine détectée pour finaliser l’enrôlement.
                        </p>
                    </div>
                </div>

                <pre class="command-box">{{ $installCommand }}</pre>

                <div class="config-impact section-gap">
                    Après installation, l’agent doit appeler <span class="mono">/api/agent/enroll</span>.
                    La plateforme liera automatiquement l’agent à l’hôte détecté par IP ou hostname.
                </div>
            </section>
        @endif

        <section class="agent-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Hôte réseau lié</h3>
                        <p class="soc-card-subtitle">Information issue de la détection LAN.</p>
                    </div>
                </div>

                @if($agent->discoveredHost)
                    <div class="timeline">
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $agent->discoveredHost->hostname ?: $agent->discoveredHost->ip_address }}</h4>
                            <div class="timeline-meta">
                                Réseau : {{ $agent->discoveredHost->managedNetwork?->cidr ?? '—' }}
                                <br>
                                IP : {{ $agent->discoveredHost->ip_address }}
                                —
                                MAC : {{ $agent->discoveredHost->mac_address ?? '—' }}
                                <br>
                                Statut : {{ $agent->discoveredHost->discovery_status }}
                                —
                                Enrôlement : {{ $agent->discoveredHost->enrollment_status }}
                            </div>
                        </div>
                    </div>
                @else
                    @include('platform.partials.empty-state', [
                        'title' => 'Aucun hôte lié.',
                        'message' => 'Cet agent a été créé sans correspondance avec un hôte découvert.'
                    ])
                @endif
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Derniers événements</h3>
                        <p class="soc-card-subtitle">Événements reçus depuis l’agent.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->events as $event)
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $event->event_type }}</h4>
                            <div class="timeline-meta">
                                {{ $event->path ?? '—' }}
                                <br>
                                Score {{ $event->score }} —
                                {{ $event->risk_level }}
                                —
                                {{ $event->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun événement.',
                            'message' => 'Les événements apparaîtront quand l’agent Python commencera à envoyer les données.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="agent-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Alertes / incidents récents</h3>
                        <p class="soc-card-subtitle">Activité sécurité de cette machine.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->alerts as $alert)
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $alert->title }}</h4>
                            <div class="timeline-meta">
                                {{ $alert->risk_level }} —
                                {{ $alert->status }}
                                —
                                {{ $alert->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune alerte.',
                            'message' => 'Aucune alerte récente pour cet agent.'
                        ])
                    @endforelse
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Actions liées</h3>
                        <p class="soc-card-subtitle">Actions SOC proposées pour cette machine.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->protectionActions as $action)
                        <div class="timeline-item">
                            <h4 class="timeline-title">{{ $action->action_type }}</h4>
                            <div class="timeline-meta">
                                {{ $action->approval_status }} /
                                {{ $action->execution_status }}
                                —
                                {{ $action->created_at?->format('d/m/Y H:i') }}
                            </div>
                            <div class="section-gap">
                                <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn primary">Ouvrir</a>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune action.',
                            'message' => 'Aucune action liée à cet agent.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
