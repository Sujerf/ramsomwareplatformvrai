@extends('layouts.soc')

@section('title', 'RansomShield — Timeline incident')
@section('page_title', 'Timeline incident')
@section('page_subtitle', $incident->title)

@section('content')
    @include('platform.partials.page-tools-style')

    @php
        $typeIcon = function ($type) {
            return match ($type) {
                'event'             => '📡',
                'alert'             => '🚨',
                'notification'      => '🔔',
                'protection_action' => '🛡️',
                default             => '•',
            };
        };

        $typeLabel = function ($type) {
            return match ($type) {
                'event'             => 'Événement',
                'alert'             => 'Alerte',
                'notification'      => 'Notification',
                'protection_action' => 'Action',
                default             => $type,
            };
        };

        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high'     => 'badge-high',
                'suspect'  => 'badge-suspect',
                'normal'   => 'badge-normal',
                default    => 'badge',
            };
        };
    @endphp

    <style>
        .timeline-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, #ef4444 14%, transparent), transparent 28%),
                radial-gradient(circle at 85% 10%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .timeline-hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: .98;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .timeline-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
        }

        .timeline-list {
            position: relative;
            padding-left: 28px;
        }

        .timeline-list::before {
            content: "";
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-soft);
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -24px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent);
            border: 2px solid var(--bg-main);
        }

        .timeline-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .timeline-title {
            margin: 0;
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-date {
            color: var(--text-muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .timeline-desc {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.55;
            word-break: break-word;
        }
    </style>

    <div class="animated-page">
        <section class="timeline-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Timeline incident
            </div>

            <h2>Chronologie de l'incident</h2>

            <p>
                Séquence chronologique des événements, alertes, notifications et actions
                liées à cet incident.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.incidents.show', $incident) }}" class="btn btn-primary">Fiche incident</a>
                <a href="{{ route('platform.incidents.index') }}" class="btn btn-soft">Tous les incidents</a>
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Événements</div>
                <div class="smart-stat-value">{{ $timeline->where('type', 'event')->count() }}</div>
                <div class="smart-stat-hint">Signaux reçus.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Alertes</div>
                <div class="smart-stat-value">{{ $timeline->where('type', 'alert')->count() }}</div>
                <div class="smart-stat-hint">Alertes déclenchées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Actions</div>
                <div class="smart-stat-value">{{ $timeline->where('type', 'protection_action')->count() }}</div>
                <div class="smart-stat-hint">Réponses proposées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $timeline->count() }}</div>
                <div class="smart-stat-hint">Entrées chronologiques.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Chronologie</h3>
                    <p class="soc-card-subtitle">Du plus ancien au plus récent.</p>
                </div>
                <span class="badge">{{ $incident->risk_level }}</span>
            </div>

            @if($timeline->count())
                <div class="timeline-list section-gap">
                    @foreach($timeline as $entry)
                        <div class="timeline-item">
                            <div class="timeline-head">
                                <h4 class="timeline-title">
                                    <span>{{ $typeIcon($entry['type']) }}</span>
                                    {{ $typeLabel($entry['type']) }} — {{ $entry['title'] }}
                                </h4>

                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    @if($entry['risk_level'])
                                        <span class="badge {{ $riskClass($entry['risk_level']) }}">
                                            {{ $entry['risk_level'] }}
                                        </span>
                                    @endif
                                    <span class="timeline-date">
                                        {{ $entry['date'] ? \Illuminate\Support\Carbon::parse($entry['date'])->format('d/m/Y H:i:s') : '—' }}
                                    </span>
                                </div>
                            </div>

                            @if($entry['description'])
                                <div class="timeline-desc mono">{{ $entry['description'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucune entrée dans la timeline.',
                    'message' => 'Les événements, alertes et actions liés à cet incident apparaîtront ici.'
                ])
            @endif
        </section>
    </div>
@endsection
