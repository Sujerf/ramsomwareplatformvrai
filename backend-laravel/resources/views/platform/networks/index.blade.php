@extends('layouts.soc')

@section('title', 'RansomShield — Réseaux surveillés')
@section('page_title', 'Réseaux surveillés')
@section('page_subtitle', 'Détection, scan et gestion des réseaux sous surveillance')

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

        $statusClass = function ($network) {
            if (!$network->is_monitored) {
                return 'badge-suspect';
            }

            return match ($network->status) {
                'approved' => 'badge-normal',
                'detected' => 'badge-high',
                'retired' => 'badge-suspect',
                default => 'badge',
            };
        };
    @endphp

    <style>
        .infra-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 15% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
                radial-gradient(circle at 86% 10%, color-mix(in srgb, #22c55e 12%, transparent), transparent 32%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .infra-hero h2 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .infra-hero p {
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

        .network-card-grid {
            display: none;
            gap: 14px;
        }

        .network-card {
            padding: 16px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
        }

        .network-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .network-title {
            margin: 0;
            font-weight: 950;
            letter-spacing: -.03em;
            font-size: 16px;
        }

        .network-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        .network-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 14px;
        }

        .network-detail {
            padding: 11px;
            border-radius: 16px;
            background: color-mix(in srgb, var(--bg-panel-soft) 65%, transparent);
            border: 1px solid var(--border-soft);
        }

        .network-detail small {
            display: block;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .network-detail strong {
            display: block;
            margin-top: 5px;
            font-size: 13px;
            word-break: break-all;
        }

        .network-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .network-actions form {
            flex: 1 1 auto;
        }

        .network-actions .action-btn {
            width: 100%;
        }

        @media (max-width: 900px) {
            .desktop-table-prefer {
                display: none !important;
            }

            .network-card-grid {
                display: grid;
            }

            .network-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="infra-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Infrastructure réseau
            </div>

            <h2>Contrôler les réseaux à surveiller.</h2>

            <p>
                Les réseaux détectés peuvent être approuvés, scannés, retirés de la surveillance ou réactivés.
                Un réseau retiré reste dans l’historique mais n’est plus scanné.
            </p>

            <div class="btn-row">
                <form method="POST" action="{{ route('platform.networks.detect') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Détecter réseaux locaux</button>
                </form>

                <a href="{{ route('platform.discovered-hosts.index') }}" class="btn btn-soft">Voir les hôtes</a>
            </div>

            <div class="filter-row">
                @foreach($filters as $key => $label)
                    <a href="{{ route('platform.networks.index', ['status' => $key]) }}"
                       class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-label">Réseaux affichés</div>
                <div class="smart-stat-value">{{ $networks->total() }}</div>
                <div class="smart-stat-hint">Selon le filtre actuel.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Filtre</div>
                <div class="smart-stat-value" style="font-size:28px;">{{ $filters[$activeStatus] ?? 'Tous' }}</div>
                <div class="smart-stat-hint">État de surveillance.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Scan</div>
                <div class="smart-stat-value" style="font-size:28px;">Safe</div>
                <div class="smart-stat-hint">Scan léger, non agressif.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Historique</div>
                <div class="smart-stat-value" style="font-size:28px;">ON</div>
                <div class="smart-stat-hint">Les retraits sont conservés.</div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Liste des réseaux</h3>
                    <p class="soc-card-subtitle">Réseaux détectés, surveillés ou retirés.</p>
                </div>
            </div>

            @if($networks->count())
                <div class="table-wrap desktop-table-prefer">
                    <table class="soc-table">
                        <thead>
                        <tr>
                            <th>Réseau</th>
                            <th>CIDR</th>
                            <th>Passerelle</th>
                            <th>Interface</th>
                            <th>Hôtes</th>
                            <th>Statut</th>
                            <th>Dernier scan</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($networks as $network)
                            <tr>
                                <td>
                                    <strong>{{ $network->name }}</strong>
                                    <div class="network-meta">
                                        {{ $network->retired_reason ?: 'Réseau disponible pour surveillance.' }}
                                    </div>
                                </td>
                                <td class="mono">{{ $network->cidr }}</td>
                                <td class="mono">{{ $network->gateway_ip ?? '—' }}</td>
                                <td>{{ $network->interface_name ?? '—' }}</td>
                                <td>{{ $network->discovered_hosts_count ?? 0 }}</td>
                                <td>
                                    <span class="badge {{ $statusClass($network) }}">
                                        {{ $network->is_monitored ? $network->status : 'retired' }}
                                    </span>
                                </td>
                                <td>{{ $network->last_scanned_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
                                <td>
                                    <div class="inline-actions">
                                        @if($network->is_monitored)
                                            <form method="POST" action="{{ route('platform.networks.scan', $network) }}">
                                                @csrf
                                                <button class="action-btn primary" type="submit">Scanner</button>
                                            </form>

                                            <form method="POST" action="{{ route('platform.networks.retire', $network) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn danger" type="submit">Retirer</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('platform.networks.restore', $network) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="action-btn success" type="submit">Réactiver</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="network-card-grid">
                    @foreach($networks as $network)
                        <article class="network-card">
                            <div class="network-card-head">
                                <div>
                                    <h3 class="network-title">{{ $network->name }}</h3>
                                    <div class="network-meta">{{ $network->retired_reason ?: 'Réseau disponible pour surveillance.' }}</div>
                                </div>

                                <span class="badge {{ $statusClass($network) }}">
                                    {{ $network->is_monitored ? $network->status : 'retired' }}
                                </span>
                            </div>

                            <div class="network-detail-grid">
                                <div class="network-detail">
                                    <small>CIDR</small>
                                    <strong class="mono">{{ $network->cidr }}</strong>
                                </div>
                                <div class="network-detail">
                                    <small>Passerelle</small>
                                    <strong class="mono">{{ $network->gateway_ip ?? '—' }}</strong>
                                </div>
                                <div class="network-detail">
                                    <small>Interface</small>
                                    <strong>{{ $network->interface_name ?? '—' }}</strong>
                                </div>
                                <div class="network-detail">
                                    <small>Hôtes</small>
                                    <strong>{{ $network->discovered_hosts_count ?? 0 }}</strong>
                                </div>
                            </div>

                            <div class="network-actions">
                                @if($network->is_monitored)
                                    <form method="POST" action="{{ route('platform.networks.scan', $network) }}">
                                        @csrf
                                        <button class="action-btn primary" type="submit">Scanner</button>
                                    </form>

                                    <form method="POST" action="{{ route('platform.networks.retire', $network) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="action-btn danger" type="submit">Retirer</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('platform.networks.restore', $network) }}">
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
                    {{ $networks->links() }}
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun réseau pour ce filtre.',
                    'message' => 'Lance une détection réseau ou change le filtre.'
                ])
            @endif
        </section>
    </div>
@endsection
