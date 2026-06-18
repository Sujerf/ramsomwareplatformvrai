@extends('layouts.soc')

@section('title', 'RansomShield — Historique webhooks')
@section('page_title', 'Historique webhooks')
@section('page_subtitle', 'Tous les envois webhook — alertes réelles et tests de connexion')

@section('content')
@include('platform.partials.page-tools-style')

<style>
.wh-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.wh-stat { background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px;
    padding:14px 18px; display:flex; align-items:center; gap:14px; }
.wh-stat-icon { width:38px; height:38px; border-radius:10px; display:grid; place-items:center;
    font-size:16px; flex-shrink:0; }
.wh-stat-value { font-size:22px; font-weight:700; line-height:1; }
.wh-stat-label { font-size:11px; color:var(--text-muted); margin-top:2px; text-transform:uppercase;
    letter-spacing:.4px; font-weight:600; }

.wh-table-wrap { background:var(--card-bg); border:1px solid var(--border-color); border-radius:14px; overflow:hidden; }
.wh-table { width:100%; border-collapse:collapse; font-size:13px; }
.wh-table th { background:var(--card-bg-alt,rgba(255,255,255,.03)); color:var(--text-muted);
    font-size:10.5px; text-transform:uppercase; letter-spacing:.5px; font-weight:700;
    padding:10px 14px; border-bottom:1px solid var(--border-color); text-align:left; }
.wh-table td { padding:10px 14px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
.wh-table tr:last-child td { border-bottom:none; }
.wh-table tr:hover td { background:rgba(255,255,255,.02); }

.status-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px;
    border-radius:99px; font-size:11px; font-weight:700; }
.status-sent    { background:rgba(34,197,94,.12); color:#22c55e; border:1px solid rgba(34,197,94,.2); }
.status-failed  { background:rgba(239,68,68,.12);  color:#ef4444; border:1px solid rgba(239,68,68,.2); }
.status-pending { background:rgba(245,158,11,.12); color:#f59e0b; border:1px solid rgba(245,158,11,.2); }

.type-badge { display:inline-block; padding:2px 8px; border-radius:6px; font-size:10.5px;
    font-weight:700; text-transform:uppercase; background:rgba(56,189,248,.1); color:#38bdf8; }
.test-badge { display:inline-block; padding:2px 8px; border-radius:6px; font-size:10px;
    font-weight:700; background:rgba(168,85,247,.1); color:#a855f7; margin-left:5px; }

.url-cell { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    font-size:11px; color:var(--text-muted); font-family:monospace; }

.filter-bar { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
.filter-select { background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary);
    border-radius:8px; padding:7px 12px; font-size:12px; cursor:pointer; }
.filter-input  { background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-primary);
    border-radius:8px; padding:7px 12px; font-size:12px; width:220px; }
.filter-input::placeholder { color:var(--text-muted); }
.filter-btn { padding:7px 16px; border-radius:8px; font-size:12px; font-weight:600;
    background:var(--accent); color:#fff; border:none; cursor:pointer; }
.filter-reset { padding:7px 12px; border-radius:8px; font-size:12px; color:var(--text-muted);
    background:transparent; border:1px solid var(--border-color); cursor:pointer; }

.detail-cell { font-size:11px; color:var(--text-muted); }
.http-ok  { color:#22c55e; font-weight:700; }
.http-err { color:#ef4444; font-weight:700; }
</style>

{{-- ── Stats ── --}}
<div class="wh-stats">
    <div class="wh-stat">
        <div class="wh-stat-icon" style="background:rgba(56,189,248,.1);color:#38bdf8;">
            <i class="fa-solid fa-webhook"></i>
        </div>
        <div>
            <div class="wh-stat-value">{{ $stats['total'] }}</div>
            <div class="wh-stat-label">Total envois</div>
        </div>
    </div>
    <div class="wh-stat">
        <div class="wh-stat-icon" style="background:rgba(34,197,94,.1);color:#22c55e;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="wh-stat-value" style="color:#22c55e;">{{ $stats['sent'] }}</div>
            <div class="wh-stat-label">Envoyés</div>
        </div>
    </div>
    <div class="wh-stat">
        <div class="wh-stat-icon" style="background:rgba(239,68,68,.1);color:#ef4444;">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div>
            <div class="wh-stat-value" style="color:#ef4444;">{{ $stats['failed'] }}</div>
            <div class="wh-stat-label">Échoués</div>
        </div>
    </div>
    <div class="wh-stat">
        <div class="wh-stat-icon" style="background:rgba(168,85,247,.1);color:#a855f7;">
            <i class="fa-solid fa-bolt"></i>
        </div>
        <div>
            <div class="wh-stat-value" style="color:#a855f7;">{{ $stats['tests'] }}</div>
            <div class="wh-stat-label">Tests manuels</div>
        </div>
    </div>
</div>

{{-- ── Filtres ── --}}
<form method="GET" action="{{ route('platform.webhook-history.index') }}" class="filter-bar">
    <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <option value="sent"    @selected($filters['status'] === 'sent')>Envoyés</option>
        <option value="failed"  @selected($filters['status'] === 'failed')>Échoués</option>
        <option value="pending" @selected($filters['status'] === 'pending')>En attente</option>
    </select>

    <select name="type" class="filter-select" onchange="this.form.submit()">
        <option value="">Tous les types</option>
        <option value="slack"   @selected($filters['type'] === 'slack')>Slack</option>
        <option value="teams"   @selected($filters['type'] === 'teams')>Teams</option>
        <option value="generic" @selected($filters['type'] === 'generic')>Generic</option>
    </select>

    <input type="text" name="search" class="filter-input"
           placeholder="Rechercher sujet ou URL…" value="{{ $filters['search'] }}">
    <button type="submit" class="filter-btn"><i class="fa-solid fa-magnifying-glass"></i></button>

    @if($filters['status'] || $filters['type'] || $filters['search'])
    <a href="{{ route('platform.webhook-history.index') }}" class="filter-reset">
        <i class="fa-solid fa-xmark"></i> Réinitialiser
    </a>
    @endif

    <a href="{{ route('platform.system-settings.index', ['group' => 'notifications']) }}"
       class="filter-reset" style="margin-left:auto;">
        <i class="fa-solid fa-gears"></i> Paramètres
    </a>
</form>

{{-- ── Tableau ── --}}
<div class="wh-table-wrap">
    @if($notifications->isEmpty())
        @include('platform.partials.empty-state', [
            'title'   => 'Aucun envoi webhook.',
            'message' => 'Les webhooks apparaîtront ici dès qu\'une alerte est envoyée ou qu\'un test est effectué.',
        ])
    @else
    <table class="wh-table">
        <thead>
            <tr>
                <th style="width:110px;">Statut</th>
                <th style="width:75px;">Type</th>
                <th>Sujet</th>
                <th>URL destinataire</th>
                <th style="width:90px;">HTTP</th>
                <th style="width:130px;">Date</th>
                <th style="width:60px;">Détail</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notifications as $notif)
            @php
                $meta       = $notif->metadata ?? [];
                $httpStatus = $meta['http_status']  ?? null;
                $wType      = $meta['webhook_type'] ?? '—';
                $isTest     = ! empty($meta['is_test']);
                $tlMsg      = $meta['timeline_message'] ?? null;
                $error      = $meta['error'] ?? null;
            @endphp
            <tr>
                <td>
                    <span class="status-badge status-{{ $notif->status }}">
                        @if($notif->status === 'sent')
                            <i class="fa-solid fa-circle-check"></i> Envoyé
                        @elseif($notif->status === 'failed')
                            <i class="fa-solid fa-circle-xmark"></i> Échoué
                        @else
                            <i class="fa-solid fa-clock"></i> En attente
                        @endif
                    </span>
                </td>
                <td>
                    <span class="type-badge">{{ strtoupper($wType) }}</span>
                    @if($isTest)<span class="test-badge">TEST</span>@endif
                </td>
                <td style="font-size:12px; font-weight:600;">{{ $notif->subject }}</td>
                <td>
                    <div class="url-cell" title="{{ $notif->recipient }}">{{ $notif->recipient }}</div>
                </td>
                <td>
                    @if($httpStatus)
                        <span class="{{ $httpStatus >= 200 && $httpStatus < 300 ? 'http-ok' : 'http-err' }}">
                            {{ $httpStatus }}
                        </span>
                    @else
                        <span style="color:var(--text-muted);">—</span>
                    @endif
                </td>
                <td class="detail-cell">{{ $notif->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($tlMsg || $error)
                    <button type="button"
                        class="action-btn"
                        style="padding:4px 8px;font-size:10px;background:transparent;border:1px solid var(--border-color);color:var(--text-muted);"
                        onclick="toggleDetail({{ $notif->id }})">
                        <i class="fa-solid fa-chevron-down" id="chevron-{{ $notif->id }}"></i>
                    </button>
                    @endif
                </td>
            </tr>
            @if($tlMsg || $error)
            <tr id="detail-{{ $notif->id }}" style="display:none;">
                <td colspan="7" style="padding:0 14px 12px; background:var(--card-bg-alt,rgba(255,255,255,.02));">
                    @if($tlMsg)
                    <div style="font-size:11px; color:var(--text-muted); margin-top:8px;">
                        <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>{{ $tlMsg }}
                    </div>
                    @endif
                    @if($error)
                    <div style="font-size:11px; color:#ef4444; margin-top:4px;">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>{{ $error }}
                    </div>
                    @endif
                    @if(! empty($meta['response_body']))
                    <pre style="font-size:10px; color:var(--text-muted); margin-top:6px; white-space:pre-wrap; word-break:break-all;">{{ $meta['response_body'] }}</pre>
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- ── Pagination ── --}}
@if($notifications->hasPages())
<div style="margin-top:16px;">{{ $notifications->links() }}</div>
@endif

<script>
function toggleDetail(id) {
    const row     = document.getElementById('detail-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const hidden  = row.style.display === 'none';
    row.style.display    = hidden ? 'table-row' : 'none';
    chevron.className    = hidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
}
</script>

@endsection
