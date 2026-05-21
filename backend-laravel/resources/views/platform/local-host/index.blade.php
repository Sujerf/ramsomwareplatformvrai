@extends('layouts.soc')

@section('title', 'RansomShield — Machine hôte locale')
@section('page_title', 'Machine hôte locale')
@section('page_subtitle', 'Analyse automatique du serveur SOC et de ses interfaces réseau')

@section('content')
    @include('platform.partials.page-tools-style')

    @php
        $interfaces = collect($localHost['interfaces'] ?? []);
        $routes = collect($localHost['routes'] ?? []);
        $activeInterfaces = $interfaces->where('is_active', true);
        $inactiveInterfaces = $interfaces->where('is_active', false);
        $networks = $interfaces->flatMap(fn ($interface) => collect($interface['ipv4_addresses'] ?? [])->pluck('cidr'))->filter()->unique()->values();
        $gateway = $routes->firstWhere('destination', 'default')['gateway'] ?? null;
    @endphp

    <section class="page-toolbar">
        <div>
            <h2>Analyse de la machine SOC</h2>
            <p>
                Cette page identifie automatiquement la machine qui héberge Laravel et prépare la découverte réseau.
            </p>
        </div>

        <div class="inline-actions">
            <form method="POST" action="{{ route('platform.local-host.detect') }}">
                @csrf
                <button class="action-btn primary" type="submit">Actualiser la détection SOC</button>
            </form>

            <a class="action-btn" href="{{ route('platform.networks.index') }}">Voir les réseaux</a>
        </div>
    </section>

    <section class="grid grid-4">
        @include('platform.partials.stat-card', [
            'label' => 'Hostname SOC',
            'value' => $hostname ?? '—',
            'hint' => 'Nom de la machine locale.'
        ])

        @include('platform.partials.stat-card', [
            'label' => 'IP principale',
            'value' => $serverIp ?? '—',
            'hint' => 'Adresse utilisée par l’interface active.'
        ])

        @include('platform.partials.stat-card', [
            'label' => 'Interface active',
            'value' => $localHost['primary_interface'] ?? '—',
            'hint' => 'Interface réseau prioritaire.'
        ])

        @include('platform.partials.stat-card', [
            'label' => 'Réseaux détectés',
            'value' => $networks->count(),
            'hint' => 'CIDR calculés depuis les interfaces.'
        ])
    </section>

    <section class="grid grid-2 section-gap">
        <div class="soc-card">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Résumé réseau SOC</h3>
                    <p class="soc-card-subtitle">Analyse automatique locale</p>
                </div>
            </div>

            <div class="detail-list">
                <div class="detail-row">
                    <div class="detail-label">Système</div>
                    <div class="detail-value">{{ $phpOs ?? '—' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">MAC principale</div>
                    <div class="detail-value mono">{{ $localHost['primary_mac'] ?? '—' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Passerelle</div>
                    <div class="detail-value mono">{{ $gateway ?? '—' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Réseaux</div>
                    <div class="detail-value mono">
                        @forelse($networks as $network)
                            <div>{{ $network }}</div>
                        @empty
                            —
                        @endforelse
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Détection</div>
                    <div class="detail-value">{{ $localHost['detected_at'] ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="soc-card">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Recommandation RansomShield</h3>
                    <p class="soc-card-subtitle">Lecture automatique de l’état local</p>
                </div>
            </div>

            @if($activeInterfaces->count() && $networks->count())
                <div class="empty-state">
                    <strong>Machine SOC prête pour la découverte réseau.</strong>
                    <br>
                    L’interface <span class="mono">{{ $localHost['primary_interface'] ?? '—' }}</span>
                    est active sur le réseau
                    <span class="mono">{{ $networks->first() }}</span>.
                    Tu peux maintenant détecter automatiquement les réseaux puis scanner les hôtes.
                </div>
            @else
                <div class="empty-state">
                    <strong>Réseau non exploitable automatiquement.</strong>
                    <br>
                    Aucune interface active avec adresse IPv4 n’a été détectée. Vérifie le Wi-Fi, Ethernet ou VPN.
                </div>
            @endif
        </div>
    </section>

    <section class="soc-card section-gap">
        <div class="soc-card-header">
            <div>
                <h3 class="soc-card-title">Interfaces réseau détectées</h3>
                <p class="soc-card-subtitle">État des cartes réseau de la machine SOC</p>
            </div>
        </div>

        @if($interfaces->count())
            <div class="table-wrap">
                <table class="soc-table">
                    <thead>
                    <tr>
                        <th>Interface</th>
                        <th>État</th>
                        <th>MAC</th>
                        <th>IP / CIDR</th>
                        <th>Analyse</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($interfaces as $interface)
                        @php
                            $ips = collect($interface['ipv4_addresses'] ?? []);
                        @endphp
                        <tr>
                            <td class="mono">{{ $interface['name'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ ($interface['is_active'] ?? false) ? 'badge-normal' : '' }}">
                                    {{ $interface['state'] ?? 'UNKNOWN' }}
                                </span>
                            </td>
                            <td class="mono">{{ $interface['mac_address'] ?? '—' }}</td>
                            <td class="mono">
                                @forelse($ips as $ip)
                                    <div>{{ $ip['ip'] ?? '—' }} / {{ $ip['cidr'] ?? '—' }}</div>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td>
                                @if($interface['is_active'] ?? false)
                                    Interface utilisable pour la surveillance réseau.
                                @else
                                    Interface inactive actuellement.
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @include('platform.partials.empty-state', [
                'title' => 'Aucune interface réseau détectée.',
                'message' => 'La commande système ip -j addr n’a pas retourné d’interface exploitable.'
            ])
        @endif
    </section>

    <section class="soc-card section-gap">
        <div class="soc-card-header">
            <div>
                <h3 class="soc-card-title">Routes réseau</h3>
                <p class="soc-card-subtitle">Passerelle et routes locales détectées</p>
            </div>
        </div>

        @if($routes->count())
            <div class="table-wrap">
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
        @else
            @include('platform.partials.empty-state', [
                'title' => 'Aucune route détectée.',
                'message' => 'La commande ip -j route n’a pas retourné de route exploitable.'
            ])
        @endif
    </section>
@endsection
