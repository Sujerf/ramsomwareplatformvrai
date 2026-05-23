@extends('layouts.soc')

@section('title', 'RansomShield — Hôtes découverts')
@section('page_title', 'Hôtes découverts')
@section('page_subtitle', 'Machines détectées sur les réseaux surveillés')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $roleIcon = function ($role) {
            return match ($role) {
                'soc_server'    => 'fa-server',
                'gateway'       => 'fa-network-wired',
                'file_server'   => 'fa-hard-drive',
                'attacker_demo' => 'fa-skull-crossbones',
                default         => 'fa-desktop',
            };
        };

        $roleColor = function ($role) {
            return match ($role) {
                'soc_server'    => '#6366f1',
                'gateway'       => '#22c55e',
                'file_server'   => '#f97316',
                'attacker_demo' => '#ef4444',
                default         => '#64748b',
            };
        };

        $statusColor = function ($host) {
            if (!$host->is_monitored) return '#6b7280';
            return match ($host->discovery_status) {
                'approved' => '#22c55e',
                'detected' => '#6366f1',
                default    => '#f97316',
            };
        };

        $enrollColor = function ($status) {
            return match ($status) {
                'enrolled'     => '#22c55e',
                'pre_enrolled' => '#6366f1',
                default        => '#64748b',
            };
        };
    @endphp

    <style>
        .hosts-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 10% 20%, color-mix(in srgb, #22c55e 10%, transparent), transparent 28%),
                radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 28%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .hosts-hero h2 {
            margin: 0;
            font-size: clamp(36px, 5vw, 64px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .hosts-hero p {
            margin-top: 12px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 860px;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 20px;
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: .15s ease;
            white-space: nowrap;
        }

        .filter-tab:hover {
            border-color: color-mix(in srgb, var(--accent) 35%, transparent);
            color: var(--text-main);
        }

        .filter-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: color-mix(in srgb, var(--accent) 60%, transparent);
        }

        /* Host cards */
        .host-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .host-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px 20px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left-width: 4px;
            box-shadow: var(--shadow-soft);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .host-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,.16);
        }

        .host-icon-col {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .host-body {
            flex: 1;
            min-width: 0;
        }

        .host-title {
            margin: 0 0 3px;
            font-size: 14px;
            font-weight: 850;
            letter-spacing: -.02em;
            font-family: monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .host-meta {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .host-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }

        .host-strip {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-shrink: 0;
        }

        .host-strip form { display: contents; }

        /* Role picker */
        .role-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border-soft);
        }

        .role-picker-label {
            width: 100%;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .role-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 8px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: .12s ease;
            white-space: nowrap;
        }

        .role-btn:hover {
            border-color: color-mix(in srgb, var(--accent) 40%, transparent);
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 8%, transparent);
        }

        .role-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
        }

        @media (max-width: 1050px) {
            .host-list { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .host-card { flex-direction: column; }
            .host-strip { flex-direction: row; flex-wrap: wrap; width: 100%; }
            .host-strip .action-btn { flex: 1 1 auto; justify-content: center; }
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="hosts-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Découverte machines — LAN
            </div>

            <h2>Identifier les hôtes du réseau.</h2>

            <p>Les hôtes détectés sont conservés en base. Tu peux retirer une machine de la surveillance
                sans supprimer son historique, puis la réactiver ou l'enrôler pour installer l'agent.</p>

            <div class="btn-row" style="margin-top:18px">
                <a href="{{ route('platform.networks.index') }}" class="action-btn primary">
                    <i class="fa-solid fa-network-wired"></i> Voir réseaux
                </a>
                <a href="{{ route('platform.agents.index') }}" class="action-btn">
                    <i class="fa-solid fa-microchip"></i> Voir agents
                </a>
                <a href="{{ route('platform.local-host.index') }}" class="action-btn">
                    <i class="fa-solid fa-server"></i> Machine SOC
                </a>
                @if($stats['retired'] > 0)
                    <form method="POST" action="{{ route('platform.discovered-hosts.purge-retired') }}"
                          onsubmit="return confirm(this.dataset.confirm)"
                          data-confirm="Supprimer définitivement {{ $stats['retired'] }} hôte(s) retiré(s) ? Cette action est irréversible.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn" style="border-color:#ef4444;color:#ef4444">
                            <i class="fa-solid fa-trash-can"></i>
                            Purger {{ $stats['retired'] }} fantôme(s)
                        </button>
                    </form>
                @endif
            </div>

            <div class="filter-tabs">
                @foreach(['monitored' => 'Surveillés', 'retired' => 'Retirés', 'all' => 'Tous'] as $key => $label)
                    <a class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}"
                       href="{{ route('platform.discovered-hosts.index', ['status' => $key]) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-desktop"></i></div>
                <div class="smart-stat-label">Total hôtes</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Machines enregistrées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-eye"></i></div>
                <div class="smart-stat-label">Surveillés</div>
                <div class="smart-stat-value" style="{{ $stats['monitored'] > 0 ? 'color:#22c55e' : '' }}">{{ $stats['monitored'] }}</div>
                <div class="smart-stat-hint">Actifs en surveillance.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-microchip"></i></div>
                <div class="smart-stat-label">Enrôlés</div>
                <div class="smart-stat-value" style="color:var(--accent)">{{ $stats['enrolled'] }}</div>
                <div class="smart-stat-hint">Agent installé et actif.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="smart-stat-label">Retirés</div>
                <div class="smart-stat-value" style="color:var(--text-muted)">{{ $stats['retired'] }}</div>
                <div class="smart-stat-hint">Hors surveillance.</div>
            </div>
        </section>

        {{-- Host list --}}
        @if($hosts->count())
            <div class="host-list section-gap">
                @foreach($hosts as $host)
                    @php
                        $ri  = $roleIcon($host->host_role);
                        $rc  = $roleColor($host->host_role);
                        $sc  = $statusColor($host);
                        $ec  = $enrollColor($host->enrollment_status ?? 'not_enrolled');
                        $isEnrolled = ($host->enrollment_status ?? 'not_enrolled') === 'enrolled';
                    @endphp

                    <article class="host-card" style="border-left-color:{{ $sc }}">
                        <div class="host-icon-col" style="background:color-mix(in srgb, {{ $rc }} 12%, transparent); color:{{ $rc }}">
                            <i class="fa-solid {{ $ri }}"></i>
                        </div>

                        <div class="host-body">
                            <h4 class="host-title">{{ $host->hostname ?: $host->ip_address }}</h4>
                            <div class="host-meta">
                                <i class="fa-solid fa-network-wired" style="margin-right:4px"></i>{{ $host->managedNetwork?->cidr ?? '—' }}
                                &nbsp;·&nbsp;
                                <i class="fa-solid fa-ethernet" style="margin-right:4px"></i><span style="font-family:monospace">{{ $host->ip_address }}</span>
                                @if($host->mac_address)
                                    &nbsp;·&nbsp;<span style="font-family:monospace">{{ $host->mac_address }}</span>
                                @endif
                                @if($host->last_seen_at)
                                    <br><i class="fa-regular fa-clock" style="margin-right:4px"></i>Vu {{ $host->last_seen_at->diffForHumans() }}
                                @endif
                            </div>
                            <div class="host-tags">
                                <span class="badge" style="color:{{ $sc }}; border-color:color-mix(in srgb, {{ $sc }} 30%, transparent)">
                                    {{ $host->is_monitored ? ($host->discovery_status ?? 'detected') : 'retiré' }}
                                </span>
                                <span class="badge">{{ $host->host_role ?? 'client' }}</span>
                                <span class="badge" style="color:{{ $ec }}; border-color:color-mix(in srgb, {{ $ec }} 30%, transparent)">
                                    {{ $host->enrollment_status ?? 'not_enrolled' }}
                                </span>
                                @if($host->agent)
                                    <span class="badge" style="color:var(--accent)">
                                        <i class="fa-solid fa-microchip" style="margin-right:4px"></i>{{ $host->agent->agent_name }}
                                    </span>
                                @endif
                            </div>

                            {{-- Qualification du rôle --}}
                            @if($host->is_monitored)
                                <div class="role-picker">
                                    <div class="role-picker-label"><i class="fa-solid fa-tag" style="margin-right:4px"></i>Rôle machine</div>
                                    @foreach([
                                        'client'        => ['fa-desktop',           'Client',          route('platform.discovered-hosts.mark-client',       $host)],
                                        'file_server'   => ['fa-hard-drive',        'Serveur fichiers', route('platform.discovered-hosts.mark-file-server',  $host)],
                                        'soc_server'    => ['fa-server',            'Serveur SOC',     route('platform.discovered-hosts.mark-soc-server',    $host)],
                                        'attacker_demo' => ['fa-skull-crossbones',  'Attaquant démo',  route('platform.discovered-hosts.mark-attacker-demo', $host)],
                                    ] as $roleKey => [$icon, $label, $action])
                                        <form method="POST" action="{{ $action }}" style="display:contents">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="role-btn {{ ($host->host_role ?? 'client') === $roleKey ? 'active' : '' }}"
                                                    title="Marquer comme {{ $label }}">
                                                <i class="fa-solid {{ $icon }}"></i> {{ $label }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="host-strip">
                            @if($host->is_monitored)
                                @if(!$isEnrolled)
                                    <form method="POST" action="{{ route('platform.discovered-hosts.enroll', $host) }}">
                                        @csrf
                                        <button class="action-btn primary" type="submit" style="width:100%">
                                            <i class="fa-solid fa-plug"></i> Enrôler
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('platform.discovered-hosts.retire', $host) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn danger" type="submit" style="width:100%">
                                        <i class="fa-solid fa-ban"></i> Retirer
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('platform.discovered-hosts.restore', $host) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn success" type="submit" style="width:100%">
                                        <i class="fa-solid fa-rotate-left"></i> Réactiver
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">
                {{ $hosts->links() }}
            </div>
        @else
            @include('platform.partials.empty-state', [
                'title'   => 'Aucun hôte pour ce filtre.',
                'message' => 'Lance un scan réseau depuis la page Réseaux ou change le filtre.'
            ])
        @endif

    </div>
@endsection
