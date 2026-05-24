@extends('layouts.soc')

@section('title', 'RansomShield — Politiques de protection')
@section('page_title', 'Politiques de protection')
@section('page_subtitle', 'Réponses proposées selon le niveau de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')
    @include('platform.partials.config-premium-style')

    @php
        // ── Helpers ────────────────────────────────────────────────
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

        $riskIcon = fn ($r) => match ($r) {
            'critical' => 'fa-radiation',
            'high'     => 'fa-shield-halved',
            'suspect'  => 'fa-eye',
            default    => 'fa-circle-dot',
        };

        // 3. Mode d'exécution — couleurs propres, indépendantes du risque
        $modeLabel = fn ($m) => match ($m) {
            'automatic'         => 'Automatique',
            'approval_required' => 'Approbation requise',
            'manual'            => 'Manuel',
            'manual_only'       => 'Manuel uniquement',
            default             => $m,
        };
        $modeIcon = fn ($m) => match ($m) {
            'automatic'         => 'fa-bolt',
            'approval_required' => 'fa-user-check',
            'manual','manual_only' => 'fa-hand',
            default             => 'fa-question',
        };
        $modeCss = fn ($m) => match ($m) {
            'automatic'         => 'badge-mode-auto',
            'approval_required' => 'badge-mode-approval',
            'manual','manual_only' => 'badge-mode-manual',
            default             => 'badge',
        };

        // 1. Icône principale dérivée des flags booléens
        $policyIcon = function ($p) {
            if ($p->isolate_host && $p->emergency_backup) return 'fa-shield-virus';
            if ($p->isolate_host)                         return 'fa-plug-circle-xmark';
            if ($p->kill_process)                         return 'fa-skull';
            if ($p->emergency_backup && $p->lock_safe_copy
                && $p->restrict_path)                     return 'fa-vault';
            if ($p->emergency_backup && $p->lock_safe_copy) return 'fa-cloud-arrow-up';
            if ($p->emergency_backup)                     return 'fa-cloud-arrow-up';
            if ($p->restrict_path)                        return 'fa-folder-xmark';
            return 'fa-bell'; // alert_only or default
        };

        // 1. Définition des 6 flags avec libellé, icône, couleur active
        $flagDefs = [
            ['key' => 'alert_only',       'label' => 'Alerte',         'icon' => 'fa-bell',              'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
            ['key' => 'emergency_backup', 'label' => 'Backup urgence', 'icon' => 'fa-cloud-arrow-up',    'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.12)'],
            ['key' => 'lock_safe_copy',   'label' => 'Copie sûre',     'icon' => 'fa-lock',              'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.12)'],
            ['key' => 'isolate_host',     'label' => 'Isolation',      'icon' => 'fa-plug-circle-xmark', 'color' => '#ef4444', 'bg' => 'rgba(239, 68, 68,.12)'],
            ['key' => 'kill_process',     'label' => 'Kill processus', 'icon' => 'fa-skull',             'color' => '#dc2626', 'bg' => 'rgba(220, 38, 38,.12)'],
            ['key' => 'restrict_path',    'label' => 'Restriction',    'icon' => 'fa-folder-xmark',      'color' => '#f97316', 'bg' => 'rgba(249,115, 22,.12)'],
        ];

        // Stats pour mini-grid
        $total    = $policies->count();
        $active   = $policies->where('is_enabled', true)->count();
        $approval = $policies->where('execution_mode', 'approval_required')->count();
        $auto     = $policies->whereIn('execution_mode', ['automatic'])->count();
    @endphp

    <style>
        /* ══════════════════════════════════════════════════════════
           3. Badges mode d'exécution — couleurs propres
           ══════════════════════════════════════════════════════════ */
        .badge-mode-auto {
            background: rgba(34,197,94,.10);
            color: #16a34a;
            border: 1px solid rgba(34,197,94,.25);
        }
        .badge-mode-approval {
            background: rgba(99,102,241,.10);
            color: #818cf8;
            border: 1px solid rgba(99,102,241,.25);
        }
        .badge-mode-manual {
            background: rgba(245,158,11,.10);
            color: #d97706;
            border: 1px solid rgba(245,158,11,.25);
        }

        /* ══════════════════════════════════════════════════════════
           4. Icône + accent couleur par niveau (pattern rules/thresholds)
           ══════════════════════════════════════════════════════════ */
        .policy-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 20px;
            flex-shrink: 0;
        }
        .policy-icon-normal   { background: rgba(107,114,128,.10); color:#6b7280;  border:1px solid rgba(107,114,128,.20); }
        .policy-icon-suspect  { background: rgba(234,179,  8,.10); color:#ca8a04;  border:1px solid rgba(234,179,  8,.22); }
        .policy-icon-high     { background: rgba(249,115, 22,.10); color:#ea580c;  border:1px solid rgba(249,115, 22,.22); }
        .policy-icon-critical { background: rgba(239, 68, 68,.10); color:#dc2626;  border:1px solid rgba(239, 68, 68,.22); }

        .policy-card-layout {
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            align-items: flex-start;
        }

        /* Accent bord gauche */
        .policy-card-normal   { border-left: 3px solid #6b7280 !important; }
        .policy-card-suspect  { border-left: 3px solid #eab308 !important; }
        .policy-card-high     { border-left: 3px solid #f97316 !important; }
        .policy-card-critical { border-left: 3px solid #ef4444 !important; }

        /* Override blob ::after */
        .policy-card-normal::after   { background: rgba(107,114,128,.09) !important; }
        .policy-card-suspect::after  { background: rgba(234,179,  8,.09) !important; }
        .policy-card-high::after     { background: rgba(249,115, 22,.09) !important; }
        .policy-card-critical::after { background: rgba(239, 68, 68,.09) !important; }

        /* ══════════════════════════════════════════════════════════
           2. En-tête de section par niveau de risque
           ══════════════════════════════════════════════════════════ */
        .policy-section-header {
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
        .policy-section-header .count-pill {
            margin-left: auto;
            font-size: .72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            opacity: .75;
        }
        .psh-critical { background:rgba(239,68,68,.08);  color:#ef4444; border-color:rgba(239,68,68,.18); }
        .psh-critical .count-pill { background:rgba(239,68,68,.15); color:#ef4444; }
        .psh-high     { background:rgba(249,115,22,.08); color:#f97316; border-color:rgba(249,115,22,.18); }
        .psh-high     .count-pill { background:rgba(249,115,22,.15); color:#f97316; }
        .psh-suspect  { background:rgba(234,179,8,.08);  color:#ca8a04; border-color:rgba(234,179,8,.18); }
        .psh-suspect  .count-pill { background:rgba(234,179,8,.15);  color:#ca8a04; }
        .psh-normal   { background:rgba(107,114,128,.06);color:#6b7280; border-color:rgba(107,114,128,.15); }
        .psh-normal   .count-pill { background:rgba(107,114,128,.12);color:#6b7280; }

        /* ══════════════════════════════════════════════════════════
           1. Flags d'action booléens
           ══════════════════════════════════════════════════════════ */
        .flag-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: .75rem 0;
        }
        .flag-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            border: 1px solid transparent;
            transition: opacity .15s;
        }
        .flag-pill-active  { opacity: 1; }
        .flag-pill-inactive {
            background: rgba(255,255,255,.03) !important;
            color: rgba(255,255,255,.2) !important;
            border-color: rgba(255,255,255,.06) !important;
        }
        .flag-pill-inactive i { opacity: .4; }

        /* ══════════════════════════════════════════════════════════
           5. Stats mini-grid icons (cohérence)
           ══════════════════════════════════════════════════════════ */
        .config-mini-with-icon      { display:flex; flex-direction:column; gap:8px; }
        .config-mini-icon-row       { display:flex; align-items:center; gap:8px; }
        .config-mini-icon {
            width:32px; height:32px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:14px; flex-shrink:0;
        }

        @media (max-width: 720px) {
            .policy-card-layout { grid-template-columns: 1fr; }
        }
    </style>

    <div class="animated-page">

        {{-- ── Hero ─────────────────────────────────────────────── --}}
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Réponse automatisée contrôlée
            </div>
            <h2>Décider quoi faire après détection.</h2>
            <p>
                Les politiques déterminent la réponse proposée selon le niveau de risque :
                notification, restriction, isolation, action manuelle.
                Les actions sensibles restent sous validation humaine.
            </p>
            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="action-btn primary">
                    <i class="fa-solid fa-diagram-project"></i> Centre configuration
                </a>
                <a href="{{ route('platform.approval-queue.index') }}" class="action-btn">
                    <i class="fa-solid fa-inbox"></i> File d'approbation
                </a>
                <a href="{{ route('platform.detection-thresholds.index') }}" class="action-btn">
                    <i class="fa-solid fa-gauge-high"></i> Seuils
                </a>
            </div>
        </section>

        {{-- ── 5. Stats mini-grid avec icônes ─────────────────────── --}}
        <section class="config-mini-grid section-gap">

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(255,255,255,.06);color:var(--text-muted,#6b7280)">
                        <i class="fa-solid fa-shield"></i>
                    </div>
                    <small>Politiques</small>
                </div>
                <strong>{{ $total }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(34,197,94,.10);color:#16a34a">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <small>Actives</small>
                </div>
                <strong>{{ $active }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(99,102,241,.10);color:#818cf8">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <small>Approbation requise</small>
                </div>
                <strong style="color:#818cf8">{{ $approval }}</strong>
            </div>

            <div class="config-mini config-mini-with-icon">
                <div class="config-mini-icon-row">
                    <div class="config-mini-icon" style="background:rgba(34,197,94,.10);color:#16a34a">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <small>Automatiques</small>
                </div>
                <strong>{{ $auto }}</strong>
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

        {{-- ── 2. Sections groupées par niveau de risque ───────────── --}}
        @forelse ($groups as $level => $levelPolicies)
            @php
                $color   = $riskColor($level);
                $icon    = $riskIcon($level);
                $pshCss  = 'psh-'.$level;
            @endphp

            <section class="section-gap">

                {{-- En-tête de section --}}
                <div class="policy-section-header {{ $pshCss }}">
                    <i class="fa-solid {{ $icon }}"></i>
                    {{ strtoupper($level) }}
                    <span class="count-pill">{{ $levelPolicies->count() }} politique{{ $levelPolicies->count() > 1 ? 's' : '' }}</span>
                </div>

                <div class="config-grid">
                    @foreach ($levelPolicies as $policy)
                        @php
                            $rc    = $policy->risk_level ?? 'normal';
                            $color = $riskColor($rc);
                            $icon  = $policyIcon($policy);
                        @endphp

                        <article class="config-card policy-card-{{ $rc }}">
                            <div class="policy-card-layout">

                                {{-- 4. Icône dérivée des flags --}}
                                <div class="policy-icon policy-icon-{{ $rc }}">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </div>

                                <div>
                                    {{-- En-tête carte --}}
                                    <div class="config-card-head">
                                        <div>
                                            <h3 class="config-title">{{ $policy->name }}</h3>
                                            <div class="config-subtitle mono">{{ $policy->code }}</div>
                                        </div>
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px">
                                            <span class="badge {{ $riskClass($rc) }}">{{ $rc }}</span>
                                            @if ($policy->scope)
                                                <span class="badge" style="font-size:.68rem;background:rgba(255,255,255,.06);color:var(--text-muted,#6b7280)">
                                                    <i class="fa-solid fa-crosshairs" style="font-size:.6rem"></i>
                                                    {{ $policy->scope }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- 6. Description DB --}}
                                    @if ($policy->description)
                                        <p style="font-size:.79rem;color:var(--text-muted,#6b7280);margin:.2rem 0 .5rem;line-height:1.55">
                                            {{ $policy->description }}
                                        </p>
                                    @endif

                                    {{-- 1. Flags d'action booléens --}}
                                    <div class="flag-pills">
                                        @foreach ($flagDefs as $flag)
                                            @php $isActive = (bool) $policy->{$flag['key']}; @endphp
                                            <span class="flag-pill {{ $isActive ? 'flag-pill-active' : 'flag-pill-inactive' }}"
                                                  style="{{ $isActive ? 'background:'.$flag['bg'].';color:'.$flag['color'].';border-color:'.str_replace('.12)', '.28)', $flag['bg']).';' : '' }}"
                                                  title="{{ $isActive ? 'Actif' : 'Inactif' }} : {{ $flag['label'] }}">
                                                <i class="fa-solid {{ $flag['icon'] }}"></i>
                                                {{ $flag['label'] }}
                                            </span>
                                        @endforeach
                                    </div>

                                    {{-- 3. Mode d'exécution --}}
                                    <div style="margin-bottom:.6rem">
                                        <span class="badge {{ $modeCss($policy->execution_mode) }}">
                                            <i class="fa-solid {{ $modeIcon($policy->execution_mode) }}" style="font-size:.65rem"></i>
                                            {{ $modeLabel($policy->execution_mode) }}
                                        </span>
                                    </div>

                                    {{-- Formulaire --}}
                                    <form method="POST"
                                          action="{{ route('platform.protection-policies.update', $policy) }}"
                                          class="config-form">
                                        @csrf
                                        @method('PUT')

                                        <div class="config-form-row">
                                            <div class="config-field">
                                                <label>Risque</label>
                                                <select class="form-control" name="risk_level">
                                                    @foreach (['normal','suspect','high','critical'] as $lvl)
                                                        <option value="{{ $lvl }}" @selected($policy->risk_level === $lvl)>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="config-field">
                                                <label>Mode</label>
                                                <select class="form-control" name="execution_mode">
                                                    <option value="automatic"          @selected($policy->execution_mode === 'automatic')>Automatique</option>
                                                    <option value="approval_required"  @selected($policy->execution_mode === 'approval_required')>Approbation requise</option>
                                                    <option value="manual"             @selected($policy->execution_mode === 'manual')>Manuel</option>
                                                    <option value="manual_only"        @selected($policy->execution_mode === 'manual_only')>Manuel uniquement</option>
                                                </select>
                                            </div>
                                            <div class="config-field">
                                                <label>État</label>
                                                <select class="form-control" name="is_enabled">
                                                    <option value="1" @selected($policy->is_enabled)>active</option>
                                                    <option value="0" @selected(!$policy->is_enabled)>inactive</option>
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
            </section>
        @empty
            @include('platform.partials.empty-state', [
                'title'   => 'Aucune politique.',
                'message' => 'Restaure les valeurs par défaut pour recréer les politiques.'
            ])
        @endforelse

    </div>
@endsection
