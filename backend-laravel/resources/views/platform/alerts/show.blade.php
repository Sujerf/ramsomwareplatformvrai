@extends('layouts.soc')

@section('title', 'RansomShield — Analyse alerte')
@section('page_title', 'Analyse alerte')
@section('page_subtitle', $alert->title)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskClass = match ($alert->risk_level) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $signals = data_get($alert->metadata, 'signals', []);
        $path = data_get($alert->metadata, 'path');
        $isSimulation = data_get($alert->metadata, 'is_simulation');
    @endphp

    <div class="animated-page">
        <section class="analysis-hero">
            <div class="analysis-hero-content">
                <div>
                    <div class="analysis-kicker"><span class="analysis-dot"></span> Fiche alerte</div>
                    <h2>{{ $alert->title }}</h2>
                    <p>{{ $alert->message }}</p>

                    <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge {{ $riskClass }}">Risque : {{ $alert->risk_level }}</span>
                        <span class="badge">Score : {{ $alert->score }}</span>
                        <span class="badge">Statut : {{ $alert->status }}</span>
                        <span class="badge">{{ $isSimulation ? 'Simulation contrôlée' : 'Événement réel' }}</span>
                    </div>

                    <div class="btn-row">
                        <a href="{{ route('platform.alerts.index') }}" class="btn btn-soft">Retour alertes</a>
                        @if($alert->incident)
                            <a href="{{ route('platform.incidents.show', $alert->incident) }}" class="btn btn-primary">Ouvrir incident</a>
                        @endif
                    </div>
                </div>

                <div class="network-orbit">
                    <div class="orbit-ring"></div>
                    <div class="orbit-ring"></div>
                    <div class="orbit-node n1"></div>
                    <div class="orbit-node n2"></div>
                    <div class="orbit-node n3"></div>
                    <div class="orbit-core">{{ strtoupper(substr($alert->risk_level, 0, 3)) }}</div>
                </div>
            </div>
        </section>

        <section class="page-toolbar">
            <div>
                <h2>Décision sur l’alerte</h2>
                <p>Les décisions restent réversibles : tu peux résoudre, marquer faux positif ou réouvrir.</p>
            </div>

            <div class="inline-actions">
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

                <form method="POST" action="{{ route('platform.alerts.reopen', $alert) }}">
                    @csrf
                    @method('PATCH')
                    <button class="action-btn primary" type="submit">Réouvrir</button>
                </form>
            </div>
        </section>

        <section class="smart-stats">
            <div class="smart-stat">
                <div class="smart-stat-label">Risque</div>
                <div class="smart-stat-value">{{ $alert->risk_level }}</div>
                <div class="smart-stat-hint">Niveau attribué par le moteur.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">Score</div>
                <div class="smart-stat-value">{{ $alert->score }}</div>
                <div class="smart-stat-hint">Score calculé.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">Notifications</div>
                <div class="smart-stat-value">{{ $alert->notifications->count() }}</div>
                <div class="smart-stat-hint">UI, son ou mail pending.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-label">Signaux</div>
                <div class="smart-stat-value">{{ count($signals) }}</div>
                <div class="smart-stat-hint">Règles déclenchées.</div>
            </div>
        </section>

        <section class="grid grid-2 section-gap">
            <div class="smart-card">
                <h3 class="smart-card-title">Pourquoi cette alerte existe ?</h3>
                <p class="smart-card-subtitle">Règles et contexte enregistrés par le moteur.</p>

                <div class="recommendation-box section-gap">
                    @if(count($signals))
                        @foreach($signals as $signal)
                            <div>
                                <strong>{{ $signal['rule_name'] ?? $signal['rule_code'] ?? 'Règle' }}</strong>
                                <br>
                                Risque : {{ $signal['risk_level'] ?? '—' }} —
                                poids : {{ $signal['score_weight'] ?? '—' }}
                            </div>
                            @if(!$loop->last)<br>@endif
                        @endforeach
                    @else
                        <strong>Aucun signal détaillé disponible.</strong>
                        <br>
                        L’alerte existe mais ses signaux ne sont pas encore enrichis.
                    @endif
                </div>
            </div>

            <div class="smart-card">
                <h3 class="smart-card-title">Recommandation RansomShield</h3>
                <p class="smart-card-subtitle">Ce que l’analyste doit vérifier.</p>

                <div class="recommendation-box section-gap">
                    @if($alert->risk_level === 'critical')
                        <strong>Alerte critique.</strong>
                        <br>
                        Ouvre l’incident lié, vérifie le fichier concerné, confirme si l’événement est une simulation,
                        puis traite les actions de protection proposées.
                    @elseif($alert->risk_level === 'high')
                        <strong>Alerte de risque élevé.</strong>
                        <br>
                        Vérifie l’agent, le chemin concerné et les répétitions d’événements.
                    @else
                        <strong>Alerte à surveiller.</strong>
                        <br>
                        Consulte le contexte avant de résoudre ou classer faux positif.
                    @endif

                    @if($path)
                        <br><br>
                        Chemin concerné : <span class="mono">{{ $path }}</span>
                    @endif
                </div>
            </div>
        </section>

        <section class="detail-grid section-gap">
            <div class="soc-card">
                <h3 class="soc-card-title">Relations</h3>
                <div class="detail-list section-gap">
                    <div class="detail-row"><div class="detail-label">Agent</div><div class="detail-value">{{ $alert->agent?->agent_name ?? '—' }}</div></div>
                    <div class="detail-row"><div class="detail-label">Incident</div><div class="detail-value">{{ $alert->incident?->title ?? '—' }}</div></div>
                    <div class="detail-row"><div class="detail-label">Événement</div><div class="detail-value">{{ $alert->event?->event_type ?? '—' }}</div></div>
                    <div class="detail-row"><div class="detail-label">Détectée</div><div class="detail-value">{{ $alert->detected_at?->format('d/m/Y H:i:s') ?? '—' }}</div></div>
                </div>
            </div>

            <div class="soc-card">
                <h3 class="soc-card-title">Métadonnées</h3>
                <pre class="json-box section-gap">{{ json_encode($alert->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </section>
    </div>
@endsection
