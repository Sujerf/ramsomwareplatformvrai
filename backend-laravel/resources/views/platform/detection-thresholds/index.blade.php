@extends('layouts.soc')

@section('title', "RansomShield — Seuils d'analyse")
@section('page_title', "Seuils d'analyse")
@section('page_subtitle', 'Transformation du score en niveau de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')
    @include('platform.partials.config-premium-style')

    @php
        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        // Couleurs brutes par niveau — utilisées en inline style pour les accents
        $riskColor = fn ($risk) => match ($risk) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#6b7280',
        };

        // Icône FA par niveau
        $riskIcon = fn ($risk) => match ($risk) {
            'critical' => 'fa-radiation',
            'high'     => 'fa-shield-halved',
            'suspect'  => 'fa-eye',
            default    => 'fa-circle-dot',
        };

        $enabledCount = $operational->where('is_enabled', true)->count();
        $coverage     = $enabledCount === 4;
        $maxScore     = 160; // plafond visuel — le moteur est illimité
    @endphp

    <style>
        /* ═══════════════════════════════════════════════════════════
           1. Accent par niveau de risque sur chaque carte
           ═══════════════════════════════════════════════════════════ */
        .threshold-card {
            border-left-width: 3px !important;
            border-left-style: solid !important;
        }
        .threshold-card-normal   { border-left-color: #6b7280 !important; }
        .threshold-card-suspect  { border-left-color: #eab308 !important; }
        .threshold-card-high     { border-left-color: #f97316 !important; }
        .threshold-card-critical { border-left-color: #ef4444 !important; }

        /* Override du blob ::after avec la couleur du niveau */
        .threshold-card-normal::after   { background: rgba(107,114,128,.10) !important; }
        .threshold-card-suspect::after  { background: rgba(234,179,  8,.10) !important; }
        .threshold-card-high::after     { background: rgba(249,115, 22,.10) !important; }
        .threshold-card-critical::after { background: rgba(239, 68, 68,.10) !important; }

        /* Icône de niveau — style repris de .rule-icon */
        .threshold-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 20px;
            flex-shrink: 0;
        }
        .threshold-icon-normal   { background: rgba(107,114,128,.10); color: #6b7280;  border: 1px solid rgba(107,114,128,.20); }
        .threshold-icon-suspect  { background: rgba(234,179,  8,.10); color: #ca8a04;  border: 1px solid rgba(234,179,  8,.22); }
        .threshold-icon-high     { background: rgba(249,115, 22,.10); color: #ea580c;  border: 1px solid rgba(249,115, 22,.22); }
        .threshold-icon-critical { background: rgba(239, 68, 68,.10); color: #dc2626;  border: 1px solid rgba(239, 68, 68,.22); }

        /* Grille icône + contenu dans la carte */
        .threshold-card-inner {
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            align-items: flex-start;
        }

        /* ═══════════════════════════════════════════════════════════
           2. Score ruler + ticks
           ═══════════════════════════════════════════════════════════ */
        .score-ruler {
            display: flex;
            height: 38px;
            border-radius: 10px;
            overflow: hidden;
            margin: 1.5rem 0 0;
            font-size: .72rem;
            font-weight: 700;
        }
        .score-ruler-segment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            gap: 5px;
            white-space: nowrap;
            overflow: hidden;
            transition: flex .4s ease;
        }
        .score-ruler-segment:hover { filter: brightness(1.12); }
        .score-ruler-label { font-size: .62rem; font-weight: 600; opacity: .85; }

        /* Ticks sous le ruler */
        .score-ruler-ticks {
            display: flex;
            margin-bottom: 1.5rem;
            margin-top: 3px;
        }
        .score-ruler-tick {
            position: relative;
            display: flex;
            justify-content: flex-start;
            min-width: 0;
        }
        .score-ruler-tick span {
            font-size: .67rem;
            color: var(--text-muted, #6b7280);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            /* Centrer le label sur le bord gauche du segment */
            transform: translateX(-50%);
            white-space: nowrap;
        }
        /* Premier tick : pas de translateX pour ne pas déborder à gauche */
        .score-ruler-tick:first-child span { transform: none; }
        .score-ruler-tick-end {
            font-size: .67rem;
            color: var(--text-muted, #6b7280);
            font-weight: 600;
            margin-left: auto;
            padding-right: 2px;
        }

        /* ═══════════════════════════════════════════════════════════
           4. Mini-bar de position dans chaque carte
           ═══════════════════════════════════════════════════════════ */
        .threshold-range-wrap {
            margin-top: 10px;
        }
        .threshold-range-header {
            display: flex;
            justify-content: space-between;
            font-size: .68rem;
            color: var(--text-muted, #6b7280);
            margin-bottom: 4px;
        }
        .threshold-range-track {
            height: 7px;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            position: relative;
            overflow: hidden;
        }
        .threshold-range-fill {
            position: absolute;
            top: 0;
            height: 100%;
            border-radius: 999px;
            opacity: .85;
        }
        .threshold-range-labels {
            display: flex;
            justify-content: space-between;
            font-size: .64rem;
            color: var(--text-muted, #6b7280);
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        /* ═══════════════════════════════════════════════════════════
           5. Stats mini-grid avec icônes
           ═══════════════════════════════════════════════════════════ */
        .config-mini-with-icon {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .config-mini-icon-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .config-mini-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .config-mini-icon-neutral  { background: rgba(255,255,255,.06); color: var(--text-muted, #6b7280); }
        .config-mini-icon-green    { background: rgba(34,197,94,.10);   color: #16a34a; }
        .config-mini-icon-blue     { background: rgba(99,102,241,.10);  color: #818cf8; }
        .config-mini-icon-dynamic  { } /* couleur définie inline */

        /* ═══════════════════════════════════════════════════════════
           Coverage bar + formulaire (inchangés)
           ═══════════════════════════════════════════════════════════ */
        .coverage-bar {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1rem;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .coverage-ok  { background: rgba(34,197,94,.08);  color: #16a34a; border: 1px solid rgba(34,197,94,.2); }
        .coverage-bad { background: rgba(239,68, 68,.08); color: #dc2626; border: 1px solid rgba(239,68, 68,.2); }

        .config-form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: .75rem;
            margin-bottom: .75rem;
        }
        .config-form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: .75rem;
        }
        .config-desc-row { margin-bottom: .75rem; }
        .config-desc-row textarea { width: 100%; min-height: 56px; resize: vertical; }

        /* Legacy */
        .legacy-toggle {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: none;
            border: 1px solid var(--border, rgba(255,255,255,.08));
            border-radius: 8px;
            padding: .6rem 1rem;
            font-size: .82rem;
            color: var(--text-muted, #6b7280);
            cursor: pointer;
            width: 100%;
            text-align: left;
            margin-bottom: .5rem;
            transition: background .15s;
        }
        .legacy-toggle:hover { background: rgba(255,255,255,.03); }
        .legacy-toggle .chevron { margin-left: auto; transition: transform .2s; }
        .legacy-toggle.open .chevron { transform: rotate(180deg); }
        .legacy-section { display: none; }
        .legacy-section.open { display: block; }
        .legacy-card {
            background: rgba(255,255,255,.02);
            border: 1px solid var(--border, rgba(255,255,255,.06));
            border-radius: 8px;
            padding: .85rem 1rem;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: .8rem;
            color: var(--text-muted, #6b7280);
        }
        .legacy-card-code { font-family: monospace; font-size: .75rem; }
        .legacy-note {
            font-size: .74rem;
            color: var(--text-muted, #6b7280);
            margin-bottom: 1rem;
            padding: .5rem .75rem;
            border-left: 3px solid rgba(107,114,128,.3);
        }

        @media (max-width: 720px) {
            .threshold-card-inner { grid-template-columns: 1fr; }
            .config-form-row-3, .config-form-row-2 { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── Hero ─────────────────────────────────────────────── --}}
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Score → risque
            </div>
            <h2>Calibrer la gravité des événements.</h2>
            <p>
                Les seuils transforment le score calculé par les règles et extensions sensibles
                en niveau de risque : <strong>normal → suspect → high → critical</strong>.
                Ce niveau déclenche ensuite les politiques de protection.
            </p>
            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="action-btn primary">
                    <i class="fa-solid fa-diagram-project"></i> Centre configuration
                </a>
                <a href="{{ route('platform.detection-rules.index') }}" class="action-btn">
                    <i class="fa-solid fa-list-check"></i> Règles de détection
                </a>
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}" style="display:contents">
                    @csrf
                    <button class="action-btn warning" type="submit">
                        <i class="fa-solid fa-rotate-left"></i> Restaurer défauts
                    </button>
                </form>
                @endif
            </div>
        </section>

        {{-- ── 5. Stats mini-grid avec icônes ──────────────────────── --}}
        @php $stateColor = $coverage ? '#16a34a' : '#dc2626'; @endphp
        <section class="config-mini-grid section-gap">

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon config-mini-icon-neutral">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <small>Seuils opérationnels</small>
                </div>
                <strong>{{ $operational->count() }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon config-mini-icon-green">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <small>Actifs</small>
                </div>
                <strong>{{ $enabledCount }} / {{ $operational->count() }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon config-mini-icon-blue">
                        <i class="fa-solid fa-arrows-left-right-to-line"></i>
                    </div>
                    <small>Couverture score</small>
                </div>
                <strong>0 – ∞</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon"
                         style="background: {{ $coverage ? 'rgba(34,197,94,.10)' : 'rgba(239,68,68,.10)' }};
                                color: {{ $stateColor }};">
                        <i class="fa-solid {{ $coverage ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                    </div>
                    <small>État</small>
                </div>
                <strong style="color: {{ $stateColor }}">
                    {{ $coverage ? 'Complet' : 'Incomplet' }}
                </strong>
            </div>

        </section>

        {{-- ── Coverage bar ─────────────────────────────────────────── --}}
        <div class="coverage-bar {{ $coverage ? 'coverage-ok' : 'coverage-bad' }}">
            <i class="fa-solid {{ $coverage ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
            @if ($coverage)
                Les 4 seuils sont actifs — la chaîne de classification est opérationnelle.
            @else
                {{ 4 - $enabledCount }} seuil(s) inactif(s) — certains scores ne seront pas correctement classés.
            @endif
        </div>

        {{-- ── 2. Score ruler + ticks ────────────────────────────────── --}}
        @php $enabledThresholds = $operational->where('is_enabled', true)->sortBy('min_score'); @endphp
        @if ($enabledThresholds->count() > 0)
            <div class="score-ruler">
                @foreach ($enabledThresholds as $t)
                    @php
                        $span  = max(1, ($t->max_score ?? $maxScore) - $t->min_score);
                        $pct   = round($span / $maxScore * 100, 1);
                        $color = $riskColor($t->risk_level);
                    @endphp
                    <div class="score-ruler-segment"
                         style="flex: {{ $pct }}; background: {{ $color }};"
                         title="{{ $t->label }} : {{ $t->min_score }}–{{ $t->max_score ?? '∞' }}">
                        <span>{{ $t->min_score }}–{{ $t->max_score ?? '∞' }}</span>
                        <span class="score-ruler-label">{{ strtoupper($t->risk_level) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Ticks alignés sur les segments --}}
            <div class="score-ruler-ticks">
                @foreach ($enabledThresholds as $t)
                    @php
                        $span = max(1, ($t->max_score ?? $maxScore) - $t->min_score);
                        $pct  = round($span / $maxScore * 100, 1);
                    @endphp
                    <div class="score-ruler-tick" style="flex: {{ $pct }}">
                        <span>{{ $t->min_score }}</span>
                    </div>
                @endforeach
                <span class="score-ruler-tick-end">∞</span>
            </div>
        @endif

        {{-- ── Flash success ─────────────────────────────────────────── --}}
        @if (session('success'))
            <div style="padding:.75rem 1rem; border-radius:8px; background:rgba(34,197,94,.08);
                        border:1px solid rgba(34,197,94,.2); color:#16a34a; font-size:.85rem;
                        margin-bottom:1rem; display:flex; align-items:center; gap:.5rem;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        {{-- ── Seuils opérationnels ──────────────────────────────────── --}}
        <section class="config-grid section-gap">
            @forelse ($operational as $threshold)
                @php
                    $rc    = $threshold->risk_level ?? 'normal';
                    $color = $riskColor($rc);
                    $icon  = $riskIcon($rc);

                    // Mini-bar : position dans la plage 0–maxScore
                    $barLeft  = round($threshold->min_score / $maxScore * 100, 1);
                    $barWidth = round(
                        min(100 - $barLeft, (($threshold->max_score ?? $maxScore) - $threshold->min_score) / $maxScore * 100),
                        1
                    );
                @endphp

                <article class="config-card threshold-card threshold-card-{{ $rc }}">
                    <div class="threshold-card-inner">

                        {{-- 1. Icône colorée par niveau --}}
                        <div class="threshold-icon threshold-icon-{{ $rc }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>

                        <div>
                            <div class="config-card-head" style="margin-bottom: 8px">
                                <div>
                                    <h3 class="config-title">
                                        {{ $threshold->label ?? $threshold->name ?? $threshold->code }}
                                    </h3>
                                    <div class="config-subtitle mono">{{ $threshold->code }}</div>
                                </div>
                                <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                            </div>

                            @if ($threshold->description)
                                <p style="font-size:.79rem; color:var(--text-muted,#6b7280); margin:.1rem 0 .6rem; line-height:1.5">
                                    {{ $threshold->description }}
                                </p>
                            @endif

                            {{-- Impact + mini-bar --}}
                            <div class="config-impact">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
                                    <span>
                                        <strong>Score</strong>
                                        <strong>{{ $threshold->min_score }}</strong>
                                        –
                                        <strong>{{ $threshold->max_score ?? '∞' }}</strong>
                                        →
                                        <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                                    </span>
                                    <span style="font-size:.7rem; color:var(--text-muted,#6b7280)">
                                        sur 160 pts affichés
                                    </span>
                                </div>

                                {{-- 4. Mini-bar de position --}}
                                <div class="threshold-range-track">
                                    <div class="threshold-range-fill"
                                         style="left: {{ $barLeft }}%;
                                                width: {{ $barWidth }}%;
                                                background: {{ $color }};"></div>
                                </div>
                                <div class="threshold-range-labels">
                                    <span>0</span>
                                    <span style="color:{{ $color }}; font-weight:700">
                                        {{ $threshold->min_score }}–{{ $threshold->max_score ?? '∞' }}
                                    </span>
                                    <span>160+</span>
                                </div>
                            </div>

                            @error('max_score')
                                <p style="color:#ef4444; font-size:.78rem; margin-top:.3rem">{{ $message }}</p>
                            @enderror

                            {{-- Formulaire --}}
                            <form method="POST"
                                  action="{{ route('platform.detection-thresholds.update', $threshold) }}"
                                  class="config-form">
                                @csrf
                                @method('PUT')

                                <div class="config-form-row-3">
                                    <div class="config-field">
                                        <label>Libellé</label>
                                        <input class="form-control" type="text" name="label"
                                               value="{{ old('label', $threshold->label ?? $threshold->name) }}"
                                               maxlength="100" required>
                                    </div>
                                    <div class="config-field">
                                        <label>Niveau de risque</label>
                                        <select class="form-control" name="risk_level">
                                            @foreach (['normal','suspect','high','critical'] as $lvl)
                                                <option value="{{ $lvl }}"
                                                    @selected(($threshold->risk_level ?? 'normal') === $lvl)>
                                                    {{ $lvl }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="config-field">
                                        <label>État</label>
                                        <select class="form-control" name="is_enabled">
                                            <option value="1" @selected($threshold->is_enabled)>actif</option>
                                            <option value="0" @selected(!$threshold->is_enabled)>inactif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="config-form-row-2">
                                    <div class="config-field">
                                        <label>Score min <small style="color:var(--text-muted)">(inclusif)</small></label>
                                        <input class="form-control" type="number" name="min_score"
                                               value="{{ old('min_score', $threshold->min_score) }}"
                                               min="0" max="1000" required>
                                    </div>
                                    <div class="config-field">
                                        <label>Score max <small style="color:var(--text-muted)">(vide = ∞)</small></label>
                                        <input class="form-control" type="number" name="max_score"
                                               value="{{ old('max_score', $threshold->max_score) }}"
                                               min="0" max="1000" placeholder="∞">
                                    </div>
                                </div>

                                <div class="config-desc-row">
                                    <label style="font-size:.8rem; display:block; margin-bottom:.25rem">Description</label>
                                    <textarea class="form-control" name="description" rows="2"
                                              maxlength="500"
                                              placeholder="Décrit le comportement de ce seuil…">{{ old('description', $threshold->description) }}</textarea>
                                </div>

                                <div class="config-actions">
                                    <button class="action-btn primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucun seuil opérationnel.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les seuils.'
                ])
            @endforelse
        </section>

        {{-- ── Legacy (accordéon) ──────────────────────────────────── --}}
        @if ($legacy->count() > 0)
            <section class="section-gap">
                <button class="legacy-toggle" onclick="this.classList.toggle('open');
                        document.getElementById('legacy-section').classList.toggle('open')" type="button">
                    <i class="fa-solid fa-box-archive"></i>
                    Seuils legacy désactivés ({{ $legacy->count() }}) — non utilisés par le moteur
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </button>

                <div class="legacy-section" id="legacy-section">
                    <p class="legacy-note">
                        Ces seuils correspondent à un ancien modèle de détection par comptage.
                        Le moteur actuel utilise un système de score — ces seuils ne sont jamais évalués.
                        Ils sont conservés en base pour un éventuel rollback.
                    </p>
                    @foreach ($legacy as $t)
                        <div class="legacy-card">
                            <span class="badge {{ $riskClass($t->risk_level) }}">{{ $t->risk_level }}</span>
                            <span class="legacy-card-code">{{ $t->code }}</span>
                            <span style="flex:1">{{ $t->label ?? $t->name ?? '—' }}</span>
                            <span style="font-size:.74rem">min={{ $t->min_score }} max={{ $t->max_score ?? 'NULL' }}</span>
                            <span class="badge badge-normal">désactivé</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
@endsection
