<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Incident #{{ $incident->id }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

    .header { background: #0a1628; color: #e2eaf5; padding: 20px 24px; margin-bottom: 20px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .brand { font-size: 18px; font-weight: bold; }
    .brand span { color: #38bdf8; }
    .brand-sub { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #5d7a99; margin-top: 2px; }
    .report-meta { text-align: right; font-size: 10px; color: #8ea8c3; }
    .report-title { font-size: 13px; font-weight: bold; color: #e2eaf5; margin-top: 10px; }

    .risk-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .risk-critical { background: #fee2e2; color: #b91c1c; }
    .risk-high     { background: #ffedd5; color: #c2410c; }
    .risk-suspect  { background: #fef9c3; color: #854d0e; }
    .risk-normal   { background: #dcfce7; color: #166534; }

    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; background: #e2e8f0; color: #475569; }

    .section { margin-bottom: 18px; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #5d7a99; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }

    .info-grid { display: table; width: 100%; border-collapse: collapse; }
    .info-row  { display: table-row; }
    .info-label { display: table-cell; width: 160px; font-weight: bold; color: #64748b; padding: 4px 8px 4px 0; vertical-align: top; }
    .info-value { display: table-cell; color: #1e293b; padding: 4px 0; vertical-align: top; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 4px; }
    th { background: #f1f5f9; color: #475569; font-weight: bold; text-align: left; padding: 6px 8px; border: 1px solid #e2e8f0; }
    td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    tr:nth-child(even) td { background: #f8fafc; }

    .score-bar-wrap { display: inline-block; width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; vertical-align: middle; margin-right: 4px; }
    .score-bar { height: 6px; border-radius: 3px; }

    .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }

    .page-break { page-break-before: always; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-top">
        <div>
            <div class="brand">Ransom<span>Shield</span></div>
            <div class="brand-sub">Security Operations Center</div>
        </div>
        <div class="report-meta">
            Rapport généré le {{ now()->format('d/m/Y à H:i:s') }}<br>
            Incident #{{ $incident->id }}
        </div>
    </div>
    <div class="report-title">{{ $incident->title }}</div>
</div>

{{-- Section 1 : Résumé incident --}}
<div class="section">
    <div class="section-title">Résumé de l'incident</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Identifiant</div>
            <div class="info-value">#{{ $incident->id }} &nbsp;·&nbsp; <small>{{ $incident->incident_uuid }}</small></div>
        </div>
        <div class="info-row">
            <div class="info-label">Statut</div>
            <div class="info-value"><span class="status-badge">{{ $incident->status }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Niveau de risque</div>
            <div class="info-value"><span class="risk-badge risk-{{ $incident->risk_level }}">{{ strtoupper($incident->risk_level) }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Score de menace</div>
            <div class="info-value">{{ $incident->risk_score ?? 0 }} / 100</div>
        </div>
        <div class="info-row">
            <div class="info-label">Agent concerné</div>
            <div class="info-value">{{ $incident->agent?->agent_name ?? '—' }} ({{ $incident->agent?->ip_address ?? 'IP inconnue' }})</div>
        </div>
        <div class="info-row">
            <div class="info-label">Profil d'attaque</div>
            <div class="info-value">{{ $incident->attackProfile?->name ?? '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Détecté le</div>
            <div class="info-value">{{ optional($incident->detected_at)->format('d/m/Y H:i:s') ?? '—' }}</div>
        </div>
        @if($incident->resolved_at)
        <div class="info-row">
            <div class="info-label">Résolu le</div>
            <div class="info-value">{{ $incident->resolved_at->format('d/m/Y H:i:s') }}</div>
        </div>
        @endif
        @if($incident->description)
        <div class="info-row">
            <div class="info-label">Description</div>
            <div class="info-value">{{ $incident->description }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Section 2 : Alertes --}}
@if($incident->alerts->isNotEmpty())
<div class="section">
    <div class="section-title">Alertes ({{ $incident->alerts->count() }})</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;">ID</th>
                <th>Titre</th>
                <th style="width:70px;">Niveau</th>
                <th style="width:50px;">Score</th>
                <th style="width:60px;">Statut</th>
                <th style="width:110px;">Détectée le</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incident->alerts as $alert)
            <tr>
                <td>{{ $alert->id }}</td>
                <td>{{ $alert->title }}</td>
                <td><span class="risk-badge risk-{{ $alert->risk_level }}" style="font-size:9px; padding:2px 6px;">{{ strtoupper($alert->risk_level) }}</span></td>
                <td>{{ $alert->score ?? 0 }}/100</td>
                <td>{{ $alert->status }}</td>
                <td>{{ optional($alert->detected_at)->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Section 3 : Événements --}}
@if($incident->events->isNotEmpty())
<div class="section {{ $incident->alerts->count() > 5 ? 'page-break' : '' }}">
    <div class="section-title">Événements détectés ({{ $incident->events->count() }})</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;">ID</th>
                <th style="width:120px;">Type</th>
                <th>Chemin</th>
                <th style="width:50px;">Ext.</th>
                <th style="width:70px;">Niveau</th>
                <th style="width:50px;">Score</th>
                <th style="width:110px;">Observé le</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incident->events->take(50) as $event)
            <tr>
                <td>{{ $event->id }}</td>
                <td>{{ $event->event_type }}</td>
                <td style="word-break:break-all; font-size:9px;">{{ $event->path ?? '—' }}</td>
                <td>{{ $event->file_extension ?? '—' }}</td>
                <td><span class="risk-badge risk-{{ $event->risk_level }}" style="font-size:9px; padding:2px 6px;">{{ strtoupper($event->risk_level) }}</span></td>
                <td>{{ $event->score ?? 0 }}/100</td>
                <td>{{ optional($event->observed_at)->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @endforeach
            @if($incident->events->count() > 50)
            <tr>
                <td colspan="7" style="text-align:center; color:#94a3b8; font-style:italic;">
                    … {{ $incident->events->count() - 50 }} événement(s) supplémentaire(s) — export CSV pour la liste complète.
                </td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
@endif

{{-- Section 4 : Actions de protection --}}
@if($incident->protectionActions->isNotEmpty())
<div class="section">
    <div class="section-title">Actions de protection ({{ $incident->protectionActions->count() }})</div>
    <table>
        <thead>
            <tr>
                <th style="width:30px;">ID</th>
                <th style="width:130px;">Type d'action</th>
                <th>Politique</th>
                <th style="width:80px;">Statut</th>
                <th style="width:80px;">Approbation</th>
                <th style="width:110px;">Créée le</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incident->protectionActions as $action)
            <tr>
                <td>{{ $action->id }}</td>
                <td>{{ $action->action_type }}</td>
                <td>{{ $action->protectionPolicy?->name ?? '—' }}</td>
                <td>{{ $action->status }}</td>
                <td>{{ $action->human_approval_required ? 'Requise' : 'Auto' }}</td>
                <td>{{ optional($action->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    Ce rapport a été généré automatiquement par RansomShield SOC · {{ now()->format('d/m/Y H:i:s') }} ·
    Incident #{{ $incident->id }} · {{ $incident->agent?->agent_name ?? 'Agent inconnu' }}
</div>

</body>
</html>
