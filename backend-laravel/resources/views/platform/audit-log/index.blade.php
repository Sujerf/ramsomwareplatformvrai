@extends('layouts.soc')

@section('title', 'RansomShield — Journal d\'audit')
@section('page_title', 'Journal d\'audit')
@section('page_subtitle', 'Traçabilité complète des actions opérateur et des événements SOC')

@section('content')
@include('platform.partials.page-tools-style')

@php
$actionMeta = [
    // Auth
    'user.login'            => ['icon' => 'fa-right-to-bracket',   'label' => 'Connexion',             'color' => 'color:#22c55e;'],
    'user.logout'           => ['icon' => 'fa-right-from-bracket',  'label' => 'Déconnexion',           'color' => 'color:#64748b;'],
    // Utilisateurs
    'user.created'          => ['icon' => 'fa-user-plus',           'label' => 'Utilisateur créé',      'color' => 'color:#38bdf8;'],
    'user.updated'          => ['icon' => 'fa-user-pen',            'label' => 'Profil modifié',        'color' => 'color:#a78bfa;'],
    'user.password_changed' => ['icon' => 'fa-key',                 'label' => 'Mot de passe changé',   'color' => 'color:#f59e0b;'],
    'user.deleted'          => ['icon' => 'fa-user-minus',          'label' => 'Utilisateur supprimé',  'color' => 'color:#ef4444;'],
    // Incidents
    'incident.created'      => ['icon' => 'fa-circle-exclamation',  'label' => 'Incident créé',         'color' => 'color:#ef4444;'],
    'incident.resolved'     => ['icon' => 'fa-circle-check',        'label' => 'Incident résolu',       'color' => 'color:#22c55e;'],
    'incident.false_positive'=> ['icon' => 'fa-ban',                'label' => 'Faux positif',          'color' => 'color:#94a3b8;'],
    'incident.reopened'     => ['icon' => 'fa-rotate-right',        'label' => 'Incident réouvert',     'color' => 'color:#fb923c;'],
    // Alertes
    'alert.resolved'        => ['icon' => 'fa-bell-slash',          'label' => 'Alerte résolue',        'color' => 'color:#22c55e;'],
    'alert.false_positive'  => ['icon' => 'fa-ban',                 'label' => 'Alerte faux positif',   'color' => 'color:#94a3b8;'],
    'alert.reopened'        => ['icon' => 'fa-bell',                'label' => 'Alerte réouverte',      'color' => 'color:#fb923c;'],
    // Protection
    'protection.approved'   => ['icon' => 'fa-lock',                'label' => 'Action approuvée',      'color' => 'color:#22c55e;'],
    'protection.executed'   => ['icon' => 'fa-bolt',                'label' => 'Action exécutée',       'color' => 'color:#38bdf8;'],
    'protection.rolled_back'=> ['icon' => 'fa-rotate-left',         'label' => 'Action annulée',        'color' => 'color:#f59e0b;'],
    // Paramètres
    'setting.updated'       => ['icon' => 'fa-gears',               'label' => 'Paramètre modifié',     'color' => 'color:#a78bfa;'],
];

$getActionMeta = fn(string $a) => $actionMeta[$a] ?? ['icon' => 'fa-circle-dot', 'label' => $a, 'color' => 'color:#64748b;'];
@endphp

<style>
.audit-filter-bar { display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; padding:14px 18px; background:var(--card-bg); border-radius:12px; border:1px solid var(--border-color); margin-bottom:18px; }
.audit-filter-bar label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:4px; }
.audit-filter-bar input,
.audit-filter-bar select { background:var(--input-bg,var(--bg-primary)); border:1px solid var(--border-color); border-radius:7px; color:var(--text-primary); font-size:12px; padding:6px 10px; height:34px; }
.audit-filter-bar input[type=date] { width:140px; }
.audit-filter-bar select { min-width:160px; }
.audit-filter-bar input[type=search] { min-width:200px; }
.audit-filter-bar .btn { height:34px; padding:0 14px; font-size:12px; }

.channel-tabs { display:flex; gap:6px; margin-bottom:16px; }
.channel-tab { padding:6px 16px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; border:1px solid var(--border-color); color:var(--text-muted); background:transparent; transition:all .15s; }
.channel-tab:hover { background:var(--card-bg); color:var(--text-primary); }
.channel-tab.active { background:var(--accent); color:#fff; border-color:var(--accent); }

.audit-table { width:100%; border-collapse:collapse; font-size:12px; }
.audit-table th { padding:9px 12px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); font-weight:600; border-bottom:1px solid var(--border-color); white-space:nowrap; }
.audit-table td { padding:9px 12px; border-bottom:1px solid var(--border-color); vertical-align:middle; }
.audit-table tr:hover td { background:var(--hover-bg, rgba(255,255,255,.03)); }

.action-cell { display:flex; align-items:center; gap:8px; }
.action-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.06); flex-shrink:0; }
.action-icon i { font-size:12px; }
.action-label { font-weight:600; color:var(--text-primary); line-height:1.2; }
.action-raw { font-size:10px; color:var(--text-muted); font-family:monospace; }

.ctx-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:5px; font-size:10px; background:rgba(255,255,255,.06); color:var(--text-muted); margin:1px; }
.ctx-pill strong { color:var(--text-primary); }

.channel-badge { display:inline-block; padding:2px 8px; border-radius:5px; font-size:10px; font-weight:700; text-transform:uppercase; }
.channel-badge.audit { background:rgba(56,189,248,.15); color:#38bdf8; }
.channel-badge.soc   { background:rgba(251,146,60,.15);  color:#fb923c; }

.smart-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.smart-stat { flex:1; min-width:120px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; padding:14px 18px; }
.smart-stat-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); font-weight:600; margin-bottom:4px; }
.smart-stat-value { font-size:22px; font-weight:800; line-height:1; }
</style>

<section class="smart-stats section-gap">
    <div class="smart-stat">
        <div class="smart-stat-label"><i class="fa-solid fa-list" style="margin-right:5px;color:var(--accent);"></i>Total entrées</div>
        <div class="smart-stat-value">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="smart-stat">
        <div class="smart-stat-label"><i class="fa-solid fa-calendar-day" style="margin-right:5px;color:#38bdf8;"></i>Aujourd'hui</div>
        <div class="smart-stat-value" style="{{ $stats['today'] > 0 ? 'color:#38bdf8;' : '' }}">{{ $stats['today'] }}</div>
    </div>
    <div class="smart-stat">
        <div class="smart-stat-label"><i class="fa-solid fa-users" style="margin-right:5px;color:#a78bfa;"></i>Utilisateurs actifs</div>
        <div class="smart-stat-value" style="color:#a78bfa;">{{ $stats['users'] }}</div>
    </div>
    <div class="smart-stat">
        <div class="smart-stat-label"><i class="fa-solid fa-tags" style="margin-right:5px;color:#22c55e;"></i>Types d'actions</div>
        <div class="smart-stat-value" style="color:#22c55e;">{{ $stats['actions'] }}</div>
    </div>
</section>

<section class="section-gap">

    {{-- Filtre canal --}}
    <div class="channel-tabs">
        @foreach(['all' => 'Tout', 'audit' => 'Audit', 'soc' => 'Détection SOC'] as $ch => $label)
        <a href="{{ route('platform.audit-log.index', array_merge(request()->query(), ['channel' => $ch])) }}"
           class="channel-tab {{ $channel === $ch ? 'active' : '' }}">
            @if($ch === 'audit') <i class="fa-solid fa-shield-halved" style="margin-right:5px;"></i>
            @elseif($ch === 'soc') <i class="fa-solid fa-biohazard" style="margin-right:5px;"></i>
            @else <i class="fa-solid fa-list" style="margin-right:5px;"></i>
            @endif
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Barre de filtres --}}
    <form method="GET" action="{{ route('platform.audit-log.index') }}" class="audit-filter-bar">
        @if($channel !== 'all') <input type="hidden" name="channel" value="{{ $channel }}"> @endif

        <div>
            <label>Action</label>
            <select name="action">
                <option value="">Toutes</option>
                @foreach($actionList as $act)
                    @php $m = $getActionMeta($act); @endphp
                    <option value="{{ $act }}" {{ $action === $act ? 'selected' : '' }}>{{ $m['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Utilisateur</label>
            <select name="user_id">
                <option value="">Tous</option>
                @foreach($userList as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Du</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}">
        </div>
        <div>
            <label>Au</label>
            <input type="date" name="date_to" value="{{ $dateTo }}">
        </div>
        <div>
            <label>Recherche</label>
            <input type="search" name="q" value="{{ $q }}" placeholder="email, IP, action…">
        </div>

        <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-soft" style="height:34px;">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrer
            </button>
        </div>
        @if($action || $userId || $dateFrom || $dateTo || $q)
        <div>
            <label>&nbsp;</label>
            <a href="{{ route('platform.audit-log.index', $channel !== 'all' ? ['channel' => $channel] : []) }}" class="btn btn-soft" style="height:34px;">
                <i class="fa-solid fa-xmark"></i> Réinitialiser
            </a>
        </div>
        @endif
    </form>

    {{-- Tableau --}}
    <div class="card" style="overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th style="width:140px;">Horodatage</th>
                        <th>Action</th>
                        <th>Utilisateur</th>
                        <th style="width:110px;">IP</th>
                        <th style="width:70px;">Canal</th>
                        <th>Contexte</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php $m = $getActionMeta($log->action); @endphp
                    <tr>
                        <td style="font-size:11px; font-family:monospace; color:var(--text-muted); white-space:nowrap;">
                            {{ $log->created_at->format('d/m/Y') }}<br>
                            <span style="color:var(--text-primary); font-weight:600;">{{ $log->created_at->format('H:i:s') }}</span>
                        </td>

                        <td>
                            <div class="action-cell">
                                <div class="action-icon"><i class="fa-solid {{ $m['icon'] }}" style="{{ $m['color'] }}"></i></div>
                                <div>
                                    <div class="action-label">{{ $m['label'] }}</div>
                                    <div class="action-raw">{{ $log->action }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if($log->user_email)
                            <div style="font-weight:600; font-size:12px;">{{ $log->user_name ?? $log->user_email }}</div>
                            <div style="font-size:10px; color:var(--text-muted);">{{ $log->user_email }}</div>
                            @else
                            <span style="color:var(--text-muted); font-size:11px;">Système</span>
                            @endif
                        </td>

                        <td style="font-size:11px; font-family:monospace; color:var(--text-muted);">
                            {{ $log->ip_address ?? '—' }}
                        </td>

                        <td>
                            <span class="channel-badge {{ $log->channel }}">{{ $log->channel }}</span>
                        </td>

                        <td>
                            @if($log->context)
                                @foreach(array_filter($log->context, fn($v) => !in_array($v, [null, '', 0, '0']) || is_string($v)) as $k => $v)
                                    @if(!in_array($k, ['user_id','user_email','ip','at','severity']))
                                    <span class="ctx-pill"><span>{{ str_replace('_', ' ', $k) }}</span> <strong>{{ is_bool($v) ? ($v ? 'oui' : 'non') : (is_array($v) ? json_encode($v) : $v) }}</strong></span>
                                    @endif
                                @endforeach
                            @else
                            <span style="color:var(--text-muted); font-size:11px;">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-shield-halved" style="font-size:28px; display:block; margin-bottom:10px; opacity:.3;"></i>
                            Aucune entrée d'audit pour ces critères.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div style="padding:12px 18px; border-top:1px solid var(--border-color);">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</section>

@endsection
