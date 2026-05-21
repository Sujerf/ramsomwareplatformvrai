@extends('layouts.soc')

@section('title', 'RansomShield — Fiche action')
@section('page_title', 'Fiche action de protection')
@section('page_subtitle', $protectionAction->action_type)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $payload = $protectionAction->payload ?? [];
        $signals = collect(data_get($payload, 'signals', []));
        $riskLevel = data_get($payload, 'risk_level', $protectionAction->incident?->risk_level ?? 'normal');
        $riskScore = data_get($payload, 'risk_score', $protectionAction->incident?->risk_score ?? 0);
        $policyCode = data_get($payload, 'policy_code', $protectionAction->protectionPolicy?->code ?? '—');
        $realExecutionAllowed = data_get($payload, 'real_execution_allowed', false);
        $humanApprovalRequired = data_get($payload, 'human_approval_required', false);

        $statusClass = function ($status) {
            return match ($status) {
                'approved', 'success' => 'badge-normal',
                'rejected', 'cancelled', 'rolled_back' => 'badge-suspect',
                'waiting_approval', 'pending' => 'badge-high',
                default => 'badge',
            };
        };

        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high' => 'badge-high',
                'suspect' => 'badge-suspect',
                default => 'badge-normal',
            };
        };
    @endphp

    <style>
        .action-show-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 15%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 85% 12%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .action-show-hero h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 58px);
            line-height: .98;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .action-show-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
        }

        .action-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .decision-timeline {
            display: grid;
            gap: 12px;
        }

        .decision-item {
            position: relative;
            padding: 14px 14px 14px 46px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            border: 1px solid var(--border-soft);
        }

        .decision-item::before {
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

        .decision-title {
            margin: 0;
            font-weight: 950;
            font-size: 14px;
        }

        .decision-meta {
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

        @media (max-width: 1100px) {
            .action-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="action-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Décision SOC
            </div>

            <h2>{{ $protectionAction->action_type }}</h2>

            <p>
                {{ $protectionAction->description ?? 'Action proposée par une politique de protection RansomShield.' }}
            </p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                <span class="badge {{ $riskClass($riskLevel) }}">Risque : {{ $riskLevel }}</span>
                <span class="badge">Score : {{ $riskScore }}</span>
                <span class="badge {{ $statusClass($protectionAction->approval_status) }}">Approbation : {{ $protectionAction->approval_status }}</span>
                <span class="badge {{ $statusClass($protectionAction->execution_status) }}">Exécution : {{ $protectionAction->execution_status }}</span>
                <span class="badge">Politique : {{ $policyCode }}</span>
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="btn btn-soft">← Historique actions</a>

                @if($protectionAction->approval_status === 'pending')
                    <form method="POST" action="{{ route('platform.protection-actions.approve', $protectionAction) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" type="submit">Approuver</button>
                    </form>

                    <form method="POST" action="{{ route('platform.protection-actions.reject', $protectionAction) }}">
                        @csrf
                        @method('PATCH')
                        <button class="action-btn warning" type="submit">Rejeter</button>
                    </form>
                @endif

                @if(in_array($protectionAction->execution_status, ['pending', 'waiting_approval'], true) && $protectionAction->approval_status !== 'rejected')
                    <form method="POST" action="{{ route('platform.protection-actions.execute', $protectionAction) }}">
                        @csrf
                        @method('PATCH')
                        <button class="action-btn" type="submit">Exécuter manuellement</button>
                    </form>
                @endif

                @if($protectionAction->rollback_available)
                    <form method="POST" action="{{ route('platform.protection-actions.rollback', $protectionAction) }}">
                        @csrf
                        @method('PATCH')
                        <button class="action-btn danger" type="submit">Rollback</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Agent</div>
                <div class="smart-stat-value" style="font-size:24px;">{{ $protectionAction->agent?->agent_name ?? '—' }}</div>
                <div class="smart-stat-hint">Machine concernée.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Incident</div>
                <div class="smart-stat-value" style="font-size:24px;">#{{ $protectionAction->incident_id ?? '—' }}</div>
                <div class="smart-stat-hint">{{ $protectionAction->incident?->title ?? 'Aucun incident.' }}</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Validation humaine</div>
                <div class="smart-stat-value">{{ $humanApprovalRequired ? 'Oui' : 'Non' }}</div>
                <div class="smart-stat-hint">Contrôle des actions sensibles.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Exécution réelle</div>
                <div class="smart-stat-value">{{ $realExecutionAllowed ? 'Oui' : 'Non' }}</div>
                <div class="smart-stat-hint">Sécurité du mode test.</div>
            </div>
        </section>

        <section class="action-detail-grid section-gap">
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Signaux ayant déclenché l'action</h3>
                        <p class="soc-card-subtitle">Données calculées par le moteur dynamique.</p>
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
                        'title' => 'Aucun signal.',
                        'message' => "Aucun signal détaillé n'a été enregistré dans le payload."
                    ])
                @endif
            </div>

            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">Historique des décisions</h3>
                        <p class="soc-card-subtitle">Approbation, rejet, exécution ou rollback.</p>
                    </div>
                </div>

                <div class="decision-timeline">
                    @forelse($protectionAction->decisions as $decision)
                        <div class="decision-item">
                            <h4 class="decision-title">{{ $decision->decision }}</h4>
                            <div class="decision-meta">
                                {{ $decision->comment ?? 'Aucun commentaire.' }}
                                <br>
                                {{ $decision->decided_at?->format('d/m/Y H:i') ?? $decision->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title' => 'Aucune décision.',
                            'message' => 'Les décisions SOC apparaîtront ici.'
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Payload technique</h3>
                    <p class="soc-card-subtitle">Données internes conservées pour audit.</p>
                </div>
            </div>

            <pre class="json-box">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    </div>
@endsection
