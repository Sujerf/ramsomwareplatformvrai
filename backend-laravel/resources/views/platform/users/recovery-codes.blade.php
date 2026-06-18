@extends('layouts.soc')

@section('title', 'RansomShield — Codes de secours 2FA')
@section('page_title', 'Codes de secours')
@section('page_subtitle', 'Codes d\'urgence pour accéder à votre compte sans application TOTP')

@section('content')
<style>
    .rc-card { background:var(--card-bg); border:1px solid var(--border-color); border-radius:16px; padding:28px 32px; max-width:580px; margin:0 auto; }
    .rc-card + .rc-card { margin-top:20px; }

    .rc-banner { display:flex; align-items:flex-start; gap:14px; padding:16px 20px;
        border-radius:10px; margin-bottom:24px; font-size:13px; line-height:1.6; }
    .rc-banner.info    { background:rgba(56,189,248,.1);  border:1px solid rgba(56,189,248,.25); color:#93c5fd; }
    .rc-banner.warning { background:rgba(245,158,11,.1);  border:1px solid rgba(245,158,11,.25); color:#fcd34d; }
    .rc-banner.success { background:rgba(34,197,94,.1);   border:1px solid rgba(34,197,94,.25);  color:#86efac; }
    .rc-banner i { font-size:18px; margin-top:1px; flex-shrink:0; }

    .rc-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:20px 0; }
    .rc-code {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 2px;
        text-align: center;
        padding: 12px 10px;
        border-radius: 8px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        user-select: all;
        cursor: text;
    }
    .rc-code.used { opacity:.35; text-decoration:line-through; color:var(--text-muted); }

    .rc-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }

    .rc-count { font-size:28px; font-weight:800; }
    .rc-count-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-top:4px; }
    .rc-count-row { display:flex; align-items:center; gap:18px; margin-bottom:20px; }
    .rc-count-icon { width:48px; height:48px; border-radius:12px; display:grid; place-items:center; font-size:20px; }

    .copy-btn { cursor:pointer; background:none; border:none; color:var(--text-muted); padding:4px 6px;
        border-radius:5px; font-size:12px; transition:color .15s; }
    .copy-btn:hover { color:var(--accent); }

    .regen-form { border-top:1px solid var(--border-color); padding-top:20px; margin-top:20px; }
    .regen-form label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
        color:var(--text-muted); display:block; margin-bottom:6px; }
    .regen-form input { background:var(--bg-primary); border:1px solid var(--border-color); border-radius:8px;
        color:var(--text-primary); font-size:14px; padding:8px 14px; width:160px; }
    .regen-form input.is-invalid { border-color:#ef4444; }
    .invalid-feedback { color:#ef4444; font-size:11px; margin-top:4px; display:block; }
    .regen-row { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
</style>

<div style="max-width:580px; margin:0 auto;">

    @if(session('success'))
    <div class="rc-banner success" style="margin-bottom:20px;">
        <i class="fa-solid fa-circle-check"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- ── Codes à afficher (juste après génération) ──────────────────── --}}
    @if($flashCodes)
    <div class="rc-card" style="border-color:rgba(34,197,94,.3);">
        <div class="rc-banner warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Conservez ces codes maintenant.</strong>
                Ils ne seront plus affichés en clair après avoir quitté cette page.
                Notez-les ou copiez-les dans un gestionnaire de mots de passe.
            </div>
        </div>

        <div class="rc-grid" id="codes-grid">
            @foreach($flashCodes as $code)
            <div class="rc-code">{{ $code }}</div>
            @endforeach
        </div>

        <div class="rc-actions">
            <button type="button" class="btn btn-primary" onclick="copyAllCodes()">
                <i class="fa-solid fa-copy"></i> Copier tous les codes
            </button>
            <a href="{{ route('platform.users.edit', auth()->user()) }}" class="btn btn-soft">
                <i class="fa-solid fa-check"></i> J'ai noté mes codes
            </a>
        </div>
    </div>

    <script>
    function copyAllCodes() {
        const codes = [...document.querySelectorAll('#codes-grid .rc-code')].map(el => el.textContent.trim());
        navigator.clipboard.writeText(codes.join('\n')).then(() => {
            const btn = event.target.closest('button');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copié !';
            setTimeout(() => btn.innerHTML = orig, 2000);
        });
    }
    </script>

    @else

    {{-- ── Vue des codes existants ────────────────────────────────────── --}}
    <div class="rc-card">
        <div class="rc-count-row">
            <div class="rc-count-icon" style="background:{{ $remaining > 2 ? 'rgba(34,197,94,.12)' : 'rgba(239,68,68,.12)' }}; color:{{ $remaining > 2 ? '#22c55e' : '#ef4444' }};">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <div class="rc-count" style="color:{{ $remaining > 2 ? '#22c55e' : '#ef4444' }};">{{ $remaining }}</div>
                <div class="rc-count-label">code(s) de secours restant(s)</div>
            </div>
        </div>

        @if($remaining <= 2)
        <div class="rc-banner warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>Il vous reste peu de codes. Régénérez-en dès que possible.</div>
        </div>
        @else
        <div class="rc-banner info">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Les codes de secours permettent d'accéder à votre compte si vous perdez
                l'accès à votre application TOTP. Chaque code n'est utilisable qu'une seule fois.
            </div>
        </div>
        @endif

        @php
            $codes = auth()->user()->two_factor_recovery_codes ?? [];
        @endphp
        @if($codes)
        <div class="rc-grid">
            @foreach($codes as $entry)
            <div class="rc-code {{ $entry['used'] ? 'used' : '' }}">
                {{ $entry['code'] }}
                @if($entry['used']) <span style="font-size:9px; display:block; font-weight:400; letter-spacing:0; margin-top:2px;">utilisé</span> @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- ── Régénérer les codes ─────────────────────────────────────────── --}}
    <div class="rc-card" style="margin-top:20px;">
        <h3 style="font-size:14px; font-weight:700; margin:0 0 8px;">
            <i class="fa-solid fa-rotate" style="color:var(--accent); margin-right:8px;"></i>
            Régénérer les codes de secours
        </h3>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 16px; line-height:1.6;">
            Générer de nouveaux codes invalide immédiatement les anciens.
            Confirmez avec votre application TOTP.
        </p>

        <form method="POST" action="{{ route('platform.two-factor.regenerate-codes') }}" class="regen-form">
            @csrf
            <div class="regen-row">
                <div>
                    <label for="code">Code TOTP (6 chiffres)</label>
                    <input type="text" id="code" name="code" inputmode="numeric" maxlength="6"
                           placeholder="000000" autocomplete="one-time-code"
                           class="{{ $errors->has('code') ? 'is-invalid' : '' }}">
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="btn btn-soft"
                        onclick="return confirm('Régénérer invalide vos anciens codes. Continuer ?')">
                    <i class="fa-solid fa-rotate"></i> Régénérer
                </button>
            </div>
        </form>
    </div>

    <div style="margin-top:16px; text-align:center;">
        <a href="{{ route('platform.two-factor.setup') }}" style="font-size:12px; color:var(--text-muted);">
            <i class="fa-solid fa-arrow-left" style="margin-right:5px;"></i>Retour à la gestion 2FA
        </a>
    </div>
</div>

@endsection
