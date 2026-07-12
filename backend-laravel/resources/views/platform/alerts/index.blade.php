@extends('layouts.soc')

@section('title', 'RansomShield — Alertes')
@section('page_title', 'Alertes')
@section('page_subtitle', 'Signaux de sécurité détectés par le moteur RansomShield')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskIcon = function ($risk) {
            return match ($risk) {
                'critical' => 'fa-skull-crossbones',
                'high'     => 'fa-triangle-exclamation',
                'suspect'  => 'fa-eye',
                default    => 'fa-bell',
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

        // [Amélioration 1] Badge statut coloré — couleur sémantique par statut
        $statusLabel = function ($status) {
            return match ($status) {
                'open'           => 'Ouverte',
                'acknowledged'   => 'Reconnue',
                'investigating'  => 'En investigation',
                'resolved'       => 'Résolue',
                'false_positive' => 'Faux positif',
                default          => $status,
            };
        };

        $statusBadgeClass = function ($status) {
            return match ($status) {
                'open'           => 'badge-open',
                'acknowledged'   => 'badge-acknowledged',
                'investigating'  => 'badge-investigating',
                'resolved'       => 'badge-resolved',
                'false_positive' => 'badge-false-positive',
                default          => 'badge',
            };
        };

        $statusIcon = function ($status) {
            return match ($status) {
                'open'           => 'fa-circle-dot',
                'acknowledged'   => 'fa-eye',
                'investigating'  => 'fa-magnifying-glass',
                'resolved'       => 'fa-circle-check',
                'false_positive' => 'fa-circle-xmark',
                default          => 'fa-circle',
            };
        };

        // [Amélioration 8] Hero gradient adaptatif selon filtre actif
        $heroGradient = match($activeStatus) {
            'resolved'       => 'radial-gradient(circle at 10% 20%, color-mix(in srgb, #22c55e 14%, transparent), transparent 30%)',
            'false_positive' => 'radial-gradient(circle at 10% 20%, color-mix(in srgb, #94a3b8 14%, transparent), transparent 30%)',
            default          => 'radial-gradient(circle at 10% 20%, color-mix(in srgb, #ef4444 14%, transparent), transparent 30%)',
        };

        $heroTitle = match($activeStatus) {
            'resolved'       => 'Alertes résolues.',
            'false_positive' => 'Faux positifs.',
            default          => "Centre d'alertes.",
        };
    @endphp

    <style>
        /* ── Hero ────────────────────────────────────────────────────────────── */
        .al-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            /* [Amélioration 8] gradient injecté via variable PHP */
            background:
                {{ $heroGradient }},
                radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 28%),
                var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .al-hero h2 {
            margin: 0;
            font-size: clamp(36px, 5vw, 64px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .al-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 820px;
            margin-top: 12px;
        }

        /* ── Filter tabs ─────────────────────────────────────────────────────── */
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

        /* [Amélioration 7] Compteur dans les onglets */
        .filter-tab .ft-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 900;
            background: color-mix(in srgb, currentColor 15%, transparent);
            line-height: 1;
        }

        .filter-tab.active .ft-count {
            background: rgba(255,255,255,.25);
            color: #fff;
        }

        /* ── Alert cards ─────────────────────────────────────────────────────── */
        .alert-card {
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

        .alert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }

        .alert-card.risk-critical { border-left-color: #ef4444; }
        .alert-card.risk-high     { border-left-color: #f97316; }
        .alert-card.risk-suspect  { border-left-color: #eab308; }
        .alert-card.risk-normal   { border-left-color: #6366f1; }

        .al-icon-col {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--icon-color, #6366f1) 12%, transparent);
            color: var(--icon-color, #6366f1);
        }

        .al-body {
            flex: 1;
            min-width: 0;
        }

        /* [Amélioration 5] Titre → 2 lignes au lieu de tronqué */
        .al-title {
            margin: 0 0 4px;
            font-size: 15px;
            font-weight: 850;
            letter-spacing: -.02em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
        }

        .al-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 10px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .al-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* [Amélioration 4] IP sous le nom agent */
        .al-agent-ip {
            font-size: 11px;
            font-family: monospace;
            color: var(--text-muted);
            opacity: .75;
        }

        /* [Amélioration 3] Âge urgency */
        .al-meta-item.age-warning { color: #f59e0b; font-weight: 800; }
        .al-meta-item.age-critical { color: #ef4444; font-weight: 800; }

        .al-msg {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .al-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        /* [Amélioration 2] Score mini-bar */
        .al-score-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: default;
        }

        .al-score-num {
            font-size: 12px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--text-main);
            min-width: 24px;
            text-align: right;
        }

        .al-score-track {
            width: 64px;
            height: 5px;
            border-radius: 3px;
            background: var(--border-soft);
            overflow: hidden;
        }

        .al-score-fill {
            height: 100%;
            border-radius: 3px;
            transition: width .3s ease;
        }

        .al-score-fill.critical { background: #ef4444; }
        .al-score-fill.high     { background: #f97316; }
        .al-score-fill.suspect  { background: #eab308; }
        .al-score-fill.normal   { background: #6366f1; }

        /* [Amélioration 1] Badges statut colorés */
        .badge-open {
            background: color-mix(in srgb, #ef4444 12%, transparent);
            color: #ef4444;
            border: 1px solid color-mix(in srgb, #ef4444 25%, transparent);
        }

        .badge-acknowledged {
            background: color-mix(in srgb, #3b82f6 12%, transparent);
            color: #3b82f6;
            border: 1px solid color-mix(in srgb, #3b82f6 25%, transparent);
        }

        .badge-investigating {
            background: color-mix(in srgb, #8b5cf6 12%, transparent);
            color: #8b5cf6;
            border: 1px solid color-mix(in srgb, #8b5cf6 25%, transparent);
        }

        .badge-resolved {
            background: color-mix(in srgb, #22c55e 12%, transparent);
            color: #22c55e;
            border: 1px solid color-mix(in srgb, #22c55e 25%, transparent);
        }

        .badge-false-positive {
            background: color-mix(in srgb, #94a3b8 10%, transparent);
            color: #64748b;
            border: 1px solid color-mix(in srgb, #94a3b8 22%, transparent);
        }

        /* [Amélioration 6] Badge "Incident lié" cliquable */
        .badge-incident-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            text-decoration: none;
            transition: background .15s ease;
        }

        .badge-incident-link:hover {
            background: color-mix(in srgb, var(--accent) 18%, transparent);
            color: var(--accent);
        }

        .al-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .al-strip form {
            display: contents;
        }

        .alert-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        @media (max-width: 800px) {
            .alert-card {
                flex-direction: column;
            }
            .al-strip {
                width: 100%;
            }
            .al-strip .action-btn {
                flex: 1 1 auto;
                justify-content: center;
            }
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="al-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Centre d'alertes SOC
            </div>

            <h2>{{ $heroTitle }}</h2>

            <p>Les alertes représentent les signaux générés par le moteur de détection. Résoudre ou classer faux positif
                retire l'alerte de la file active tout en conservant l'historique complet.</p>

            {{-- [Amélioration 7] Onglets avec compteurs --}}
            <div class="filter-tabs">
                @php
                    $statusFilters = [
                        'active'        => ['label' => 'Actives',      'icon' => 'fa-bolt'],
                        'resolved'      => ['label' => 'Résolues',     'icon' => 'fa-circle-check'],
                        'false_positive'=> ['label' => 'Faux positifs','icon' => 'fa-circle-xmark'],
                        'all'           => ['label' => 'Toutes',       'icon' => 'fa-list'],
                    ];
                    $riskFilters = [
                        ''         => ['label' => 'Tous risques', 'icon' => 'fa-layer-group'],
                        'critical' => ['label' => 'Critical',    'icon' => 'fa-skull-crossbones'],
                        'high'     => ['label' => 'High',        'icon' => 'fa-triangle-exclamation'],
                        'suspect'  => ['label' => 'Suspect',     'icon' => 'fa-eye'],
                        'normal'   => ['label' => 'Normal',      'icon' => 'fa-circle-dot'],
                    ];
                @endphp

                @foreach($statusFilters as $key => $sf)
                    @php $cnt = $filterCounts['status'][$key] ?? 0; @endphp
                    <a class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $key, 'risk' => $activeRisk], fn($v) => $v !== '' && $v !== null)) }}">
                        <i class="fa-solid {{ $sf['icon'] }}"></i>
                        {{ $sf['label'] }}
                        @if($cnt > 0)
                            <span class="ft-count">{{ $cnt }}</span>
                        @endif
                    </a>
                @endforeach

                <div class="filter-sep"></div>

                @foreach($riskFilters as $key => $rf)
                    @php $cnt = $filterCounts['risk'][$key] ?? 0; @endphp
                    <a class="filter-tab {{ ($activeRisk ?? '') === $key ? 'active' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $activeStatus, 'risk' => $key], fn($v) => $v !== '' && $v !== null)) }}">
                        <i class="fa-solid {{ $rf['icon'] }}"></i>
                        {{ $rf['label'] }}
                        @if($cnt > 0)
                            <span class="ft-count">{{ $cnt }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                <div class="smart-stat-label">Actives</div>
                <div class="smart-stat-value" style="{{ $stats['active'] > 0 ? 'color:#ef4444' : '' }}">{{ $stats['active'] }}</div>
                <div class="smart-stat-hint">À traiter maintenant.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="smart-stat-label">Résolues</div>
                <div class="smart-stat-value" style="{{ $stats['resolved'] > 0 ? 'color:#22c55e' : '' }}">{{ $stats['resolved'] }}</div>
                <div class="smart-stat-hint">Traitées avec succès.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="smart-stat-label">Faux positifs</div>
                <div class="smart-stat-value">{{ $stats['false_positive'] }}</div>
                <div class="smart-stat-hint">Écartées — non retenues.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-database"></i></div>
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Toutes alertes confondues.</div>
            </div>
        </section>

        {{-- List --}}
        @if($alerts->count())
            <div class="alert-list section-gap">
                @foreach($alerts as $alert)
                    @php
                        $ic       = $riskIcon($alert->risk_level);
                        $col      = $riskColor($alert->risk_level);
                        $isActive = !in_array($alert->status, ['resolved', 'false_positive'], true);

                        // [Amélioration 3] Âge urgency
                        $detectedAt = $alert->detected_at ?? $alert->created_at;
                        $ageMinutes = $detectedAt ? $detectedAt->diffInMinutes(now()) : 0;
                        $ageClass   = $ageMinutes >= 240 ? 'age-critical'
                                    : ($ageMinutes >= 60  ? 'age-warning' : '');
                        $ageLabel   = $ageMinutes >= 240 ? '⚠ ' . $detectedAt->diffForHumans()
                                    : $detectedAt?->diffForHumans();

                        // [Amélioration 2] Score mini-bar (max 200pts = 100%)
                        $scoreBarWidth = min(100, round(($alert->score ?? 0) / 200 * 100));
                    @endphp

                    <article class="alert-card risk-{{ $alert->risk_level }}">
                        <div class="al-icon-col" style="--icon-color:{{ $col }}">
                            <i class="fa-solid {{ $ic }}"></i>
                        </div>

                        <div class="al-body">
                            {{-- [Amélioration 5] Titre 2 lignes --}}
                            <h4 class="al-title">{{ $alert->title }}</h4>

                            {{-- [Amélioration 4] Meta structurée + IP agent --}}
                            <div class="al-meta">
                                <span class="al-meta-item">
                                    <i class="fa-solid fa-microchip"></i>
                                    {{ $alert->agent?->agent_name ?? 'Agent inconnu' }}
                                    @if($alert->agent?->ip_address)
                                        <span class="al-agent-ip">({{ $alert->agent->ip_address }})</span>
                                    @endif
                                </span>
                                <span class="al-meta-item {{ $isActive ? $ageClass : '' }}">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ $ageLabel ?? '—' }}
                                </span>
                                @if($alert->event)
                                    <span class="al-meta-item">
                                        <i class="fa-solid fa-bolt"></i>{{ $alert->event->event_type }}
                                    </span>
                                @endif
                            </div>

                            @if($alert->message)
                                <div class="al-msg">{{ Str::limit($alert->message, 120) }}</div>
                            @endif

                            <div class="al-tags">
                                {{-- Badge risque --}}
                                <span class="badge badge-{{ $alert->risk_level === 'critical' ? 'critical' : ($alert->risk_level === 'high' ? 'high' : ($alert->risk_level === 'suspect' ? 'suspect' : 'normal')) }}">
                                    <i class="fa-solid {{ $ic }}"></i> {{ $alert->risk_level }}
                                </span>

                                {{-- [Amélioration 1] Badge statut coloré --}}
                                <span class="badge {{ $statusBadgeClass($alert->status) }}">
                                    <i class="fa-solid {{ $statusIcon($alert->status) }}"></i>
                                    {{ $statusLabel($alert->status) }}
                                </span>

                                {{-- [Amélioration 2] Score mini-bar --}}
                                <div class="al-score-wrap" title="Score de risque : {{ $alert->score ?? 0 }} / 200">
                                    <span class="al-score-num">{{ $alert->score ?? 0 }}</span>
                                    <div class="al-score-track">
                                        <div class="al-score-fill {{ $alert->risk_level }}" style="width:{{ $scoreBarWidth }}%"></div>
                                    </div>
                                </div>

                                {{-- [Amélioration 6] Incident lié → lien cliquable --}}
                                @if($alert->incident)
                                    <a href="{{ route('platform.incidents.show', $alert->incident) }}"
                                       class="badge badge-incident-link">
                                        <i class="fa-solid fa-link"></i> Incident #{{ $alert->incident->id }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="al-strip">
                            <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn primary">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir
                            </a>
                            @if($isActive)
                                <form method="POST" action="{{ route('platform.alerts.resolve', $alert) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn success" type="submit">
                                        <i class="fa-solid fa-check"></i> Résoudre
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('platform.alerts.false-positive', $alert) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn warning" type="submit">
                                        <i class="fa-solid fa-xmark"></i> Faux positif
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('platform.alerts.reopen', $alert) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn" type="submit">
                                        <i class="fa-solid fa-rotate-left"></i> Réouvrir
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">
                {{ $alerts->links() }}
            </div>
        @else
            @include('platform.partials.empty-state', [
                'title'   => 'Aucune alerte pour ce filtre.',
                'message' => "Change le filtre ou déclenche un test contrôlé depuis l'agent."
            ])
        @endif

    </div>
@endsection
