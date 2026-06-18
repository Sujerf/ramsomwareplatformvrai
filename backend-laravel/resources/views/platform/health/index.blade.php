@extends('layouts.soc')

@section('title', 'RansomShield — Santé SOC')
@section('page_title', 'Santé SOC')
@section('page_subtitle', 'État en temps réel de tous les composants de la plateforme')

@section('content')
@include('platform.partials.page-tools-style')

@php
$statusColor = fn(string $s) => match($s) {
    'ok'    => '#22c55e',
    'warn'  => '#f59e0b',
    'error' => '#ef4444',
    'off'   => '#64748b',
    default => '#64748b',
};
$statusBg = fn(string $s) => match($s) {
    'ok'    => 'rgba(34,197,94,.1)',
    'warn'  => 'rgba(245,158,11,.1)',
    'error' => 'rgba(239,68,68,.1)',
    'off'   => 'rgba(100,116,139,.1)',
    default => 'rgba(100,116,139,.1)',
};
$statusIcon = fn(string $s) => match($s) {
    'ok'    => 'fa-circle-check',
    'warn'  => 'fa-triangle-exclamation',
    'error' => 'fa-circle-xmark',
    'off'   => 'fa-circle-minus',
    default => 'fa-circle-question',
};
$statusLabel = fn(string $s) => match($s) {
    'ok'    => 'OK',
    'warn'  => 'Avertissement',
    'error' => 'Erreur',
    'off'   => 'Désactivé',
    default => 'Inconnu',
};

$global = collect($checks)->pluck('status')->contains('error') ? 'error'
        : (collect($checks)->pluck('status')->contains('warn')  ? 'warn'  : 'ok');
@endphp

<style>
/* ── Global banner ── */
.health-banner { border-radius:14px; padding:18px 22px; display:flex; align-items:center;
    gap:16px; margin-bottom:20px; }
.health-banner.ok    { background:rgba(34,197,94,.08);  border:1px solid rgba(34,197,94,.25); }
.health-banner.warn  { background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.25); }
.health-banner.error { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.25); }
.banner-icon { font-size:28px; flex-shrink:0; }
.banner-ok    .banner-icon { color:#22c55e; }
.banner-warn  .banner-icon { color:#f59e0b; }
.banner-error .banner-icon { color:#ef4444; }
.banner-title { font-size:15px; font-weight:700; }
.banner-sub   { font-size:12px; color:var(--text-muted); margin-top:2px; }
.banner-time  { margin-left:auto; font-size:11px; color:var(--text-muted); flex-shrink:0; }

/* ── Grid checks ── */
.health-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; }
.health-card { background:var(--card-bg); border:1px solid var(--border-color);
    border-radius:14px; padding:18px 20px; position:relative; overflow:hidden; }
.health-card::before { content:''; position:absolute; left:0; top:0; bottom:0;
    width:4px; border-radius:4px 0 0 4px; }
.health-card.ok::before    { background:#22c55e; }
.health-card.warn::before  { background:#f59e0b; }
.health-card.error::before { background:#ef4444; }
.health-card.off::before   { background:#64748b; }

.hc-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.hc-icon { width:36px; height:36px; border-radius:10px; display:grid;
    place-items:center; font-size:15px; flex-shrink:0; }
.hc-title { font-size:12px; font-weight:700; color:var(--text-primary); }
.hc-status { margin-left:auto; display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:99px; font-size:10.5px; font-weight:700; }

.hc-value  { font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:4px; }
.hc-detail { font-size:11px; color:var(--text-muted); line-height:1.5; }

/* ── Progress bar ── */
.disk-bar-wrap { margin-top:10px; background:rgba(255,255,255,.06);
    border-radius:99px; height:6px; overflow:hidden; }
.disk-bar { height:100%; border-radius:99px; transition:width .4s; }

/* ── Refresh button ── */
.refresh-btn { padding:7px 16px; border-radius:8px; font-size:12px; font-weight:600;
    background:transparent; border:1px solid var(--border-color); color:var(--text-muted);
    cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.refresh-btn:hover { border-color:var(--accent); color:var(--accent); }
</style>

{{-- ── Bannière globale ── --}}
<div class="health-banner {{ $global }} banner-{{ $global }}" style="margin-bottom:20px;">
    <div class="banner-icon">
        <i class="fa-solid {{ $statusIcon($global) }}"></i>
    </div>
    <div>
        @if($global === 'ok')
        <div class="banner-title" style="color:#22c55e;">Tous les systèmes opérationnels</div>
        <div class="banner-sub">{{ collect($checks)->count() }} composants vérifiés — aucune anomalie détectée.</div>
        @elseif($global === 'warn')
        <div class="banner-title" style="color:#f59e0b;">Avertissements détectés</div>
        <div class="banner-sub">{{ collect($checks)->where('status','warn')->count() }} composant(s) nécessitent votre attention.</div>
        @else
        <div class="banner-title" style="color:#ef4444;">Erreurs critiques détectées</div>
        <div class="banner-sub">{{ collect($checks)->where('status','error')->count() }} composant(s) en erreur.</div>
        @endif
    </div>
    <div class="banner-time">
        Vérifié à {{ now()->format('H:i:s') }}<br>
        <button class="refresh-btn" style="margin-top:6px;" onclick="window.location.reload()">
            <i class="fa-solid fa-rotate-right"></i> Actualiser
        </button>
    </div>
</div>

{{-- ── Grille des composants ── --}}
<div class="health-grid">

    {{-- Database --}}
    @php $c = $checks['database']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-database"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Cache --}}
    @php $c = $checks['cache']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Queue --}}
    @php $c = $checks['queue']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Scheduler --}}
    @php $c = $checks['scheduler']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Stockage --}}
    @php $c = $checks['storage']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-hard-drive"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
        <div class="disk-bar-wrap">
            <div class="disk-bar"
                 style="width:{{ $c['pct'] }}%;background:{{ $statusColor($c['status']) }};"></div>
        </div>
    </div>

    {{-- Agents --}}
    @php $c = $checks['agents']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-desktop"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Incidents --}}
    @php $c = $checks['incidents']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
    </div>

    {{-- Mail --}}
    @php $c = $checks['mail']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
        @if(! empty($c['host']))
        <div class="hc-detail" style="margin-top:3px;">Serveur SMTP : {{ $c['host'] }}</div>
        @endif
    </div>

    {{-- Webhook --}}
    @php $c = $checks['webhook']; @endphp
    <div class="health-card {{ $c['status'] }}">
        <div class="hc-header">
            <div class="hc-icon" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid fa-webhook"></i>
            </div>
            <div class="hc-title">{{ $c['label'] }}</div>
            <span class="hc-status" style="background:{{ $statusBg($c['status']) }};color:{{ $statusColor($c['status']) }};">
                <i class="fa-solid {{ $statusIcon($c['status']) }}"></i> {{ $statusLabel($c['status']) }}
            </span>
        </div>
        <div class="hc-value">{{ $c['value'] }}</div>
        <div class="hc-detail">{{ $c['detail'] }}</div>
        @if(! empty($c['url']))
        <div class="hc-detail" style="margin-top:3px; font-family:monospace; font-size:10.5px;
             overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $c['url'] }}">
            {{ $c['url'] }}
        </div>
        @endif
    </div>

</div>

{{-- ── Liens rapides ── --}}
<div style="display:flex; gap:10px; margin-top:20px; flex-wrap:wrap;">
    <a href="{{ route('platform.system-settings.index', ['group' => 'notifications']) }}"
       class="btn btn-soft" style="font-size:12px;">
        <i class="fa-solid fa-gears"></i> Paramètres notifications
    </a>
    <a href="{{ route('platform.webhook-history.index') }}"
       class="btn btn-soft" style="font-size:12px;">
        <i class="fa-solid fa-webhook"></i> Historique webhooks
    </a>
    <a href="{{ route('platform.audit-log.index') }}"
       class="btn btn-soft" style="font-size:12px;">
        <i class="fa-solid fa-shield-halved"></i> Journal d'audit
    </a>
    <a href="{{ route('platform.agents.index') }}"
       class="btn btn-soft" style="font-size:12px;">
        <i class="fa-solid fa-desktop"></i> Gestion des agents
    </a>
</div>

@endsection
