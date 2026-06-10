@extends('layouts.soc')

@section('title', 'RansomShield — Simulateur d\'attaque')
@section('page_title', 'Simulateur d\'attaque')
@section('page_subtitle', 'Génère des événements contrôlés pour tester le pipeline de détection')

@section('content')
@include('platform.partials.network-visual-style')
@include('platform.partials.page-tools-style')

<style>
    /* ── Hero ─────────────────────────────────────────────────────────────── */
    .sim-hero {
        background: linear-gradient(135deg, #1a0a0a 0%, #2d0f0f 40%, #1a0a1a 100%);
        border: 1px solid rgba(220, 60, 60, .25);
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .sim-hero-icon {
        width: 64px; height: 64px;
        border-radius: 16px;
        background: rgba(220, 60, 60, .15);
        border: 1.5px solid rgba(220, 60, 60, .4);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        color: #f87171;
        flex-shrink: 0;
    }
    .sim-hero-text h2 {
        font-size: 1.1rem; font-weight: 700;
        color: #f87171;
        margin: 0 0 .25rem;
    }
    .sim-hero-text p {
        font-size: .85rem; color: var(--text-muted);
        margin: 0;
    }
    .sim-warning-badge {
        margin-left: auto;
        background: rgba(251, 191, 36, .12);
        border: 1px solid rgba(251, 191, 36, .4);
        border-radius: 8px;
        padding: .5rem 1rem;
        font-size: .78rem;
        color: #fbbf24;
        display: flex; align-items: center; gap: .5rem;
        white-space: nowrap;
    }

    /* ── Layout deux colonnes ─────────────────────────────────────────────── */
    .sim-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 1.5rem;
        align-items: start;
    }
    @media (max-width: 900px) {
        .sim-grid { grid-template-columns: 1fr; }
    }

    /* ── Card générique ──────────────────────────────────────────────────── */
    .sim-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }
    .sim-card-header {
        padding: .75rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        font-size: .78rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .06em; color: var(--text-muted);
        display: flex; align-items: center; gap: .5rem;
    }

    /* ── Sélection agent ─────────────────────────────────────────────────── */
    .agent-list { padding: .5rem; }
    .agent-item {
        display: flex; align-items: center; gap: .75rem;
        padding: .625rem .75rem;
        border-radius: 8px;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: all .15s;
        margin-bottom: .25rem;
    }
    .agent-item:hover { background: rgba(var(--accent-rgb), .06); }
    .agent-item.selected {
        border-color: var(--accent);
        background: rgba(var(--accent-rgb), .1);
    }
    .agent-radio { display: none; }
    .agent-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        background: #22c55e;
        flex-shrink: 0;
    }
    .agent-dot.offline { background: #6b7280; }
    .agent-info { flex: 1; min-width: 0; }
    .agent-name {
        font-size: .85rem; font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .agent-ip { font-size: .75rem; color: var(--text-muted); font-family: monospace; }
    .agent-risk {
        font-size: .7rem; font-weight: 600;
        padding: .2rem .5rem; border-radius: 4px;
    }
    .agent-risk.critical { background: rgba(239,68,68,.15); color: #ef4444; }
    .agent-risk.high     { background: rgba(249,115,22,.15); color: #f97316; }
    .agent-risk.suspect  { background: rgba(234,179,8,.15);  color: #eab308; }
    .agent-risk.normal   { background: rgba(34,197,94,.15);  color: #22c55e; }

    .no-agents {
        padding: 2rem; text-align: center;
        color: var(--text-muted); font-size: .85rem;
    }
    .no-agents i { font-size: 2rem; display: block; margin-bottom: .75rem; opacity: .4; }

    /* ── Scénarios ───────────────────────────────────────────────────────── */
    .scenarios-grid {
        padding: 1rem;
        display: flex; flex-direction: column; gap: .75rem;
    }
    .scenario-card {
        padding: 1rem 1.25rem;
        border-radius: 10px;
        border: 1.5px solid var(--border-color);
        cursor: pointer;
        transition: all .15s;
        display: flex; align-items: flex-start; gap: 1rem;
    }
    .scenario-card:hover { border-color: rgba(var(--accent-rgb), .5); background: rgba(var(--accent-rgb), .04); }
    .scenario-card.selected {
        border-color: var(--accent);
        background: rgba(var(--accent-rgb), .08);
    }
    .scenario-card.color-critical.selected { border-color: #ef4444; background: rgba(239,68,68,.07); }
    .scenario-card.color-high.selected     { border-color: #f97316; background: rgba(249,115,22,.07); }
    .scenario-card.color-suspect.selected  { border-color: #eab308; background: rgba(234,179,8,.07); }
    .scenario-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .scenario-icon.critical { background: rgba(239,68,68,.12); color: #ef4444; }
    .scenario-icon.high     { background: rgba(249,115,22,.12); color: #f97316; }
    .scenario-icon.suspect  { background: rgba(234,179,8,.12);  color: #eab308; }
    .scenario-body { flex: 1; }
    .scenario-title {
        font-size: .88rem; font-weight: 600; color: var(--text-primary);
        margin-bottom: .2rem;
    }
    .scenario-desc {
        font-size: .78rem; color: var(--text-muted);
        line-height: 1.4;
    }
    .scenario-meta {
        display: flex; gap: .5rem; margin-top: .5rem; align-items: center;
    }
    .scenario-count {
        font-size: .72rem; font-weight: 500;
        background: rgba(var(--accent-rgb), .1);
        color: var(--accent);
        border-radius: 4px; padding: .15rem .45rem;
    }
    .scenario-radio { display: none; }

    /* ── Bouton lancer ───────────────────────────────────────────────────── */
    .launch-section {
        padding: 1.25rem;
        border-top: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 1rem;
    }
    .btn-launch {
        flex: 1;
        padding: .75rem 1.5rem;
        border-radius: 8px;
        font-weight: 700; font-size: .9rem;
        border: none; cursor: pointer;
        background: linear-gradient(135deg, #dc2626, #991b1b);
        color: #fff;
        display: flex; align-items: center; justify-content: center; gap: .6rem;
        transition: opacity .15s, transform .1s;
    }
    .btn-launch:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
    .btn-launch:disabled {
        background: var(--border-color);
        color: var(--text-muted);
        cursor: not-allowed; transform: none;
    }
    .btn-launch .spin { display: none; }
    .btn-launch.running .spin { display: inline-block; animation: spin .7s linear infinite; }
    .btn-launch.running .icon { display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Résultats ───────────────────────────────────────────────────────── */
    .results-area { margin-top: 1.5rem; }
    .result-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        animation: fadeIn .3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    .result-header {
        padding: 1rem 1.5rem;
        display: flex; align-items: center; gap: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .result-status-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
    }
    .result-status-icon.success { background: rgba(34,197,94,.12); color: #22c55e; }
    .result-status-icon.error   { background: rgba(239,68,68,.12);  color: #ef4444; }

    .result-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
    .result-sub   { font-size: .8rem; color: var(--text-muted); }

    .result-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        border-bottom: 1px solid var(--border-color);
    }
    .result-stat {
        padding: 1rem;
        text-align: center;
        border-right: 1px solid var(--border-color);
    }
    .result-stat:last-child { border-right: none; }
    .result-stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .result-stat-label { font-size: .72rem; color: var(--text-muted); margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }
    .stat-events   .result-stat-num { color: #60a5fa; }
    .stat-alerts   .result-stat-num { color: #f97316; }
    .stat-incidents.result-stat-num { color: #ef4444; }
    .stat-actions  .result-stat-num { color: #a855f7; }

    /* ── Timeline des événements ──────────────────────────────────────────── */
    .events-timeline {
        padding: 1rem 1.5rem;
        max-height: 340px;
        overflow-y: auto;
    }
    .tl-event {
        display: flex; align-items: flex-start; gap: .75rem;
        padding: .5rem 0;
        border-bottom: 1px solid rgba(255,255,255,.04);
        font-size: .8rem;
    }
    .tl-event:last-child { border-bottom: none; }
    .tl-step {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: rgba(var(--accent-rgb), .15);
        color: var(--accent);
        font-size: .7rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .tl-type { font-weight: 600; color: var(--text-primary); flex-shrink: 0; width: 200px; }
    .tl-path { color: var(--text-muted); font-family: monospace; font-size: .75rem; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tl-score { font-weight: 700; flex-shrink: 0; width: 80px; text-align: right; }
    .tl-score.critical { color: #ef4444; }
    .tl-score.high     { color: #f97316; }
    .tl-score.suspect  { color: #eab308; }
    .tl-score.normal   { color: #22c55e; }

    /* ── Liens résultats ──────────────────────────────────────────────────── */
    .result-links {
        padding: .75rem 1.5rem;
        display: flex; gap: .75rem;
        border-top: 1px solid var(--border-color);
        flex-wrap: wrap;
    }
    .result-link {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .4rem .9rem;
        border-radius: 6px;
        font-size: .78rem; font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        transition: all .15s;
    }
    .result-link:hover { border-color: var(--accent); color: var(--accent); }

    /* ── Risk bar ────────────────────────────────────────────────────────── */
    .risk-max-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem;
        border-radius: 6px;
        font-size: .78rem; font-weight: 700;
        margin-left: auto;
    }
    .risk-max-badge.critical { background: rgba(239,68,68,.15); color: #ef4444; border: 1px solid rgba(239,68,68,.3); }
    .risk-max-badge.high     { background: rgba(249,115,22,.15); color: #f97316; border: 1px solid rgba(249,115,22,.3); }
    .risk-max-badge.suspect  { background: rgba(234,179,8,.15);  color: #eab308; border: 1px solid rgba(234,179,8,.3); }
    .risk-max-badge.normal   { background: rgba(34,197,94,.15);  color: #22c55e; border: 1px solid rgba(34,197,94,.3); }
</style>

{{-- Hero ------------------------------------------------------------------- --}}
<div class="sim-hero">
    <div class="sim-hero-icon">
        <i class="fa-solid fa-biohazard"></i>
    </div>
    <div class="sim-hero-text">
        <h2>Simulateur d'attaque ransomware</h2>
        <p>Génère une séquence d'événements contrôlés sur un agent enrôlé, du simple chiffrement à la kill chain complète. Tous les événements sont marqués <code>is_simulation = true</code>.</p>
    </div>
    <div class="sim-warning-badge">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Simulation uniquement — aucun fichier réel n'est modifié
    </div>
</div>

{{-- Formulaire principal ---------------------------------------------------- --}}
<form id="simForm">
    @csrf
    <div class="sim-grid">

        {{-- Colonne gauche : choix de l'agent --------------------------------- --}}
        <div>
            <div class="sim-card">
                <div class="sim-card-header">
                    <i class="fa-solid fa-laptop"></i>
                    Cible — choisir un agent
                </div>
                @if ($agents->isEmpty())
                    <div class="no-agents">
                        <i class="fa-solid fa-plug-circle-xmark"></i>
                        Aucun agent enrôlé disponible.<br>
                        <a href="{{ route('platform.agents.index') }}" style="color:var(--accent)">Enrôler un agent →</a>
                    </div>
                @else
                    <div class="agent-list">
                        @foreach ($agents as $ag)
                            @php
                                $isOnline = $ag->last_seen_at && $ag->last_seen_at->gt(now()->subMinutes(10));
                                $risk     = $ag->risk_level ?? 'normal';
                            @endphp
                            <label class="agent-item" data-agent-id="{{ $ag->id }}">
                                <input type="radio" name="agent_id" value="{{ $ag->id }}" class="agent-radio" {{ $loop->first ? 'checked' : '' }}>
                                <span class="agent-dot {{ $isOnline ? '' : 'offline' }}"></span>
                                <div class="agent-info">
                                    <div class="agent-name">{{ $ag->agent_name }}</div>
                                    <div class="agent-ip">{{ $ag->ip_address ?? $ag->hostname }}</div>
                                </div>
                                <span class="agent-risk {{ $risk }}">{{ strtoupper($risk) }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Colonne droite : scénarios + lancement ---------------------------- --}}
        <div>
            <div class="sim-card">
                <div class="sim-card-header">
                    <i class="fa-solid fa-scroll"></i>
                    Scénario d'attaque
                </div>

                <div class="scenarios-grid">
                    @foreach ($scenarios as $key => $sc)
                        <label class="scenario-card color-{{ $sc['color'] }} {{ $loop->first ? 'selected' : '' }}" data-scenario="{{ $key }}">
                            <input type="radio" name="scenario" value="{{ $key }}" class="scenario-radio" {{ $loop->first ? 'checked' : '' }}>
                            <div class="scenario-icon {{ $sc['color'] }}">
                                <i class="fa-solid {{ $sc['icon'] }}"></i>
                            </div>
                            <div class="scenario-body">
                                <div class="scenario-title">{{ $sc['label'] }}</div>
                                <div class="scenario-desc">{{ $sc['description'] }}</div>
                                <div class="scenario-meta">
                                    <span class="scenario-count">{{ $sc['event_count'] }} événements</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="launch-section">
                    @if ($agents->isEmpty())
                        <button type="button" class="btn-launch" disabled>
                            <i class="fa-solid fa-ban icon"></i>
                            Aucun agent disponible
                        </button>
                    @elseif(auth()->user()->isAdmin())
                        <button type="submit" class="btn-launch" id="btnLaunch">
                            <i class="fa-solid fa-play icon"></i>
                            <i class="fa-solid fa-circle-notch spin"></i>
                            Lancer la simulation
                        </button>
                    @else
                        <button type="button" class="btn-launch" disabled title="Réservé aux administrateurs">
                            <i class="fa-solid fa-lock icon"></i>
                            Réservé aux administrateurs
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Zone de résultats -------------------------------------------------------- --}}
<div class="results-area" id="resultsArea" style="display:none"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Sélection agent (highlight) ──────────────────────────────────────────
    document.querySelectorAll('.agent-item').forEach(item => {
        const radio = item.querySelector('.agent-radio');
        if (radio.checked) item.classList.add('selected');
        item.addEventListener('click', () => {
            document.querySelectorAll('.agent-item').forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            radio.checked = true;
        });
    });

    // ── Sélection scénario (highlight) ──────────────────────────────────────
    document.querySelectorAll('.scenario-card').forEach(card => {
        const radio = card.querySelector('.scenario-radio');
        card.addEventListener('click', () => {
            document.querySelectorAll('.scenario-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            radio.checked = true;
        });
    });

    // ── Soumission du formulaire ─────────────────────────────────────────────
    const form     = document.getElementById('simForm');
    const btnLaunch = document.getElementById('btnLaunch');
    const resultsArea = document.getElementById('resultsArea');

    if (!form || !btnLaunch) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const agentId  = form.querySelector('input[name="agent_id"]:checked')?.value;
        const scenario = form.querySelector('input[name="scenario"]:checked')?.value;

        if (!agentId || !scenario) {
            alert('Sélectionnez un agent et un scénario.');
            return;
        }

        // État chargement
        btnLaunch.disabled = true;
        btnLaunch.classList.add('running');
        resultsArea.style.display = 'none';

        const csrf = document.querySelector('input[name="_token"]')?.value
                  || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                  || '{{ csrf_token() }}';

        try {
            const resp = await fetch('{{ route("platform.simulation.run") }}', {
                method : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept'      : 'application/json',
                },
                body: JSON.stringify({ agent_id: agentId, scenario }),
            });

            const data = await resp.json();

            if (data.success) {
                renderResults(data.result);
            } else {
                renderError(data.error || 'Erreur inconnue');
            }
        } catch (err) {
            renderError(err.message);
        } finally {
            btnLaunch.disabled = false;
            btnLaunch.classList.remove('running');
        }
    });

    // ── Rendu des résultats ──────────────────────────────────────────────────
    function renderResults(result) {
        const s = result.summary;
        const scenarios = @json($scenarios);
        const scenarioLabel = scenarios[result.scenario]?.label ?? result.scenario;
        const riskColors = { critical: 'critical', high: 'high', suspect: 'suspect', normal: 'normal' };
        const riskLabels = { critical: 'CRITIQUE', high: 'ÉLEVÉ', suspect: 'SUSPECT', normal: 'NORMAL' };
        const maxRisk    = s.max_risk ?? 'normal';

        // Timeline des événements
        let timelineHtml = '';
        for (const ev of result.events) {
            const rl = ev.risk_level ?? 'normal';
            const pathShort = ev.path ? ev.path.replace(/\\/g, '/').split('/').slice(-1)[0] : '—';
            timelineHtml += `
                <div class="tl-event">
                    <span class="tl-step">${ev.step}</span>
                    <span class="tl-type">${ev.event_type}</span>
                    <span class="tl-path" title="${ev.path ?? ''}">${ev.path ?? '—'}</span>
                    <span class="tl-score ${rl}">${ev.score}pts&nbsp;·&nbsp;${rl}</span>
                </div>`;
        }

        // Erreurs éventuelles
        let errorsHtml = '';
        if (result.errors?.length) {
            errorsHtml = `<div style="padding:.75rem 1.5rem;border-top:1px solid var(--border-color);color:#f87171;font-size:.8rem">
                <i class="fa-solid fa-circle-exclamation"></i> ${result.errors.length} erreur(s) : ${result.errors.map(e => 'Étape ' + e.step + ' — ' + e.message).join(', ')}
            </div>`;
        }

        resultsArea.innerHTML = `
            <div class="result-card">
                <div class="result-header">
                    <div class="result-status-icon success">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <div class="result-title">Simulation « ${scenarioLabel} » terminée</div>
                        <div class="result-sub">Agent : ${result.agent_name} &nbsp;·&nbsp; Run UUID : <code style="font-size:.7rem">${result.run_uuid}</code></div>
                    </div>
                    <span class="risk-max-badge ${riskColors[maxRisk]}">
                        <i class="fa-solid fa-shield-halved"></i>
                        Risque max : ${riskLabels[maxRisk] ?? maxRisk}
                    </span>
                </div>

                <div class="result-stats">
                    <div class="result-stat stat-events">
                        <div class="result-stat-num" style="color:#60a5fa">${s.total_events}</div>
                        <div class="result-stat-label">Événements</div>
                    </div>
                    <div class="result-stat stat-alerts">
                        <div class="result-stat-num" style="color:#f97316">${s.total_alerts}</div>
                        <div class="result-stat-label">Alertes</div>
                    </div>
                    <div class="result-stat stat-incidents">
                        <div class="result-stat-num" style="color:#ef4444">${s.total_incidents}</div>
                        <div class="result-stat-label">Incidents</div>
                    </div>
                    <div class="result-stat stat-actions">
                        <div class="result-stat-num" style="color:#a855f7">${s.total_actions}</div>
                        <div class="result-stat-label">Actions</div>
                    </div>
                </div>

                <div class="events-timeline">
                    ${timelineHtml || '<p style="color:var(--text-muted);font-size:.85rem">Aucun événement.</p>'}
                </div>

                ${errorsHtml}

                <div class="result-links">
                    <a href="/console/events?simulation=1" class="result-link">
                        <i class="fa-solid fa-list"></i> Voir les événements
                    </a>
                    <a href="/console/alerts" class="result-link">
                        <i class="fa-solid fa-bell"></i> Voir les alertes
                    </a>
                    <a href="/console/incidents" class="result-link">
                        <i class="fa-solid fa-fire"></i> Voir les incidents
                    </a>
                    <a href="/console/approval-queue" class="result-link">
                        <i class="fa-solid fa-shield"></i> File d'approbation
                    </a>
                </div>
            </div>`;

        resultsArea.style.display = 'block';
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderError(message) {
        resultsArea.innerHTML = `
            <div class="result-card">
                <div class="result-header">
                    <div class="result-status-icon error">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <div class="result-title" style="color:#f87171">Échec de la simulation</div>
                        <div class="result-sub">${message}</div>
                    </div>
                </div>
            </div>`;
        resultsArea.style.display = 'block';
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>
@endsection
