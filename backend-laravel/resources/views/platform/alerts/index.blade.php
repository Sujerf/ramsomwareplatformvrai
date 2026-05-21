@extends('layouts.soc')

@section('title', 'RansomShield — Alertes')
@section('page_title', 'Alertes')
@section('page_subtitle', 'Analyse, traitement et historique des signaux de sécurité')

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
                'investigating', 'acknowledged' => 'badge-high',
                default => 'badge-critical',
            };
        };

        $statusFilters = [
            'active' => 'Actives',
            'resolved' => 'Résolues',
            'false_positive' => 'Faux positifs',
            'all' => 'Toutes',
        ];

        $riskFilters = [
            null => 'Tous risques',
            'critical' => 'Critical',
            'high' => 'High',
            'suspect' => 'Suspect',
            'normal' => 'Normal',
        ];
    @endphp

    <style>
        .alert-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            background:
                radial-gradient(circle at 15% 15%, color-mix(in srgb, #ef4444 16%, transparent), transparent 28%),
                radial-gradient(circle at 85% 10%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 30%),
                var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .alert-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .alert-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 820px;
            margin-top: 14px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .alerts-card-grid {
            display: none;
            gap: 14px;
        }

        .alert-mobile-card {
            padding: 16px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .alert-mobile-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .alert-title {
            margin: 0;
            font-weight: 950;
            letter-spacing: -.03em;
            font-size: 15px;
        }

        .alert-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .alert-actions form {
            flex: 1 1 auto;
        }

        .alert-actions .action-btn {
            width: 100%;
        }

        .alert-signal-box {
            padding: 12px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--accent) 6%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 14%, transparent);
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .desktop-table-prefer {
                display: none !important;
            }

            .alerts-card-grid {
                display: grid;
            }
        }
    </style>

    <div class="animated-page">
        <section class="alert-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Centre d’alertes SOC
            </div>

            <h2>Prioriser les signaux importants.</h2>

            <p>
                Les alertes représentent les signaux générés par le moteur de détection.
                Les alertes traitées ne sont plus comptées comme actives, mais restent visibles dans l’historique.
            </p>

            <div class="filter-row">
                @foreach($statusFilters as $key => $label)
                    <a class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $key, 'risk' => $activeRisk])) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="filter-row">
                @foreach($riskFilters as $key => $label)
                    <a class="action-btn {{ $activeRisk === $key ? 'primary' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $activeStatus, 'risk' => $key])) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Actives</div>
                <div class="smart-stat-value">{{ $stats['active'] }}</div>
                <div class="smart-stat-hint">Alertes à traiter.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Critical</div>
                <div class="smart-stat-value">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Signaux critiques enregistrés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Résolues</div>
                <div class="smart-stat-value">{{ $stats['resolved'] }}</div>
                <div class="smart-stat-hint">Historique traité.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Toutes alertes confondues.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Liste des alertes</h3>
                    <p class="soc-card-subtitle">
                        Filtre actuel :
                        {{ $statusFilters[$activeStatus] ?? 'Toutes' }}
                        @if($activeRisk)
                            / risque {{ $activeRisk }}
                        @endif
                    </p>
                </div>
            </div>

            @if($alerts->count())
                <div class="table-wrap desktop-table-prefer">
                    <table class="soc-table">
                        <thead>
                        <tr>
                            <th>Alerte</th>
                            <th>Agent</th>
                            <th>Risque</th>
                            <th>Score</th>
                            <th>Statut</th>
                            <th>Incident</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td>
                                    <strong>{{ $alert->title }}</strong>
                                    <div class="alert-meta">{{ Str::limit($alert->message, 90) }}</div>
                                </td>

                                <td>{{ $alert->agent?->agent_name ?? '—' }}</td>

                                <td>
                                    <span class="badge {{ $riskClass($alert->risk_level) }}">{{ $alert->risk_level }}</span>
                                </td>

                                <td>{{ $alert->score }}</td>

                                <td>
                                    <span class="badge {{ $statusClass($alert->status) }}">{{ $alert->status }}</span>
                                </td>

                                <td>
                                    @if($alert->incident)
                                        <a href="{{ route('platform.incidents.show', $alert->incident) }}" class="action-btn">Incident</a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>{{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}</td>

                                <td>
                                    <div class="inline-actions">
                                        <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn primary">Voir</a>

                                        @if(!in_array($alert->status, ['resolved', 'false_positive'], true))
                                            <form method="POST" action="{{ route('platform.alerts.resolve', $alert) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn success" type="submit">Résoudre</button>
                                            </form>

                                            <form method="POST" action="{{ route('platform.alerts.false-positive', $alert) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn warning" type="submit">Faux +</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('platform.alerts.reopen', $alert) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn" type="submit">Réouvrir</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alerts-card-grid">
                    @foreach($alerts as $alert)
                        <article class="alert-mobile-card">
                            <div class="alert-mobile-head">
                                <div>
                                    <h3 class="alert-title">{{ $alert->title }}</h3>
                                    <div class="alert-meta">
                                        {{ $alert->agent?->agent_name ?? 'Agent inconnu' }}
                                        —
                                        {{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}
                                    </div>
                                </div>

                                <span class="badge {{ $riskClass($alert->risk_level) }}">{{ $alert->risk_level }}</span>
                            </div>

                            <div class="alert-signal-box">
                                {{ $alert->message ?? 'Aucun message détaillé.' }}
                            </div>

                            <div class="filter-row">
                                <span class="badge {{ $statusClass($alert->status) }}">Statut : {{ $alert->status }}</span>
                                <span class="badge">Score : {{ $alert->score }}</span>
                            </div>

                            <div class="alert-actions">
                                <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn primary">Voir détail</a>

                                @if(!in_array($alert->status, ['resolved', 'false_positive'], true))
                                    <form method="POST" action="{{ route('platform.alerts.resolve', $alert) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn success" type="submit">Résoudre</button>
                                    </form>

                                    <form method="POST" action="{{ route('platform.alerts.false-positive', $alert) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn warning" type="submit">Faux positif</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.alerts.reopen', $alert) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn" type="submit">Réouvrir</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $alerts->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucune alerte pour ce filtre.',
                    'message' => 'Change le filtre ou lance un test contrôlé depuis l’agent.'
                ])
            @endif
        </section>
    </div>
@endsection
