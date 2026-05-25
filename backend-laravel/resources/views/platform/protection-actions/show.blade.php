@extends('layouts.soc')

@section('title', 'RansomShield — Fiche action')
@section('page_title', 'Fiche action de protection')
@section('page_subtitle', $protectionAction->action_type)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $payload               = $protectionAction->payload ?? [];
        $signals               = collect(data_get($payload, 'signals', []));
        $riskLevel             = data_get($payload, 'risk_level', $protectionAction->incident?->risk_level ?? 'normal');
        $riskScore             = data_get($payload, 'risk_score', $protectionAction->incident?->risk_score ?? 0);
        $policyCode            = data_get($payload, 'policy_code', $protectionAction->protectionPolicy?->code ?? '—');
        $realExecutionAllowed  = data_get($payload, 'real_execution_allowed', false);
        $humanApprovalRequired = data_get($payload, 'human_approval_required', false);

        $riskClass = fn($r) => match($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        $approvalClass = fn($s) => match($s) {
            'approved'             => 'badge-normal',
            'rejected',
            'cancelled'            => 'badge-critical',
            'pending'              => 'badge-high',
            default                => 'badge',
        };

        $execClass = fn($s) => match($s) {
            'executed', 'success'         => 'badge-normal',
            'rolled_back'                 => 'badge-suspect',
            'cancelled', 'failed'         => 'badge-critical',
            default                       => 'badge-high',
        };

        $approvalLabel = fn($s) => match($s) {
            'approved'  => 'Approuvée',
            'rejected'  => 'Rejetée',
            'cancelled' => 'Annulée',
            'pending'   => 'En attente',
            default     => $s,
        };

        $execLabel = fn($s) => match($s) {
            'executed', 'success' => 'Exécutée',
            'executing'           => 'En cours…',
            'pending'             => 'En attente',
            'waiting_approval'    => 'Attente approbation',
            'cancelled'           => 'Annulée',
            'failed'              => 'Échec',
            'rolled_back'         => 'Rollback effectué',
            default               => $s,
        };

        $decisionClass = fn($d) => match($d) {
            'approved', 'executed'  => 'dec-dot-green',
            'rejected', 'cancelled' => 'dec-dot-red',
            'rollback'              => 'dec-dot-orange',
            default                 => 'dec-dot-accent',
        };

        $decisionLabel = fn($d) => match($d) {
            'approved'  => 'Approuvée',
            'rejected'  => 'Rejetée',
            'executed'  => 'Exécutée',
            'rollback'  => 'Rollback',
            'cancelled' => 'Annulée',
            default     => $d,
        };

        $aIcon = match(true) {
            str_contains($protectionAction->action_type, 'isolat')   => 'fa-plug-circle-xmark',
            str_contains($protectionAction->action_type, 'kill')     => 'fa-ban',
            str_contains($protectionAction->action_type, 'backup')   => 'fa-cloud-arrow-up',
            str_contains($protectionAction->action_type, 'block')    => 'fa-shield-halved',
            str_contains($protectionAction->action_type, 'restrict') => 'fa-folder-minus',
            str_contains($protectionAction->action_type, 'quarant')  => 'fa-box',
            str_contains($protectionAction->action_type, 'notify'),
            str_contains($protectionAction->action_type, 'alert')    => 'fa-bell',
            default                                                   => 'fa-shield-virus',
        };

        $canApprove  = $protectionAction->approval_status === 'pending';
        $canExecute  = in_array($protectionAction->execution_status, ['pending', 'waiting_approval'], true)
                       && $protectionAction->approval_status !== 'rejected';
        $canRollback = $protectionAction->rollback_available;
    @endphp

    <style>
        /* ── SHOW HERO ────────────────────────────────────────────────────── */
        .pa-show-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 16%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 88% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .pa-show-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(32px, 4vw, 56px);
            line-height: .96;
            letter-spacing: -.07em;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pa-show-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
            margin-top: 12px;
        }

        .pa-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .pa-hero-icon.critical { background: color-mix(in srgb, #ef4444 14%, transparent); color: #ef4444; }
        .pa-hero-icon.high     { background: color-mix(in srgb, #fb923c 14%, transparent); color: #fb923c; }
        .pa-hero-icon.suspect  { background: color-mix(in srgb, #f59e0b 14%, transparent); color: #f59e0b; }
        .pa-hero-icon.normal   { background: color-mix(in srgb, #22c55e 14%, transparent); color: #22c55e; }

        /* ── STATUS BAR ───────────────────────────────────────────────────── */
        .status-bar {
            display: flex;
            gap: 10px;
            align-items: stretch;
            margin-top: 18px;
        }

        .status-block {
            flex: 1;
            padding: 14px 16px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .status-block-label {
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .status-block-value {
            font-size: 15px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        /* ── CONTEXT STRIP ────────────────────────────────────────────────── */
        .ctx-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .ctx-field {
            padding: 14px 16px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .ctx-field-label {
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
        }

        .ctx-field-value {
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── DETAIL GRID ──────────────────────────────────────────────────── */
        .pa-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        /* ── SIGNAL CARD ──────────────────────────────────────────────────── */
        .signal-grid { display: grid; gap: 10px; }

        .signal-card {
            padding: 14px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: 1px solid var(--border-soft);
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
        }

        .signal-card.risk-critical { border-left: 3px solid #ef4444; }
        .signal-card.risk-high     { border-left: 3px solid #fb923c; }
        .signal-card.risk-suspect  { border-left: 3px solid #f59e0b; }
        .signal-card.risk-normal   { border-left: 3px solid #22c55e; }

        .signal-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 14px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
        }

        .signal-icon.critical { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .signal-icon.high     { background: color-mix(in srgb, #fb923c 12%, transparent); color: #fb923c; }
        .signal-icon.suspect  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }

        .signal-label { font-size: 13px; font-weight: 950; letter-spacing: -.02em; }

        .signal-meta {
            margin-top: 4px;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ── DECISION TIMELINE ────────────────────────────────────────────── */
        .decision-timeline { display: grid; gap: 10px; }

        .decision-item {
            position: relative;
            padding: 14px 14px 14px 48px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }

        .decision-item::before {
            content: "";
            position: absolute;
            left: 17px;
            top: 19px;
            width: 13px;
            height: 13px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 16%, transparent);
        }

        .decision-item.dec-dot-green::before  { background: #22c55e; box-shadow: 0 0 0 4px color-mix(in srgb, #22c55e 16%, transparent); }
        .decision-item.dec-dot-red::before    { background: #ef4444; box-shadow: 0 0 0 4px color-mix(in srgb, #ef4444 16%, transparent); }
        .decision-item.dec-dot-orange::before { background: #fb923c; box-shadow: 0 0 0 4px color-mix(in srgb, #fb923c 16%, transparent); }

        .decision-title {
            margin: 0;
            font-weight: 950;
            font-size: 13px;
            letter-spacing: -.02em;
        }

        .decision-meta {
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .decision-comment {
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-main);
            padding: 8px 10px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--bg-panel-soft) 80%, transparent);
            border: 1px solid var(--border-soft);
        }

        /* ── META ACCORDION ───────────────────────────────────────────────── */
        .meta-accordion {
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            overflow: hidden;
        }

        .meta-accordion-toggle {
            width: 100%;
            padding: 14px 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 850;
            color: var(--text-muted);
            text-align: left;
            transition: .15s ease;
        }

        .meta-accordion-toggle:hover { color: var(--text-main); }
        .meta-accordion-toggle i.chevron { margin-left: auto; transition: .2s ease; }
        .meta-accordion-toggle.open i.chevron { transform: rotate(180deg); }
        .meta-accordion-body { display: none; padding: 0 18px 18px; }
        .meta-accordion-body.open { display: block; }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .pa-detail-grid { grid-template-columns: 1fr; }
            .ctx-strip       { grid-template-columns: repeat(2, 1fr); }
            .status-bar      { flex-wrap: wrap; }
        }

        @media (max-width: 640px) {
            .ctx-strip { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 440px) {
            .ctx-strip { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="pa-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Action de protection #{{ $protectionAction->id }}
            </div>

            <h2>
                <span class="pa-hero-icon {{ $riskLevel }}">
                    <i class="fa-solid {{ $aIcon }}"></i>
                </span>
                {{ $protectionAction->action_type }}
            </h2>

            <p>{{ $protectionAction->description ?? 'Action proposée par une politique de protection RansomShield.' }}</p>

            <div class="btn-row">
                <a href="{{ route('platform.protection-actions.index', ['status' => 'all']) }}" class="btn btn-soft">
                    <i class="fa-solid fa-arrow-left"></i> Historique
                </a>

                @if($canApprove)
                    <form method="POST" action="{{ route('platform.protection-actions.approve', $protectionAction) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> Approuver
                        </button>
                    </form>
                    <form method="POST" action="{{ route('platform.protection-actions.reject', $protectionAction) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="action-btn danger">
                            <i class="fa-solid fa-xmark"></i> Rejeter
                        </button>
                    </form>
                @endif

                @if($canExecute && !$canApprove)
                    <form method="POST" action="{{ route('platform.protection-actions.execute', $protectionAction) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="action-btn primary">
                            <i class="fa-solid fa-play"></i> Exécuter manuellement
                        </button>
                    </form>
                @endif

                @if($canRollback)
                    <form method="POST" action="{{ route('platform.protection-actions.rollback', $protectionAction) }}" style="display:contents;">
                        @csrf @method('PATCH')
                        <button type="submit" class="action-btn warning">
                            <i class="fa-solid fa-rotate-left"></i> Rollback
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- ── STATUS BAR ───────────────────────────────────────────────── --}}
        <div class="status-bar section-gap">
            <div class="status-block">
                <div class="status-block-label">Approbation</div>
                <div class="status-block-value">
                    <span class="badge {{ $approvalClass($protectionAction->approval_status) }}" style="font-size:13px; padding:5px 12px;">
                        {{ $approvalLabel($protectionAction->approval_status) }}
                    </span>
                </div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Exécution</div>
                <div class="status-block-value">
                    <span class="badge {{ $execClass($protectionAction->execution_status) }}" style="font-size:13px; padding:5px 12px;">
                        {{ $execLabel($protectionAction->execution_status) }}
                    </span>
                </div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Risque</div>
                <div class="status-block-value">
                    <span class="badge {{ $riskClass($riskLevel) }}" style="font-size:13px; padding:5px 12px;">
                        {{ $riskLevel }} — Score {{ $riskScore }}
                    </span>
                </div>
            </div>
            <div class="status-block">
                <div class="status-block-label">Mode exécution</div>
                <div class="status-block-value" style="{{ $realExecutionAllowed ? 'color:#ef4444;' : 'color:#22c55e;' }}; font-size:14px;">
                    @if($realExecutionAllowed)
                        <i class="fa-solid fa-circle-radiation" style="margin-right:6px;"></i> Réel autorisé
                    @else
                        <i class="fa-solid fa-flask" style="margin-right:6px;"></i> Mode test
                    @endif
                </div>
            </div>
        </div>

        {{-- ── CONTEXT STRIP ────────────────────────────────────────────── --}}
        <div class="ctx-strip section-gap">
            <div class="ctx-field">
                <div class="ctx-field-label"><i class="fa-solid fa-robot"></i> Agent</div>
                <div class="ctx-field-value" title="{{ $protectionAction->agent?->agent_name ?? '—' }}">
                    {{ $protectionAction->agent?->agent_name ?? '—' }}
                </div>
            </div>
            <div class="ctx-field">
                <div class="ctx-field-label"><i class="fa-solid fa-fire-flame-curved"></i> Incident</div>
                <div class="ctx-field-value">
                    @if($protectionAction->incident)
                        <a href="{{ route('platform.incidents.show', $protectionAction->incident) }}"
                           style="color:var(--accent); text-decoration:none;">
                            #{{ $protectionAction->incident_id }}
                        </a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="ctx-field">
                <div class="ctx-field-label"><i class="fa-solid fa-scroll"></i> Politique</div>
                <div class="ctx-field-value">{{ $policyCode }}</div>
            </div>
            <div class="ctx-field">
                <div class="ctx-field-label"><i class="fa-regular fa-clock"></i> Proposée le</div>
                <div class="ctx-field-value" style="font-family:inherit; font-size:13px;">
                    {{ $protectionAction->proposed_at?->format('d/m/Y H:i') ?? $protectionAction->created_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>
        </div>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-user-shield" style="color:var(--accent); margin-right:6px;"></i>
                    Validation humaine
                </div>
                <div class="smart-stat-value" style="{{ $humanApprovalRequired ? 'color:#f59e0b;' : '' }}">
                    {{ $humanApprovalRequired ? 'Oui' : 'Non' }}
                </div>
                <div class="smart-stat-hint">Contrôle manuel requis.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-rotate-left" style="color:#fb923c; margin-right:6px;"></i>
                    Rollback dispo.
                </div>
                <div class="smart-stat-value" style="{{ $canRollback ? 'color:#22c55e;' : '' }}">
                    {{ $canRollback ? 'Oui' : 'Non' }}
                </div>
                <div class="smart-stat-hint">Retour arrière possible.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:6px;"></i>
                    Signaux
                </div>
                <div class="smart-stat-value" style="{{ $signals->count() > 0 ? 'color:var(--accent);' : '' }}">
                    {{ $signals->count() }}
                </div>
                <div class="smart-stat-hint">Dans le payload.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-timeline" style="color:#22c55e; margin-right:6px;"></i>
                    Décisions
                </div>
                <div class="smart-stat-value" style="{{ $protectionAction->decisions->count() > 0 ? 'color:#22c55e;' : '' }}">
                    {{ $protectionAction->decisions->count() }}
                </div>
                <div class="smart-stat-hint">Historique SOC.</div>
            </div>
        </section>

        {{-- ── SIGNAUX / DÉCISIONS ──────────────────────────────────────── --}}
        <section class="pa-detail-grid section-gap">

            {{-- Signaux --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:8px;"></i>
                            Signaux déclencheurs
                        </h3>
                        <p class="soc-card-subtitle">Données calculées par le moteur dynamique.</p>
                    </div>
                </div>

                @if($signals->count())
                    <div class="signal-grid">
                        @foreach($signals as $signal)
                            @php
                                $sigRisk = data_get($signal, 'risk_level', 'normal');
                                $sigIcon = match($sigRisk) {
                                    'critical' => 'fa-circle-exclamation',
                                    'high'     => 'fa-triangle-exclamation',
                                    'suspect'  => 'fa-eye',
                                    default    => 'fa-bolt',
                                };
                            @endphp
                            <div class="signal-card risk-{{ $sigRisk }}">
                                <div class="signal-icon {{ $sigRisk }}">
                                    <i class="fa-solid {{ $sigIcon }}"></i>
                                </div>
                                <div>
                                    <div class="signal-label">
                                        {{ data_get($signal, 'label', data_get($signal, 'code', 'Signal')) }}
                                    </div>
                                    <div class="signal-meta">
                                        @if(data_get($signal, 'source'))
                                            <span><i class="fa-solid fa-location-dot"></i> {{ data_get($signal, 'source') }}</span>
                                        @endif
                                        <span><i class="fa-solid fa-chart-simple"></i> Score {{ data_get($signal, 'score', 0) }}</span>
                                        @if($sigRisk !== 'normal')
                                            <span class="badge {{ $riskClass($sigRisk) }}" style="font-size:10px; padding:2px 7px;">{{ $sigRisk }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('platform.partials.empty-state', [
                        'title'   => 'Aucun signal.',
                        'message' => "Aucun signal détaillé n'a été enregistré dans le payload.",
                    ])
                @endif
            </div>

            {{-- Historique décisions --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-timeline" style="color:#22c55e; margin-right:8px;"></i>
                            Historique des décisions
                        </h3>
                        <p class="soc-card-subtitle">Approbation, rejet, exécution ou rollback.</p>
                    </div>
                </div>

                <div class="decision-timeline">
                    @forelse($protectionAction->decisions as $decision)
                        <div class="decision-item {{ $decisionClass($decision->decision) }}">
                            <h4 class="decision-title">
                                {{ $decisionLabel($decision->decision) }}
                                @if($decision->user)
                                    <span style="font-weight:700; color:var(--text-muted);"> — {{ $decision->user->name }}</span>
                                @endif
                            </h4>
                            <div class="decision-meta">
                                <i class="fa-regular fa-clock"></i>
                                {{ $decision->decided_at?->format('d/m/Y H:i') ?? $decision->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </div>
                            @if($decision->comment)
                                <div class="decision-comment">{{ $decision->comment }}</div>
                            @endif
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucune décision.',
                            'message' => 'Les décisions SOC apparaîtront ici.',
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── PAYLOAD JSON (accordéon) ─────────────────────────────────── --}}
        <div class="section-gap">
            <div class="meta-accordion">
                <button type="button" class="meta-accordion-toggle" id="payloadToggle">
                    <i class="fa-solid fa-code" style="color:var(--accent);"></i>
                    Payload technique (JSON)
                    <span style="margin-left:6px; font-size:11px; color:var(--text-muted);">données internes pour audit</span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="meta-accordion-body" id="payloadBody">
                    <pre class="json-box" style="margin:0;">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>

    </div>

    <script>
    document.getElementById('payloadToggle')?.addEventListener('click', function () {
        this.classList.toggle('open');
        document.getElementById('payloadBody').classList.toggle('open');
    });

    // ── Polling AJAX du statut (actif seulement si l'action est en cours) ──
    (function () {
        const POLL_INTERVAL = 5000; // 5 secondes
        const TERMINAL_STATUSES = ['executed', 'failed', 'rolled_back', 'success'];

        const initialStatus = @json($protectionAction->execution_status);
        if (TERMINAL_STATUSES.includes(initialStatus)) return; // déjà terminé, pas besoin de poller

        const statusUrl = @json(route('platform.protection-actions.status', $protectionAction));

        // Labels et classes (miroir PHP)
        function execLabel(s) {
            const map = {
                waiting_approval: '⏳ En attente d\'approbation',
                pending:          '🕐 En attente d\'exécution',
                executing:        '⚙️ En cours…',
                executed:         '✅ Exécutée',
                success:          '✅ Exécutée',
                failed:           '❌ Échouée',
                rolled_back:      '↩️ Annulée',
            };
            return map[s] || s;
        }
        function execClass(s) {
            if (['executed','success'].includes(s))  return 'badge-normal';
            if (s === 'failed')                       return 'badge-critical';
            if (s === 'executing')                    return 'badge-medium';
            return 'badge-high';
        }

        const execBadge = document.querySelector('.status-bar .status-block:nth-child(2) .badge');

        let timer = setInterval(function () {
            fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;

                    const s = data.execution_status;

                    // Mettre à jour le badge
                    if (execBadge) {
                        execBadge.textContent = execLabel(s);
                        execBadge.className   = 'badge ' + execClass(s);
                    }

                    // Animer si "en cours"
                    if (s === 'executing' && execBadge) {
                        execBadge.style.animation = 'pulse 1s infinite';
                    }

                    // Statut terminal → arrêt du poll + rechargement de la page pour afficher les détails complets
                    if (TERMINAL_STATUSES.includes(s)) {
                        clearInterval(timer);
                        setTimeout(function () { window.location.reload(); }, 800);
                    }
                })
                .catch(function () {}); // réseau coupé → silencieux
        }, POLL_INTERVAL);
    })();
    </script>
@endsection
