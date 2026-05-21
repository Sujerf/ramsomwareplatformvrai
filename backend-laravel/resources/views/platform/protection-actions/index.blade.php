@extends('layouts.soc')

@section('title', 'RansomShield — Actions de protection')
@section('page_title', 'Actions de protection')
@section('page_subtitle', 'Décisions SOC, actions proposées et historique de réponse')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus = $activeStatus ?? request('status', 'active');

        $filters = [
            'active' => 'Actives',
            'executed' => 'Exécutées',
            'rejected' => 'Rejetées',
            'rollback' => 'Rollback',
            'all' => 'Toutes',
        ];

        $statusClass = function ($status) {
            return match ($status) {
                'approved', 'success' => 'badge-normal',
                'rejected', 'cancelled', 'rolled_back' => 'badge-suspect',
                'waiting_approval', 'pending' => 'badge-high',
                default => 'badge',
            };
        };

        $actionIcon = function ($type) {
            return match ($type) {
                'isolate_agent' => '🧱',
                'restrict_path' => '📁',
                'kill_process' => '✂️',
                'notify' => '🔔',
                default => '🛡️',
            };
        };
    @endphp

    <style>
        .action-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 85% 12%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .action-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .action-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 840px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .action-card-grid {
            display: grid;
            gap: 14px;
        }

        .protection-card {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 16px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            transition: .18s ease;
        }

        .protection-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--accent) 32%, transparent);
        }

        .protection-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-size: 25px;
        }

        .protection-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .protection-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .protection-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
        }

        .protection-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .protection-actions form {
            display: inline-flex;
        }

        @media (max-width: 900px) {
            .protection-card {
                grid-template-columns: 52px 1fr;
            }

            .protection-actions {
                grid-column: 1 / -1;
                justify-content: stretch;
                display: grid;
                grid-template-columns: 1fr;
            }

            .protection-actions .action-btn,
            .protection-actions form,
            .protection-actions button {
                width: 100%;
            }
        }
    </style>

    <div class="animated-page">
        <section class="action-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Réponse SOC
            </div>

            <h2>Décider sans perdre l'historique.</h2>

            <p>
                Les actions de protection sont proposées par les politiques. Une action traitée ne reste plus active,
                mais demeure consultable dans l'historique pour l'audit et la traçabilité.
            </p>

            <div class="filter-row">
                @foreach($filters as $key => $label)
                    <a href="{{ route('platform.protection-actions.index', ['status' => $key]) }}"
                       class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Actions de protection</h3>
                    <p class="soc-card-subtitle">
                        Filtre : {{ $filters[$activeStatus] ?? 'Toutes' }}
                    </p>
                </div>

                <a href="{{ route('platform.approval-queue.index') }}" class="action-btn primary">
                    File d'approbation
                </a>
            </div>

            @if($actions->count())
                <div class="action-card-grid">
                    @foreach($actions as $action)
                        @php
                            $riskLevel = data_get($action->payload, 'risk_level', $action->incident?->risk_level ?? 'normal');
                            $riskScore = data_get($action->payload, 'risk_score', $action->incident?->risk_score ?? 0);
                            $policyCode = data_get($action->payload, 'policy_code', $action->protectionPolicy?->code ?? '—');
                            $humanApproval = data_get($action->payload, 'human_approval_required', false);
                            $realExecution = data_get($action->payload, 'real_execution_allowed', false);
                        @endphp

                        <article class="protection-card">
                            <div class="protection-icon">{{ $actionIcon($action->action_type) }}</div>

                            <div>
                                <h3 class="protection-title">{{ $action->action_type }}</h3>

                                <div class="protection-meta">
                                    Agent : {{ $action->agent?->agent_name ?? '—' }}
                                    —
                                    Incident : {{ $action->incident?->title ?? '—' }}
                                    <br>
                                    Politique : <span class="mono">{{ $policyCode }}</span>
                                    —
                                    proposé le {{ $action->proposed_at?->format('d/m/Y H:i') ?? $action->created_at?->format('d/m/Y H:i') }}
                                </div>

                                <div class="protection-badges">
                                    <span class="badge {{ $statusClass($action->approval_status) }}">
                                        Approbation : {{ $action->approval_status }}
                                    </span>
                                    <span class="badge {{ $statusClass($action->execution_status) }}">
                                        Exécution : {{ $action->execution_status }}
                                    </span>
                                    <span class="badge">Risque : {{ $riskLevel }}</span>
                                    <span class="badge">Score : {{ $riskScore }}</span>
                                    <span class="badge {{ $humanApproval ? 'badge-high' : 'badge-normal' }}">
                                        Validation humaine : {{ $humanApproval ? 'oui' : 'non' }}
                                    </span>
                                    <span class="badge {{ $realExecution ? 'badge-critical' : 'badge-normal' }}">
                                        Réel : {{ $realExecution ? 'autorisé' : 'désactivé' }}
                                    </span>
                                </div>
                            </div>

                            <div class="protection-actions">
                                <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn primary">Ouvrir</a>

                                @if($action->approval_status === 'pending')
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
                                @endif

                                @if(in_array($action->execution_status, ['pending', 'waiting_approval'], true) && $action->approval_status !== 'rejected')
                                    <form method="POST" action="{{ route('platform.protection-actions.execute', $action) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn" type="submit">Exécuter</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $actions->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucune action pour ce filtre.',
                    'message' => 'Les actions apparaissent après un incident high ou critical selon les politiques.'
                ])
            @endif
        </section>
    </div>
@endsection
