@extends('layouts.soc')

@section('title', 'RansomShield — Machine SOC locale')
@section('page_title', 'Machine SOC locale')
@section('page_subtitle', 'Analyse automatique du serveur hébergeant la console RansomShield')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $interfaces      = collect($localHost['interfaces'] ?? []);
        $routes          = collect($localHost['routes'] ?? []);
        $activeIfaces    = $interfaces->where('is_active', true);
        $networks        = $interfaces->flatMap(fn ($i) => collect($i['ipv4_addresses'] ?? [])->pluck('cidr'))->filter()->unique()->values();
        $gateway         = $routes->firstWhere('destination', 'default')['gateway'] ?? null;
        $isReady         = $activeIfaces->count() > 0 && $networks->count() > 0;
    @endphp

    <style>
        .soc-machine-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            border-left: 4px solid #22c55e;
            background:
                radial-gradient(circle at 8% 20%, color-mix(in srgb, #22c55e 10%, transparent), transparent 28%),
                radial-gradient(circle at 90% 5%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 25%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .soc-machine-hero h2 {
            margin: 0;
            font-size: clamp(30px, 4vw, 56px);
            line-height: 1;
            letter-spacing: -.06em;
            font-weight: 950;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .soc-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: color-mix(in srgb, #22c55e 14%, transparent);
            color: #22c55e;
            flex-shrink: 0;
        }

        .soc-machine-hero p {
            color: var(--text-muted);
            max-width: 780px;
            line-height: 1.75;
            margin-top: 12px;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        /* Status bar */
        .soc-status-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border-soft);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-soft);
        }

        .status-block {
            padding: 16px 20px;
            background: var(--bg-card);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .status-block-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .status-block-value {
            font-size: 14px;
            font-weight: 850;
            letter-spacing: -.02em;
            font-family: monospace;
            word-break: break-all;
        }

        @media (max-width: 700px) {
            .soc-status-bar { grid-template-columns: repeat(2, 1fr); }
        }

        /* Interface cards */
        .iface-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .iface-card {
            padding: 16px 18px;
            border-radius: 18px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left: 3px solid var(--iface-color, #6366f1);
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .iface-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--iface-color, #6366f1);
            flex-shrink: 0;
            margin-top: 5px;
        }

        .iface-dot.pulse {
            animation: pulseDot 2s ease-in-out infinite;
        }

        .iface-body { flex: 1; min-width: 0; }

        .iface-name {
            font-size: 14px;
            font-weight: 850;
            font-family: monospace;
            margin-bottom: 4px;
        }

        .iface-meta {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.55;
            font-family: monospace;
        }

        .iface-ips {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        @media (max-width: 900px) {
            .iface-grid { grid-template-columns: 1fr; }
        }

        /* Recommendation box */
        .rec-box {
            padding: 18px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.7;
        }

        .rec-box.ready {
            background: color-mix(in srgb, #22c55e 8%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 20%, transparent);
        }

        .rec-box.not-ready {
            background: color-mix(in srgb, #ef4444 8%, transparent);
            border: 1px solid color-mix(in srgb, #ef4444 20%, transparent);
        }

        .rec-title {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="soc-machine-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Serveur SOC local
            </div>

            <h2>
                <span class="soc-hero-icon"><i class="fa-solid fa-server"></i></span>
                {{ $hostname ?? 'Machine SOC' }}
            </h2>

            <p>Cette page identifie automatiquement la machine hébergeant Laravel et prépare la découverte réseau.
                L'IP principale et les interfaces sont lues directement depuis le système d'exploitation.</p>

            <div class="btn-row">
                @if(auth()->user()->isAdmin())
                    @if($isReady)
                        <form method="POST" action="{{ route('platform.local-host.push-to-networks') }}" style="display:contents"
                              data-loading="Détection des réseaux et hôtes en cours…"
                              data-loading-hint="Scan ARP + fping sur toutes les interfaces actives. Cette opération peut prendre 10 à 20 secondes.">
                            @csrf
                            <button class="action-btn lg primary" type="submit">
                                <i class="fa-solid fa-satellite-dish"></i> Détecter réseaux et hôtes
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('platform.local-host.detect') }}" style="display:contents"
                          data-loading="Analyse de la machine SOC en cours…"
                          data-loading-hint="Lecture des interfaces réseau, routes et adresses IP. Quelques secondes…">
                        @csrf
                        <button class="action-btn lg {{ $isReady ? '' : 'primary' }}" type="submit">
                            <i class="fa-solid fa-rotate"></i> Actualiser
                        </button>
                    </form>
                @endif
                <a href="{{ route('platform.networks.index') }}" class="action-btn">
                    <i class="fa-solid fa-network-wired"></i> Voir les réseaux
                </a>
                <a href="{{ route('platform.discovered-hosts.index') }}" class="action-btn">
                    <i class="fa-solid fa-desktop"></i> Hôtes découverts
                </a>
            </div>
        </section>

        {{-- Status bar --}}
        <div class="soc-status-bar section-gap">
            <div class="status-block">
                <div class="status-block-label"><i class="fa-solid fa-display" style="margin-right:5px"></i>Hostname</div>
                <div class="status-block-value">{{ $hostname ?? '—' }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label"><i class="fa-solid fa-ethernet" style="margin-right:5px"></i>IP principale</div>
                <div class="status-block-value" style="color:var(--accent)">{{ $serverIp ?? '—' }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label"><i class="fa-solid fa-plug" style="margin-right:5px"></i>Interface active</div>
                <div class="status-block-value">{{ $localHost['primary_interface'] ?? '—' }}</div>
            </div>
            <div class="status-block">
                <div class="status-block-label"><i class="fa-solid fa-route" style="margin-right:5px"></i>Passerelle</div>
                <div class="status-block-value">{{ $gateway ?? '—' }}</div>
            </div>
        </div>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-plug"></i></div>
                <div class="smart-stat-label">Interfaces actives</div>
                <div class="smart-stat-value" style="{{ $activeIfaces->count() > 0 ? 'color:#22c55e' : '' }}">{{ $activeIfaces->count() }}</div>
                <div class="smart-stat-hint">Cartes réseau disponibles.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-network-wired"></i></div>
                <div class="smart-stat-label">Réseaux CIDR</div>
                <div class="smart-stat-value">{{ $networks->count() }}</div>
                <div class="smart-stat-hint">Calculés depuis les interfaces.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-route"></i></div>
                <div class="smart-stat-label">Routes</div>
                <div class="smart-stat-value">{{ $routes->count() }}</div>
                <div class="smart-stat-hint">Routes réseau détectées.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="smart-stat-label">Prêt pour scan</div>
                <div class="smart-stat-value" style="color:{{ $isReady ? '#22c55e' : '#ef4444' }}">
                    {{ $isReady ? 'Oui' : 'Non' }}
                </div>
                <div class="smart-stat-hint">Interface active + CIDR disponible.</div>
            </div>
        </section>

        {{-- Recommendation + OS info --}}
        <div class="grid grid-2 section-gap">
            <div class="soc-card">
                <h3 class="soc-card-title">Recommandation SOC</h3>
                <p class="soc-card-subtitle">Lecture automatique de l'état local.</p>

                <div class="rec-box {{ $isReady ? 'ready' : 'not-ready' }}" style="margin-top:14px">
                    @if($isReady)
                        <div class="rec-title" style="color:#22c55e">
                            <i class="fa-solid fa-circle-check" style="margin-right:6px"></i>Machine SOC prête pour la découverte réseau.
                        </div>
                        L'interface <code>{{ $localHost['primary_interface'] ?? '—' }}</code> est active sur
                        <code>{{ $networks->first() }}</code>. Clique sur le bouton ci-dessous pour détecter et surveiller les réseaux et hôtes en une seule action.

                        @if(auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('platform.local-host.push-to-networks') }}" style="margin-top:14px"
                              data-loading="Détection des réseaux et hôtes en cours…"
                              data-loading-hint="Scan ARP + fping sur toutes les interfaces actives. Cette opération peut prendre 10 à 20 secondes.">
                            @csrf
                            <button type="submit" style="display:inline-flex; align-items:center; gap:7px; padding:8px 18px; border-radius:10px; border:none; background:#22c55e; color:#fff; font-size:13px; font-weight:800; cursor:pointer; transition:.15s ease;">
                                <i class="fa-solid fa-satellite-dish"></i> Détecter réseaux et hôtes maintenant
                            </button>
                        </form>
                        @endif
                    @else
                        <div class="rec-title" style="color:#ef4444">
                            <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px"></i>Réseau non exploitable automatiquement.
                        </div>
                        Aucune interface active avec adresse IPv4 n'a été détectée. Vérifie le Wi-Fi, Ethernet ou VPN.
                    @endif
                </div>
            </div>

            <div class="soc-card">
                <h3 class="soc-card-title">Identité système</h3>
                <p class="soc-card-subtitle">Informations collectées depuis PHP et le système.</p>

                <div style="display:flex; flex-direction:column; gap:10px; margin-top:14px">
                    @foreach([
                        ['label' => 'Système OS', 'icon' => 'fa-microchip', 'value' => $phpOs ?? '—'],
                        ['label' => 'MAC principale', 'icon' => 'fa-ethernet', 'value' => $localHost['primary_mac'] ?? '—'],
                        ['label' => 'Détection', 'icon' => 'fa-clock', 'value' => $localHost['detected_at'] ?? '—'],
                    ] as $row)
                        <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:12px; background:color-mix(in srgb, var(--bg-panel-soft) 60%, transparent); border:1px solid var(--border-soft)">
                            <div style="width:32px; height:32px; border-radius:9px; background:color-mix(in srgb, var(--accent) 10%, transparent); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0">
                                <i class="fa-solid {{ $row['icon'] }}"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted)">{{ $row['label'] }}</div>
                                <div style="font-size:13px; font-weight:750; font-family:monospace; margin-top:2px">{{ $row['value'] }}</div>
                            </div>
                        </div>
                    @endforeach

                    @if($networks->count())
                        <div style="padding:10px 12px; border-radius:12px; background:color-mix(in srgb, #22c55e 6%, transparent); border:1px solid color-mix(in srgb, #22c55e 20%, transparent)">
                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-bottom:6px">Réseaux CIDR</div>
                            @foreach($networks as $cidr)
                                <div style="font-size:13px; font-family:monospace; color:#22c55e">{{ $cidr }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Interfaces --}}
        <div class="soc-card section-gap">
            <h3 class="soc-card-title">Interfaces réseau détectées</h3>
            <p class="soc-card-subtitle">État des cartes réseau de la machine SOC.</p>

            @if($interfaces->count())
                <div class="iface-grid" style="margin-top:16px">
                    @foreach($interfaces as $iface)
                        @php
                            $ips = collect($iface['ipv4_addresses'] ?? []);
                            $active = $iface['is_active'] ?? false;
                            $ifaceColor = $active ? '#22c55e' : '#6b7280';
                        @endphp
                        <div class="iface-card" style="--iface-color:{{ $ifaceColor }}">
                            <div class="iface-dot {{ $active ? 'pulse' : '' }}"></div>
                            <div class="iface-body">
                                <div class="iface-name">{{ $iface['name'] ?? '—' }}</div>
                                <div class="iface-meta">
                                    État : {{ $iface['state'] ?? 'UNKNOWN' }}
                                    @if($iface['mac_address'] ?? null)
                                        &nbsp;·&nbsp; MAC : {{ $iface['mac_address'] }}
                                    @endif
                                </div>
                                @if($ips->count())
                                    <div class="iface-ips">
                                        @foreach($ips as $ip)
                                            <span class="badge" style="font-family:monospace; color:#22c55e; border-color:rgba(34,197,94,.25)">
                                                {{ $ip['ip'] ?? '—' }} / {{ $ip['cidr'] ?? '—' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="margin-top:6px; font-size:12px; color:var(--text-muted)">Aucune adresse IPv4</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucune interface réseau détectée.',
                    'message' => "La commande système ip -j addr n'a pas retourné d'interface exploitable."
                ])
            @endif
        </div>

        {{-- Routes --}}
        @if($routes->count())
            <div class="soc-card section-gap">
                <h3 class="soc-card-title">Routes réseau</h3>
                <p class="soc-card-subtitle">Passerelle et routes locales détectées.</p>

                <div class="table-wrap" style="margin-top:14px">
                    <table class="soc-table">
                        <thead>
                        <tr>
                            <th>Destination</th>
                            <th>Passerelle</th>
                            <th>Interface</th>
                            <th>Source préférée</th>
                            <th>Protocole</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($routes as $route)
                            <tr>
                                <td class="mono">{{ $route['destination'] ?? 'default' }}</td>
                                <td class="mono">{{ $route['gateway'] ?? '—' }}</td>
                                <td class="mono">{{ $route['interface'] ?? '—' }}</td>
                                <td class="mono">{{ $route['preferred_source'] ?? '—' }}</td>
                                <td>{{ $route['protocol'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection
