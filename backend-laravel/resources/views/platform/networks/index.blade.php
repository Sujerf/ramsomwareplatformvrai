@extends('layouts.soc')

@section('title', 'RansomShield — Réseaux surveillés')
@section('page_title', 'Réseaux surveillés')
@section('page_subtitle', 'Détection, scan et gestion des réseaux sous surveillance')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $statusColor = function ($network) {
            if (!$network->is_monitored) return '#6b7280';
            return match ($network->status) {
                'approved' => '#22c55e',
                'detected' => '#f97316',
                default    => '#6366f1',
            };
        };

        $statusLabel = function ($network) {
            if (!$network->is_monitored) return 'Retiré';
            return match ($network->status) {
                'approved' => 'Approuvé',
                'detected' => 'Détecté',
                'retired'  => 'Retiré',
                default    => $network->status,
            };
        };
    @endphp

    <style>
        .net-hero {
            position: relative;
            overflow: hidden;
            padding: 32px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 18%, color-mix(in srgb, #22c55e 12%, transparent), transparent 28%),
                radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 28%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .net-hero h2 {
            margin: 0;
            font-size: clamp(36px, 5vw, 64px);
            line-height: .95;
            letter-spacing: -.08em;
            font-weight: 950;
        }

        .net-hero p {
            margin-top: 12px;
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 860px;
        }

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

        .filter-tab:hover { border-color: color-mix(in srgb, var(--accent) 35%, transparent); color: var(--text-main); }
        .filter-tab.active { background: var(--accent); color: #fff; border-color: color-mix(in srgb, var(--accent) 60%, transparent); }

        /* Network cards */
        .net-list { display: flex; flex-direction: column; gap: 10px; }

        .net-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-left-width: 4px;
            box-shadow: var(--shadow-soft);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .net-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,.18); }

        .net-icon-col {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .net-body { flex: 1; min-width: 0; }

        .net-title { margin: 0 0 4px; font-size: 15px; font-weight: 850; letter-spacing: -.02em; }

        .net-ctx {
            display: grid;
            grid-template-columns: repeat(4, auto);
            gap: 8px 20px;
            margin: 8px 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .net-ctx-item strong { display: block; font-size: 13px; font-weight: 750; color: var(--text-main); font-family: monospace; }
        .net-ctx-item small { font-size: 10px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }

        .net-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; flex-shrink: 0; }
        .net-strip form { display: contents; }

        /* ── Scan result inline ───────────────────────────────────────────────── */
        .scan-result {
            display: none;
            margin-top: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            background: color-mix(in srgb, #22c55e 8%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 25%, transparent);
            font-size: 12px;
            color: var(--text-muted);
            animation: fadeIn .2s ease;
        }

        .scan-result.error {
            background: color-mix(in srgb, #ef4444 8%, transparent);
            border-color: color-mix(in srgb, #ef4444 25%, transparent);
        }

        .scan-result-title {
            font-size: 13px; font-weight: 850; color: #22c55e; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
        }

        .scan-result.error .scan-result-title { color: #ef4444; }

        .scan-result-ips {
            display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px;
        }

        .scan-ip-tag {
            padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;
            background: color-mix(in srgb, #22c55e 14%, transparent);
            border: 1px solid color-mix(in srgb, #22c55e 22%, transparent);
            color: #22c55e; font-family: monospace;
        }

        /* Spinner inline sur le bouton */
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-spinner { animation: spin .7s linear infinite; display: inline-block; }

        /* Detect global banner */
        #detect-banner {
            display: none;
            padding: 14px 20px;
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            font-size: 13px; font-weight: 750; color: var(--accent);
            align-items: center; gap: 10px;
            animation: fadeIn .2s ease;
        }

        @keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        /* Add form */
        .net-add-form { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; }
        .net-field label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin-bottom: 6px; }

        @media (max-width: 900px) {
            .net-card { flex-direction: column; }
            .net-strip { width: 100%; }
            .net-strip .action-btn { flex: 1 1 auto; justify-content: center; }
            .net-ctx { grid-template-columns: repeat(2, 1fr); }
            .net-add-form { grid-template-columns: 1fr 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- Hero --}}
        <section class="net-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Infrastructure réseau SOC
            </div>

            <h2>Contrôler les réseaux à surveiller.</h2>

            <p>Les réseaux détectés peuvent être approuvés, scannés, retirés ou réactivés. Un réseau retiré reste
                dans l'historique mais n'est plus scanné ni surveillé.</p>

            <div class="btn-row" style="margin-top:18px">
                {{-- Detect all — AJAX --}}
                <button type="button" id="btn-detect-all" class="action-btn lg success"
                        data-url="{{ route('platform.networks.detect') }}"
                        data-csrf="{{ csrf_token() }}">
                    <i class="fa-solid fa-magnifying-glass-location" id="detect-icon"></i>
                    <span id="detect-label">Détecter réseaux locaux</span>
                </button>

                <a href="{{ route('platform.discovered-hosts.index') }}" class="action-btn">
                    <i class="fa-solid fa-desktop"></i> Voir les hôtes
                </a>
                <a href="{{ route('platform.local-host.index') }}" class="action-btn">
                    <i class="fa-solid fa-server"></i> Machine SOC
                </a>
            </div>

            {{-- Detect result banner --}}
            <div id="detect-banner" style="margin-top:14px; display:none">
                <i class="fa-solid fa-circle-check"></i>
                <span id="detect-banner-text"></span>
            </div>

            <div class="filter-tabs">
                @foreach(['monitored' => 'Surveillés', 'retired' => 'Retirés', 'all' => 'Tous'] as $key => $label)
                    <a class="filter-tab {{ $activeStatus === $key ? 'active' : '' }}"
                       href="{{ route('platform.networks.index', ['status' => $key]) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Stats --}}
        <section class="smart-stats section-gap">
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-network-wired"></i></div>
                <div class="smart-stat-label">Total</div>
                <div class="smart-stat-value">{{ $stats['total'] }}</div>
                <div class="smart-stat-hint">Réseaux enregistrés.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-eye"></i></div>
                <div class="smart-stat-label">Surveillés</div>
                <div class="smart-stat-value" style="{{ $stats['monitored'] > 0 ? 'color:#22c55e' : '' }}">{{ $stats['monitored'] }}</div>
                <div class="smart-stat-hint">Actifs en ce moment.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="smart-stat-label">Approuvés</div>
                <div class="smart-stat-value">{{ $stats['approved'] }}</div>
                <div class="smart-stat-hint">Validés par l'opérateur.</div>
            </div>
            <div class="smart-stat">
                <div class="smart-stat-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="smart-stat-label">Retirés</div>
                <div class="smart-stat-value" style="{{ $stats['retired'] > 0 ? 'color:var(--text-muted)' : '' }}">{{ $stats['retired'] }}</div>
                <div class="smart-stat-hint">Hors surveillance.</div>
            </div>
        </section>

        {{-- Network list --}}
        @if($networks->count())
            <div class="net-list section-gap">
                @foreach($networks as $network)
                    @php
                        $sc = $statusColor($network);
                        $sl = $statusLabel($network);
                    @endphp

                    <article class="net-card" id="net-card-{{ $network->id }}" style="border-left-color:{{ $sc }}">
                        <div class="net-icon-col" style="background:color-mix(in srgb, {{ $sc }} 12%, transparent); color:{{ $sc }}">
                            <i class="fa-solid fa-network-wired"></i>
                        </div>

                        <div class="net-body">
                            <h4 class="net-title">{{ $network->name }}</h4>

                            <div class="net-ctx">
                                <div class="net-ctx-item">
                                    <small>CIDR</small>
                                    <strong>{{ $network->cidr }}</strong>
                                </div>
                                <div class="net-ctx-item">
                                    <small>Passerelle</small>
                                    <strong>{{ $network->gateway_ip ?? '—' }}</strong>
                                </div>
                                <div class="net-ctx-item">
                                    <small>Interface</small>
                                    <strong>{{ $network->interface_name ?? '—' }}</strong>
                                </div>
                                <div class="net-ctx-item">
                                    <small>Hôtes</small>
                                    <strong id="host-count-{{ $network->id }}" style="font-family:sans-serif">{{ $network->discovered_hosts_count ?? 0 }}</strong>
                                </div>
                            </div>

                            <div style="display:flex; gap:6px; flex-wrap:wrap">
                                <span class="badge" style="color:{{ $sc }}; border-color:color-mix(in srgb, {{ $sc }} 30%, transparent)">
                                    {{ $sl }}
                                </span>
                                <span class="badge" id="last-scan-{{ $network->id }}">
                                    @if($network->last_scanned_at)
                                        <i class="fa-regular fa-clock" style="margin-right:4px"></i>
                                        Dernier scan {{ $network->last_scanned_at->diffForHumans() }}
                                    @else
                                        <i class="fa-regular fa-clock" style="margin-right:4px"></i>
                                        Jamais scanné
                                    @endif
                                </span>
                                @if($network->retired_reason)
                                    <span class="badge" style="color:var(--text-muted)">{{ Str::limit($network->retired_reason, 50) }}</span>
                                @endif
                            </div>

                            {{-- Zone de résultat de scan (affichée après scan AJAX) --}}
                            <div class="scan-result" id="scan-result-{{ $network->id }}">
                                <div class="scan-result-title">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="scan-result-msg"></span>
                                </div>
                                <div style="font-size:11px; color:var(--text-muted)" class="scan-method-note"></div>
                                <div class="scan-result-ips"></div>
                            </div>
                        </div>

                        <div class="net-strip">
                            @if($network->is_monitored)
                                {{-- Bouton Scanner AJAX --}}
                                <button type="button"
                                        class="action-btn primary btn-scan"
                                        id="btn-scan-{{ $network->id }}"
                                        data-network-id="{{ $network->id }}"
                                        data-url="{{ route('platform.networks.scan', $network) }}"
                                        data-csrf="{{ csrf_token() }}"
                                        data-name="{{ $network->name }}">
                                    <i class="fa-solid fa-satellite-dish" id="scan-icon-{{ $network->id }}"></i>
                                    <span id="scan-label-{{ $network->id }}">Scanner</span>
                                </button>

                                <form method="POST" action="{{ route('platform.networks.retire', $network) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn danger" type="submit">
                                        <i class="fa-solid fa-ban"></i> Retirer
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('platform.networks.restore', $network) }}">
                                    @csrf @method('PATCH')
                                    <button class="action-btn success" type="submit">
                                        <i class="fa-solid fa-rotate-left"></i> Réactiver
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap section-gap">
                {{ $networks->links() }}
            </div>
        @else
            @include('platform.partials.empty-state', [
                'title'   => 'Aucun réseau pour ce filtre.',
                'message' => 'Lance une détection réseau ou change le filtre.'
            ])
        @endif

        {{-- Add network form --}}
        <div class="soc-card section-gap">
            <h3 class="soc-card-title">Ajouter un réseau manuellement</h3>
            <p class="soc-card-subtitle">Pour les réseaux non détectés automatiquement.</p>

            <form method="POST" action="{{ route('platform.networks.store') }}" class="net-add-form" style="margin-top:16px">
                @csrf
                <div class="net-field">
                    <label>Nom</label>
                    <input class="form-control" type="text" name="name" placeholder="LAN Bureau" required>
                </div>
                <div class="net-field">
                    <label>CIDR</label>
                    <input class="form-control" type="text" name="cidr" placeholder="192.168.1.0/24" required>
                </div>
                <div class="net-field">
                    <label>Passerelle</label>
                    <input class="form-control" type="text" name="gateway_ip" placeholder="192.168.1.1">
                </div>
                <div class="net-field">
                    <label>Interface</label>
                    <input class="form-control" type="text" name="interface_name" placeholder="eth0">
                </div>
                <button class="action-btn primary" type="submit" style="align-self:end">
                    <i class="fa-solid fa-plus"></i> Ajouter
                </button>
            </form>
        </div>

    </div>

    <script>
    // ── Helpers ──────────────────────────────────────────────────────────────────
    function setScanning(id, scanning, name) {
        const btn   = document.getElementById('btn-scan-' + id);
        const icon  = document.getElementById('scan-icon-' + id);
        const label = document.getElementById('scan-label-' + id);
        if (!btn) return;

        btn.disabled = scanning;
        if (scanning) {
            icon.className  = 'fa-solid fa-circle-notch btn-spinner';
            label.textContent = 'Scan…';
        } else {
            icon.className  = 'fa-solid fa-satellite-dish';
            label.textContent = 'Scanner';
        }
    }

    function showScanResult(id, data) {
        const box    = document.getElementById('scan-result-' + id);
        const msgEl  = box.querySelector('.scan-result-msg');
        const noteEl = box.querySelector('.scan-method-note');
        const ipsEl  = box.querySelector('.scan-result-ips');

        box.classList.remove('error');
        msgEl.textContent  = data.message;
        noteEl.textContent = data.note || '';

        ipsEl.innerHTML = '';
        (data.discovered_ips || []).slice(0, 20).forEach(ip => {
            const tag = document.createElement('span');
            tag.className   = 'scan-ip-tag';
            tag.textContent = ip;
            ipsEl.appendChild(tag);
        });
        if ((data.discovered_ips || []).length > 20) {
            const more = document.createElement('span');
            more.className   = 'scan-ip-tag';
            more.textContent = '+' + (data.discovered_ips.length - 20) + ' autres';
            ipsEl.appendChild(more);
        }

        box.style.display = 'block';

        // Mettre à jour le badge "dernier scan" et le compte hôtes
        const lastScan   = document.getElementById('last-scan-' + id);
        const hostCount  = document.getElementById('host-count-' + id);
        if (lastScan)  lastScan.innerHTML  = '<i class="fa-regular fa-clock" style="margin-right:4px"></i> À l\'instant';
        if (hostCount) hostCount.textContent = data.hosts_detected;
    }

    function showScanError(id, message) {
        const box = document.getElementById('scan-result-' + id);
        const msgEl = box.querySelector('.scan-result-msg');
        box.classList.add('error');
        box.querySelector('.scan-result-title').querySelector('i').className = 'fa-solid fa-triangle-exclamation';
        box.querySelector('.scan-method-note').textContent = '';
        box.querySelector('.scan-result-ips').innerHTML = '';
        msgEl.textContent = message;
        box.style.display = 'block';
    }

    // ── Scan bouton individuel ────────────────────────────────────────────────────
    document.querySelectorAll('.btn-scan').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id   = this.dataset.networkId;
            const url  = this.dataset.url;
            const csrf = this.dataset.csrf;
            const name = this.dataset.name;

            setScanning(id, true, name);

            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':  csrf,
                        'Accept':        'application/json',
                        'Content-Type':  'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await resp.json();

                if (resp.ok && data.success) {
                    showScanResult(id, data);
                } else {
                    showScanError(id, data.message || 'Erreur lors du scan.');
                }
            } catch (err) {
                showScanError(id, 'Erreur réseau : ' + err.message);
            } finally {
                setScanning(id, false, name);
            }
        });
    });

    // ── Détection globale ─────────────────────────────────────────────────────────
    document.getElementById('btn-detect-all')?.addEventListener('click', async function() {
        const url  = this.dataset.url;
        const csrf = this.dataset.csrf;
        const icon  = document.getElementById('detect-icon');
        const label = document.getElementById('detect-label');
        const banner = document.getElementById('detect-banner');
        const bannerText = document.getElementById('detect-banner-text');

        this.disabled  = true;
        icon.className = 'fa-solid fa-circle-notch btn-spinner';
        label.textContent = 'Détection en cours…';
        banner.style.display = 'none';

        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            });

            const data = await resp.json();

            if (resp.ok && data.success) {
                bannerText.textContent = data.message;
                banner.style.display = 'flex';

                // Recharger la page après 2 s pour afficher les nouveaux réseaux
                setTimeout(() => window.location.reload(), 2000);
            } else {
                bannerText.textContent = data.message || 'Erreur lors de la détection.';
                banner.style.display = 'flex';
                banner.style.color = '#ef4444';
            }
        } catch (err) {
            bannerText.textContent = 'Erreur réseau : ' + err.message;
            banner.style.display = 'flex';
        } finally {
            this.disabled  = false;
            icon.className = 'fa-solid fa-magnifying-glass-location';
            label.textContent = 'Détecter réseaux locaux';
        }
    });
    </script>
@endsection
