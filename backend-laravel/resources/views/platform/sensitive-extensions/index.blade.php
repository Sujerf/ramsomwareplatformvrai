@extends('layouts.soc')

@section('title', 'RansomShield — Extensions sensibles')
@section('page_title', 'Extensions sensibles')
@section('page_subtitle', 'Extensions à surveiller pour détecter les comportements ransomware')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')
    @include('platform.partials.config-premium-style')

    @php
        $riskClass = fn ($r) => match ($r) {
            'critical' => 'badge-critical',
            'high'     => 'badge-high',
            'suspect'  => 'badge-suspect',
            default    => 'badge-normal',
        };

        $riskColor = fn ($r) => match ($r) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'suspect'  => '#eab308',
            default    => '#6b7280',
        };

        $riskBg = fn ($r) => match ($r) {
            'critical' => 'rgba(239,68,68,.09)',
            'high'     => 'rgba(249,115,22,.09)',
            'suspect'  => 'rgba(234,179,8,.09)',
            default    => 'rgba(107,114,128,.07)',
        };

        $riskIcon = fn ($r) => match ($r) {
            'critical' => 'fa-radiation',
            'high'     => 'fa-shield-halved',
            'suspect'  => 'fa-eye',
            default    => 'fa-circle-dot',
        };

        // Stats
        $totalCount    = $extensions->count();
        $activeCount   = $extensions->where('is_enabled', true)->count();
        $criticalCount = $extensions->where('risk_level', 'critical')->count();
        $highCount     = $extensions->where('risk_level', 'high')->count();
        $suspectCount  = $extensions->where('risk_level', 'suspect')->count();
    @endphp

    <style>
        /* ══════════════════════════════════════════════════════
           1. Extension "tech pill" proéminente
           ══════════════════════════════════════════════════════ */
        .ext-hero {
            position: relative;
            z-index: 1;
            padding: 16px 18px 12px;
            border-radius: 18px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .ext-hero::after {
            content: '';
            position: absolute;
            right: -28px; top: -28px;
            width: 100px; height: 100px;
            border-radius: 999px;
            opacity: .35;
            pointer-events: none;
        }
        .ext-name {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', ui-monospace, monospace;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1;
            margin-bottom: 6px;
        }
        .ext-meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ══════════════════════════════════════════════════════
           2. Score meter calibré + coloré
           ══════════════════════════════════════════════════════ */
        .score-meter {
            height: 7px;
            border-radius: 999px;
            background: rgba(255,255,255,.07);
            overflow: hidden;
            margin: 8px 0 3px;
        }
        .score-meter-fill { height: 100%; border-radius: 999px; }
        .score-meter-legend {
            display: flex;
            justify-content: space-between;
            font-size: .64rem;
            color: var(--text-muted, #6b7280);
            font-variant-numeric: tabular-nums;
            margin-bottom: 10px;
        }

        /* ══════════════════════════════════════════════════════
           3. Bouton supprimer
           ══════════════════════════════════════════════════════ */
        .action-btn-danger {
            background: rgba(239,68,68,.10);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,.25);
        }
        .action-btn-danger:hover {
            background: rgba(239,68,68,.18);
        }

        /* ══════════════════════════════════════════════════════
           5. Accent bord gauche + blob ::after par niveau
           ══════════════════════════════════════════════════════ */
        .ext-card-normal   { border-left: 3px solid #6b7280 !important; }
        .ext-card-suspect  { border-left: 3px solid #eab308 !important; }
        .ext-card-high     { border-left: 3px solid #f97316 !important; }
        .ext-card-critical { border-left: 3px solid #ef4444 !important; }

        .ext-card-normal::after   { background: rgba(107,114,128,.09) !important; }
        .ext-card-suspect::after  { background: rgba(234,179,  8,.09) !important; }
        .ext-card-high::after     { background: rgba(249,115, 22,.09) !important; }
        .ext-card-critical::after { background: rgba(239, 68, 68,.09) !important; }

        /* ══════════════════════════════════════════════════════
           6. Stats mini-grid icons
           ══════════════════════════════════════════════════════ */
        .config-mini-with-icon { display:flex; flex-direction:column; gap:8px; }
        .config-mini-icon-row  { display:flex; align-items:center; gap:8px; }
        .config-mini-icon {
            width:32px; height:32px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:14px; flex-shrink:0;
        }

        /* ══════════════════════════════════════════════════════
           7. En-têtes de section par niveau (même pattern policies)
           ══════════════════════════════════════════════════════ */
        .ext-section-header {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem 1rem;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        .ext-section-header .count-pill {
            margin-left: auto;
            font-size: .72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            opacity: .75;
        }
        .esh-critical { background:rgba(239,68,68,.08);  color:#ef4444; border-color:rgba(239,68,68,.18); }
        .esh-critical .count-pill { background:rgba(239,68,68,.15); color:#ef4444; }
        .esh-high     { background:rgba(249,115,22,.08); color:#f97316; border-color:rgba(249,115,22,.18); }
        .esh-high     .count-pill { background:rgba(249,115,22,.15); color:#f97316; }
        .esh-suspect  { background:rgba(234,179,8,.08);  color:#ca8a04; border-color:rgba(234,179,8,.18); }
        .esh-suspect  .count-pill { background:rgba(234,179,8,.15);  color:#ca8a04; }

        /* ══════════════════════════════════════════════════════
           8. Formulaire "Ajouter" différencié
           ══════════════════════════════════════════════════════ */
        .create-card {
            border: 1px dashed rgba(34,197,94,.3) !important;
            background: rgba(34,197,94,.03) !important;
        }
        .create-card::after { display: none !important; }
        .create-header {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .create-icon {
            width: 40px; height: 40px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px;
            background: rgba(34,197,94,.12);
            color: #16a34a;
            border: 1px solid rgba(34,197,94,.25);
            flex-shrink: 0;
        }
        .create-desc-row { margin-top: .5rem; }
        .create-desc-row textarea { width:100%; min-height:54px; resize:vertical; }

        /* Grille 3 colonnes pour les cartes compactes */
        .ext-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 14px;
        }

        @media (max-width: 1200px) { .ext-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 720px)  { .ext-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="animated-page">

        {{-- ── Hero ──────────────────────────────────────────────── --}}
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Extensions ransomware
            </div>
            <h2>Définir les extensions à risque.</h2>
            <p>
                Ces extensions alimentent directement <code>analyzeSensitiveExtension()</code>
                dans le moteur de détection. Plus le poids est élevé, plus l'événement
                contribue au score final, indépendamment des règles de détection.
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

        {{-- ── 6. Stats mini-grid avec icônes ───────────────────── --}}
        <section class="config-mini-grid section-gap">

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(255,255,255,.06);color:var(--text-muted,#6b7280)">
                        <i class="fa-solid fa-file-code"></i>
                    </div>
                    <small>Extensions actives</small>
                </div>
                <strong>{{ $activeCount }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(239,68,68,.10);color:#dc2626">
                        <i class="fa-solid fa-radiation"></i>
                    </div>
                    <small>Critical</small>
                </div>
                <strong style="color:#dc2626">{{ $criticalCount }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(249,115,22,.10);color:#ea580c">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <small>High</small>
                </div>
                <strong style="color:#ea580c">{{ $highCount }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(234,179,8,.10);color:#ca8a04">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <small>Suspect</small>
                </div>
                <strong style="color:#ca8a04">{{ $suspectCount }}</strong>
            </div>

        </section>

        {{-- ── Flash ─────────────────────────────────────────────── --}}
        @if (session('success'))
            <div style="padding:.75rem 1rem;border-radius:8px;background:rgba(34,197,94,.08);
                        border:1px solid rgba(34,197,94,.2);color:#16a34a;font-size:.85rem;
                        margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        {{-- ── 8. Formulaire "Ajouter" — admin uniquement ─────── --}}
        @if(auth()->user()->isAdmin())
        <section class="section-gap">
            <article class="config-card create-card">
                <div class="create-header">
                    <div class="create-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="config-title" style="font-size:16px">Ajouter une extension</h3>
                        <div class="config-subtitle">Exemple : <code>locked</code>, <code>encrypted</code>, <code>crypt</code>, <code>enc</code></div>
                    </div>
                </div>

                <form method="POST" action="{{ route('platform.sensitive-extensions.store') }}" class="config-form">
                    @csrf

                    <div class="config-form-row">
                        <div class="config-field">
                            <label>Extension <small style="color:var(--text-muted)">(sans le point)</small></label>
                            <input class="form-control" type="text" name="extension"
                                   placeholder="locked" required maxlength="50">
                        </div>
                        <div class="config-field">
                            <label>Niveau de risque</label>
                            <select class="form-control" name="risk_level">
                                <option value="critical">critical</option>
                                <option value="high">high</option>
                                <option value="suspect">suspect</option>
                            </select>
                        </div>
                        <div class="config-field">
                            <label>Poids score</label>
                            <input class="form-control" type="number" name="score_weight"
                                   value="50" min="0" max="1000" required>
                        </div>
                    </div>

                    {{-- 10. Champ description dans le formulaire create --}}
                    <div class="create-desc-row">
                        <label style="font-size:.8rem;display:block;margin-bottom:.3rem;
                                      color:var(--text-muted,#6b7280);font-weight:700;
                                      text-transform:uppercase;letter-spacing:.06em">
                            Description <small style="text-transform:none;letter-spacing:0">(optionnelle)</small>
                        </label>
                        <textarea class="form-control" name="description" rows="2"
                                  maxlength="300"
                                  placeholder="Contexte : famille de ransomware connue, usage légitime rare…"></textarea>
                    </div>

                    <div class="config-actions" style="margin-top:.75rem">
                        <button class="action-btn primary" type="submit">
                            <i class="fa-solid fa-plus"></i> Ajouter / Mettre à jour
                        </button>
                    </div>
                </form>
            </article>
        </section>
        @endif {{-- end @if(auth()->user()->isAdmin()) add extension --}}

        {{-- ── 7. Sections groupées par niveau ──────────────────── --}}
        @forelse ($groups as $level => $levelExts)
            @php
                $color  = $riskColor($level);
                $bg     = $riskBg($level);
                $icon   = $riskIcon($level);
                $eshCss = 'esh-'.$level;
            @endphp

            <section class="section-gap">

                {{-- En-tête de section --}}
                <div class="ext-section-header {{ $eshCss }}">
                    <i class="fa-solid {{ $icon }}"></i>
                    {{ strtoupper($level) }}
                    <span class="count-pill">
                        {{ $levelExts->count() }} extension{{ $levelExts->count() > 1 ? 's' : '' }}
                    </span>
                </div>

                {{-- Grille 3 colonnes --}}
                <div class="ext-grid">
                    @foreach ($levelExts as $ext)
                        @php
                            $rc           = $ext->risk_level ?? 'normal';
                            $extColor     = $riskColor($rc);
                            $extBg        = $riskBg($rc);
                            $scorePercent = round($ext->score_weight / $maxScore * 100);
                        @endphp

                        <article class="config-card ext-card-{{ $rc }}">

                            {{-- 1. Extension "tech pill" + fond teinté --}}
                            <div class="ext-hero"
                                 style="background: {{ $extBg }};
                                        border: 1px solid {{ str_replace('.09)', '.20)', $extBg) }}">
                                <div class="ext-name" style="color: {{ $extColor }}">
                                    .{{ $ext->extension }}
                                </div>
                                <div class="ext-meta-row">
                                    <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                                    <span class="score-pill" style="font-size:.72rem; padding:4px 10px">
                                        <i class="fa-solid fa-weight-hanging" style="font-size:.6rem"></i>
                                        {{ $ext->score_weight }} pts
                                    </span>
                                    @if (! $ext->is_enabled)
                                        <span class="badge" style="background:rgba(107,114,128,.12);color:#6b7280;border:1px solid rgba(107,114,128,.22)">
                                            inactive
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- 2. Score meter calibré + coloré --}}
                            <div class="score-meter">
                                <div class="score-meter-fill"
                                     style="width:{{ $scorePercent }}%; background:{{ $extColor }};"></div>
                            </div>
                            <div class="score-meter-legend">
                                <span>0</span>
                                <span style="color:{{ $extColor }};font-weight:700">
                                    {{ $ext->score_weight }} / {{ $maxScore }} pts ({{ $scorePercent }}%)
                                </span>
                                <span>{{ $maxScore }}</span>
                            </div>

                            {{-- 4. Description DB --}}
                            @if (! empty($ext->description))
                                <p style="font-size:.78rem;color:var(--text-muted,#6b7280);
                                          margin:.2rem 0 .75rem;line-height:1.5">
                                    {{ $ext->description }}
                                </p>
                            @else
                                <p style="font-size:.75rem;color:rgba(107,114,128,.4);
                                          margin:.2rem 0 .75rem;font-style:italic">
                                    Aucune description — modifiez et sauvegardez pour en ajouter.
                                </p>
                            @endif

                            {{-- Formulaire édition --}}
                            <form method="POST"
                                  action="{{ route('platform.sensitive-extensions.update', $ext) }}"
                                  class="config-form">
                                @csrf
                                @method('PUT')

                                <div class="config-form-row">
                                    <div class="config-field">
                                        <label>Niveau</label>
                                        <select class="form-control" name="risk_level">
                                            @foreach (['suspect','high','critical'] as $lvl)
                                                <option value="{{ $lvl }}" @selected($ext->risk_level === $lvl)>{{ $lvl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="config-field">
                                        <label>Poids</label>
                                        <input class="form-control" type="number" name="score_weight"
                                               value="{{ $ext->score_weight }}" min="0" max="1000">
                                    </div>
                                    <div class="config-field">
                                        <label>État</label>
                                        <select class="form-control" name="is_enabled">
                                            <option value="1" @selected($ext->is_enabled)>active</option>
                                            <option value="0" @selected(!$ext->is_enabled)>inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="config-actions">
                                    <button class="action-btn primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                                    </button>
                                </div>
                            </form>

                            {{-- 3. Bouton supprimer --}}
                            <form method="POST"
                                  action="{{ route('platform.sensitive-extensions.destroy', $ext) }}"
                                  class="config-form"
                                  style="margin-top:6px"
                                  onsubmit="return confirm('Supprimer .{{ $ext->extension }} ? Cette action est irréversible.')">
                                @csrf
                                @method('DELETE')
                                <button class="action-btn action-btn-danger" type="submit" style="width:100%">
                                    <i class="fa-solid fa-trash-can"></i> Supprimer
                                </button>
                            </form>

                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            @include('platform.partials.empty-state', [
                'title'   => 'Aucune extension sensible.',
                'message' => 'Ajoute une extension ou restaure les valeurs par défaut.'
            ])
        @endforelse

    </div>
@endsection
