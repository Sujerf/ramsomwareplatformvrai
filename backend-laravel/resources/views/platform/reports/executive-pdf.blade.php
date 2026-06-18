<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Exécutif SOC — {{ $periodLabel }}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:DejaVu Sans, sans-serif; font-size:10.5px; color:#1e293b; background:#fff; }

    /* ── Header ── */
    .header { background:#0a1628; color:#e2eaf5; padding:22px 28px 18px; }
    .header-top { display:table; width:100%; }
    .header-left  { display:table-cell; vertical-align:top; }
    .header-right { display:table-cell; vertical-align:top; text-align:right; }
    .brand { font-size:20px; font-weight:bold; }
    .brand span { color:#38bdf8; }
    .brand-sub { font-size:8px; text-transform:uppercase; letter-spacing:2px; color:#5d7a99; margin-top:2px; }
    .report-type { font-size:12px; font-weight:bold; color:#e2eaf5; margin-top:12px; }
    .report-period { font-size:10px; color:#5d7a99; margin-top:3px; }
    .report-meta { font-size:9px; color:#5d7a99; }

    /* ── Sections ── */
    .section { margin:16px 0; }
    .section-title { font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:1px;
        color:#5d7a99; border-bottom:1px solid #e2e8f0; padding-bottom:4px; margin-bottom:10px; }

    /* ── Stats grid ── */
    .stat-grid { display:table; width:100%; border-collapse:separate; border-spacing:6px; }
    .stat-cell { display:table-cell; background:#f8fafc; border:1px solid #e2e8f0;
        border-radius:6px; padding:10px 12px; text-align:center; vertical-align:middle; }
    .stat-value { font-size:22px; font-weight:bold; line-height:1; }
    .stat-label { font-size:8px; text-transform:uppercase; letter-spacing:.5px;
        color:#64748b; margin-top:3px; }

    .col-red    .stat-value { color:#dc2626; }
    .col-orange .stat-value { color:#ea580c; }
    .col-amber  .stat-value { color:#d97706; }
    .col-green  .stat-value { color:#16a34a; }
    .col-blue   .stat-value { color:#2563eb; }
    .col-purple .stat-value { color:#7c3aed; }

    /* ── Table ── */
    table.data { width:100%; border-collapse:collapse; font-size:9.5px; margin-top:6px; }
    table.data th { background:#f1f5f9; color:#475569; font-weight:bold; text-align:left;
        padding:5px 8px; border:1px solid #e2e8f0; }
    table.data td { padding:4px 8px; border:1px solid #e2e8f0; vertical-align:middle; }
    table.data tr:nth-child(even) td { background:#f8fafc; }

    /* ── Risk badges ── */
    .badge { display:inline-block; padding:1px 7px; border-radius:4px; font-size:8.5px;
        font-weight:bold; text-transform:uppercase; }
    .badge-critical { background:#fee2e2; color:#b91c1c; }
    .badge-high     { background:#ffedd5; color:#c2410c; }
    .badge-suspect  { background:#fef9c3; color:#854d0e; }
    .badge-normal   { background:#dcfce7; color:#166534; }
    .badge-resolved { background:#dcfce7; color:#166534; }
    .badge-open     { background:#fee2e2; color:#b91c1c; }

    /* ── Alert box ── */
    .alert-box { padding:8px 12px; border-radius:6px; font-size:9.5px; margin-bottom:10px; }
    .alert-box.critical { background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; }
    .alert-box.info     { background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; }
    .alert-box.success  { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }

    /* ── Two-column layout ── */
    .two-col { display:table; width:100%; border-spacing:8px; border-collapse:separate; }
    .col-left  { display:table-cell; width:50%; vertical-align:top; }
    .col-right { display:table-cell; width:50%; vertical-align:top; }

    /* ── Mini stat row ── */
    .mini-stat { display:table; width:100%; margin-bottom:4px; }
    .mini-label { display:table-cell; color:#64748b; }
    .mini-value { display:table-cell; font-weight:bold; text-align:right; }

    /* ── Footer ── */
    .footer { margin-top:20px; padding-top:10px; border-top:1px solid #e2e8f0;
        font-size:8.5px; color:#94a3b8; text-align:center; }

    .page-break { page-break-before:always; }
</style>
</head>
<body>

{{-- ══ HEADER ══════════════════════════════════════════════════════════════ --}}
<div class="header">
    <div class="header-top">
        <div class="header-left">
            <div class="brand">Ransom<span>Shield</span></div>
            <div class="brand-sub">Security Operations Center</div>
            <div class="report-type">Rapport exécutif SOC — {{ ucfirst($frequency === 'monthly' ? 'Mensuel' : 'Hebdomadaire') }}</div>
            <div class="report-period">Période : {{ $periodLabel }}</div>
        </div>
        <div class="header-right">
            <div class="report-meta">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
            <div class="report-meta" style="margin-top:4px;">{{ now()->format('l') }}</div>
        </div>
    </div>
</div>

{{-- ══ VUE D'ENSEMBLE ═══════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Vue d'ensemble — {{ $periodLabel }}</div>

    @if($data['incidents']['critical'] > 0)
    <div class="alert-box critical">
        ⚠  {{ $data['incidents']['critical'] }} incident(s) de niveau CRITIQUE détecté(s) sur la période.
        Vérifiez l'état des systèmes concernés.
    </div>
    @elseif($data['incidents']['total'] === 0)
    <div class="alert-box success">
        ✓  Aucun incident détecté sur la période. L'infrastructure est sécurisée.
    </div>
    @else
    <div class="alert-box info">
        {{ $data['incidents']['total'] }} incident(s) détecté(s) sur la période.
        {{ $data['incidents']['resolved'] }} résolu(s), {{ $data['incidents']['open'] }} en cours.
    </div>
    @endif

    <div class="stat-grid">
        <div class="stat-cell col-red">
            <div class="stat-value">{{ $data['incidents']['total'] }}</div>
            <div class="stat-label">Incidents</div>
        </div>
        <div class="stat-cell col-orange">
            <div class="stat-value">{{ $data['alerts']['total'] }}</div>
            <div class="stat-label">Alertes</div>
        </div>
        <div class="stat-cell col-green">
            <div class="stat-value">{{ $data['agents']['online'] }}</div>
            <div class="stat-label">Agents actifs</div>
        </div>
        <div class="stat-cell col-blue">
            <div class="stat-value">{{ $data['actions']['executed'] }}</div>
            <div class="stat-label">Actions exécutées</div>
        </div>
        <div class="stat-cell col-purple">
            <div class="stat-value">{{ $data['audit']['operator_actions'] }}</div>
            <div class="stat-label">Actions opérateur</div>
        </div>
    </div>
</div>

{{-- ══ INCIDENTS ════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Incidents ({{ $data['incidents']['total'] }} sur la période)</div>

    <div class="two-col">
        <div class="col-left">
            <div class="mini-stat"><span class="mini-label">Critiques</span><span class="mini-value" style="color:#dc2626;">{{ $data['incidents']['critical'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Élevés</span>  <span class="mini-value" style="color:#ea580c;">{{ $data['incidents']['high'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Suspects</span><span class="mini-value" style="color:#d97706;">{{ $data['incidents']['suspect'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Normaux</span> <span class="mini-value" style="color:#16a34a;">{{ $data['incidents']['normal'] }}</span></div>
        </div>
        <div class="col-right">
            <div class="mini-stat"><span class="mini-label">Résolus</span>         <span class="mini-value" style="color:#16a34a;">{{ $data['incidents']['resolved'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">En cours</span>        <span class="mini-value" style="color:#ea580c;">{{ $data['incidents']['open'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Faux positifs</span>   <span class="mini-value">{{ $data['incidents']['false_positive'] }}</span></div>
            @if($data['incidents']['mttr_hours'] !== null)
            <div class="mini-stat"><span class="mini-label">MTTR</span>            <span class="mini-value">{{ $data['incidents']['mttr_hours'] }}h</span></div>
            @endif
        </div>
    </div>

    @if(count($data['incidents']['recent']) > 0)
    <table class="data" style="margin-top:8px;">
        <thead><tr>
            <th>Titre</th><th style="width:70px;">Niveau</th>
            <th style="width:80px;">Statut</th><th style="width:100px;">Détecté le</th>
        </tr></thead>
        <tbody>
            @foreach($data['incidents']['recent'] as $inc)
            <tr>
                <td>{{ $inc['title'] }}</td>
                <td><span class="badge badge-{{ $inc['risk_level'] }}">{{ strtoupper($inc['risk_level']) }}</span></td>
                <td>{{ $inc['status'] }}</td>
                <td>{{ $inc['detected_at'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- ══ ALERTES ══════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Alertes ({{ $data['alerts']['total'] }} sur la période)</div>

    <div class="two-col">
        <div class="col-left">
            <div class="mini-stat"><span class="mini-label">Critiques</span><span class="mini-value" style="color:#dc2626;">{{ $data['alerts']['critical'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Élevées</span>  <span class="mini-value" style="color:#ea580c;">{{ $data['alerts']['high'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Suspectes</span><span class="mini-value" style="color:#d97706;">{{ $data['alerts']['suspect'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Normales</span> <span class="mini-value" style="color:#16a34a;">{{ $data['alerts']['normal'] }}</span></div>
        </div>
        <div class="col-right">
            <div class="mini-stat"><span class="mini-label">Résolues</span>      <span class="mini-value" style="color:#16a34a;">{{ $data['alerts']['resolved'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Actives</span>       <span class="mini-value" style="color:#ea580c;">{{ $data['alerts']['active'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Faux positifs</span> <span class="mini-value">{{ $data['alerts']['false_positive'] }}</span></div>
            @if($data['alerts']['total'] > 0)
            <div class="mini-stat"><span class="mini-label">Taux FP</span>       <span class="mini-value">{{ round($data['alerts']['false_positive'] / $data['alerts']['total'] * 100, 1) }}%</span></div>
            @endif
        </div>
    </div>
</div>

{{-- ══ AGENTS ═══════════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">État des agents ({{ $data['agents']['total'] }} au total)</div>

    <div class="two-col">
        <div class="col-left">
            <div class="mini-stat"><span class="mini-label">En ligne</span>     <span class="mini-value" style="color:#16a34a;">{{ $data['agents']['online'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Hors-ligne</span>   <span class="mini-value" style="color:#ef4444;">{{ $data['agents']['offline'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Compromis</span>    <span class="mini-value" style="color:#dc2626;">{{ $data['agents']['compromised'] }}</span></div>
        </div>
        <div class="col-right">
            <div class="mini-stat"><span class="mini-label">Risque critique</span><span class="mini-value" style="color:#dc2626;">{{ $data['agents']['risk_critical'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Risque élevé</span>  <span class="mini-value" style="color:#ea580c;">{{ $data['agents']['risk_high'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Risque normal</span> <span class="mini-value" style="color:#16a34a;">{{ $data['agents']['risk_normal'] }}</span></div>
        </div>
    </div>

    @if(count($data['agents']['offline_list']) > 0)
    <table class="data" style="margin-top:8px;">
        <thead><tr><th>Agent hors-ligne</th><th style="width:110px;">IP</th><th style="width:120px;">Dernier contact</th></tr></thead>
        <tbody>
            @foreach($data['agents']['offline_list'] as $ag)
            <tr>
                <td>{{ $ag['name'] }}</td>
                <td>{{ $ag['ip'] }}</td>
                <td>{{ $ag['last_seen'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- ══ ACTIONS DE PROTECTION ════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Actions de protection ({{ $data['actions']['total'] }} sur la période)</div>

    <div class="two-col">
        <div class="col-left">
            <div class="mini-stat"><span class="mini-label">Approuvées</span> <span class="mini-value" style="color:#16a34a;">{{ $data['actions']['approved'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Exécutées</span>  <span class="mini-value" style="color:#2563eb;">{{ $data['actions']['executed'] }}</span></div>
        </div>
        <div class="col-right">
            <div class="mini-stat"><span class="mini-label">Rejetées</span>   <span class="mini-value" style="color:#ef4444;">{{ $data['actions']['rejected'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">En attente</span> <span class="mini-value" style="color:#d97706;">{{ $data['actions']['pending'] }}</span></div>
        </div>
    </div>
</div>

{{-- ══ ACTIVITÉ SOC ════════════════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Activité opérateur SOC ({{ $data['audit']['total'] }} entrées d'audit)</div>

    <div class="two-col">
        <div class="col-left">
            <div class="mini-stat"><span class="mini-label">Connexions</span>       <span class="mini-value">{{ $data['audit']['logins'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Actions opérateur</span><span class="mini-value">{{ $data['audit']['operator_actions'] }}</span></div>
        </div>
        <div class="col-right">
            <div class="mini-stat"><span class="mini-label">Param. modifiés</span>  <span class="mini-value">{{ $data['audit']['settings_changes'] }}</span></div>
            <div class="mini-stat"><span class="mini-label">Utilisateurs actifs</span><span class="mini-value">{{ $data['audit']['active_users'] }}</span></div>
        </div>
    </div>
</div>

<div class="footer">
    Rapport généré automatiquement par RansomShield SOC · {{ now()->format('d/m/Y à H:i:s') }} ·
    Période {{ $periodLabel }} · Confidentiel — Usage interne uniquement
</div>

</body>
</html>
