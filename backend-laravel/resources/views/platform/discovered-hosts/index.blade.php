@extends('layouts.soc')

@section('title', 'RansomShield — Hôtes découverts')
@section('page_title', 'Hôtes découverts')
@section('page_subtitle', 'Machines détectées sur les réseaux surveillés')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $activeStatus = $activeStatus ?? request('status', 'monitored');

        $filters = [
            'monitored' => 'Surveillés',
            'retired' => 'Retirés',
            'all' => 'Tous',
        ];

        $roleIcon = function ($role) {
            return match ($role) {
                'soc_server' => '🛡️',
                'gateway' => '🌐',
                'server' => '🖥️',
                default => '💻',
            };
        };

        $statusClass = function ($host) {
            if (!$host->is_monitored) {
                return 'badge-suspect';
            }

            return match ($host->discovery_status) {
                'detected' => 'badge-normal',
                'approved' => 'badge-normal',
                'retired' => 'badge-suspect',
                default => 'badge-high',
            };
        };
    @endphp

    <style>
        .host-hero {
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .host-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .host-hero p {
            margin-top: 14px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 860px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .host-grid-premium {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .host-card {
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

        .host-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-size: 25px;
        }

        .host-title {
            margin: 0;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .host-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .host-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
        }

        .host-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .host-actions form {
            display: inline-flex;
        }

        @media (max-width: 1100px) {
            .host-grid-premium {
                grid-template-columns: 1fr;
            }

            .host-card {
                grid-template-columns: 52px 1fr;
            }

            .host-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: 1fr;
            }

            .host-actions form,
            .host-actions .action-btn,
            .host-actions button {
                width: 100%;
            }
        }
    </style>

    <div class="animated-page">
        <section class="host-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Découverte machines
            </div>

            <h2>Identifier les hôtes du réseau.</h2>

            <p>
                Les hôtes détectés sont conservés en base. Tu peux retirer une machine de la surveillance
                sans supprimer son historique, puis la réactiver plus tard.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.networks.index') }}" class="btn btn-primary">Voir réseaux</a>
                <a href="{{ route('platform.agents.index') }}" class="btn btn-soft">Voir agents</a>
            </div>

            <div class="filter-row">
                @foreach($filters as $key => $label)
                    <a href="{{ route('platform.discovered-hosts.index', ['status' => $key]) }}"
                       class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Hôtes affichés</div>
                <div class="smart-stat-value">{{ $hosts->total() }}</div>
                <div class="smart-stat-hint">Selon le filtre actuel.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Filtre</div>
                <div class="smart-stat-value" style="font-size:28px;">{{ $filters[$activeStatus] ?? 'Tous' }}</div>
                <div class="smart-stat-hint">État de surveillance.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Historique</div>
                <div class="smart-stat-value" style="font-size:28px;">ON</div>
                <div class="smart-stat-hint">Les hôtes retirés restent visibles.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Source</div>
                <div class="smart-stat-value" style="font-size:28px;">LAN</div>
                <div class="smart-stat-hint">Découverte réseau locale.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Machines détectées</h3>
                    <p class="soc-card-subtitle">Hôtes surveillés, retirés ou historiques.</p>
                </div>
            </div>

            @if($hosts->count())
                <div class="host-grid-premium">
                    @foreach($hosts as $host)
                        <article class="host-card">
                            <div class="host-icon">{{ $roleIcon($host->host_role) }}</div>

                            <div>
                                <h3 class="host-title">{{ $host->hostname ?: $host->ip_address }}</h3>

                                <div class="host-meta">
                                    Réseau : {{ $host->managedNetwork?->cidr ?? '—' }}
                                    <br>
                                    IP : <span class="mono">{{ $host->ip_address }}</span>
                                    —
                                    MAC : <span class="mono">{{ $host->mac_address ?? '—' }}</span>
                                    <br>
                                    {{ $host->retired_reason ?: 'Machine disponible pour surveillance.' }}
                                </div>

                                <div class="host-badges">
                                    <span class="badge {{ $statusClass($host) }}">
                                        {{ $host->is_monitored ? $host->discovery_status : 'retired' }}
                                    </span>
                                    <span class="badge">Rôle : {{ $host->host_role ?? 'client' }}</span>
                                    <span class="badge">Enrôlement : {{ $host->enrollment_status ?? 'not_enrolled' }}</span>
                                    <span class="badge">
                                        Vu : {{ $host->last_seen_at?->format('d/m H:i') ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            <div class="host-actions">
                                @if($host->is_monitored)
                                    @if(($host->enrollment_status ?? 'not_enrolled') !== 'enrolled')
                                        <form method="POST" action="{{ route('platform.discovered-hosts.enroll', $host) }}">
                                            @csrf
                                            <button class="action-btn primary" type="submit">Enrôler</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('platform.discovered-hosts.retire', $host) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn danger" type="submit">Retirer</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.discovered-hosts.restore', $host) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn success" type="submit">Réactiver</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrap">
                    {{ $hosts->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun hôte pour ce filtre.',
                    'message' => 'Lance un scan réseau ou change le filtre.'
                ])
            @endif
        </section>
    </div>
@endsection
