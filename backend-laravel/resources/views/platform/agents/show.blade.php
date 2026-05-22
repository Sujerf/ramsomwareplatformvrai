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

        {{-- ── INSTALL COMMAND ──────────────────────────────────────────── --}}
        @if($isPending)
            <section class="soc-card section-gap">
                <div class="soc-card-header">
                    <div>
                        <h3 class="soc-card-title">
                            <i class="fa-solid fa-terminal" style="color:#6366f1; margin-right:8px;"></i>
                            Commande d'installation
                        </h3>
                        <p class="soc-card-subtitle">
                            À exécuter sur la machine cible pour finaliser l'enrôlement.
                            L'agent appellera ensuite <code style="font-size:12px;">/api/agent/enroll</code>.
                        </p>
                    </div>
                </div>

                <div class="install-box">
                    <button type="button" class="copy-btn" id="copyInstall">
                        <i class="fa-solid fa-copy"></i> Copier
                    </button>
                    <code id="installCmd">{{ $installCommand }}</code>
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
                                str_contains($action->action_type, 'backup')  => 'fa-cloud-arrow-up',
                                str_contains($action->action_type, 'block')   => 'fa-shield-halved',
                                str_contains($action->action_type, 'alert')   => 'fa-bell',
                                default                                        => 'fa-shield-virus',
                            };
                            $aClass = match($action->approval_status) {
                                'approved' => 'approved',
                                'rejected' => 'rejected',
                                default    => 'pending',
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

    </div>

    <script>
    document.getElementById('copyInstall')?.addEventListener('click', function () {
        const text = document.getElementById('installCmd')?.textContent?.trim() || '';
        navigator.clipboard.writeText(text).then(() => {
            this.classList.add('copied');
            this.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
            setTimeout(() => {
                this.classList.remove('copied');
                this.innerHTML = '<i class="fa-solid fa-copy"></i> Copier';
            }, 2000);
        });
    });
    </script>
@endsection
