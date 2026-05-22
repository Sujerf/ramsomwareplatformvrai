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

        $statusLabel = function ($status) {
            return match ($status) {
                'open'          => 'Ouverte',
                'acknowledged'  => 'Reconnue',
                'investigating' => 'En investigation',
                'resolved'      => 'Résolue',
                'false_positive'=> 'Faux positif',
                default         => $status,
            };
        };

        $heroTitle = match($activeStatus) {
            'resolved'       => 'Alertes résolues.',
            'false_positive' => 'Faux positifs.',
            default          => "Centre d'alertes.",
        };
    @endphp

    <style>
        .al-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            background:
                radial-gradient(circle at 10% 20%, color-mix(in srgb, #ef4444 14%, transparent), transparent 30%),
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

        /* Alert cards */
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
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--icon-color, #6366f1) 12%, transparent);
            color: var(--icon-color, #6366f1);
        }

        .al-body {
            flex: 1;
            min-width: 0;
        }

        .al-title {
            margin: 0 0 4px;
            font-size: 15px;
            font-weight: 850;
            letter-spacing: -.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .al-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

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

            <div class="filter-tabs">
                @php
                    $statusFilters = ['active' => 'Actives', 'resolved' => 'Résolues', 'false_positive' => 'Faux positifs', 'all' => 'Toutes'];
                    $riskFilters = ['' => 'Tous risques', 'critical' => 'Critical', 'high' => 'High', 'suspect' => 'Suspect', 'normal' => 'Normal'];
                @endphp

                @foreach($statusFilters as $key => $label)
                    <a class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $key, 'risk' => $activeRisk], fn($v) => $v !== '' && $v !== null)) }}">
                        {{ $label }}
                    </a>
                @endforeach

                <div class="filter-sep"></div>

                @foreach($riskFilters as $key => $label)
                    <a class="filter-tab {{ ($activeRisk ?? '') === $key ? 'active' : '' }}"
                       href="{{ route('platform.alerts.index', array_filter(['status' => $activeStatus, 'risk' => $key], fn($v) => $v !== '' && $v !== null)) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-bell"></i></div>
                <div class="smart-stat-label">Actives</div>
                <div class="smart-stat-value" style="{{ $stats['active'] > 0 ? 'color:#ef4444' : '' }}">{{ $stats['active'] }}</div>
                <div class="smart-stat-hint">À traiter maintenant.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-skull-crossbones"></i></div>
                <div class="smart-stat-label">Critical</div>
                <div class="smart-stat-value" style="{{ $stats['critical'] > 0 ? 'color:#ef4444' : '' }}">{{ $stats['critical'] }}</div>
                <div class="smart-stat-hint">Signaux de niveau critique.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="smart-stat-label">Résolues</div>
                <div class="smart-stat-value">{{ $stats['resolved'] }}</div>
                <div class="smart-stat-hint">Traitées avec succès.</div>
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
                        $ic   = $riskIcon($alert->risk_level);
                        $col  = $riskColor($alert->risk_level);
                        $isActive = !in_array($alert->status, ['resolved', 'false_positive'], true);
                    @endphp

                    <article class="alert-card risk-{{ $alert->risk_level }}">
                        <div class="al-icon-col" style="--icon-color:{{ $col }}">
                            <i class="fa-solid {{ $ic }}"></i>
                        </div>

                        <div class="al-body">
                            <h4 class="al-title">{{ $alert->title }}</h4>
                            <div class="al-meta">
                                <i class="fa-solid fa-microchip" style="margin-right:4px"></i>{{ $alert->agent?->agent_name ?? 'Agent inconnu' }}
                                &nbsp;·&nbsp;
                                <i class="fa-regular fa-clock" style="margin-right:4px"></i>{{ $alert->detected_at?->diffForHumans() ?? $alert->created_at?->diffForHumans() }}
                                @if($alert->event)
                                    &nbsp;·&nbsp;<i class="fa-solid fa-bolt" style="margin-right:4px"></i>{{ $alert->event->event_type }}
                                @endif
                            </div>
                            @if($alert->message)
                                <div class="al-msg">{{ Str::limit($alert->message, 120) }}</div>
                            @endif
                            <div class="al-tags">
                                <span class="badge badge-{{ $alert->risk_level === 'critical' ? 'critical' : ($alert->risk_level === 'high' ? 'high' : ($alert->risk_level === 'suspect' ? 'suspect' : 'normal')) }}">
                                    {{ $alert->risk_level }}
                                </span>
                                <span class="badge">{{ $statusLabel($alert->status) }}</span>
                                <span class="badge">Score : {{ $alert->score }}</span>
                                @if($alert->incident)
                                    <span class="badge" style="color:var(--accent)">Incident lié</span>
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
