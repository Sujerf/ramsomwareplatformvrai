@extends('layouts.soc')

@section('title', 'RansomShield — Incidents')
@section('page_title', 'Incidents')
@section('page_subtitle', 'Suivi, investigation et historique des incidents ransomware')

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
                'under_review' => 'badge-high',
                'investigating', 'reopened' => 'badge-high',
                default => 'badge-critical',
            };
        };

        $statusFilters = [
            'active' => 'Actifs',
            'resolved' => 'Résolus',
            'false_positive' => 'Faux positifs',
            'all' => 'Tous',
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
        .incident-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            background:
                radial-gradient(circle at 18% 18%, color-mix(in srgb, #fb923c 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 12%, color-mix(in srgb, #ef4444 12%, transparent), transparent 32%),
                var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .incident-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .incident-hero p {
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

        .incident-card-grid {
            display: none;
            gap: 14px;
        }

        .incident-mobile-card {
            padding: 16px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .incident-mobile-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .incident-title {
            margin: 0;
            font-weight: 950;
            letter-spacing: -.03em;
            font-size: 15px;
        }

        .incident-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .incident-box {
            padding: 12px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--accent) 6%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 14%, transparent);
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .incident-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .incident-actions form {
            flex: 1 1 auto;
        }

        .incident-actions .action-btn {
            width: 100%;
        }

        @media (max-width: 900px) {
            .desktop-table-prefer {
                display: none !important;
            }

            .incident-card-grid {
                display: grid;
            }
        }
    </style>

    <div class="animated-page">
        <section class="incident-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Centre incidents SOC
            </div>

            <h2>Suivre les menaces jusqu'à leur clôture.</h2>

            <p>
                Les incidents regroupent les alertes, signaux, actions et décisions SOC. Un incident résolu
                disparaît des actifs, mais reste consultable dans l'historique.
            </p>

            <div class="filter-row">
                @foreach($statusFilters as $key => $label)
                    <a class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}"
                       href="{{ route('platform.incidents.index', array_filter(['status' => $key, 'risk' => $activeRisk])) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="filter-row">
                @foreach($riskFilters as $key => $label)
                    <a class="action-btn {{ $activeRisk === $key ? 'primary' : '' }}"
                       href="{{ route('platform.incidents.index', array_filter(['status' => $activeStatus, 'risk' => $key])) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Actifs</div>
                <div class="smart-stat-value">{{ $stats['active'] }}</div>
                <div class="smart-stat-hint">À investiguer ou valider.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Critical</div>
                <div class="smart-stat-value">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Incidents critiques enregistrés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Résolus</div>
                <div class="smart-stat-value">{{ $stats['resolved'] }}</div>
                <div class="smart-stat-hint">Historique traité.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Tous incidents confondus.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Liste des incidents</h3>
                    <p class="soc-card-subtitle">
                        Filtre actuel :
                        {{ $statusFilters[$activeStatus] ?? 'Tous' }}
                        @if($activeRisk)
                            / risque {{ $activeRisk }}
                        @endif
                    </p>
                </div>
            </div>

            @if($incidents->count())
                <div class="table-wrap desktop-table-prefer">
                    <table class="soc-table">
                        <thead>
                        <tr>
                            <th>Incident</th>
                            <th>Agent</th>
                            <th>Risque</th>
                            <th>Score</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($incidents as $incident)
                            <tr>
                                <td>
                                    <strong>{{ $incident->title }}</strong>
                                    <div class="incident-meta">{{ Str::limit($incident->description, 90) }}</div>
                                </td>

                                <td>{{ $incident->agent?->agent_name ?? '—' }}</td>

                                <td>
                                    <span class="badge {{ $riskClass($incident->risk_level) }}">{{ $incident->risk_level }}</span>
                                </td>

                                <td>{{ $incident->risk_score }}</td>

                                <td>
                                    <span class="badge {{ $statusClass($incident->status) }}">{{ $incident->status }}</span>
                                </td>

                                <td>{{ $incident->detected_at?->format('d/m/Y H:i') ?? $incident->created_at?->format('d/m/Y H:i') }}</td>

                                <td>
                                    <div class="inline-actions">
                                        <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn primary">Voir</a>

                                        @if(!in_array($incident->status, ['resolved', 'false_positive'], true))
                                            <form method="POST" action="{{ route('platform.incidents.resolve', $incident) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn success" type="submit">Résoudre</button>
                                            </form>

                                            <form method="POST" action="{{ route('platform.incidents.false-positive', $incident) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn warning" type="submit">Faux +</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('platform.incidents.reopen', $incident) }}">
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

                <div class="incident-card-grid">
                    @foreach($incidents as $incident)
                        <article class="incident-mobile-card">
                            <div class="incident-mobile-head">
                                <div>
                                    <h3 class="incident-title">{{ $incident->title }}</h3>
                                    <div class="incident-meta">
                                        {{ $incident->agent?->agent_name ?? 'Agent inconnu' }}
                                        —
                                        {{ $incident->detected_at?->format('d/m/Y H:i') ?? $incident->created_at?->format('d/m/Y H:i') }}
                                    </div>
                                </div>

                                <span class="badge {{ $riskClass($incident->risk_level) }}">{{ $incident->risk_level }}</span>
                            </div>

                            <div class="incident-box">
                                {{ $incident->description ?? 'Aucune description détaillée.' }}
                            </div>

                            <div class="filter-row">
                                <span class="badge {{ $statusClass($incident->status) }}">Statut : {{ $incident->status }}</span>
                                <span class="badge">Score : {{ $incident->risk_score }}</span>
                            </div>

                            <div class="incident-actions">
                                <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn primary">Voir détail</a>

                                @if(!in_array($incident->status, ['resolved', 'false_positive'], true))
                                    <form method="POST" action="{{ route('platform.incidents.resolve', $incident) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn success" type="submit">Résoudre</button>
                                    </form>

                                    <form method="POST" action="{{ route('platform.incidents.false-positive', $incident) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn warning" type="submit">Faux positif</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.incidents.reopen', $incident) }}">
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
                    {{ $incidents->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun incident pour ce filtre.',
                    'message' => "Change le filtre ou lance un test contrôlé depuis l'agent."
                ])
            @endif
        </section>
    </div>
@endsection
