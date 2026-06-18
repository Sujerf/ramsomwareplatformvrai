<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rapport exécutif SOC — {{ $periodLabel }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,ui-sans-serif,system-ui,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

  {{-- Header --}}
  <tr>
    <td style="background:#0a1628;padding:28px 36px;">
      <div style="font-size:22px;font-weight:800;color:#e2eaf5;letter-spacing:-.5px;">
        Ransom<span style="color:#38bdf8;">Shield</span>
      </div>
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:2px;color:#5d7a99;margin-top:4px;">
        Security Operations Center
      </div>
      <div style="margin-top:16px;font-size:18px;font-weight:700;color:#e2eaf5;">
        Rapport exécutif SOC
      </div>
      <div style="font-size:13px;color:#5d7a99;margin-top:4px;">
        Période : {{ $periodLabel }}
      </div>
    </td>
  </tr>

  {{-- Body --}}
  <tr>
    <td style="padding:32px 36px;">

      <p style="color:#475569;font-size:14px;line-height:1.7;margin:0 0 24px;">
        Bonjour,<br><br>
        Veuillez trouver ci-joint le rapport de sécurité automatique généré par RansomShield SOC
        pour la période <strong>{{ $periodLabel }}</strong>.
      </p>

      {{-- Stats grid --}}
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
          <td width="50%" style="padding:0 6px 12px 0;">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;text-align:center;">
              <div style="font-size:28px;font-weight:800;color:#dc2626;">{{ $summary['incidents_total'] }}</div>
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#ef4444;margin-top:4px;">Incidents</div>
            </div>
          </td>
          <td width="50%" style="padding:0 0 12px 6px;">
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:16px 20px;text-align:center;">
              <div style="font-size:28px;font-weight:800;color:#ea580c;">{{ $summary['alerts_total'] }}</div>
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#f97316;margin-top:4px;">Alertes</div>
            </div>
          </td>
        </tr>
        <tr>
          <td width="50%" style="padding:0 6px 0 0;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;text-align:center;">
              <div style="font-size:28px;font-weight:800;color:#16a34a;">{{ $summary['agents_online'] }}</div>
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#22c55e;margin-top:4px;">Agents actifs</div>
            </div>
          </td>
          <td width="50%" style="padding:0 0 0 6px;">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:16px 20px;text-align:center;">
              <div style="font-size:28px;font-weight:800;color:#2563eb;">{{ $summary['actions_executed'] }}</div>
              <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#3b82f6;margin-top:4px;">Actions exécutées</div>
            </div>
          </td>
        </tr>
      </table>

      @if($summary['incidents_critical'] > 0)
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#b91c1c;">
        <strong>⚠ Attention :</strong> {{ $summary['incidents_critical'] }} incident(s) de niveau CRITIQUE détecté(s) sur la période.
      </div>
      @endif

      <p style="color:#64748b;font-size:13px;line-height:1.6;margin:0 0 8px;">
        Le rapport complet (PDF) est joint à cet e-mail. Il détaille les incidents, alertes,
        état des agents, actions de protection et l'activité des opérateurs SOC.
      </p>

      <div style="margin:24px 0;">
        <a href="{{ url('/console/dashboard') }}"
           style="display:inline-block;background:#38bdf8;color:#030f1c;font-weight:700;font-size:13px;
                  padding:12px 24px;border-radius:8px;text-decoration:none;letter-spacing:.2px;">
          Ouvrir la console SOC →
        </a>
      </div>

    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 36px;font-size:11px;color:#94a3b8;text-align:center;">
      Généré automatiquement par RansomShield SOC · {{ now()->format('d/m/Y à H:i') }}<br>
      Ce message est envoyé automatiquement, merci de ne pas y répondre.
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
