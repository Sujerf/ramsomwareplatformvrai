@extends('layouts.soc')

@section('title', 'RansomShield — Actions de protection')
@section('page_title', 'Actions de protection')
@section('page_subtitle', 'Décisions SOC, actions proposées et historique de réponse')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus  = $activeStatus  ?? 'active';
        $filterCounts  = $filterCounts  ?? [];

        // ── Libellés lisibles ──────────────────────────────────────────────────
        $actionLabels = [
            'isolate_host'       => 'Isoler l\'hôte',
            'rollback_isolation' => 'Lever l\'isolation',
            'kill_process'       => 'Tuer le processus',
            'update_agent'       => 'Mettre à jour l\'agent',
            'alert_only'         => 'Alerte uniquement',
            'emergency_backup'   => 'Sauvegarde d\'urgence',
            'block_path'         => 'Bloquer le chemin',
            'restrict_path'      => 'Restreindre l\'accès',
            'quarantine_file'    => 'Mettre en quarantaine',
            'lock_safe_copy'     => 'Copie de sécurité',
            'notify_only'        => 'Notifier seulement',
        ];

        $actionDescriptions = [
            'isolate_host'       => 'Coupe tout le trafic réseau sauf le canal SOC. L\'hôte ne peut plus communiquer.',
            'rollback_isolation' => 'Rétablit la connectivité réseau complète après une isolation.',
            'kill_process'       => 'Termine immédiatement le processus suspect par son PID.',
            'update_agent'       => 'Télécharge et redémarre l\'agent avec la dernière version disponible.',
            'alert_only'         => 'Déclenche une alerte sans bloquer l\'activité. Mode surveillance.',
            'emergency_backup'   => 'Lance une sauvegarde d\'urgence des fichiers critiques avant dommage.',
            'block_path'         => 'Bloque l\'accès au chemin suspect pour tous les processus.',
            'restrict_path'      => 'Restreint les permissions d\'accès au chemin ciblé.',
            'quarantine_file'    => 'Déplace le fichier suspect dans un espace isolé.',
            'lock_safe_copy'     => 'Crée une copie immuable des fichiers avant modification.',
            'notify_only'        => 'Envoie une notification SOC sans action sur l\'hôte.',
        ];

        $approvalLabel = fn($s) => match($s) {
            'approved'  => 'Approuvée',
            'rejected'  => 'Rejetée',
            'cancelled' => 'Annulée',
            'pending'   => 'En attente',
            default     => ucfirst($s),
        };

        $execLabel = fn($s) => match($s) {
            'executed', 'success' => 'Exécutée',
            'executing'           => 'En cours…',
            'pending'             => 'En attente',
            'waiting_approval'    => 'Attente appro.',
            'cancelled'           => 'Annulée',
            'failed'              => 'Échec',
            'rolled_back'         => 'Rollback',
            default               => ucfirst($s),
        };

        $approvalClass = fn($s) => match($s) {
            'approved'           => 'badge-normal',
            'rejected',
            'cancelled'          => 'badge-critical',
            'pending'            => 'badge-high',
            default              => 'badge',
        };

        $execClass = fn($s) => match($s) {
            'executed', 'success' => 'badge-normal',
            'executing'           => 'badge-suspect',
            'rolled_back'         => 'badge-suspect',
            'cancelled', 'failed' => 'badge-critical',
            default               => 'badge-high',
        };

        $riskClass = fn($r) => match($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        // ── Gradient hero adaptatif ─────────────────────────────────────────
        $heroGradient = match($activeStatus) {
            'executed' => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #22c55e 15%, transparent), transparent 30%), radial-gradient(circle at 88% 10%, color-mix(in srgb, #4ade80 10%, transparent), transparent 32%)',
            'rejected' => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #ef4444 14%, transparent), transparent 30%), radial-gradient(circle at 88% 10%, color-mix(in srgb, #f87171 8%, transparent), transparent 32%)',
            'rollback' => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #a78bfa 14%, transparent), transparent 30%), radial-gradient(circle at 88% 10%, color-mix(in srgb, #c084fc 8%, transparent), transparent 32%)',
            'approved' => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, #38bdf8 15%, transparent), transparent 30%), radial-gradient(circle at 88% 10%, color-mix(in srgb, #0ea5e9 8%, transparent), transparent 32%)',
            default    => 'radial-gradient(circle at 14% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%), radial-gradient(circle at 88% 10%, color-mix(in srgb, #f59e0b 12%, transparent), transparent 32%)',
        };

        $heroTitle = match($activeStatus) {
            'executed' => 'Actions exécutées.',
            'rejected' => 'Actions rejetées.',
            'rollback' => 'Actions rollback.',
            'approved' => 'Actions approuvées.',
            'all'      => 'Toutes les actions.',
            default    => 'Actions en attente.',
        };

        // ── Onglets de filtre ──────────────────────────────────────────────────
        $statusFilters = [
            'active'   => ['label' => 'En attente',  'icon' => 'fa-hourglass-half',   'color' => '#f59e0b'],
            'approved' => ['label' => 'Approuvées',  'icon' => 'fa-circle-dot',        'color' => '#38bdf8'],
            'executed' => ['label' => 'Exécutées',   'icon' => 'fa-circle-check',      'color' => '#22c55e'],
            'rejected' => ['label' => 'Rejetées',    'icon' => 'fa-ban',               'color' => '#ef4444'],
            'rollback' => ['label' => 'Rollback',    'icon' => 'fa-rotate-left',       'color' => '#a78bfa'],
            'all'      => ['label' => 'Toutes',      'icon' => 'fa-list',              'color' => 'var(--text-muted)'],
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
            background: {{ $heroGradient }}, var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .pa-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(38px, 5vw, 64px);
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

        /* ── STATS ────────────────────────────────────────────────────────── */
        .pa-stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        @media (max-width: 900px) { .pa-stats-row { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 560px) { .pa-stats-row { grid-template-columns: repeat(2, 1fr); } }

        /* ── FILTER TABS ──────────────────────────────────────────────────── */
        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            padding: 10px 14px;
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
            padding: 7px 14px;
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
            color: var(--text-body);
        }

        .filter-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            background: color-mix(in srgb, currentColor 18%, transparent);
        }

        .filter-tab.active .tab-count {
            background: rgba(255,255,255,.25);
            color: #fff;
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

        .pa-card.risk-critical { border-left: 4px solid #ef4444; }
        .pa-card.risk-high     { border-left: 4px solid #fb923c; }
        .pa-card.risk-suspect  { border-left: 4px solid #f59e0b; }
        .pa-card.risk-normal   { border-left: 4px solid #22c55e; }

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

        .pa-title-wrap { flex: 1; min-width: 0; }

        .pa-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .pa-desc {
            margin: 3px 0 0;
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .pa-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        /* ── MODE BADGE ──────────────────────────────────────────────────── */
        .badge-mode-manual {
            background: color-mix(in srgb, #38bdf8 14%, transparent);
            color: #38bdf8;
            border: 1px solid color-mix(in srgb, #38bdf8 30%, transparent);
        }

        .badge-mode-auto {
            background: color-mix(in srgb, #a78bfa 14%, transparent);
            color: #a78bfa;
            border: 1px solid color-mix(in srgb, #a78bfa 30%, transparent);
        }

        /* ── META GRID ───────────────────────────────────────────────────── */
        .pa-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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

        /* ── SCORE BAR ───────────────────────────────────────────────────── */
        .score-bar-wrap {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .score-bar-track {
            flex: 1;
            height: 4px;
            border-radius: 4px;
            background: color-mix(in srgb, var(--border-soft) 70%, transparent);
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .4s ease;
        }

        .score-bar-label {
            font-size: 11px;
            font-weight: 900;
            color: var(--text-muted);
            min-width: 28px;
            text-align: right;
        }

        /* ── AGE URGENCY ─────────────────────────────────────────────────── */
        .age-warning { color: #f59e0b !important; font-weight: 900 !important; }
        .age-critical { color: #ef4444 !important; font-weight: 900 !important; }

        /* ── STRIP ───────────────────────────────────────────────────────── */
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
                Les actions sont proposées automatiquement par les politiques de protection ou manuellement
                par un opérateur SOC. Chaque décision est tracée pour l'audit.
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
        <section class="pa-stats-row section-gap">
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
                <div class="smart-stat-hint">Décisions requises.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-circle-dot" style="color:#38bdf8; margin-right:6px;"></i>
                    Approuvées
                </div>
                <div class="smart-stat-value" style="{{ $stats['approved'] > 0 ? 'color:#38bdf8;' : '' }}">
                    {{ $stats['approved'] }}
                </div>
                <div class="smart-stat-hint">En attente d'exécution.</div>
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
                    <i class="fa-solid fa-rotate-left" style="color:#a78bfa; margin-right:6px;"></i>
                    Rollbacks
                </div>
                <div class="smart-stat-value" style="{{ ($stats['rollback'] ?? 0) > 0 ? 'color:#a78bfa;' : '' }}">
                    {{ $stats['rollback'] ?? 0 }}
                </div>
                <div class="smart-stat-hint">Isolations révoquées.</div>
            </div>
        </section>

        {{-- ── FILTER TABS ──────────────────────────────────────────────── --}}
        <div class="filter-tabs section-gap">
            @foreach($statusFilters as $key => $filter)
                @php $count = $filterCounts[$key] ?? 0; @endphp
                <a href="{{ route('platform.protection-actions.index', ['status' => $key]) }}"
                   class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}"
                   style="{{ $activeStatus !== $key && $count > 0 ? '--tab-color:'.$filter['color'].';' : '' }}">
                    <i class="fa-solid {{ $filter['icon'] }}"
                       style="{{ $activeStatus !== $key ? 'color:'.$filter['color'] : '' }}"></i>
                    {{ $filter['label'] }}
                    @if($count > 0)
                        <span class="tab-count">{{ $count }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- ── ACTION LIST ──────────────────────────────────────────────── --}}
        @if($actions->count())
            <div class="pa-list section-gap">
                @foreach($actions as $action)
                    @php
                        $riskLevel  = data_get($action->payload, 'risk_level', $action->incident?->risk_level ?? 'normal');
                        $riskScore  = (int) data_get($action->payload, 'risk_score', $action->incident?->risk_score ?? 0);
                        $policyCode = data_get($action->payload, 'policy_code', $action->protectionPolicy?->code ?? '—');

                        $actionType  = $action->action_type;
                        $actionLabel = $actionLabels[$actionType] ?? ucwords(str_replace('_', ' ', $actionType));
                        $actionDesc  = $actionDescriptions[$actionType] ?? null;

                        // Âge pour l'urgence
                        $proposedAt = $action->proposed_at ?? $action->created_at;
                        $ageHours   = $proposedAt ? $proposedAt->diffInHours(now()) : 0;
                        $ageClass   = match(true) {
                            $ageHours >= 4  => 'age-critical',
                            $ageHours >= 1  => 'age-warning',
                            default         => '',
                        };

                        // Mode d'exécution
                        $isManual   = $action->decision_mode === 'manual';
                        $modeLabel  = $isManual ? 'Manuel' : 'Automatique';
                        $modeClass  = $isManual ? 'badge-mode-manual' : 'badge-mode-auto';
                        $modeIcon   = $isManual ? 'fa-user-shield' : 'fa-microchip';

                        // Icône selon action
                        $aIcon = match(true) {
                            str_contains($actionType, 'isolat')   => 'fa-plug-circle-xmark',
                            str_contains($actionType, 'rollback') => 'fa-rotate-left',
                            str_contains($actionType, 'kill')     => 'fa-skull-crossbones',
                            str_contains($actionType, 'backup')   => 'fa-cloud-arrow-up',
                            str_contains($actionType, 'block')    => 'fa-shield-halved',
                            str_contains($actionType, 'restrict') => 'fa-folder-minus',
                            str_contains($actionType, 'quarant')  => 'fa-box',
                            str_contains($actionType, 'lock')     => 'fa-lock',
                            str_contains($actionType, 'update')   => 'fa-cloud-arrow-down',
                            str_contains($actionType, 'notify'),
                            str_contains($actionType, 'alert')    => 'fa-bell',
                            default                                => 'fa-shield-virus',
                        };

                        // Couleur de la barre de score
                        $scoreColor = match(true) {
                            $riskScore >= 80 => '#ef4444',
                            $riskScore >= 50 => '#fb923c',
                            $riskScore >= 25 => '#f59e0b',
                            default          => '#22c55e',
                        };

                        $canApprove = $action->approval_status === 'pending';
                        $canExecute = in_array($action->execution_status, ['pending', 'waiting_approval'], true)
                                      && $action->approval_status !== 'rejected';
                        $canRollback = $action->rollback_available ?? false;

                        // Adresse IP de l'agent
                        $agentIp = $action->agent?->ip_address ?? null;
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
                                    <div class="pa-title-wrap">
                                        <h3 class="pa-title">{{ $actionLabel }}</h3>
                                        @if($actionDesc)
                                            <p class="pa-desc">{{ $actionDesc }}</p>
                                        @endif
                                    </div>
                                    <div class="pa-badges">
                                        {{-- Mode --}}
                                        <span class="badge {{ $modeClass }}">
                                            <i class="fa-solid {{ $modeIcon }}" style="font-size:9px; margin-right:3px;"></i>
                                            {{ $modeLabel }}
                                        </span>
                                        {{-- Risque --}}
                                        <span class="badge {{ $riskClass($riskLevel) }}">
                                            <i class="fa-solid fa-circle" style="font-size:6px; margin-right:3px;"></i>
                                            {{ ucfirst($riskLevel) }}
                                        </span>
                                        {{-- Approbation --}}
                                        <span class="badge {{ $approvalClass($action->approval_status) }}">
                                            {{ $approvalLabel($action->approval_status) }}
                                        </span>
                                        {{-- Exécution --}}
                                        <span class="badge {{ $execClass($action->execution_status) }}">
                                            {{ $execLabel($action->execution_status) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Barre de score --}}
                                @if($riskScore > 0)
                                    <div class="score-bar-wrap">
                                        <div class="score-bar-track">
                                            <div class="score-bar-fill"
                                                 style="width:{{ min(100, $riskScore) }}%; background:{{ $scoreColor }};"></div>
                                        </div>
                                        <span class="score-bar-label" style="color:{{ $scoreColor }};">{{ $riskScore }}</span>
                                    </div>
                                @endif

                                {{-- Grille meta --}}
                                <div class="pa-meta">
                                    <div class="pa-ctx">
                                        <div class="pa-ctx-label"><i class="fa-solid fa-robot"></i> Agent</div>
                                        <div class="pa-ctx-value" title="{{ $action->agent?->agent_name ?? '—' }}">
                                            {{ $action->agent?->agent_name ?? '—' }}
                                        </div>
                                    </div>
                                    @if($agentIp)
                                        <div class="pa-ctx">
                                            <div class="pa-ctx-label"><i class="fa-solid fa-network-wired"></i> IP hôte</div>
                                            <div class="pa-ctx-value" style="font-family: monospace; font-size: 11px;">{{ $agentIp }}</div>
                                        </div>
                                    @endif
                                    <div class="pa-ctx">
                                        <div class="pa-ctx-label"><i class="fa-solid fa-fire-flame-curved"></i> Incident</div>
                                        <div class="pa-ctx-value">
                                            @if($action->incident)
                                                <a href="{{ route('platform.incidents.show', $action->incident_id) }}"
                                                   style="color: inherit; text-decoration: none;">#{{ $action->incident_id }}</a>
                                            @else
                                                —
                                            @endif
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
                            <span class="proposed-at {{ $ageClass }}">
                                <i class="fa-regular fa-clock"></i>
                                {{ $proposedAt?->diffForHumans() ?? '—' }}
                                @if($ageHours >= 1 && $canApprove)
                                    <span style="margin-left: 4px; opacity: .8;">— décision urgente</span>
                                @endif
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

                            @if($canRollback)
                                <form method="POST" action="{{ route('platform.protection-actions.rollback', $action) }}" style="display:contents;"
                                      onsubmit="return confirm('Confirmer le rollback de cette action ?');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-btn" style="color:#a78bfa; border-color:color-mix(in srgb, #a78bfa 30%, transparent);">
                                        <i class="fa-solid fa-rotate-left"></i> Rollback
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
