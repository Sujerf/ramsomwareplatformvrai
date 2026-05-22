<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alerte RansomShield</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a1628; color: #e2eaf5; }
    .wrap { max-width: 600px; margin: 0 auto; padding: 32px 16px; }

    /* header */
    .header { background: #050f1c; border-radius: 16px 16px 0 0; padding: 28px 32px; display: flex; align-items: center; gap: 16px; border-bottom: 1px solid rgba(56,189,248,0.15); }
    .logo-box { width: 48px; height: 48px; border-radius: 13px; background: linear-gradient(145deg, #38bdf8, #1a7abf); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .brand-name { font-size: 20px; font-weight: 800; color: #e2eaf5; }
    .brand-name span { color: #38bdf8; }
    .brand-sub { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #5d7a99; margin-top: 3px; }

    /* risk banner */
    .risk-banner { padding: 18px 32px; text-align: center; font-weight: 800; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; }
    .risk-normal   { background: rgba(34,197,94,0.15);  color: #22c55e; }
    .risk-suspect  { background: rgba(234,179,8,0.15);  color: #eab308; }
    .risk-high     { background: rgba(249,115,22,0.15); color: #f97316; }
    .risk-critical { background: rgba(239,68,68,0.18);  color: #ef4444; }

    /* body */
    .body { background: #080f1d; padding: 32px; }

    .section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #5d7a99; font-weight: 700; margin-bottom: 14px; }

    /* alert card */
    .alert-card { background: #0a1628; border: 1px solid rgba(56,189,248,0.12); border-radius: 12px; padding: 20px 22px; margin-bottom: 24px; }
    .alert-title { font-size: 17px; font-weight: 700; color: #e2eaf5; margin-bottom: 8px; line-height: 1.3; }
    .alert-message { font-size: 13px; color: #8ea8c3; line-height: 1.6; }

    /* meta grid */
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
    .meta-item { background: #0a1628; border: 1px solid rgba(56,189,248,0.10); border-radius: 10px; padding: 14px 16px; }
    .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #5d7a99; font-weight: 700; margin-bottom: 5px; }
    .meta-value { font-size: 14px; font-weight: 600; color: #e2eaf5; }
    .meta-value.accent { color: #38bdf8; }
    .meta-value.normal   { color: #22c55e; }
    .meta-value.suspect  { color: #eab308; }
    .meta-value.high     { color: #f97316; }
    .meta-value.critical { color: #ef4444; }

    /* score bar */
    .score-wrap { margin-bottom: 24px; }
    .score-bar-bg { background: #0a1628; border-radius: 999px; height: 8px; overflow: hidden; border: 1px solid rgba(56,189,248,0.10); }
    .score-bar-fill { height: 100%; border-radius: 999px; transition: width 0.3s; }
    .score-label { display: flex; justify-content: space-between; font-size: 12px; color: #5d7a99; margin-bottom: 8px; }

    /* cta */
    .cta { text-align: center; margin: 28px 0; }
    .cta a { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #38bdf8, #1a7abf); color: #03111f; font-size: 14px; font-weight: 800; border-radius: 10px; text-decoration: none; letter-spacing: 0.3px; }

    /* footer */
    .footer { background: #050f1c; border-radius: 0 0 16px 16px; padding: 20px 32px; border-top: 1px solid rgba(56,189,248,0.10); text-align: center; font-size: 11px; color: #3d5571; line-height: 1.6; }
    .footer a { color: #38bdf8; text-decoration: none; }
</style>
</head>
<body>
<div class="wrap">

    {{-- header --}}
    <div class="header">
        <div class="logo-box">🛡</div>
        <div>
            <div class="brand-name">Ransom<span>Shield</span></div>
            <div class="brand-sub">Security Operations Center</div>
        </div>
    </div>

    {{-- risk banner --}}
    @php
        $risk = $alert->risk_level;
        $riskLabel = match($risk) {
            'critical' => '🔴 ALERTE CRITIQUE',
            'high'     => '🟠 ALERTE HAUTE',
            'suspect'  => '🟡 COMPORTEMENT SUSPECT',
            default    => '🟢 ALERTE NORMALE',
        };
    @endphp
    <div class="risk-banner risk-{{ $risk }}">{{ $riskLabel }}</div>

    {{-- body --}}
    <div class="body">

        <div class="section-title">Détails de l'alerte</div>

        <div class="alert-card">
            <div class="alert-title">{{ $alert->title }}</div>
            <div class="alert-message">{{ $alert->message }}</div>
        </div>

        <div class="section-title">Informations</div>

        <div class="meta-grid">
            <div class="meta-item">
                <div class="meta-label">Agent concerné</div>
                <div class="meta-value accent">{{ $alert->agent?->agent_name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Niveau de risque</div>
                <div class="meta-value {{ $risk }}">{{ strtoupper($risk) }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Score de risque</div>
                <div class="meta-value">{{ $alert->score ?? 0 }} / 100</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Détecté le</div>
                <div class="meta-value">{{ optional($alert->detected_at)->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</div>
            </div>
            @if($alert->agent?->ip_address)
            <div class="meta-item">
                <div class="meta-label">Adresse IP</div>
                <div class="meta-value">{{ $alert->agent->ip_address }}</div>
            </div>
            @endif
            @if($alert->incident_id)
            <div class="meta-item">
                <div class="meta-label">Incident lié</div>
                <div class="meta-value accent">#{{ $alert->incident_id }}</div>
            </div>
            @endif
        </div>

        {{-- score bar --}}
        @php
            $score = min(100, max(0, $alert->score ?? 0));
            $barColor = match(true) {
                $score >= 75 => '#ef4444',
                $score >= 50 => '#f97316',
                $score >= 25 => '#eab308',
                default      => '#22c55e',
            };
        @endphp
        <div class="score-wrap">
            <div class="score-label">
                <span>Score de menace</span>
                <span>{{ $score }}/100</span>
            </div>
            <div class="score-bar-bg">
                <div class="score-bar-fill" style="width:{{ $score }}%; background:{{ $barColor }};"></div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/console/alerts/' . $alert->id) }}">
                Voir l'alerte dans la console →
            </a>
        </div>

    </div>

    {{-- footer --}}
    <div class="footer">
        Ce message a été envoyé automatiquement par <strong>RansomShield SOC</strong>.<br>
        Ne pas répondre à cet email. Pour accéder à la console : <a href="{{ url('/console/dashboard') }}">{{ url('/console/dashboard') }}</a>
    </div>

</div>
</body>
</html>
