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

        $riskColor = fn ($risk) => match ($risk) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#6b7280',
        };

        $enabledCount = $operational->where('is_enabled', true)->count();
        $coverage     = $operational->where('is_enabled', true)->count() === 4;
    @endphp

    <style>
        /* ── Score ruler ─────────────────────────────────────────── */
        .score-ruler {
            display: flex;
            height: 36px;
            border-radius: 8px;
            overflow: hidden;
            margin: 1.5rem 0 .5rem;
            font-size: .72rem;
            font-weight: 600;
        }
        .score-ruler-segment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            transition: flex .3s ease;
            gap: 4px;
            white-space: nowrap;
            overflow: hidden;
        }
        .score-ruler-label { font-size: .65rem; opacity: .9; }

        /* ── Coverage status bar ─────────────────────────────────── */
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
        .coverage-ok  { background: rgba(34,197,94,.08); color: #16a34a; border: 1px solid rgba(34,197,94,.2); }
        .coverage-bad { background: rgba(239,68,68,.08);  color: #dc2626; border: 1px solid rgba(239,68,68,.2); }

        /* ── Threshold form extra fields ─────────────────────────── */
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
        .config-desc-row textarea {
            width: 100%;
            min-height: 56px;
            resize: vertical;
        }
        select.form-control option[value="critical"] { color: #ef4444; }
        select.form-control option[value="high"]     { color: #f97316; }
        select.form-control option[value="suspect"]  { color: #eab308; }

        /* ── Legacy accordion ────────────────────────────────────── */
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
    </style>

    <div class="animated-page">
        {{-- ── Hero ──────────────────────────────────────────────── --}}
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
                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}" style="display:contents">
                    @csrf
                    <button class="action-btn warning" type="submit">
                        <i class="fa-solid fa-rotate-left"></i> Restaurer défauts
                    </button>
                </form>
            </div>
        </section>

        {{-- ── Stats ─────────────────────────────────────────────── --}}
        <section class="config-mini-grid section-gap">
            <div class="config-mini">
                <small>Seuils opérationnels</small>
                <strong>{{ $operational->count() }}</strong>
            </div>
            <div class="config-mini">
                <small>Actifs</small>
                <strong>{{ $enabledCount }} / {{ $operational->count() }}</strong>
            </div>
            <div class="config-mini">
                <small>Couverture</small>
                <strong>0 – ∞</strong>
            </div>
            <div class="config-mini">
                <small>État</small>
                <strong style="color: {{ $coverage ? '#16a34a' : '#dc2626' }}">
                    {{ $coverage ? 'Complet' : 'Incomplet' }}
                </strong>
            </div>
        </section>

        {{-- ── Coverage bar ───────────────────────────────────────── --}}
        <div class="coverage-bar {{ $coverage ? 'coverage-ok' : 'coverage-bad' }}">
            <i class="fa-solid {{ $coverage ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
            @if ($coverage)
                Les 4 seuils sont actifs — la chaîne de classification est opérationnelle.
            @else
                {{ 4 - $enabledCount }} seuil(s) inactif(s) — certains scores ne seront pas correctement classés.
            @endif
        </div>

        {{-- ── Score ruler ─────────────────────────────────────────── --}}
        @php
            $enabledThresholds = $operational->where('is_enabled', true)->sortBy('min_score');
            $maxScore = 160; // référence visuelle
        @endphp
        @if ($enabledThresholds->count() > 0)
            <div class="score-ruler">
                @foreach ($enabledThresholds as $t)
                    @php
                        $from  = $t->min_score;
                        $to    = $t->max_score ?? $maxScore;
                        $span  = max(1, $to - $from);
                        $pct   = round($span / $maxScore * 100, 1);
                        $color = $riskColor($t->risk_level);
                    @endphp
                    <div class="score-ruler-segment"
                         style="flex: {{ $pct }}; background: {{ $color }};"
                         title="{{ $t->label }} : {{ $from }}–{{ $t->max_score ?? '∞' }}">
                        <span>{{ $from }}–{{ $t->max_score ?? '∞' }}</span>
                        <span class="score-ruler-label">{{ strtoupper($t->risk_level) }}</span>
                    </div>
                @endforeach
            </div>
            <p style="font-size:.72rem; color:var(--text-muted,#6b7280); margin-bottom:1.5rem; text-align:right">
                Visualisation jusqu'à {{ $maxScore }} pts (le moteur est illimité)
            </p>
        @endif

        {{-- ── Seuils opérationnels ──────────────────────────────── --}}
        @if (session('success'))
            <div class="alert alert-success mb-3" style="padding:.75rem 1rem;border-radius:8px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);color:#16a34a;font-size:.85rem;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        <section class="config-grid section-gap">
            @forelse ($operational as $threshold)
                <article class="config-card">
                    <div class="config-card-head">
                        <div>
                            <h3 class="config-title">{{ $threshold->label ?? $threshold->name ?? $threshold->code }}</h3>
                            <div class="config-subtitle mono">{{ $threshold->code }}</div>
                        </div>
                        <span class="badge {{ $riskClass($threshold->risk_level) }}">
                            {{ $threshold->risk_level }}
                        </span>
                    </div>

                    @if ($threshold->description)
                        <p style="font-size:.8rem; color:var(--text-muted,#6b7280); margin:.25rem 0 .75rem">
                            {{ $threshold->description }}
                        </p>
                    @endif

                    <div class="config-impact">
                        <strong>Impact :</strong>
                        score entre <strong>{{ $threshold->min_score }}</strong>
                        et <strong>{{ $threshold->max_score ?? '∞' }}</strong>
                        → <span class="badge {{ $riskClass($threshold->risk_level) }}">{{ $threshold->risk_level }}</span>
                    </div>

                    @error('max_score') <p class="text-danger" style="font-size:.8rem">{{ $message }}</p> @enderror

                    <form method="POST"
                          action="{{ route('platform.detection-thresholds.update', $threshold) }}"
                          class="config-form">
                        @csrf
                        @method('PUT')

                        {{-- Ligne 1 : label + risk_level + état --}}
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
                                    @foreach (['normal', 'suspect', 'high', 'critical'] as $lvl)
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

                        {{-- Ligne 2 : score min + score max --}}
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

                        {{-- Ligne 3 : description --}}
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
                </article>
            @empty
                @include('platform.partials.empty-state', [
                    'title'   => 'Aucun seuil opérationnel.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les seuils.'
                ])
            @endforelse
        </section>

        {{-- ── Seuils legacy (accordéon) ──────────────────────────── --}}
        @if ($legacy->count() > 0)
            <section class="section-gap">
                <button class="legacy-toggle" onclick="toggleLegacy(this)" type="button">
                    <i class="fa-solid fa-box-archive"></i>
                    Seuils legacy désactivés ({{ $legacy->count() }}) — non utilisés par le moteur
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </button>

                <div class="legacy-section" id="legacy-section">
                    <p class="legacy-note">
                        Ces seuils correspondent à un ancien modèle de détection par comptage
                        (nombre de modifications, de renommages…). Le moteur actuel utilise
                        un système de score — ces seuils ne sont donc jamais évalués.
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

    <script>
        function toggleLegacy(btn) {
            btn.classList.toggle('open');
            document.getElementById('legacy-section').classList.toggle('open');
        }
    </script>
@endsection
