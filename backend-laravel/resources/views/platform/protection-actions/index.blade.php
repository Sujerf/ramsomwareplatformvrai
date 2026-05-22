@extends('layouts.soc')

@section('title', 'RansomShield — Actions de protection')
@section('page_title', 'Actions de protection')
@section('page_subtitle', 'Décisions SOC, actions proposées et historique de réponse')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus = $activeStatus ?? 'active';

        $approvalLabel = fn($s) => match($s) {
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'pending'  => 'En attente',
            default    => $s,
        };

        $execLabel = fn($s) => match($s) {
            'success'          => 'Exécutée',
            'pending'          => 'En attente',
            'waiting_approval' => 'Attente appro.',
            'cancelled'        => 'Annulée',
            'failed'           => 'Échec',
            'rolled_back'      => 'Rollback',
            default            => $s,
        };

        $approvalClass = fn($s) => match($s) {
            'approved'           => 'badge-normal',
            'rejected'           => 'badge-critical',
            'pending'            => 'badge-high',
            default              => 'badge',
        };

        $execClass = fn($s) => match($s) {
            'success'            => 'badge-normal',
            'rolled_back'        => 'badge-suspect',
            'cancelled', 'failed'=> 'badge-critical',
            default              => 'badge-high',
        };

        $riskClass = fn($r) => match($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        $heroTitle = match($activeStatus) {
            'executed' => 'Actions exécutées.',
            'rejected' => 'Actions rejetées.',
            'rollback' => 'Actions avec rollback.',
            default    => 'Actions en attente.',
        };

        $statusFilters = [
            'active'   => ['label' => 'En attente',  'icon' => 'fa-hourglass-half'],
            'executed' => ['label' => 'Exécutées',   'icon' => 'fa-circle-check'],
            'rejected' => ['label' => 'Rejetées',    'icon' => 'fa-ban'],
            'rollback' => ['label' => 'Rollback',    'icon' => 'fa-rotate-left'],
            'all'      => ['label' => 'Toutes',      'icon' => 'fa-list'],
        ];
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────────────── */
        .pa-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 14% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 88% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .pa-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .93;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .pa-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 820px;
            margin-top: 14px;
        }

        /* ── FILTER TABS ──────────────────────────────────────────────────── */
        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            align-items: center;
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 14px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            transition: .15s ease;
        }

        .filter-tab:hover {
            background: color-mix(in srgb, var(--accent) 7%, transparent);
            color: var(--accent);
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        .filter-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        /* ── PROTECTION CARD ──────────────────────────────────────────────── */
        .pa-list { display: grid; gap: 0; }

        .pa-card {
            border-radius: 26px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            margin-bottom: 14px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .pa-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 48px rgba(2, 6, 23, .18);
        }

        .pa-card.risk-critical { border-left: 3px solid #ef4444; }
        .pa-card.risk-high     { border-left: 3px solid #fb923c; }
        .pa-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .pa-card.risk-normal   { border-left: 3px solid #22c55e; }

        .pa-card-inner {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
        }

        .pa-icon-col {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px 0 20px 12px;
        }

        .pa-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .pa-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .pa-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .pa-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }
        .pa-icon.normal   { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }

        .pa-body { padding: 18px 18px 0 12px; }

        .pa-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pa-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .pa-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        .pa-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 12px;
        }

        .pa-ctx {
            padding: 8px 10px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }

        .pa-ctx-label {
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pa-ctx-value {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 950;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Strip */
        .pa-strip {
            margin-top: 14px;
            padding: 10px 18px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            background: color-mix(in srgb, var(--bg-panel-soft) 30%, transparent);
        }

        .pa-strip .spacer { flex: 1; }

        .pa-strip .proposed-at {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .pa-meta { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .pa-card-inner { grid-template-columns: 1fr; }
            .pa-icon-col   { padding: 14px 14px 0; justify-content: flex-start; }
            .pa-body       { padding: 12px 14px 0; }
            .pa-meta       { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="pa-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Réponse SOC
            </div>

            <h2>{{ $heroTitle }}</h2>

            <p>
                Les actions sont proposées par les politiques de protection. Une action traitée reste
                consultable dans l'historique pour l'audit et la traçabilité.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.approval-queue.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-circle-dot"></i> File d'approbation
                </a>
                <a href="{{ route('platform.incidents.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-fire-flame-curved"></i> Incidents
                </a>
            </div>
        </section>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-layer-group" style="color:var(--accent); margin-right:6px;"></i>
                    Total
                </div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Actions enregistrées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-hourglass-half" style="color:#f59e0b; margin-right:6px;"></i>
                    En attente
                </div>
                <div class="smart-stat-value" style="{{ $stats['pending'] > 0 ? 'color:#f59e0b;' : '' }}">
                    {{ $stats['pending'] }}
                </div>
                <div class="smart-stat-hint">Décisions à prendre.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-check" style="color:#22c55e; margin-right:6px;"></i>
                    Exécutées
                </div>
                <div class="smart-stat-value" style="{{ $stats['executed'] > 0 ? 'color:#22c55e;' : '' }}">
                    {{ $stats['executed'] }}
                </div>
                <div class="smart-stat-hint">Actions appliquées.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-ban" style="color:#ef4444; margin-right:6px;"></i>
                    Rejetées
                </div>
                <div class="smart-stat-value" style="{{ $stats['rejected'] > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $stats['rejected'] }}
                </div>
                <div class="smart-stat-hint">Annulées ou rejetées.</div>
            </div>
        </section>

        {{-- ── FILTER TABS ──────────────────────────────────────────────── --}}
        <div class="filter-tabs section-gap">
            @foreach($statusFilters as $key => $filter)
                <a href="{{ route('platform.protection-actions.index', ['status' => $key]) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"></i>
                    {{ $filter['label'] }}
                </a>
            @endforeach
        </div>

        {{-- ── ACTION LIST ──────────────────────────────────────────────── --}}
        @if($actions->count())
            <div class="pa-list section-gap">
                @foreach($actions as $action)
                    @php
                        $riskLevel = data_get($action->payload, 'risk_level', $action->incident?->risk_level ?? 'normal');
                        $riskScore = data_get($action->payload, 'risk_score', $action->incident?->risk_score ?? 0);
                        $policyCode = data_get($action->payload, 'policy_code', $action->protectionPolicy?->code ?? '—');

                        $aIcon = match(true) {
                            str_contains($action->action_type, 'isolat')   => 'fa-plug-circle-xmark',
                            str_contains($action->action_type, 'kill')     => 'fa-ban',
                            str_contains($action->action_type, 'backup')   => 'fa-cloud-arrow-up',
                            str_contains($action->action_type, 'block')    => 'fa-shield-halved',
                            str_contains($action->action_type, 'restrict') => 'fa-folder-minus',
                            str_contains($action->action_type, 'quarant')  => 'fa-box',
                            str_contains($action->action_type, 'notify'),
                            str_contains($action->action_type, 'alert')    => 'fa-bell',
                            default                                         => 'fa-shield-virus',
                        };

                        $canApprove = $action->approval_status === 'pending';
                        $canExecute = in_array($action->execution_status, ['pending', 'waiting_approval'], true)
                                      && $action->approval_status !== 'rejected';
                    @endphp

                    <article class="pa-card risk-{{ $riskLevel }}">
                        <div class="pa-card-inner">
                            <div class="pa-icon-col">
                                <div class="pa-icon {{ $riskLevel }}">
                                    <i class="fa-solid {{ $aIcon }}"></i>
                                </div>
                            </div>

                            <div class="pa-body">
                                <div class="pa-head">
                                    <h3 class="pa-title">{{ $action->action_type }}</h3>
                                    <div class="pa-badges">
                                        <span class="badge {{ $riskClass($riskLevel) }}">
                                            <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i>
                                            {{ $riskLevel }}
                                        </span>
                                        <span class="badge {{ $approvalClass($action->approval_status) }}">
                                            {{ $approvalLabel($action->approval_status) }}
                                        </span>
                                        <span class="badge {{ $execClass($action->execution_status) }}">
                                            {{ $execLabel($action->execution_status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pa-meta">
                                    <div class="pa-ctx">
                                        <div class="pa-ctx-label"><i class="fa-solid fa-robot"></i> Agent</div>
                                        <div class="pa-ctx-value" title="{{ $action->agent?->agent_name ?? '—' }}">
                                            {{ $action->agent?->agent_name ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="pa-ctx">
                                        <div class="pa-ctx-label"><i class="fa-solid fa-fire-flame-curved"></i> Incident</div>
                                        <div class="pa-ctx-value">
                                            {{ $action->incident ? '#'.$action->incident_id : '—' }}
                                        </div>
                                    </div>
                                    <div class="pa-ctx">
                                        <div class="pa-ctx-label"><i class="fa-solid fa-scroll"></i> Politique</div>
                                        <div class="pa-ctx-value">{{ $policyCode }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pa-strip">
                            <span class="proposed-at">
                                <i class="fa-regular fa-clock"></i>
                                {{ $action->proposed_at?->diffForHumans() ?? $action->created_at?->diffForHumans() ?? '—' }}
                            </span>
                            <div class="spacer"></div>

                            <a href="{{ route('platform.protection-actions.show', $action) }}" class="action-btn">
                                <i class="fa-solid fa-magnifying-glass"></i> Voir
                            </a>

                            @if($canApprove)
                                <form method="POST" action="{{ route('platform.protection-actions.approve', $action) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn success">
                                        <i class="fa-solid fa-check"></i> Approuver
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('platform.protection-actions.reject', $action) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn danger">
                                        <i class="fa-solid fa-xmark"></i> Rejeter
                                    </button>
                                </form>
                            @endif

                            @if($canExecute && !$canApprove)
                                <form method="POST" action="{{ route('platform.protection-actions.execute', $action) }}" style="display:contents;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn primary">
                                        <i class="fa-solid fa-play"></i> Exécuter
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $actions->links() }}</div>

        @else
            <div class="section-gap">
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucune action pour ce filtre.',
                    'message' => 'Les actions apparaissent après un incident high ou critical selon les politiques.',
                ])
            </div>
        @endif

    </div>
@endsection
