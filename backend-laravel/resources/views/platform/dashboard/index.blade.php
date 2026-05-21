@extends('layouts.soc')

@section('title', 'RansomShield — Dashboard SOC')
@section('page_title', 'Dashboard SOC')
@section('page_subtitle', 'Vue globale de surveillance, détection et réponse ransomware')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskTotal = max(1, array_sum($riskDistribution));

        $criticalPercent = round(($riskDistribution['critical'] / $riskTotal) * 100);
        $highPercent = round(($riskDistribution['high'] / $riskTotal) * 100);
        $suspectPercent = round(($riskDistribution['suspect'] / $riskTotal) * 100);
        $normalPercent = round(($riskDistribution['normal'] / $riskTotal) * 100);

        $globalLevel = match (true) {
            $stats['active_incidents'] > 0 || $stats['critical_agents'] > 0 => 'critical',
            $stats['active_alerts'] > 0 || $stats['pending_actions'] > 0 => 'high',
            $stats['agents_total'] > 0 => 'normal',
            default => 'suspect',
        };

        $globalLabel = match ($globalLevel) {
            'critical' => 'Attention critique',
            'high' => 'Surveillance renforcée',
            'normal' => 'Situation maîtrisée',
            default => 'En attente de données',
        };

        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high' => 'badge-high',
                'suspect' => 'badge-suspect',
                default => 'badge-normal',
            };
        };

        $dashboardCharts = $chartData ?? [
            'labels' => [],
            'alerts' => [],
            'incidents' => [],
            'actions' => [],
            'risk_by_alert' => [],
            'actions_by_status' => [],
        ];
    @endphp

    <style>
        .dashboard-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 20%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 28%),
                radial-gradient(circle at 80% 10%, color-mix(in srgb, var(--accent-2) 14%, transparent), transparent 30%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .dashboard-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(300px, .6fr);
            gap: 24px;
            align-items: center;
        }

        .dashboard-hero h2 {
            margin: 0;
            font-size: clamp(42px, 5vw, 76px);
            line-height: .92;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .dashboard-hero p {
            margin: 16px 0 0;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 780px;
        }

        .status-orb {
            position: relative;
            width: min(100%, 280px);
            aspect-ratio: 1;
            margin: auto;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background:
                conic-gradient(
                    color-mix(in srgb, var(--accent) 80%, transparent) 0 35%,
                    color-mix(in srgb, #ef4444 80%, transparent) 35% 55%,
                    color-mix(in srgb, #f59e0b 80%, transparent) 55% 72%,
                    color-mix(in srgb, var(--accent-2) 80%, transparent) 72% 100%
                );
            box-shadow: 0 28px 80px rgba(2, 6, 23, .20);
            animation: rsPulse 4s ease-in-out infinite;
        }

        .status-orb::before {
            content: "";
            position: absolute;
            inset: 18px;
            border-radius: inherit;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
        }

        .status-orb-content {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 24px;
        }

        .status-orb-content strong {
            display: block;
            font-size: 34px;
            line-height: 1;
            letter-spacing: -.06em;
        }

        .status-orb-content span {
            display: block;
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 800;
        }

        @keyframes rsPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.025); }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .risk-bars {
            display: grid;
            gap: 14px;
        }

        .risk-row {
            display: grid;
            gap: 7px;
        }

        .risk-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 850;
        }

        .risk-track {
            height: 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--text-muted) 10%, transparent);
            overflow: hidden;
        }

        .risk-fill {
            height: 100%;
            border-radius: inherit;
        }

        .activity-list {
            display: grid;
            gap: 10px;
        }

        .activity-item {
            display: grid;
            grid-template-columns: 42px 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            border: 1px solid var(--border-soft);
        }

        .activity-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
        }

        .activity-title {
            margin: 0;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .activity-subtitle {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .chart-panel {
            position: relative;
            min-height: 340px;
            padding: 18px;
            border-radius: 28px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .chart-panel::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -70px;
            width: 170px;
            height: 170px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            pointer-events: none;
        }

        .chart-head {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 18px;
        }

        .chart-title {
            margin: 0;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .chart-subtitle {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .chart-box {
            position: relative;
            z-index: 1;
            height: 250px;
        }

        .chart-box canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .chart-grid-premium {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr);
            gap: 16px;
        }

        .chart-grid-equal {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .quick-action {
            padding: 16px;
            border-radius: 22px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            text-decoration: none;
            color: var(--text-main);
            transition: .18s ease;
        }

        .quick-action:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--accent) 32%, transparent);
        }

        .quick-action span {
            display: block;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .quick-action strong {
            display: block;
            font-size: 13px;
            font-weight: 950;
        }

        .quick-action small {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        @media (max-width: 1200px) {
            .dashboard-hero-grid,
            .dashboard-grid,
            .chart-grid-premium,
            .chart-grid-equal {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .dashboard-hero {
                padding: 20px;
                border-radius: 24px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .activity-item {
                grid-template-columns: 42px 1fr;
            }

            .activity-item > a,
            .activity-item > span:last-child {
                grid-column: 1 / -1;
            }
        }
    </style>

    <div class="animated-page">
        <section class="dashboard-hero">
            <div class="dashboard-hero-grid">
                <div>
                    <div class="analysis-kicker">
                        <span class="analysis-dot"></span>
                        Centre opérationnel SOC
                    </div>

                    <h2>Surveiller. Détecter. Décider.</h2>

                    <p>
                        RansomShield centralise les signaux des agents, les alertes, les incidents et les actions
                        de protection. Le dashboard présente seulement les éléments actifs, tout en conservant
                        l'historique dans les pages dédiées.
                    </p>

                    <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge {{ $riskClass($globalLevel) }}">{{ $globalLabel }}</span>
                        <span class="badge">Agents : {{ $stats['agents_total'] }}</span>
                        <span class="badge">Réseaux surveillés : {{ $stats['monitored_networks'] }}</span>
                        <span class="badge">Hôtes surveillés : {{ $stats['monitored_hosts'] }}</span>
                    </div>

                    <div class="btn-row">
                        <a href="{{ route('platform.configuration.index') }}" class="btn btn-primary">Centre configuration</a>
                        <a href="{{ route('platform.alerts.index') }}" class="btn btn-soft">Voir alertes actives</a>
                        <a href="{{ route('platform.incidents.index') }}" class="btn btn-soft">Voir incidents</a>
                    </div>
                </div>

                <div class="status-orb">
                    <div class="status-orb-content">
                        <strong>{{ $stats['active_incidents'] }}</strong>
                        <span>incident(s) actif(s)</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Alertes actives</div>
                <div class="smart-stat-value">{{ $stats['active_alerts'] }}</div>
                <div class="smart-stat-hint">Ouvertes, reconnues ou en investigation.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Incidents actifs</div>
                <div class="smart-stat-value">{{ $stats['active_incidents'] }}</div>
                <div class="smart-stat-hint">Ouverts, en analyse ou réouverts.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Actions en attente</div>
                <div class="smart-stat-value">{{ $stats['pending_actions'] }}</div>
                <div class="smart-stat-hint">Décisions SOC à traiter.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Agents critiques</div>
                <div class="smart-stat-value">{{ $stats['critical_agents'] }}</div>
                <div class="smart-stat-hint">Machines à risque critique.</div>
            </div>
        </section>

        <section class="chart-grid-premium section-gap">
            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Activité SOC — 7 derniers jours</h3>
                        <p class="chart-subtitle">Alertes, incidents et actions générés par le moteur dynamique.</p>
                    </div>
                    <span class="badge">Temps réel DB</span>
                </div>

                <div class="chart-box">
                    <canvas id="socActivityChart"></canvas>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Risques des alertes</h3>
                        <p class="chart-subtitle">Répartition globale par niveau de risque.</p>
                    </div>
                    <span class="badge">Analyse</span>
                </div>

                <div class="chart-box">
                    <canvas id="alertRiskChart"></canvas>
                </div>
            </div>
        </section>

        <section class="chart-grid-equal section-gap">
            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Actions par statut</h3>
                        <p class="chart-subtitle">Décisions SOC : pending, approved, rejected, cancelled.</p>
                    </div>
                    <span class="badge">Réponse</span>
                </div>

                <div class="chart-box">
                    <canvas id="actionStatusChart"></canvas>
                </div>
            </div>

            <div class="chart-panel">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Distribution agents</h3>
                        <p class="chart-subtitle">Répartition des agents par niveau actuel.</p>
                    </div>
                    <span class="badge">Agents</span>
                </div>

                <div class="chart-box">
                    <canvas id="agentRiskChart"></canvas>
                </div>
            </div>
        </section>

        <section class="quick-actions section-gap">
            <a class="quick-action" href="{{ route('platform.networks.index') }}">
                <span>🌐</span>
                <strong>Infrastructure</strong>
                <small>Réseaux et hôtes surveillés.</small>
            </a>

            <a class="quick-action" href="{{ route('platform.detection-rules.index') }}">
                <span>🎯</span>
                <strong>Détection</strong>
                <small>Règles, seuils et extensions.</small>
            </a>

            <a class="quick-action" href="{{ route('platform.protection-actions.index') }}">
                <span>🛡️</span>
                <strong>Réponse</strong>
                <small>Actions et file d'approbation.</small>
            </a>

            <a class="quick-action" href="{{ route('platform.system-settings.index') }}">
                <span>⚙️</span>
                <strong>Paramètres</strong>
                <small>Configuration dynamique.</small>
            </a>
        </section>

        <section class="dashboard-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Répartition des risques agents</h3>
                        <p class="soc-card-subtitle">État actuel des machines enrôlées.</p>
                    </div>
                </div>

                <div class="risk-bars">
                    <div class="risk-row">
                        <div class="risk-meta"><span>Critical</span><strong>{{ $riskDistribution['critical'] }}</strong></div>
                        <div class="risk-track"><div class="risk-fill" style="width: {{ $criticalPercent }}%; background:#ef4444;"></div></div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta"><span>High</span><strong>{{ $riskDistribution['high'] }}</strong></div>
                        <div class="risk-track"><div class="risk-fill" style="width: {{ $highPercent }}%; background:#fb923c;"></div></div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta"><span>Suspect</span><strong>{{ $riskDistribution['suspect'] }}</strong></div>
                        <div class="risk-track"><div class="risk-fill" style="width: {{ $suspectPercent }}%; background:#f59e0b;"></div></div>
                    </div>

                    <div class="risk-row">
                        <div class="risk-meta"><span>Normal</span><strong>{{ $riskDistribution['normal'] }}</strong></div>
                        <div class="risk-track"><div class="risk-fill" style="width: {{ $normalPercent }}%; background:#22c55e;"></div></div>
                    </div>
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">État réseau</h3>
                        <p class="soc-card-subtitle">Derniers réseaux enregistrés.</p>
                    </div>

                    <a href="{{ route('platform.networks.index') }}" class="action-btn primary">Voir</a>
                </div>

                <div class="activity-list">
                    @forelse($networks as $network)
                        <div class="activity-item">
                            <div class="activity-icon">🌐</div>
                            <div>
                                <p class="activity-title">{{ $network->name }}</p>
                                <div class="activity-subtitle">
                                    {{ $network->cidr }} — {{ $network->discovered_hosts_count }} hôte(s)
                                </div>
                            </div>
                            <span class="badge {{ $network->is_monitored ? 'badge-normal' : 'badge-suspect' }}">
                                {{ $network->is_monitored ? 'surveillé' : 'retiré' }}
                            </span>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun réseau.',
                            'message' => 'Lance une détection réseau pour alimenter cette section.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dashboard-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Dernières alertes</h3>
                        <p class="soc-card-subtitle">Alertes les plus récentes, actives ou historiques.</p>
                    </div>

                    <a href="{{ route('platform.alerts.index', ['status' => 'all']) }}" class="action-btn primary">Historique</a>
                </div>

                <div class="activity-list">
                    @forelse($recentAlerts as $alert)
                        <div class="activity-item">
                            <div class="activity-icon">🚨</div>
                            <div>
                                <p class="activity-title">{{ $alert->title }}</p>
                                <div class="activity-subtitle">
                                    {{ $alert->agent?->agent_name ?? 'Agent inconnu' }} —
                                    {{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <span class="badge {{ $riskClass($alert->risk_level) }}">{{ $alert->risk_level }}</span>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune alerte.',
                            'message' => 'Les alertes apparaîtront ici après les événements agents.'
                        ])
                    @endforelse
                </div>
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Incidents récents</h3>
                        <p class="soc-card-subtitle">Derniers incidents créés par le moteur.</p>
                    </div>

                    <a href="{{ route('platform.incidents.index', ['status' => 'all']) }}" class="action-btn primary">Historique</a>
                </div>

                <div class="activity-list">
                    @forelse($recentIncidents as $incident)
                        <div class="activity-item">
                            <div class="activity-icon">🔥</div>
                            <div>
                                <p class="activity-title">{{ $incident->title }}</p>
                                <div class="activity-subtitle">
                                    {{ $incident->agent?->agent_name ?? 'Agent inconnu' }} —
                                    statut : {{ $incident->status }}
                                </div>
                            </div>
                            <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn">Voir</a>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucun incident.',
                            'message' => 'Les incidents apparaîtront après une alerte high ou critical.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Décisions SOC récentes</h3>
                    <p class="soc-card-subtitle">Actions proposées, exécutées, rejetées ou en attente.</p>
                </div>

                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="action-btn primary">Toutes les actions</a>
            </div>

            <div class="activity-list">
                @forelse($recentActions as $action)
                    <div class="activity-item">
                        <div class="activity-icon">🛡️</div>
                        <div>
                            <p class="activity-title">{{ $action->action_type }}</p>
                            <div class="activity-subtitle">
                                {{ $action->agent?->agent_name ?? 'Agent inconnu' }}
                                —
                                {{ $action->approval_status }} / {{ $action->execution_status }}
                            </div>
                        </div>
                        <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn">Ouvrir</a>
                    </div>
                @empty
                    @include('platform.partials.empty-state', [
                        'title' => 'Aucune action.',
                        'message' => 'Les actions seront proposées selon les politiques de protection.'
                    ])
                @endforelse
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chartData = @json($dashboardCharts);
            const agentRiskDistribution = @json($riskDistribution);

            const css = getComputedStyle(document.documentElement);
            const textColor = css.getPropertyValue('--text-main').trim() || '#0f172a';
            const mutedColor = css.getPropertyValue('--text-muted').trim() || '#64748b';
            const accent = css.getPropertyValue('--accent').trim() || '#2563eb';
            const accent2 = css.getPropertyValue('--accent-2').trim() || '#7c3aed';

            const gridColor = 'rgba(148, 163, 184, .18)';

            const colors = {
                normal: '#22c55e',
                suspect: '#f59e0b',
                high: '#fb923c',
                critical: '#ef4444',
                blue: accent,
                purple: accent2,
                slate: '#64748b'
            };

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: mutedColor,
                            usePointStyle: true,
                            boxWidth: 8,
                            font: {
                                weight: '700'
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: mutedColor },
                        grid: { color: gridColor }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: mutedColor,
                            precision: 0
                        },
                        grid: { color: gridColor }
                    }
                }
            };

            function makeLineChart() {
                const el = document.getElementById('socActivityChart');
                if (!el) return;

                new Chart(el, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [
                            {
                                label: 'Alertes',
                                data: chartData.alerts || [],
                                borderColor: colors.critical,
                                backgroundColor: 'rgba(239, 68, 68, .10)',
                                tension: .38,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Incidents',
                                data: chartData.incidents || [],
                                borderColor: colors.high,
                                backgroundColor: 'rgba(251, 146, 60, .10)',
                                tension: .38,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Actions',
                                data: chartData.actions || [],
                                borderColor: colors.blue,
                                backgroundColor: 'rgba(37, 99, 235, .10)',
                                tension: .38,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: baseOptions
                });
            }

            function makeDoughnutChart(id, labels, data, palette) {
                const el = document.getElementById(id);
                if (!el) return;

                new Chart(el, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data,
                            backgroundColor: palette,
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: mutedColor,
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    font: { weight: '700' }
                                }
                            }
                        }
                    }
                });
            }

            function makeBarChart() {
                const el = document.getElementById('actionStatusChart');
                if (!el) return;

                const actionStatus = chartData.actions_by_status || {};

                new Chart(el, {
                    type: 'bar',
                    data: {
                        labels: ['Pending', 'Approved', 'Rejected', 'Cancelled'],
                        datasets: [{
                            label: 'Actions',
                            data: [
                                actionStatus.pending || 0,
                                actionStatus.approved || 0,
                                actionStatus.rejected || 0,
                                actionStatus.cancelled || 0
                            ],
                            backgroundColor: [
                                colors.suspect,
                                colors.normal,
                                colors.critical,
                                colors.slate
                            ],
                            borderRadius: 12,
                            maxBarThickness: 52
                        }]
                    },
                    options: baseOptions
                });
            }

            makeLineChart();

            const alertRisk = chartData.risk_by_alert || {};
            makeDoughnutChart(
                'alertRiskChart',
                ['Normal', 'Suspect', 'High', 'Critical'],
                [
                    alertRisk.normal || 0,
                    alertRisk.suspect || 0,
                    alertRisk.high || 0,
                    alertRisk.critical || 0
                ],
                [colors.normal, colors.suspect, colors.high, colors.critical]
            );

            makeBarChart();

            makeDoughnutChart(
                'agentRiskChart',
                ['Normal', 'Suspect', 'High', 'Critical'],
                [
                    agentRiskDistribution.normal || 0,
                    agentRiskDistribution.suspect || 0,
                    agentRiskDistribution.high || 0,
                    agentRiskDistribution.critical || 0
                ],
                [colors.normal, colors.suspect, colors.high, colors.critical]
            );
        });
    </script>

@endsection
