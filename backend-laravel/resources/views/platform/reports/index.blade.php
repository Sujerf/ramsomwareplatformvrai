@extends('layouts.soc')

@section('title', 'RansomShield — Rapports exécutifs')
@section('page_title', 'Rapports exécutifs')
@section('page_subtitle', 'Génération et envoi automatique des rapports de sécurité périodiques')

@section('content')
@include('platform.partials.page-tools-style')

@php
$enabled   = ($settings['report_executive_enabled']->value   ?? '0') === '1';
$recipient = $settings['report_executive_recipient']->value  ?? '';
$frequency = $settings['report_executive_frequency']->value  ?? 'weekly';
$freqLabel = $frequency === 'monthly' ? 'Mensuel (1er du mois à 8h)' : 'Hebdomadaire (lundi à 8h)';
@endphp

<style>
.report-config-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; padding:22px 26px; margin-bottom:18px; }
.report-config-title { font-size:13px; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.config-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-color); font-size:13px; }
.config-row:last-child { border-bottom:none; }
.config-label { color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
.config-value { font-weight:600; color:var(--text-primary); }

.report-list { display:grid; gap:8px; }
.report-item { display:flex; align-items:center; gap:14px; padding:12px 16px;
    background:var(--card-bg); border:1px solid var(--border-color); border-radius:10px; }
.report-icon { width:36px; height:36px; border-radius:9px; background:rgba(239,68,68,.1);
    color:#ef4444; display:grid; place-items:center; font-size:15px; flex-shrink:0; }
.report-name  { font-size:12px; font-weight:600; color:var(--text-primary); }
.report-meta  { font-size:11px; color:var(--text-muted); margin-top:2px; }
.report-item .btn { margin-left:auto; flex-shrink:0; }

.gen-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; padding:22px 26px; margin-bottom:18px; }
.gen-card-title { font-size:13px; font-weight:700; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.period-btns { display:flex; gap:8px; margin-bottom:14px; }
.period-btn { padding:8px 18px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid var(--border-color); color:var(--text-muted); background:transparent; cursor:pointer; transition:all .15s; }
.period-btn:hover { background:var(--card-bg); color:var(--text-primary); }
.period-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }

.status-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 12px;
    border-radius:99px; font-size:11px; font-weight:700; }
.status-pill.on  { background:rgba(34,197,94,.12); color:#22c55e; border:1px solid rgba(34,197,94,.25); }
.status-pill.off { background:rgba(245,158,11,.12); color:#f59e0b; border:1px solid rgba(245,158,11,.25); }
</style>

@if(session('success'))
<div class="flash flash-success section-gap">{{ session('success') }}</div>
@endif
@if($errors->has('report'))
<div class="flash flash-error section-gap">{{ $errors->first('report') }}</div>
@endif

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start;" class="section-gap">

    {{-- ── Configuration ────────────────────────────────────────────── --}}
    <div>
        <div class="report-config-card">
            <div class="report-config-title">
                <i class="fa-solid fa-gears" style="color:var(--accent);"></i>
                Configuration
            </div>

            <div class="config-row">
                <div>
                    <div class="config-label">Statut</div>
                    <div class="config-value" style="margin-top:3px;">
                        <span class="status-pill {{ $enabled ? 'on' : 'off' }}">
                            <i class="fa-solid {{ $enabled ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                            {{ $enabled ? 'Activé' : 'Désactivé' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="config-row">
                <div>
                    <div class="config-label">Destinataire</div>
                    <div class="config-value" style="margin-top:3px;">
                        {{ $recipient ?: '— non configuré —' }}
                    </div>
                </div>
            </div>

            <div class="config-row">
                <div>
                    <div class="config-label">Fréquence</div>
                    <div class="config-value" style="margin-top:3px;">{{ $freqLabel }}</div>
                </div>
            </div>

            <div style="margin-top:16px;">
                <a href="{{ route('platform.system-settings.index', ['group' => 'reports']) }}"
                   class="btn btn-soft" style="font-size:12px;">
                    <i class="fa-solid fa-pen-to-square"></i> Modifier dans les paramètres
                </a>
            </div>
        </div>

        {{-- ── Générer maintenant ──────────────────────────────────── --}}
        <div class="gen-card">
            <div class="gen-card-title">
                <i class="fa-solid fa-bolt" style="color:#f59e0b;"></i>
                Générer maintenant
            </div>
            <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px; line-height:1.6;">
                Génère immédiatement un rapport pour la période choisie et l'envoie au destinataire configuré.
            </p>

            @if($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL))
            <div class="flash flash-error" style="margin-bottom:12px; font-size:12px;">
                Configurez d'abord un destinataire valide dans les paramètres système.
            </div>
            @endif

            <form method="POST" action="{{ route('platform.reports.generate') }}" id="genForm">
                @csrf
                <input type="hidden" name="period" id="genPeriod" value="weekly">

                <div class="period-btns">
                    <button type="button" class="period-btn active" onclick="setPeriod('weekly', this)">
                        <i class="fa-solid fa-calendar-week" style="margin-right:5px;"></i>
                        Hebdomadaire
                    </button>
                    <button type="button" class="period-btn" onclick="setPeriod('monthly', this)">
                        <i class="fa-solid fa-calendar" style="margin-right:5px;"></i>
                        Mensuel
                    </button>
                </div>

                <button type="submit" class="btn btn-primary" {{ ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) ? 'disabled' : '' }}>
                    <i class="fa-solid fa-paper-plane"></i> Générer et envoyer
                </button>
            </form>
        </div>
    </div>

    {{-- ── Historique des rapports ──────────────────────────────────── --}}
    <div>
        <div class="report-config-card">
            <div class="report-config-title">
                <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent);"></i>
                Rapports générés ({{ count($reports) }})
            </div>

            @if(count($reports) === 0)
            <div style="text-align:center; padding:30px 0; color:var(--text-muted);">
                <i class="fa-solid fa-file-pdf" style="font-size:28px; display:block; margin-bottom:10px; opacity:.3;"></i>
                Aucun rapport généré pour l'instant.<br>
                <span style="font-size:11px;">Utilisez "Générer maintenant" pour créer le premier.</span>
            </div>
            @else
            <div class="report-list">
                @foreach($reports as $report)
                <div class="report-item">
                    <div class="report-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div>
                        <div class="report-name">{{ $report['name'] }}</div>
                        <div class="report-meta">{{ $report['modified'] }} · {{ $report['size'] }} Ko</div>
                    </div>
                    <a href="{{ route('platform.reports.download', $report['name']) }}"
                       class="btn btn-soft" style="font-size:11px; padding:5px 12px;">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

<script>
function setPeriod(period, btn) {
    document.getElementById('genPeriod').value = period;
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>

@endsection
