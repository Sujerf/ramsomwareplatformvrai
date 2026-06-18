@extends('layouts.soc')

@section('title', 'RansomShield — Fiche agent')
@section('page_title', 'Fiche agent')
@section('page_subtitle', $agent->agent_name)

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $riskClass = fn($r) => match($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        $enrollClass = fn($s) => match($s) {
            'pending'  => 'badge-high',
            'enrolled' => 'badge-normal',
            default    => 'badge-suspect',
        };

        $statusClass = fn($s) => match($s) {
            'resolved'                                   => 'badge-normal',
            'false_positive'                             => 'badge-suspect',
            'under_review', 'investigating', 'reopened' => 'badge-high',
            default                                      => 'badge-critical',
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
            'waiting_approval'    => 'Attente appro.',
            'cancelled'           => 'Annulée',
            'failed'              => 'Échec',
            'rolled_back'         => 'Rollback',
            default               => $s,
        };

        $risk       = $agent->risk_level ?? 'normal';
        $enroll     = $agent->enrollment_status ?? 'enrolled';
        $isIsolated = $agent->is_isolated ?? false;
        $isPending  = $enroll === 'pending';
        $isOnline   = $enroll === 'enrolled' && $agent->last_seen_at?->gt(now()->subMinutes(10));

        $agIcon = match($agent->host_role ?? '') {
            'server'      => 'fa-server',
            'workstation' => 'fa-display',
            default       => 'fa-laptop',
        };
    @endphp

    <style>
        /* ── SHOW HERO ────────────────────────────────────────────────────── */
        .agent-show-hero {
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

        .agent-show-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(32px, 4vw, 56px);
            line-height: .96;
            letter-spacing: -.07em;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .agent-show-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 900px;
            margin-top: 12px;
        }

        /* Status dot in h2 */
        .hero-status-dot {
            width: 14px;
            height: 14px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .hero-status-dot.online   { background: #22c55e; box-shadow: 0 0 0 4px color-mix(in srgb, #22c55e 20%, transparent); }
        .hero-status-dot.offline  { background: #64748b; }
        .hero-status-dot.pending  { background: #6366f1; box-shadow: 0 0 0 4px color-mix(in srgb, #6366f1 20%, transparent); }
        .hero-status-dot.isolated { background: #ef4444; box-shadow: 0 0 0 4px color-mix(in srgb, #ef4444 20%, transparent); }

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
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family: monospace;
        }

        .ctx-field-value.normal-font {
            font-family: inherit;
            font-size: 14px;
        }

        /* ── AGENT DETAIL GRID ────────────────────────────────────────────── */
        .agent-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        /* ── OS TAB SELECTOR ─────────────────────────────────────────────── */
        .os-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 18px;
        }

        .os-tab {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 50%, transparent);
            font-size: 12px;
            font-weight: 850;
            color: var(--text-muted);
            cursor: pointer;
            transition: .15s ease;
            user-select: none;
        }

        .os-tab:hover { border-color: color-mix(in srgb, #6366f1 40%, transparent); color: var(--text-main); }

        .os-tab.active {
            border-color: #6366f1;
            background: color-mix(in srgb, #6366f1 12%, transparent);
            color: #a5b4fc;
        }

        .os-tab .os-tab-icon { font-size: 15px; }

        .os-panel { display: none; }
        .os-panel.active { display: block; }

        /* ── INSTALL COMMAND BOX ──────────────────────────────────────────── */
        .install-box {
            position: relative;
            padding: 18px 20px;
            border-radius: 20px;
            background: #0a0f1e;
            border: 1px solid rgba(99, 102, 241, .3);
            overflow-x: auto;
        }

        .install-box code {
            display: block;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.7;
            color: #a5b4fc;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .install-box code .cmd-part { color: #93c5fd; }
        .install-box code .cmd-flag { color: #6ee7b7; }
        .install-box code .cmd-val  { color: #fde68a; }

        .copy-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 12px;
            border-radius: 10px;
            background: color-mix(in srgb, #6366f1 16%, transparent);
            color: #a5b4fc;
            border: 1px solid color-mix(in srgb, #6366f1 28%, transparent);
            font-size: 11px;
            font-weight: 850;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: .15s ease;
        }

        .copy-btn:hover { background: #6366f1; color: #fff; }
        .copy-btn.copied { background: color-mix(in srgb, #22c55e 16%, transparent); color: #22c55e; border-color: color-mix(in srgb, #22c55e 28%, transparent); }

        /* ── NETWORK HOST CARD ────────────────────────────────────────────── */
        .host-info-grid {
            display: grid;
            gap: 10px;
        }

        .host-info-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
            align-items: center;
        }

        .host-info-label {
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .host-info-value {
            font-size: 13px;
            font-weight: 950;
            font-family: monospace;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .host-info-value.normal-font {
            font-family: inherit;
        }

        /* ── TIMELINE ─────────────────────────────────────────────────────── */
        .timeline { display: grid; gap: 10px; }

        .timeline-item {
            position: relative;
            padding: 12px 14px 12px 44px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel-soft) 55%, transparent);
            border: 1px solid var(--border-soft);
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 15px;
            top: 17px;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 16%, transparent);
        }

        .timeline-item.risk-critical::before { background: #ef4444; box-shadow: 0 0 0 4px color-mix(in srgb, #ef4444 16%, transparent); }
        .timeline-item.risk-high::before     { background: #fb923c; box-shadow: 0 0 0 4px color-mix(in srgb, #fb923c 16%, transparent); }
        .timeline-item.risk-suspect::before  { background: #f59e0b; box-shadow: 0 0 0 4px color-mix(in srgb, #f59e0b 16%, transparent); }
        .timeline-item.risk-normal::before   { background: #22c55e; box-shadow: 0 0 0 4px color-mix(in srgb, #22c55e 16%, transparent); }

        .timeline-title {
            margin: 0;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .timeline-meta {
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.5;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .timeline-badges {
            margin-top: 7px;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── ACTION ITEM ──────────────────────────────────────────────────── */
        .action-list { display: grid; gap: 10px; }

        .action-item {
            padding: 12px;
            border-radius: 18px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .action-item-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 14px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            flex-shrink: 0;
        }

        .action-item-icon.approved { background: color-mix(in srgb, #22c55e 12%, transparent); color: #22c55e; }
        .action-item-icon.rejected { background: color-mix(in srgb, #ef4444 12%, transparent); color: #ef4444; }
        .action-item-icon.pending  { background: color-mix(in srgb, #f59e0b 12%, transparent); color: #f59e0b; }

        .action-item-title {
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .action-item-meta {
            margin-top: 3px;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            .agent-detail-grid { grid-template-columns: 1fr; }
            .ctx-strip { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 640px) {
            .ctx-strip { grid-template-columns: 1fr 1fr; }
            .action-item { grid-template-columns: 38px 1fr; }
            .action-item a { grid-column: span 2; }
        }

        @media (max-width: 440px) {
            .ctx-strip { grid-template-columns: 1fr; }
            .host-info-row { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── HERO ──────────────────────────────────────────────────────── --}}
        <section class="agent-show-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Machine surveillée
            </div>

            <h2>
                <span class="hero-status-dot {{ $isIsolated ? 'isolated' : ($isPending ? 'pending' : ($isOnline ? 'online' : 'offline')) }}"></span>
                {{ $agent->agent_name }}
            </h2>

            <p>
                Fiche d'état de l'agent : enrôlement, hôte réseau lié, dernières données de sécurité
                et commande d'installation si applicable.
            </p>

            <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <span class="badge {{ $enrollClass($enroll) }}">
                    <i class="fa-solid {{ $isPending ? 'fa-circle-dot' : 'fa-circle-check' }}" style="font-size:8px; margin-right:3px;"></i>
                    {{ $isPending ? 'À enrôler' : 'Enrôlé' }}
                </span>
                <span class="badge {{ $riskClass($risk) }}">
                    <i class="fa-solid fa-circle" style="font-size:7px; margin-right:3px;"></i>
                    {{ $risk }}
                </span>
                <span class="badge">Score {{ $agent->risk_score ?? 0 }}</span>
                <span class="badge">{{ $agent->status }}</span>
                @if($isIsolated)
                    <span class="badge badge-critical">
                        <i class="fa-solid fa-plug-circle-xmark" style="margin-right:4px;"></i> Isolé
                    </span>
                @endif
                @if($agent->host_role)
                    <span class="badge">
                        <i class="fa-solid {{ $agIcon }}" style="margin-right:4px;"></i>
                        {{ $agent->host_role }}
                    </span>
                @endif
            </div>

            <div class="btn-row">
                <a href="{{ route('platform.agents.index') }}" class="btn btn-soft">
                    <i class="fa-solid fa-arrow-left"></i> Tous les agents
                </a>
                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-network-wired"></i> Hôtes détectés
                </a>
            </div>
        </section>

        {{-- ── CONTEXT STRIP ────────────────────────────────────────────── --}}
        <div class="ctx-strip section-gap">
            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-ethernet"></i> IP
                </div>
                <div class="ctx-field-value">{{ $agent->ip_address ?? '—' }}</div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-tag"></i> Hostname
                </div>
                <div class="ctx-field-value normal-font" title="{{ $agent->hostname ?? '—' }}">
                    {{ $agent->hostname ?? '—' }}
                </div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-regular fa-clock"></i> Dernier contact
                </div>
                <div class="ctx-field-value normal-font"
                     style="{{ $isOnline ? 'color:#22c55e;' : '' }}">
                    {{ $agent->last_seen_at?->diffForHumans() ?? 'Jamais' }}
                </div>
            </div>

            <div class="ctx-field">
                <div class="ctx-field-label">
                    <i class="fa-solid fa-calendar-check"></i> Enrôlé le
                </div>
                <div class="ctx-field-value normal-font">
                    {{ $agent->enrolled_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>
        </div>

        {{-- ── SMART STATS ──────────────────────────────────────────────── --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:6px;"></i>
                    Événements
                </div>
                <div class="smart-stat-value" style="{{ $agent->events->count() > 0 ? 'color:var(--accent);' : '' }}">
                    {{ $agent->events->count() }}
                </div>
                <div class="smart-stat-hint">Chargés (12 max).</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-bell" style="color:#f59e0b; margin-right:6px;"></i>
                    Alertes
                </div>
                <div class="smart-stat-value" style="{{ $agent->alerts->count() > 0 ? 'color:#f59e0b;' : '' }}">
                    {{ $agent->alerts->count() }}
                </div>
                <div class="smart-stat-hint">Alertes récentes.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-fire-flame-curved" style="color:#fb923c; margin-right:6px;"></i>
                    Incidents
                </div>
                <div class="smart-stat-value" style="{{ $agent->incidents->count() > 0 ? 'color:#fb923c;' : '' }}">
                    {{ $agent->incidents->count() }}
                </div>
                <div class="smart-stat-hint">Incidents récents.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">
                    <i class="fa-solid fa-shield-virus" style="color:#ef4444; margin-right:6px;"></i>
                    Actions SOC
                </div>
                <div class="smart-stat-value" style="{{ $agent->protectionActions->count() > 0 ? 'color:#ef4444;' : '' }}">
                    {{ $agent->protectionActions->count() }}
                </div>
                <div class="smart-stat-hint">Réponses proposées.</div>
            </div>
        </section>

        {{-- ── GUIDE D'INSTALLATION ─────────────────────────────────────── --}}
        @if($isPending)
            <section class="soc-card section-gap">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-terminal" style="color:#6366f1; margin-right:8px;"></i>
                            Installer l'agent sur <strong>{{ $agent->ip_address ?? $agent->agent_name }}</strong>
                        </h3>
                        <p class="soc-card-subtitle">
                            Sélectionne le système d'exploitation cible puis lance la commande sur la machine.
                            Le script télécharge l'agent, configure le <code>.env</code> et démarre le service automatiquement.
                        </p>
                    </div>
                </div>

                {{-- ── BANNER : URL SOC AUTO-DÉTECTÉE ─────────────────────────── --}}
                <div style="
                    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
                    padding:10px 16px; margin-bottom:18px;
                    border-radius:12px;
                    background: color-mix(in srgb, #6366f1 8%, transparent);
                    border: 1px solid color-mix(in srgb, #6366f1 25%, transparent);
                    font-size:12px;
                ">
                    <i class="fa-solid fa-satellite-dish" style="color:#818cf8; font-size:14px; flex-shrink:0;"></i>
                    <span style="color:var(--text-muted);">URL SOC auto-détectée pour ce réseau :</span>
                    <code style="
                        background:color-mix(in srgb,#6366f1 18%,transparent);
                        color:#a5b4fc; padding:2px 8px; border-radius:6px; font-size:12px;
                    ">{{ $installInfo['soc_url'] }}</code>
                    @if($installInfo['network_name'])
                        <span style="color:var(--text-muted);">—</span>
                        <span style="color:var(--text-muted);">
                            <i class="fa-solid fa-network-wired" style="margin-right:3px;"></i>
                            {{ $installInfo['network_name'] }}
                            @if($installInfo['network_cidr'])
                                <span style="opacity:.6;">({{ $installInfo['network_cidr'] }})</span>
                            @endif
                        </span>
                    @endif
                </div>

                {{-- ── COMMANDE COURTE KVM ─────────────────────────────────────── --}}
                @if($installInfo['has_valid_token'] && $installInfo['short_enroll_url'])
                <div style="border-radius:16px; border:2px solid color-mix(in srgb, #6366f1 50%, transparent);
                            background: linear-gradient(135deg,
                                color-mix(in srgb, #6366f1 10%, transparent),
                                color-mix(in srgb, #8b5cf6 6%, transparent));
                            padding:18px 20px; margin-bottom:20px;">

                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                        <i class="fa-solid fa-keyboard" style="color:#818cf8; font-size:18px;"></i>
                        <div>
                            <div style="font-size:14px; font-weight:900; color:#a5b4fc; letter-spacing:-.02em;">
                                🚀 Commande courte — KVM / terminal sans copier-coller
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                ~50 caractères · facile à taper · code court : <code style="background:color-mix(in srgb,#6366f1 20%,transparent); padding:1px 5px; border-radius:4px;">{{ $installInfo['short_code'] }}</code>
                            </div>
                        </div>
                        <span style="margin-left:auto; font-size:11px; color:var(--text-muted); white-space:nowrap;">
                            Token valide — expire {{ $installInfo['token_expires_label'] }}
                        </span>
                    </div>

                    {{-- Linux (défaut) --}}
                    <div style="margin-bottom:10px;">
                        <div style="font-size:11px; font-weight:750; color:var(--text-muted); margin-bottom:5px;">
                            🐧 Linux / 🍎 macOS
                        </div>
                        <div class="install-box" style="border-color:color-mix(in srgb, #6366f1 40%, transparent);">
                            <button type="button" class="copy-btn" data-copy="copyShortLinux">
                                <i class="fa-solid fa-copy"></i> Copier
                            </button>
                            <code id="copyShortLinux">curl {{ $installInfo['short_enroll_url'] }} | sudo bash</code>
                        </div>
                    </div>

                    {{-- macOS --}}
                    <div style="margin-bottom:10px;">
                        <div style="font-size:11px; font-weight:750; color:var(--text-muted); margin-bottom:5px;">
                            🍎 macOS (launchd)
                        </div>
                        <div class="install-box" style="border-color:color-mix(in srgb, #6366f1 40%, transparent);">
                            <button type="button" class="copy-btn" data-copy="copyShortMac">
                                <i class="fa-solid fa-copy"></i> Copier
                            </button>
                            <code id="copyShortMac">curl "{{ $installInfo['short_enroll_url'] }}?os=macos" | sudo bash</code>
                        </div>
                    </div>

                    {{-- Windows --}}
                    <div>
                        <div style="font-size:11px; font-weight:750; color:var(--text-muted); margin-bottom:5px;">
                            🪟 Windows (PowerShell admin)
                        </div>
                        <div class="install-box" style="border-color:color-mix(in srgb, #6366f1 40%, transparent);">
                            <button type="button" class="copy-btn" data-copy="copyShortWin">
                                <i class="fa-solid fa-copy"></i> Copier
                            </button>
                            <code id="copyShortWin">powershell -ExecutionPolicy Bypass -Command "iwr '{{ $installInfo['short_enroll_url'] }}?os=windows' -UseBasicParsing | iex"</code>
                        </div>
                    </div>

                </div>
                @endif

                {{-- ── SÉLECTEUR OS ──────────────────────────────────────────── --}}
                <div class="os-tabs" id="osTabs">
                    <button type="button" class="os-tab active" data-os="linux">
                        <span class="os-tab-icon">🐧</span>
                        Linux
                    </button>
                    <button type="button" class="os-tab" data-os="macos">
                        <span class="os-tab-icon">🍎</span>
                        macOS
                    </button>
                    <button type="button" class="os-tab" data-os="windows">
                        <span class="os-tab-icon">🪟</span>
                        Windows
                    </button>
                </div>

                @if($installInfo['has_valid_token'])

                    {{-- ── ONE-LINER BOOTSTRAP : LINUX ──────────────────────── --}}
                    <div class="os-panel active" id="panel-linux">
                        <div style="border-radius:16px; border:2px solid color-mix(in srgb, #22c55e 35%, transparent);
                                    background:color-mix(in srgb, #22c55e 5%, transparent);
                                    padding:18px 20px; margin-bottom:20px;">

                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                                <i class="fa-solid fa-bolt" style="color:#22c55e; font-size:15px;"></i>
                                <span style="font-size:13px; font-weight:850; color:#22c55e;">Installation en une commande — Linux (systemd)</span>
                                <span style="margin-left:auto; font-size:11px; color:var(--text-muted);">
                                    Token valide — expire {{ $installInfo['token_expires_label'] }}
                                </span>
                            </div>

                            <div class="install-box" style="border-color:color-mix(in srgb, #22c55e 30%, transparent);">
                                <button type="button" class="copy-btn" data-copy="copyBootstrapLinux">
                                    <i class="fa-solid fa-copy"></i> Copier
                                </button>
                                <code id="copyBootstrapLinux">curl -fsSL {{ $installInfo['bootstrap_url'] }} | sudo bash</code>
                            </div>

                            <p style="margin:10px 0 0; font-size:12px; color:var(--text-muted);">
                                <i class="fa-solid fa-circle-info" style="margin-right:4px; color:#22c55e;"></i>
                                Télécharge l'agent depuis le SOC, écrit le <code>.env</code>, installe le venv Python
                                et active le service <strong>systemd</strong> (Ubuntu, Debian, RHEL…). Token détruit dès l'enrôlement réussi.
                            </p>
                        </div>

                        {{-- Manuel Linux --}}
                        <details style="border-radius:12px; border:1px solid var(--border-soft);
                                        background:color-mix(in srgb, var(--bg-panel-soft) 40%, transparent); overflow:hidden;">
                            <summary style="padding:12px 16px; cursor:pointer; font-size:13px; font-weight:750;
                                            color:var(--text-muted); list-style:none; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                                Installation manuelle Linux (alternative)
                            </summary>
                            <div style="padding:14px 16px 16px; display:flex; flex-direction:column; gap:14px; border-top:1px solid var(--border-soft);">

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">1 — Copier les fichiers sur la cible</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyLinuxStep1"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyLinuxStep1">rsync -avz {{ $installInfo['agent_source_path'] }} {{ $agent->ip_address ?? 'IP_MACHINE' }}:/opt/ransomshield-agent/</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">2 — Créer le fichier <code>.env</code></p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyLinuxStep2"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyLinuxStep2">{{ $installInfo['env_content'] }}</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">3 — Installer le service</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyLinuxStep3"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyLinuxStep3">cd /opt/ransomshield-agent && sudo bash install.sh</code>
                                    </div>
                                </div>

                            </div>
                        </details>
                    </div>

                    {{-- ── ONE-LINER BOOTSTRAP : MACOS ──────────────────────── --}}
                    <div class="os-panel" id="panel-macos">
                        <div style="border-radius:16px; border:2px solid color-mix(in srgb, #f59e0b 35%, transparent);
                                    background:color-mix(in srgb, #f59e0b 6%, transparent);
                                    padding:18px 20px; margin-bottom:20px;">

                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                                <i class="fa-brands fa-apple" style="color:#f59e0b; font-size:15px;"></i>
                                <span style="font-size:13px; font-weight:850; color:#fbbf24;">Installation en une commande — macOS (Terminal admin)</span>
                                <span style="margin-left:auto; font-size:11px; color:var(--text-muted);">
                                    Token valide — expire {{ $installInfo['token_expires_label'] }}
                                </span>
                            </div>

                            <div class="install-box" style="border-color:color-mix(in srgb, #f59e0b 35%, transparent);">
                                <button type="button" class="copy-btn" data-copy="copyBootstrapMac">
                                    <i class="fa-solid fa-copy"></i> Copier
                                </button>
                                <code id="copyBootstrapMac">curl -fsSL "{{ $installInfo['bootstrap_url'] }}?os=macos" | sudo bash</code>
                            </div>

                            <p style="margin:10px 0 0; font-size:12px; color:var(--text-muted);">
                                <i class="fa-solid fa-circle-info" style="margin-right:4px; color:#f59e0b;"></i>
                                À exécuter dans un <strong>Terminal avec sudo</strong>. Installe Python via Homebrew si absent,
                                configure le <code>.env</code>, le venv, et enregistre un <strong>LaunchDaemon</strong>
                                (<code>launchctl</code>) qui redémarre automatiquement. Token détruit dès l'enrôlement réussi.
                            </p>

                            <div style="margin-top:12px; padding:10px 12px; border-radius:8px;
                                        background:color-mix(in srgb, #ef4444 8%, transparent);
                                        border:1px solid color-mix(in srgb, #ef4444 25%, transparent);">
                                <p style="margin:0; font-size:11.5px; color:var(--text-muted);">
                                    <i class="fa-solid fa-shield-halved" style="color:#ef4444; margin-right:5px;"></i>
                                    <strong style="color:#ef4444;">Isolation réseau sur macOS</strong> — utilise
                                    <code>pfctl</code> (Packet Filter). Requiert que l'agent tourne en <code>root</code>.
                                    Un backup des règles pf est sauvegardé avant isolation pour un rollback propre.
                                </p>
                            </div>
                        </div>

                        {{-- Manuel macOS --}}
                        <details style="border-radius:12px; border:1px solid var(--border-soft);
                                        background:color-mix(in srgb, var(--bg-panel-soft) 40%, transparent); overflow:hidden;">
                            <summary style="padding:12px 16px; cursor:pointer; font-size:13px; font-weight:750;
                                            color:var(--text-muted); list-style:none; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                                Installation manuelle macOS (alternative)
                            </summary>
                            <div style="padding:14px 16px 16px; display:flex; flex-direction:column; gap:14px; border-top:1px solid var(--border-soft);">

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">1 — Copier les fichiers sur la cible</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyMacStep1"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyMacStep1">rsync -avz {{ $installInfo['agent_source_path'] }} {{ $agent->ip_address ?? 'IP_MAC' }}:/opt/ransomshield-agent/</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">2 — Créer le fichier <code>.env</code></p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyMacStep2"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyMacStep2">{{ $installInfo['env_content'] }}</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">3 — Installer le LaunchDaemon</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyMacStep3"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyMacStep3">curl -fsSL "{{ $installInfo['bootstrap_url'] }}?os=macos" -o ransomshield-install.sh && sudo bash ransomshield-install.sh</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">4 — Vérifier le service</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyMacStep4"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyMacStep4">launchctl list | grep ransomshield && tail -f /var/log/ransomshield-agent.log</code>
                                    </div>
                                </div>

                            </div>
                        </details>
                    </div>

                    {{-- ── ONE-LINER BOOTSTRAP : WINDOWS ────────────────────── --}}
                    <div class="os-panel" id="panel-windows">
                        <div style="border-radius:16px; border:2px solid color-mix(in srgb, #6366f1 40%, transparent);
                                    background:color-mix(in srgb, #6366f1 6%, transparent);
                                    padding:18px 20px; margin-bottom:20px;">

                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                                <i class="fa-brands fa-windows" style="color:#6366f1; font-size:15px;"></i>
                                <span style="font-size:13px; font-weight:850; color:#a5b4fc;">Installation en une commande — Windows (PowerShell admin)</span>
                                <span style="margin-left:auto; font-size:11px; color:var(--text-muted);">
                                    Token valide — expire {{ $installInfo['token_expires_label'] }}
                                </span>
                            </div>

                            <div class="install-box" style="border-color:color-mix(in srgb, #6366f1 35%, transparent);">
                                <button type="button" class="copy-btn" data-copy="copyBootstrapWin">
                                    <i class="fa-solid fa-copy"></i> Copier
                                </button>
                                <code id="copyBootstrapWin">powershell -ExecutionPolicy Bypass -Command "iwr '{{ $installInfo['bootstrap_url'] }}?os=windows' -UseBasicParsing | iex"</code>
                            </div>

                            <p style="margin:10px 0 0; font-size:12px; color:var(--text-muted);">
                                <i class="fa-solid fa-circle-info" style="margin-right:4px; color:#6366f1;"></i>
                                À exécuter dans un <strong>PowerShell administrateur</strong>. Le script télécharge et configure
                                l'agent, installe Python si absent, et enregistre un <strong>service Windows</strong>
                                (<code>sc.exe</code>). Token détruit dès l'enrôlement réussi.
                            </p>
                        </div>

                        {{-- Manuel Windows --}}
                        <details style="border-radius:12px; border:1px solid var(--border-soft);
                                        background:color-mix(in srgb, var(--bg-panel-soft) 40%, transparent); overflow:hidden;">
                            <summary style="padding:12px 16px; cursor:pointer; font-size:13px; font-weight:750;
                                            color:var(--text-muted); list-style:none; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                                Installation manuelle Windows (alternative)
                            </summary>
                            <div style="padding:14px 16px 16px; display:flex; flex-direction:column; gap:14px; border-top:1px solid var(--border-soft);">

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">1 — Télécharger le script PowerShell</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyWinStep1"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyWinStep1">Invoke-WebRequest -Uri '{{ $installInfo['bootstrap_url'] }}?os=windows' -OutFile 'ransomshield-install.ps1'; .\ransomshield-install.ps1</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">2 — Vérifier le service après installation</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyWinStep2"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyWinStep2">Get-Service -Name RansomShieldAgent | Select-Object Name, Status, StartType</code>
                                    </div>
                                </div>

                                <div>
                                    <p style="margin:0 0 6px; font-size:12px; font-weight:850; color:var(--text-muted);">3 — Consulter les logs</p>
                                    <div class="install-box">
                                        <button type="button" class="copy-btn" data-copy="copyWinStep3"><i class="fa-solid fa-copy"></i> Copier</button>
                                        <code id="copyWinStep3">Get-Content C:\RansomShieldAgent\.ransomshield_host_agent_state.json</code>
                                    </div>
                                </div>

                            </div>
                        </details>
                    </div>

                @elseif($installInfo['token_is_expired'])

                    {{-- ── TOKEN EXPIRÉ ──────────────────────────────────────── --}}
                    <div style="border-radius:12px; border:1px solid color-mix(in srgb, #ef4444 30%, transparent);
                                background:color-mix(in srgb, #ef4444 7%, transparent);
                                padding:14px 18px; margin-bottom:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <i class="fa-solid fa-lock-open" style="color:#ef4444; font-size:16px; flex-shrink:0;"></i>
                        <div style="flex:1; min-width:200px;">
                            <p style="margin:0 0 4px; font-size:13px; font-weight:850; color:#ef4444;">Token d'enrôlement expiré</p>
                            <p style="margin:0; font-size:12px; color:var(--text-muted);">
                                Régénère un nouveau token pour obtenir le script d'installation.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('platform.agents.regenerate-token', $agent) }}">
                            @csrf
                            <button type="submit" style="padding:8px 16px; border-radius:8px; border:none;
                                    background:#ef4444; color:#fff; font-weight:700; font-size:13px; cursor:pointer;
                                    display:flex; align-items:center; gap:7px; white-space:nowrap;">
                                <i class="fa-solid fa-rotate-right"></i> Régénérer le token
                            </button>
                        </form>
                    </div>

                @endif

                {{-- ── BOUTON RÉGÉNÉRER (agent non-enrôlé avec token valide) ── --}}
                @if($agent->enrollment_status !== 'enrolled')
                    <div style="margin-bottom:16px; display:flex; justify-content:flex-end;">
                        <form method="POST" action="{{ route('platform.agents.regenerate-token', $agent) }}">
                            @csrf
                            <button type="submit"
                                    style="padding:7px 14px; border-radius:8px; border:1.5px solid var(--border);
                                           background:transparent; color:var(--text-muted); font-size:12px;
                                           font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px;"
                                    title="Génère un nouveau token valable 48h et rafraîchit le script bootstrap">
                                <i class="fa-solid fa-rotate-right"></i> Nouveau token (48h)
                            </button>
                        </form>
                    </div>
                @endif

                {{-- ── BANDEAU UUID + TOKEN ──────────────────────────────── --}}
                <div style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
                    <div style="padding:10px 14px; border-radius:12px;
                                background:color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
                                border:1px solid var(--border-soft); display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <i class="fa-solid fa-fingerprint" style="color:var(--accent); font-size:13px;"></i>
                        <span style="font-size:11px; font-weight:850; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">UUID agent</span>
                        <code style="font-size:12px; color:var(--accent);">{{ $installInfo['agent_uuid'] }}</code>
                    </div>

                    @if($installInfo['enrollment_token'])
                        <div style="padding:10px 14px; border-radius:12px;
                                    background:color-mix(in srgb, {{ $installInfo['token_is_expired'] ? '#ef4444' : '#22c55e' }} 7%, transparent);
                                    border:1px solid color-mix(in srgb, {{ $installInfo['token_is_expired'] ? '#ef4444' : '#22c55e' }} 22%, transparent);
                                    display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <i class="fa-solid {{ $installInfo['token_is_expired'] ? 'fa-lock-open' : 'fa-lock' }}"
                               style="color:{{ $installInfo['token_is_expired'] ? '#ef4444' : '#22c55e' }}; font-size:13px;"></i>
                            <span style="font-size:11px; font-weight:850; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Token</span>
                            <code style="font-size:12px; color:{{ $installInfo['token_is_expired'] ? '#ef4444' : '#22c55e' }};">
                                {{ substr($installInfo['enrollment_token'], 0, 8) }}••••••••••••••
                            </code>
                            <span style="font-size:11px; color:var(--text-muted); margin-left:auto;">
                                {{ $installInfo['token_is_expired'] ? '⚠ Expiré' : 'Expire '.$installInfo['token_expires_label'].' — usage unique' }}
                            </span>
                        </div>
                    @else
                        <div style="padding:10px 14px; border-radius:12px;
                                    background:color-mix(in srgb, #22c55e 7%, transparent);
                                    border:1px solid color-mix(in srgb, #22c55e 22%, transparent);
                                    display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-circle-check" style="color:#22c55e; font-size:13px;"></i>
                            <span style="font-size:12px; color:#22c55e; font-weight:700;">Agent enrôlé — token détruit après usage.</span>
                        </div>
                    @endif
                </div>

            </section>
        @endif

        {{-- ── MISE À JOUR DE L'AGENT ──────────────────────────────────── --}}
        @if($agent->enrollment_status === 'enrolled')
        <section class="soc-card section-gap" style="border-color:rgba(56,189,248,.2);">
            <div class="soc-card-header" style="border-bottom-color:rgba(56,189,248,.15);">
                <div>
                    <h3 class="soc-card-title">
                        <i class="fa-solid fa-cloud-arrow-down" style="color:#38bdf8; margin-right:8px;"></i>
                        Mettre à jour l'agent
                    </h3>
                    <p class="soc-card-subtitle">Déploie la dernière version depuis le SOC.</p>
                </div>
                {{-- Bouton "Envoyer la commande" via le système de commandes --}}
                <form method="POST" action="{{ route('platform.agents.send-command', $agent) }}" style="margin-left:auto;">
                    @csrf
                    <input type="hidden" name="action_type" value="update_agent">
                    <input type="hidden" name="note" value="Mise à jour déclenchée depuis la console SOC">
                    <button type="submit"
                            style="padding:8px 16px; border-radius:8px; border:none;
                                   background:#38bdf8; color:#071d2e; font-size:.8rem;
                                   font-weight:700; cursor:pointer;
                                   display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Envoyer la commande (auto)
                    </button>
                </form>
            </div>
            <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">

                {{-- Info processus --}}
                <div style="font-size:.82rem; color:var(--text-muted); line-height:1.6;">
                    <strong style="color:var(--text-primary);">Comment ça marche (auto) :</strong>
                    La commande est envoyée à l'agent via le système de poll (≤&nbsp;30&nbsp;s).
                    L'agent télécharge la nouvelle version, se remplace et redémarre automatiquement.
                    <br>
                    <strong style="color:#f97316;">⚠ Prérequis :</strong> L'agent doit avoir la version ≥ 1.1 (commande <code>update_agent</code> supportée).
                    Pour les versions antérieures, utilise la commande manuelle ci-dessous.
                </div>

                {{-- Commande manuelle PowerShell --}}
                @php
                    $socUrl    = $installInfo['soc_url'];
                    $updateCmd = "Stop-ScheduledTask -TaskName RansomShieldAgent\n" .
                                 "iwr '{$socUrl}/api/agent/download/ransomshield_host_agent.py' -UseBasicParsing -OutFile C:\\RansomShieldAgent\\ransomshield_host_agent.py\n" .
                                 "Start-ScheduledTask -TaskName RansomShieldAgent\n" .
                                 "Write-Host 'Agent mis a jour et redémarre.'";
                @endphp
                <div>
                    <div style="font-size:.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;
                                letter-spacing:.06em; margin-bottom:.5rem; display:flex; align-items:center; gap:.5rem;">
                        <i class="fa-brands fa-windows" style="color:#38bdf8;"></i>
                        Mise à jour manuelle — PowerShell (Windows)
                        <button onclick="navigator.clipboard.writeText(document.getElementById('updateCmd').innerText)"
                                style="margin-left:auto; padding:3px 10px; border-radius:5px; border:1px solid var(--border);
                                       background:transparent; color:var(--text-muted); font-size:.7rem; cursor:pointer;">
                            <i class="fa-solid fa-copy"></i> Copier
                        </button>
                    </div>
                    <pre id="updateCmd" style="margin:0; padding:10px 14px; border-radius:8px;
                                               background:var(--bg-deep,#030810); border:1px solid rgba(56,189,248,.15);
                                               font-size:.75rem; color:#7dd3fc; line-height:1.6;
                                               white-space:pre-wrap; word-break:break-all;">{{ $updateCmd }}</pre>
                </div>

                {{-- Linux / macOS --}}
                @php
                    $updateCmdLinux = "sudo systemctl stop ransomshield-agent\n" .
                                      "sudo curl -fsSL '{$socUrl}/api/agent/download/ransomshield_host_agent.py' -o /opt/ransomshield-agent/ransomshield_host_agent.py\n" .
                                      "sudo systemctl start ransomshield-agent";
                @endphp
                <div>
                    <div style="font-size:.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;
                                letter-spacing:.06em; margin-bottom:.5rem; display:flex; align-items:center; gap:.5rem;">
                        <i class="fa-brands fa-linux" style="color:#22c55e;"></i>
                        Mise à jour manuelle — Linux
                        <button onclick="navigator.clipboard.writeText(document.getElementById('updateCmdLinux').innerText)"
                                style="margin-left:auto; padding:3px 10px; border-radius:5px; border:1px solid var(--border);
                                       background:transparent; color:var(--text-muted); font-size:.7rem; cursor:pointer;">
                            <i class="fa-solid fa-copy"></i> Copier
                        </button>
                    </div>
                    <pre id="updateCmdLinux" style="margin:0; padding:10px 14px; border-radius:8px;
                                                    background:var(--bg-deep,#030810); border:1px solid rgba(34,197,94,.15);
                                                    font-size:.75rem; color:#86efac; line-height:1.6;
                                                    white-space:pre-wrap; word-break:break-all;">{{ $updateCmdLinux }}</pre>
                </div>

            </div>
        </section>
        @endif

        {{-- ── RÉSEAU / IDENTITÉ ────────────────────────────────────────── --}}
        <section class="agent-detail-grid section-gap">

            {{-- Hôte réseau lié --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-network-wired" style="color:var(--accent); margin-right:8px;"></i>
                            Hôte réseau lié
                        </h3>
                        <p class="soc-card-subtitle">Information issue de la détection LAN.</p>
                    </div>
                </div>

                @if($agent->discoveredHost)
                    <div class="host-info-grid">
                        @php $host = $agent->discoveredHost; @endphp

                        <div class="host-info-row">
                            <div class="host-info-label"><i class="fa-solid fa-tag"></i> Hostname</div>
                            <div class="host-info-value normal-font">{{ $host->hostname ?: '—' }}</div>
                        </div>
                        <div class="host-info-row">
                            <div class="host-info-label"><i class="fa-solid fa-ethernet"></i> IP</div>
                            <div class="host-info-value">{{ $host->ip_address }}</div>
                        </div>
                        <div class="host-info-row">
                            <div class="host-info-label"><i class="fa-solid fa-id-card"></i> MAC</div>
                            <div class="host-info-value">{{ $host->mac_address ?? '—' }}</div>
                        </div>
                        <div class="host-info-row">
                            <div class="host-info-label"><i class="fa-solid fa-sitemap"></i> Réseau</div>
                            <div class="host-info-value">{{ $host->managedNetwork?->cidr ?? '—' }}</div>
                        </div>
                        <div class="host-info-row">
                            <div class="host-info-label"><i class="fa-solid fa-circle-info"></i> Statut</div>
                            <div class="host-info-value normal-font">{{ $host->discovery_status }}</div>
                        </div>
                    </div>
                @else
                    @include('platform.partials.empty-state', [
                        'title'   => 'Aucun hôte lié.',
                        'message' => 'Cet agent a été créé sans correspondance avec un hôte découvert.',
                    ])
                @endif
            </div>

            {{-- Identité technique --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-fingerprint" style="color:#6366f1; margin-right:8px;"></i>
                            Identité technique
                        </h3>
                        <p class="soc-card-subtitle">Informations d'identification de l'agent.</p>
                    </div>
                </div>

                <div class="host-info-grid">
                    <div class="host-info-row">
                        <div class="host-info-label"><i class="fa-solid fa-fingerprint"></i> UUID</div>
                        <div class="host-info-value" style="font-size:11px;">{{ $agent->agent_uuid }}</div>
                    </div>
                    <div class="host-info-row">
                        <div class="host-info-label"><i class="fa-solid fa-id-card"></i> MAC</div>
                        <div class="host-info-value">{{ $agent->mac_address ?? '—' }}</div>
                    </div>
                    <div class="host-info-row">
                        <div class="host-info-label"><i class="fa-solid fa-display"></i> Rôle</div>
                        <div class="host-info-value normal-font">{{ $agent->host_role ?? '—' }}</div>
                    </div>
                    <div class="host-info-row">
                        <div class="host-info-label"><i class="fa-solid fa-circle-info"></i> Statut</div>
                        <div class="host-info-value normal-font">{{ $agent->status }}</div>
                    </div>
                    <div class="host-info-row">
                        <div class="host-info-label"><i class="fa-solid fa-plug-circle-xmark"></i> Isolé</div>
                        <div class="host-info-value normal-font" style="{{ $isIsolated ? 'color:#ef4444;' : 'color:#22c55e;' }}">
                            {{ $isIsolated ? 'Oui' : 'Non' }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── ALERTES / INCIDENTS ──────────────────────────────────────── --}}
        <section class="agent-detail-grid section-gap">

            {{-- Alertes --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bell" style="color:#f59e0b; margin-right:8px;"></i>
                            Alertes récentes
                        </h3>
                        <p class="soc-card-subtitle">8 dernières alertes de cet agent.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->alerts as $alert)
                        <div class="timeline-item risk-{{ $alert->risk_level ?? 'normal' }}">
                            <h4 class="timeline-title">{{ $alert->title }}</h4>
                            <div class="timeline-meta">
                                <span><i class="fa-regular fa-clock"></i>
                                    {{ $alert->detected_at?->format('d/m/Y H:i') ?? $alert->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="timeline-badges">
                                <span class="badge {{ $riskClass($alert->risk_level ?? 'normal') }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $alert->risk_level ?? 'normal' }}
                                </span>
                                <span class="badge" style="font-size:10px; padding:2px 7px;">{{ $alert->status }}</span>
                                <a href="{{ route('platform.alerts.show', $alert) }}" class="action-btn" style="padding:3px 10px; font-size:11px;">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucune alerte.',
                            'message' => 'Aucune alerte récente pour cet agent.',
                        ])
                    @endforelse
                </div>
            </div>

            {{-- Incidents --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-fire-flame-curved" style="color:#fb923c; margin-right:8px;"></i>
                            Incidents récents
                        </h3>
                        <p class="soc-card-subtitle">8 derniers incidents de cet agent.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->incidents as $incident)
                        <div class="timeline-item risk-{{ $incident->risk_level ?? 'normal' }}">
                            <h4 class="timeline-title">{{ $incident->title }}</h4>
                            <div class="timeline-meta">
                                <span><i class="fa-regular fa-clock"></i>
                                    {{ $incident->detected_at?->format('d/m/Y H:i') ?? $incident->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="timeline-badges">
                                <span class="badge {{ $riskClass($incident->risk_level ?? 'normal') }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $incident->risk_level ?? 'normal' }}
                                </span>
                                <span class="badge {{ $statusClass($incident->status) }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $incident->status }}
                                </span>
                                <a href="{{ route('platform.incidents.show', $incident) }}" class="action-btn" style="padding:3px 10px; font-size:11px;">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucun incident.',
                            'message' => 'Aucun incident récent pour cet agent.',
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── ÉVÉNEMENTS / ACTIONS ─────────────────────────────────────── --}}
        <section class="agent-detail-grid section-gap">

            {{-- Événements --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-bolt" style="color:var(--accent); margin-right:8px;"></i>
                            Événements récents
                        </h3>
                        <p class="soc-card-subtitle">12 derniers événements reçus.</p>
                    </div>
                </div>

                <div class="timeline">
                    @forelse($agent->events as $event)
                        <div class="timeline-item risk-{{ $event->risk_level ?? 'normal' }}">
                            <h4 class="timeline-title">{{ $event->event_type }}</h4>
                            @if($event->path)
                                <div class="timeline-meta">
                                    <span style="font-family:monospace; font-size:10px; word-break:break-all;">
                                        {{ Str::limit($event->path, 55) }}
                                    </span>
                                </div>
                            @endif
                            <div class="timeline-badges">
                                <span class="badge {{ $riskClass($event->risk_level ?? 'normal') }}" style="font-size:10px; padding:2px 7px;">
                                    {{ $event->risk_level ?? 'normal' }}
                                </span>
                                <span class="badge" style="font-size:10px; padding:2px 7px;">Score {{ $event->score ?? 0 }}</span>
                                <span style="font-size:10px; color:var(--text-muted);">
                                    {{ $event->observed_at?->format('d/m H:i') ?? $event->created_at?->format('d/m H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucun événement.',
                            'message' => "Les événements apparaîtront quand l'agent Python commencera à transmettre les données.",
                        ])
                    @endforelse
                </div>
            </div>

            {{-- ═══ Commandes manuelles ═══ --}}
            <div class="soc-card" id="manual-command-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-terminal" style="color:#6366f1; margin-right:8px;"></i>
                            Envoyer une commande
                        </h3>
                        <p class="soc-card-subtitle">La commande sera exécutée par l'agent lors de son prochain poll (≤ 30 s).</p>
                    </div>
                </div>

                @if($agent->enrollment_status !== 'enrolled')
                    <div class="empty-state">
                        <i class="fa-solid fa-circle-xmark" style="font-size:28px; color:#64748b; margin-bottom:8px;"></i>
                        <div class="empty-state-title">Agent non enrôlé</div>
                        <div class="empty-state-message">Les commandes ne peuvent être envoyées qu'à un agent actif.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('platform.agents.send-command', $agent) }}" id="command-form">
                        @csrf
                        <div style="display:grid; gap:14px;">

                            {{-- Type d'action --}}
                            <div>
                                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px;">
                                    Type d'action
                                </label>
                                <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:8px;">
                                    @foreach([
                                        ['isolate_host',      'fa-plug-circle-xmark', '#ef4444', 'Isoler l\'hôte',       'Coupe tout le trafic sauf SOC'],
                                        ['kill_process',      'fa-ban',               '#f59e0b', 'Tuer un process',      'Nécessite un PID'],
                                        ['rollback_isolation','fa-plug-circle-check', '#22c55e', 'Lever l\'isolation',   'Restaure le trafic réseau'],
                                        ['update_agent',      'fa-cloud-arrow-down',  '#38bdf8', 'Mettre à jour',        'Télécharge et redémarre avec la dernière version'],
                                        ['force_scan',        'fa-magnifying-glass-chart', '#a855f7', 'Scan actif',      'Analyse immédiate des fichiers surveillés'],
                                    ] as [$val, $icon, $color, $label, $desc])
                                    <label style="cursor:pointer;">
                                        <input type="radio" name="action_type" value="{{ $val }}" class="cmd-type-radio"
                                               style="display:none;" {{ old('action_type') === $val ? 'checked' : '' }}>
                                        <div class="cmd-type-btn" data-value="{{ $val }}"
                                             style="border:2px solid var(--border); border-radius:10px; padding:12px 10px; text-align:center; transition:.15s; cursor:pointer; background:var(--card-bg);">
                                            <i class="fa-solid {{ $icon }}" style="font-size:20px; color:{{ $color }}; display:block; margin-bottom:6px;"></i>
                                            <div style="font-size:12px; font-weight:700; color:var(--text-main);">{{ $label }}</div>
                                            <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">{{ $desc }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                @error('action_type')
                                    <p style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Options scan actif (affiché seulement pour force_scan) --}}
                            <div id="scan-options-field" style="display:none;">
                                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px;">
                                    Type de scan
                                </label>
                                <div style="display:flex; gap:10px; margin-bottom:10px;">
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                        <input type="radio" name="scan_type" value="quick" checked> Rapide
                                        <span style="color:var(--text-muted);font-size:11px;">(3 niveaux de profondeur)</span>
                                    </label>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                                        <input type="radio" name="scan_type" value="full"> Complet
                                        <span style="color:var(--text-muted);font-size:11px;">(récursif, peut être long)</span>
                                    </label>
                                </div>
                                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px;">
                                    Chemins supplémentaires <span style="font-weight:400;text-transform:none;">(optionnel — un par ligne)</span>
                                </label>
                                <textarea name="scan_paths" rows="3"
                                    placeholder="/home/user/Documents&#10;C:\Users\Public"
                                    style="width:100%;padding:9px 12px;border-radius:8px;border:1.5px solid var(--border);background:var(--card-bg);color:var(--text-main);font-size:13px;font-family:monospace;box-sizing:border-box;resize:vertical;"></textarea>
                                <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                                    Les chemins par défaut (<code>{{ implode(', ', ['/home', '/tmp', 'C:\Users']) }}</code> etc.) sont toujours inclus.
                                </p>
                            </div>

                            {{-- PID (affiché seulement pour kill_process) --}}
                            <div id="pid-field" style="display:none;">
                                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px;">
                                    PID du processus
                                </label>
                                <input type="number" name="pid" id="pid-input" min="1"
                                       value="{{ old('pid') }}"
                                       placeholder="ex : 4721"
                                       style="width:100%; padding:9px 12px; border-radius:8px; border:1.5px solid var(--border); background:var(--card-bg); color:var(--text-main); font-size:14px; box-sizing:border-box;">
                                @error('pid')
                                    <p style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Note optionnelle --}}
                            <div>
                                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:6px;">
                                    Note (optionnelle)
                                </label>
                                <input type="text" name="note" value="{{ old('note') }}"
                                       placeholder="ex : Ransomware détecté sur ce poste"
                                       style="width:100%; padding:9px 12px; border-radius:8px; border:1.5px solid var(--border); background:var(--card-bg); color:var(--text-main); font-size:14px; box-sizing:border-box;">
                            </div>

                            <button type="submit" id="send-cmd-btn"
                                    style="padding:10px 18px; border-radius:9px; border:none; background:#6366f1; color:#fff; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px; justify-content:center; opacity:.5; pointer-events:none; transition:.15s;"
                                    disabled>
                                <i class="fa-solid fa-paper-plane"></i> Envoyer la commande
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Actions --}}
            <div class="soc-card">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-shield-virus" style="color:#ef4444; margin-right:8px;"></i>
                            Actions SOC liées
                        </h3>
                        <p class="soc-card-subtitle">8 dernières actions proposées pour cet agent.</p>
                    </div>
                </div>

                <div class="action-list">
                    @forelse($agent->protectionActions as $action)
                        @php
                            $aIcon = match(true) {
                                str_contains($action->action_type, 'isolat')  => 'fa-plug-circle-xmark',
                                str_contains($action->action_type, 'kill')    => 'fa-ban',
                                str_contains($action->action_type, 'scan')    => 'fa-magnifying-glass-chart',
                                str_contains($action->action_type, 'backup')  => 'fa-cloud-arrow-up',
                                str_contains($action->action_type, 'block')   => 'fa-shield-halved',
                                str_contains($action->action_type, 'alert')   => 'fa-bell',
                                default                                        => 'fa-shield-virus',
                            };
                            $aClass = match($action->approval_status) {
                                'approved'             => 'approved',
                                'rejected', 'cancelled' => 'rejected',
                                default                => 'pending',
                            };
                        @endphp
                        <div class="action-item">
                            <div class="action-item-icon {{ $aClass }}">
                                <i class="fa-solid {{ $aIcon }}"></i>
                            </div>
                            <div>
                                <div class="action-item-title">{{ $action->action_type }}</div>
                                <div class="action-item-meta">
                                    <span class="badge {{ $action->approval_status === 'approved' ? 'badge-normal' : ($action->approval_status === 'rejected' ? 'badge-critical' : 'badge-high') }}" style="font-size:10px; padding:2px 7px;">
                                        {{ $approvalLabel($action->approval_status) }}
                                    </span>
                                    <span>{{ $execLabel($action->execution_status) }}</span>
                                    <span>{{ $action->created_at?->format('d/m H:i') }}</span>
                                </div>
                            </div>
                            <a href="{{ route('platform.protection-actions.show', $action) }}"
                               class="action-btn" style="align-self:center; padding:6px 10px;">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    @empty
                        @include('platform.partials.empty-state', [
                            'title'   => 'Aucune action.',
                            'message' => 'Aucune action liée à cet agent.',
                        ])
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── ZONE DE DANGER ───────────────────────────────────────────── --}}
        <section class="section-gap" style="
            border: 1.5px solid rgba(239, 68, 68, .25);
            border-radius: 16px;
            padding: 20px 24px;
            background: rgba(239, 68, 68, .04);
        ">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444; font-size:1.1rem;"></i>
                <span style="font-size:.85rem; font-weight:700; color:#ef4444; text-transform:uppercase; letter-spacing:.07em;">Zone de danger</span>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">

                {{-- Désinscrire (soft) — admin uniquement --}}
                @if(auth()->user()->isAdmin() && $agent->enrollment_status === 'enrolled')
                <div style="flex:1; min-width:240px;">
                    <p style="font-size:.8rem; color:var(--text-muted); margin:0 0 10px;">
                        <strong style="color:var(--text-primary);">Désinscrire l'agent</strong><br>
                        Efface la clé API et le token. L'agent repasse en <em>pending</em>. L'historique est conservé. Utile pour forcer un re-enrôlement.
                    </p>
                    <form method="POST" action="{{ route('platform.agents.unenroll', $agent) }}"
                          data-confirm="Désinscrire cet agent ?\nLa clé API sera révoquée. Un ré-enrôlement sera nécessaire."
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        @method('PATCH')
                        <button type="submit" style="
                            padding:8px 16px; border-radius:8px; cursor:pointer;
                            background:transparent; border:1.5px solid #f97316;
                            color:#f97316; font-size:.8rem; font-weight:700;
                            display:inline-flex; align-items:center; gap:6px;
                        ">
                            <i class="fa-solid fa-plug-circle-minus"></i> Désinscrire
                        </button>
                    </form>
                </div>
                @endif

                {{-- Supprimer définitivement — admin uniquement --}}
                @if(auth()->user()->isAdmin())
                <div style="flex:1; min-width:240px;">
                    <p style="font-size:.8rem; color:var(--text-muted); margin:0 0 10px;">
                        <strong style="color:#ef4444;">Supprimer définitivement</strong><br>
                        Supprime l'agent et <strong>toutes ses données</strong> (events, alertes, incidents, actions). Action irréversible.
                    </p>
                    <form method="POST" action="{{ route('platform.agents.destroy', $agent) }}"
                          data-confirm="⚠ Supprimer DÉFINITIVEMENT cet agent et toutes ses données ?\nCette action est irréversible."
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="
                            padding:8px 16px; border-radius:8px; cursor:pointer;
                            background:#ef4444; border:none;
                            color:#fff; font-size:.8rem; font-weight:700;
                            display:inline-flex; align-items:center; gap:6px;
                        ">
                            <i class="fa-solid fa-trash"></i> Supprimer l'agent
                        </button>
                    </form>
                </div>
                @endif

            </div>
        </section>

    </div>

    <script>
    // ── Sélecteur de commande manuelle ──────────────────────────────────────
    (function () {
        const radios   = document.querySelectorAll('.cmd-type-radio');
        const btns     = document.querySelectorAll('.cmd-type-btn');
        const pidField      = document.getElementById('pid-field');
        const scanField     = document.getElementById('scan-options-field');
        const sendBtn       = document.getElementById('send-cmd-btn');

        function selectAction(value) {
            // Visual state
            btns.forEach(function (b) {
                const isActive = b.dataset.value === value;
                b.style.borderColor  = isActive ? '#6366f1' : 'var(--border)';
                b.style.background   = isActive ? 'color-mix(in srgb, #6366f1 10%, var(--card-bg))' : 'var(--card-bg)';
            });

            // Show/hide contextual fields
            if (pidField)  pidField.style.display  = (value === 'kill_process') ? 'block' : 'none';
            if (scanField) scanField.style.display = (value === 'force_scan')   ? 'block' : 'none';

            // Enable send button
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.style.pointerEvents = 'auto';
            }
        }

        // Click on card → check radio
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const val    = this.dataset.value;
                const radio  = document.querySelector('.cmd-type-radio[value="' + val + '"]');
                if (radio) radio.checked = true;
                selectAction(val);
            });
        });

        // In case page is reloaded with old() value
        radios.forEach(function (r) {
            if (r.checked) selectAction(r.value);
        });
    })();

    // ── Copie dans le presse-papier ─────────────────────────────────────────
    document.querySelectorAll('.copy-btn[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-copy');
            const text = document.getElementById(targetId)?.textContent?.trim() || '';
            navigator.clipboard.writeText(text).then(() => {
                this.classList.add('copied');
                this.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
                setTimeout(() => {
                    this.classList.remove('copied');
                    this.innerHTML = '<i class="fa-solid fa-copy"></i> Copier';
                }, 2000);
            });
        });
    });

    // ── Sélecteur OS (Linux / Windows) ─────────────────────────────────────
    const osTabs   = document.querySelectorAll('.os-tab');
    const osPanels = document.querySelectorAll('.os-panel');

    osTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const os = this.getAttribute('data-os');

            osTabs.forEach(t => t.classList.remove('active'));
            osPanels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const panel = document.getElementById('panel-' + os);
            if (panel) panel.classList.add('active');
        });
    });
    </script>
@endsection
