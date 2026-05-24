@extends('layouts.soc')

@section('title', 'RansomShield — Règles de détection')
@section('page_title', 'Règles de détection')
@section('page_subtitle', 'Règles utilisées par le moteur dynamique pour calculer le score de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')
    @include('platform.partials.config-premium-style')

    @php
        // ── Helpers ────────────────────────────────────────────────
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

        $ruleIcon = function ($code) {
            return match ($code) {
                'rule_sensitive_extension' => 'fa-file-circle-exclamation',
                'rule_mass_rename'         => 'fa-arrows-rotate',
                'rule_ransom_note'         => 'fa-note-sticky',
                'rule_fast_write_activity' => 'fa-bolt',
                'rule_simulation_marker'   => 'fa-flask',
                default                    => 'fa-brain',
            };
        };

        // 7. Description : priorité DB, fallback texte court
        $ruleDesc = function ($rule) {
            if (! empty($rule->description)) {
                return $rule->description;
            }
            return match ($rule->code) {
                'rule_sensitive_extension' => "Vérifie si l'extension du fichier est présente dans la table des extensions sensibles.",
                'rule_mass_rename'         => "Détecte les renommages ou déplacements suspects, typiques d'un chiffrement massif.",
                'rule_ransom_note'         => 'Détecte les fichiers de type README, RECOVER, DECRYPT ou instructions de rançon.',
                'rule_fast_write_activity' => "Déclenche sur une activité rapide de modification de fichiers.",
                'rule_simulation_marker'   => 'Permet de tester le moteur sans déclencher une vraie attaque.',
                default                    => 'Participe au calcul dynamique du score de risque.',
            };
        };

        // Règles à logique hardcodée dans matchRule() — event_type DB non utilisé
        $hardcodedCodes = ['rule_mass_rename','rule_ransom_note','rule_fast_write_activity','rule_simulation_marker'];

        // Stats
        $totalActive   = $active->count();
        $totalInactive = $inactive->count();
        $critical      = $active->where('risk_level', 'critical')->count();
        $highSuspect   = $active->whereIn('risk_level', ['high','suspect'])->count();
    @endphp

    <style>
        /* ═══════════════════════════════════════════════════════════
           2. Icône colorée par niveau de risque
           ═══════════════════════════════════════════════════════════ */
        .rule-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            font-size: 22px;
            flex-shrink: 0;
        }
        .rule-icon-normal   { background: rgba(107,114,128,.10); color: #6b7280;  border: 1px solid rgba(107,114,128,.20); }
        .rule-icon-suspect  { background: rgba(234,179,  8,.10); color: #ca8a04;  border: 1px solid rgba(234,179,  8,.22); }
        .rule-icon-high     { background: rgba(249,115, 22,.10); color: #ea580c;  border: 1px solid rgba(249,115, 22,.22); }
        .rule-icon-critical { background: rgba(239, 68, 68,.10); color: #dc2626;  border: 1px solid rgba(239, 68, 68,.22); }

        /* ═══════════════════════════════════════════════════════════
           1 + 6. Score-meter calibré sur max réel + couleur par risque
           ═══════════════════════════════════════════════════════════ */
        .score-meter {
            margin-top: 10px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            overflow: hidden;
            position: relative;
        }
        .score-meter-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .35s ease;
        }
        .score-meter-legend {
            display: flex;
            justify-content: space-between;
            font-size: .65rem;
            color: var(--text-muted, #6b7280);
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        .rule-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 14px;
            align-items: flex-start;
        }

        .rule-tags {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        /* 3. Badge inactif → gris neutre (évite confusion avec badge-suspect jaune) */
        .badge-inactive {
            background: rgba(107,114,128,.12);
            color: #6b7280;
            border: 1px solid rgba(107,114,128,.25);
        }

        /* Tag event_type hardcodé */
        .badge-hardcoded {
            background: rgba(99,102,241,.10);
            color: #818cf8;
            border: 1px solid rgba(99,102,241,.22);
            font-size: .7rem;
        }

        /* 5. Stats mini-grid icons (cohérence avec page seuils) */
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

        /* 4. Section inactive — accordion */
        .inactive-toggle {
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
            margin-bottom: .75rem;
            transition: background .15s;
        }
        .inactive-toggle:hover { background: rgba(255,255,255,.03); }
        .inactive-toggle .chevron { margin-left: auto; transition: transform .2s; }
        .inactive-toggle.open .chevron { transform: rotate(180deg); }
        .inactive-section { display: none; }
        .inactive-section.open { display: block; }
        .inactive-note {
            font-size: .74rem;
            color: var(--text-muted, #6b7280);
            margin-bottom: 1rem;
            padding: .5rem .75rem;
            border-left: 3px solid rgba(107,114,128,.3);
        }

        /* Carte inactive — plus discrète */
        .rule-card-inactive { opacity: .7; }
        .rule-card-inactive:hover { opacity: 1; transition: opacity .15s; }

        @media (max-width: 720px) {
            .rule-card { grid-template-columns: 1fr; }
            .rule-icon { width: 52px; height: 52px; }
        }
    </style>

    <div class="animated-page">

        {{-- ── Hero ─────────────────────────────────────────────── --}}
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Moteur de détection
            </div>
            <h2>Transformer les événements en score.</h2>
            <p>
                Les règles analysent les événements reçus des agents. Chaque règle active
                ajoute un poids au score final — les seuils convertissent ensuite ce score
                en niveau de risque, qui déclenche les politiques de protection.
            </p>
            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="action-btn primary">
                    <i class="fa-solid fa-diagram-project"></i> Centre configuration
                </a>
                <a href="{{ route('platform.detection-thresholds.index') }}" class="action-btn">
                    <i class="fa-solid fa-gauge-high"></i> Seuils d'analyse
                </a>
                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}" style="display:contents">
                    @csrf
                    <button class="action-btn warning" type="submit">
                        <i class="fa-solid fa-rotate-left"></i> Restaurer défauts
                    </button>
                </form>
            </div>
        </section>

        {{-- ── 5. Stats mini-grid avec icônes ─────────────────────── --}}
        <section class="config-mini-grid section-gap">

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(255,255,255,.06);color:var(--text-muted,#6b7280)">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <small>Règles actives</small>
                </div>
                <strong>{{ $totalActive }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(107,114,128,.10);color:#6b7280">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <small>Inactives</small>
                </div>
                <strong style="color:var(--text-muted,#6b7280)">{{ $totalInactive }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(239,68,68,.10);color:#dc2626">
                        <i class="fa-solid fa-radiation"></i>
                    </div>
                    <small>Critical actives</small>
                </div>
                <strong style="color:#dc2626">{{ $critical }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(249,115,22,.10);color:#ea580c">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <small>High / Suspect</small>
                </div>
                <strong style="color:#ea580c">{{ $highSuspect }}</strong>
            </div>

        </section>

        {{-- ── Flash success ─────────────────────────────────────── --}}
        @if (session('success'))
            <div style="padding:.75rem 1rem; border-radius:8px; background:rgba(34,197,94,.08);
                        border:1px solid rgba(34,197,94,.2); color:#16a34a; font-size:.85rem;
                        margin-bottom:1rem; display:flex; align-items:center; gap:.5rem;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        {{-- ── 4. Règles actives ─────────────────────────────────── --}}
        <section class="config-grid section-gap">
            @forelse ($active as $rule)
                @php
                    $rc           = $rule->risk_level ?? 'normal';
                    $color        = $riskColor($rc);
                    $icon         = $ruleIcon($rule->code);
                    $scorePercent = round($rule->score_weight / $maxScore * 100);  // #1
                    $isHardcoded  = in_array($rule->code, $hardcodedCodes);
                @endphp

                <article class="config-card">
                    <div class="rule-card">

                        {{-- 2. Icône colorée par niveau --}}
                        <div class="rule-icon rule-icon-{{ $rc }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>

                        <div>
                            <div class="config-card-head">
                                <div>
                                    <h3 class="config-title">{{ $rule->name }}</h3>
                                    <div class="config-subtitle mono">{{ $rule->code }}</div>
                                </div>
                                <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                            </div>

                            {{-- 7. Description DB (ou fallback) --}}
                            <p style="font-size:.79rem; color:var(--text-muted,#6b7280); margin:.3rem 0 .5rem; line-height:1.55">
                                {{ $ruleDesc($rule) }}
                            </p>

                            {{-- Tags --}}
                            <div class="rule-tags">
                                <span class="score-pill">
                                    <i class="fa-solid fa-weight-hanging" style="font-size:.65rem"></i>
                                    Score : {{ $rule->score_weight }}
                                </span>
                                @if ($isHardcoded)
                                    <span class="badge badge-hardcoded" title="Logique PHP dans DynamicDetectionEngineService — event_type DB non utilisé">
                                        <i class="fa-solid fa-code"></i> hardcodé
                                    </span>
                                @elseif ($rule->event_type)
                                    <span class="badge">{{ $rule->event_type }}</span>
                                @endif
                                {{-- 3. Badge actif/inactif → vert ou gris neutre --}}
                                <span class="badge" style="background:rgba(34,197,94,.10);color:#16a34a;border:1px solid rgba(34,197,94,.22)">
                                    <i class="fa-solid fa-circle-check" style="font-size:.65rem"></i> active
                                </span>
                            </div>

                            {{-- 1 + 6. Score meter calibré + coloré --}}
                            <div class="score-meter">
                                <div class="score-meter-fill"
                                     style="width: {{ $scorePercent }}%; background: {{ $color }};"></div>
                            </div>
                            <div class="score-meter-legend">
                                <span>0</span>
                                <span style="color: {{ $color }}; font-weight:700">
                                    {{ $rule->score_weight }} pts ({{ $scorePercent }}%)
                                </span>
                                <span>{{ $maxScore }}</span>
                            </div>

                            {{-- Formulaire --}}
                            <form method="POST"
                                  action="{{ route('platform.detection-rules.update', $rule) }}"
                                  class="config-form">
                                @csrf
                                @method('PUT')
                                <div class="config-form-row">
                                    <div class="config-field">
                                        <label>Risque</label>
                                        <select class="form-control" name="risk_level">
                                            @foreach (['normal','suspect','high','critical'] as $lvl)
                                                <option value="{{ $lvl }}" @selected($rule->risk_level === $lvl)>{{ $lvl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="config-field">
                                        <label>Poids score</label>
                                        <input class="form-control" type="number" name="score_weight"
                                               value="{{ $rule->score_weight }}" min="0" max="1000">
                                    </div>
                                    <div class="config-field">
                                        <label>État</label>
                                        <select class="form-control" name="is_enabled">
                                            <option value="1" @selected($rule->is_enabled)>active</option>
                                            <option value="0" @selected(!$rule->is_enabled)>inactive</option>
                                        </select>
                                    </div>
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
                    'title'   => 'Aucune règle active.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les règles.'
                ])
            @endforelse
        </section>

        {{-- ── 4. Règles inactives — accordéon ──────────────────── --}}
        @if ($inactive->count() > 0)
            <section class="section-gap">
                <button class="inactive-toggle"
                        onclick="this.classList.toggle('open'); document.getElementById('inactive-section').classList.toggle('open')"
                        type="button">
                    <i class="fa-solid fa-box-archive"></i>
                    Règles inactives ({{ $inactive->count() }}) — désactivées, non évaluées par le moteur
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </button>

                <div class="inactive-section" id="inactive-section">
                    <p class="inactive-note">
                        Ces règles sont désactivées (orphelines — event_type jamais émis par l'agent,
                        ou doublons). Elles ne contribuent pas au score. Réactivables depuis le formulaire.
                    </p>

                    <div class="config-grid">
                        @foreach ($inactive as $rule)
                            @php
                                $rc           = $rule->risk_level ?? 'normal';
                                $color        = $riskColor($rc);
                                $icon         = $ruleIcon($rule->code);
                                $scorePercent = round($rule->score_weight / $maxScore * 100);
                                $isHardcoded  = in_array($rule->code, $hardcodedCodes);
                            @endphp

                            <article class="config-card rule-card-inactive">
                                <div class="rule-card">

                                    <div class="rule-icon rule-icon-{{ $rc }}">
                                        <i class="fa-solid {{ $icon }}"></i>
                                    </div>

                                    <div>
                                        <div class="config-card-head">
                                            <div>
                                                <h3 class="config-title">{{ $rule->name }}</h3>
                                                <div class="config-subtitle mono">{{ $rule->code }}</div>
                                            </div>
                                            <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                                        </div>

                                        {{-- 7. Description --}}
                                        <p style="font-size:.79rem; color:var(--text-muted,#6b7280); margin:.3rem 0 .5rem; line-height:1.55">
                                            {{ $ruleDesc($rule) }}
                                        </p>

                                        <div class="rule-tags">
                                            <span class="score-pill">
                                                <i class="fa-solid fa-weight-hanging" style="font-size:.65rem"></i>
                                                Score : {{ $rule->score_weight }}
                                            </span>
                                            @if ($isHardcoded)
                                                <span class="badge badge-hardcoded">
                                                    <i class="fa-solid fa-code"></i> hardcodé
                                                </span>
                                            @elseif ($rule->event_type)
                                                <span class="badge">{{ $rule->event_type }}</span>
                                            @endif
                                            {{-- 3. Badge inactive → gris neutre --}}
                                            <span class="badge badge-inactive">
                                                <i class="fa-solid fa-circle-xmark" style="font-size:.65rem"></i> inactive
                                            </span>
                                        </div>

                                        {{-- 1 + 6. Score meter --}}
                                        <div class="score-meter">
                                            <div class="score-meter-fill"
                                                 style="width: {{ $scorePercent }}%; background: {{ $color }}; opacity:.5;"></div>
                                        </div>
                                        <div class="score-meter-legend">
                                            <span>0</span>
                                            <span style="color:var(--text-muted,#6b7280)">
                                                {{ $rule->score_weight }} pts ({{ $scorePercent }}%)
                                            </span>
                                            <span>{{ $maxScore }}</span>
                                        </div>

                                        <form method="POST"
                                              action="{{ route('platform.detection-rules.update', $rule) }}"
                                              class="config-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="config-form-row">
                                                <div class="config-field">
                                                    <label>Risque</label>
                                                    <select class="form-control" name="risk_level">
                                                        @foreach (['normal','suspect','high','critical'] as $lvl)
                                                            <option value="{{ $lvl }}" @selected($rule->risk_level === $lvl)>{{ $lvl }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="config-field">
                                                    <label>Poids score</label>
                                                    <input class="form-control" type="number" name="score_weight"
                                                           value="{{ $rule->score_weight }}" min="0" max="1000">
                                                </div>
                                                <div class="config-field">
                                                    <label>État</label>
                                                    <select class="form-control" name="is_enabled">
                                                        <option value="1" @selected($rule->is_enabled)>active</option>
                                                        <option value="0" @selected(!$rule->is_enabled)>inactive</option>
                                                    </select>
                                                </div>
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
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    </div>
@endsection
