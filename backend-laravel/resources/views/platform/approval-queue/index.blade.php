@extends('layouts.soc')

@section('title', 'RansomShield — File d’approbation')
@section('page_title', 'File d’approbation')
@section('page_subtitle', 'Actions sensibles en attente de validation humaine')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    <style>
        .approval-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, #f59e0b 18%, transparent), transparent 28%),
                radial-gradient(circle at 85% 10%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .approval-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .approval-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 840px;
            margin-top: 14px;
        }

        .approval-list {
            display: grid;
            gap: 14px;
        }

        .approval-card {
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

        .approval-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, #f59e0b 14%, transparent);
            border: 1px solid color-mix(in srgb, #f59e0b 24%, transparent);
            font-size: 24px;
        }

        .approval-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .approval-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .approval-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        @media(max-width: 900px) {
            .approval-card {
                grid-template-columns: 52px 1fr;
            }

            .approval-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: 1fr;
            }

            .approval-actions form,
            .approval-actions .action-btn,
            .approval-actions button {
                width: 100%;
            }
        }
    </style>

    <div class="animated-page">
        <section class="approval-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Validation humaine
            </div>

            <h2>Contrôler les actions sensibles.</h2>

            <p>
                Les actions en attente d’approbation sont volontairement bloquées avant exécution.
                Cela évite qu’une isolation, restriction ou action sensible soit appliquée sans validation.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.protection-actions.index', ['status' => 'active']) }}" class="btn btn-primary">Actions actives</a>
                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="btn btn-soft">Historique complet</a>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Actions en attente</h3>
                    <p class="soc-card-subtitle">Approuver, rejeter ou ouvrir la fiche détaillée.</p>
                </div>
            </div>

            @if($actions->count())
                <div class="approval-list">
                    @foreach($actions as $action)
                        @php
                            $riskLevel = data_get($action->payload, 'risk_level', $action->incident?->risk_level ?? 'normal');
                            $riskScore = data_get($action->payload, 'risk_score', $action->incident?->risk_score ?? 0);
                            $policyCode = data_get($action->payload, 'policy_code', $action->protectionPolicy?->code ?? '—');
                        @endphp

                        <article class="approval-card">
                            <div class="approval-icon">✅</div>

                            <div>
                                <h3 class="approval-title">{{ $action->action_type }}</h3>
                                <div class="approval-meta">
                                    Agent : {{ $action->agent?->agent_name ?? '—' }}
                                    —
                                    Incident : {{ $action->incident?->title ?? '—' }}
                                    <br>
                                    Politique : {{ $policyCode }}
                                    —
                                    Risque : {{ $riskLevel }}
                                    —
                                    Score : {{ $riskScore }}
                                </div>

                                <div class="section-gap" style="display:flex; gap:7px; flex-wrap:wrap;">
                                    <span class="badge badge-high">En attente</span>
                                    <span class="badge">Mode : {{ $action->decision_mode }}</span>
                                    <span class="badge">Exécution : {{ $action->execution_status }}</span>
                                </div>
                            </div>

                            <div class="approval-actions">
                                <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn primary">Ouvrir</a>

                                <form method="POST" action="{{ route('platform.protection-actions.approve', $action) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="action-btn success" type="submit">Approuver</button>
                                </form>

                                <form method="POST" action="{{ route('platform.protection-actions.reject', $action) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="action-btn warning" type="submit">Rejeter</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $actions->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucune action en attente.',
                    'message' => 'La file est vide. Les nouvelles actions sensibles apparaîtront ici.'
                ])
            @endif
        </section>
    </div>
@endsection
