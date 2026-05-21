@extends('layouts.soc')

@section('title', 'RansomShield — Agents')
@section('page_title', 'Machines surveillées')
@section('page_subtitle', 'Agents enrôlés, pré-enrôlés et machines à risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $filters = [
            'all' => 'Tous',
            'pending' => 'À enrôler',
            'enrolled' => 'Enrôlés',
            'critical' => 'Critiques',
        ];

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
        .agent-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .agent-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .agent-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 860px;
        }

        .agent-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .agent-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .agent-card {
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

        .agent-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-size: 25px;
        }

        .agent-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .agent-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .agent-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
        }

        @media (max-width: 1100px) {
            .agent-grid {
                grid-template-columns: 1fr;
            }

            .agent-card {
                grid-template-columns: 52px 1fr;
            }

            .agent-card > a {
                grid-column: 1 / -1;
                width: 100%;
            }
        }
    </style>

    <div class="animated-page">
        <section class="agent-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Agents RansomShield
            </div>

            <h2>Suivre les machines réellement surveillées.</h2>

            <p>
                Un hôte détecté peut être pré-enrôlé. Lorsqu'un agent Python s'installe et appelle l'API,
                la machine passe en enrôlée et commence à envoyer ses événements.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-primary">Hôtes détectés</a>
                <a href="{{ route('platform.dashboard') }}" class="btn btn-soft">Dashboard</a>
            </div>

            <div class="agent-filter-row">
                @foreach($filters as $key => $label)
                    <a href="{{ route('platform.agents.index', ['status' => $key]) }}"
                       class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Agents créés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">À enrôler</div>
                <div class="smart-stat-value">{{ $stats['pending'] }}</div>
                <div class="smart-stat-hint">Installation agent attendue.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Enrôlés</div>
                <div class="smart-stat-value">{{ $stats['enrolled'] }}</div>
                <div class="smart-stat-hint">Machines actives.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Critiques</div>
                <div class="smart-stat-value">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Agents à risque.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Liste des agents</h3>
                    <p class="soc-card-subtitle">Machines pré-enrôlées, actives ou critiques.</p>
                </div>
            </div>

            @if($agents->count())
                <div class="agent-grid">
                    @foreach($agents as $agent)
                        <article class="agent-card">
                            <div class="agent-icon">💻</div>

                            <div>
                                <h3 class="agent-title">{{ $agent->agent_name }}</h3>

                                <div class="agent-meta">
                                    UUID : <span class="mono">{{ $agent->agent_uuid }}</span>
                                    <br>
                                    Hostname : {{ $agent->hostname ?? '—' }}
                                    —
                                    IP : <span class="mono">{{ $agent->ip_address ?? '—' }}</span>
                                    <br>
                                    Hôte lié :
                                    {{ $agent->discoveredHost?->ip_address ?? 'non lié' }}
                                </div>

                                <div class="agent-badges">
                                    <span class="badge {{ $enrollClass($agent->enrollment_status) }}">
                                        {{ $agent->enrollment_status ?? 'enrolled' }}
                                    </span>
                                    <span class="badge {{ $riskClass($agent->risk_level) }}">
                                        {{ $agent->risk_level ?? 'normal' }}
                                    </span>
                                    <span class="badge">Score : {{ $agent->risk_score ?? 0 }}</span>
                                    <span class="badge">Statut : {{ $agent->status }}</span>
                                </div>
                            </div>

                            <a href="{{ route('platform.agents.show', $agent) }}" class="action-btn primary">Ouvrir</a>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $agents->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun agent.',
                    'message' => "Va dans Hôtes détectés puis clique sur 'Enrôler' pour créer un agent."
                ])
            @endif
        </section>
    </div>
@endsection
