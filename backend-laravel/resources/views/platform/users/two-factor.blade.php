@extends('layouts.soc')

@section('title', 'RansomShield — Authentification 2FA')
@section('page_title', 'Authentification à deux facteurs')
@section('page_subtitle', 'Sécuriser votre compte avec une application TOTP')

@section('content')

<style>
    .tfa-card {
        max-width: 640px;
        margin: 0 auto;
        background: var(--bg-card, #0d1b2a);
        border: 1px solid rgba(56,189,248,0.12);
        border-radius: 20px;
        padding: 32px 36px;
    }
    .tfa-header {
        display: flex; align-items: center; gap: 16px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(56,189,248,0.10);
        margin-bottom: 28px;
    }
    .tfa-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .tfa-icon.enabled  { background: rgba(34,197,94,0.15);  color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
    .tfa-icon.disabled { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.25); }

    .tfa-status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 999px;
        font-size: 11px; font-weight: 700; letter-spacing: 0.8px;
        text-transform: uppercase; margin-top: 4px;
    }
    .tfa-status-badge.on  { background: rgba(34,197,94,0.12);  color: #22c55e; border: 1px solid rgba(34,197,94,0.25); }
    .tfa-status-badge.off { background: rgba(245,158,11,0.10); color: #f59e0b; border: 1px solid rgba(245,158,11,0.22); }

    .tfa-section-title { font-size: 14px; font-weight: 700; color: #e2eaf5; margin-bottom: 8px; }
    .tfa-section-body  { font-size: 13px; color: #5d7a99; line-height: 1.65; margin-bottom: 20px; }

    /* QR code */
    .qr-wrap {
        display: flex; flex-direction: column; align-items: center;
        gap: 16px; padding: 24px;
        background: rgba(56,189,248,0.04);
        border: 1px dashed rgba(56,189,248,0.2);
        border-radius: 14px;
        margin-bottom: 24px;
    }
    #qrcode canvas, #qrcode img { border-radius: 8px; }

    .secret-row {
        display: flex; align-items: center; gap: 10px;
        font-family: 'Courier New', monospace;
        font-size: 14px; font-weight: 700;
        letter-spacing: 3px; color: #38bdf8;
        word-break: break-all;
        text-align: center;
    }
    .btn-copy {
        padding: 5px 10px; border-radius: 8px;
        background: rgba(56,189,248,0.10);
        border: 1px solid rgba(56,189,248,0.2);
        color: #38bdf8; font-size: 12px; cursor: pointer;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .btn-copy:hover { background: rgba(56,189,248,0.20); }

    /* field */
    .field-label {
        display: block; font-size: 11px; font-weight: 700;
        letter-spacing: 0.8px; text-transform: uppercase;
        color: #5d7a99; margin-bottom: 8px;
    }
    .input-code {
        display: block; width: 100%;
        padding: 14px; background: #080f1d;
        border: 1px solid rgba(56,189,248,0.14);
        border-radius: 12px; color: #e2eaf5;
        font-size: 24px; font-family: 'Courier New', monospace;
        font-weight: 700; letter-spacing: 8px; text-align: center;
        outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        margin-bottom: 20px;
    }
    .input-code:focus {
        border-color: rgba(56,189,248,0.35);
        box-shadow: 0 0 0 3px rgba(56,189,248,0.12);
    }

    /* buttons */
    .btn-enable {
        width: 100%; padding: 13px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border: none; border-radius: 11px;
        color: #fff; font-size: 14px; font-weight: 700;
        font-family: inherit; cursor: pointer;
        transition: opacity 0.2s, transform 0.15s;
        box-shadow: 0 4px 16px rgba(34,197,94,0.25);
    }
    .btn-enable:hover { opacity: 0.9; transform: translateY(-1px); }

    .btn-disable {
        width: 100%; padding: 13px;
        background: rgba(248,113,113,0.10);
        border: 1px solid rgba(248,113,113,0.25);
        border-radius: 11px;
        color: #f87171; font-size: 14px; font-weight: 700;
        font-family: inherit; cursor: pointer;
        transition: background 0.2s;
    }
    .btn-disable:hover { background: rgba(248,113,113,0.18); }

    .alert-ok  {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border-radius: 10px; margin-bottom: 20px;
        background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25);
        font-size: 13px; color: #22c55e;
    }
    .alert-err {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border-radius: 10px; margin-bottom: 20px;
        background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.25);
        font-size: 13px; color: #f87171;
    }

    .hint {
        padding: 12px 16px; border-radius: 12px;
        background: rgba(56,189,248,0.04);
        border: 1px solid rgba(56,189,248,0.12);
        font-size: 12px; color: #5d7a99; line-height: 1.6; margin-top: 20px;
    }
    .hint i { color: #38bdf8; margin-right: 6px; }

    .back-link {
        display: inline-flex; align-items: center; gap: 8px;
        margin-top: 28px; font-size: 13px; color: #5d7a99;
        text-decoration: none; transition: color 0.2s;
    }
    .back-link:hover { color: #38bdf8; }
</style>

<div class="tfa-card">

    @if (session('success'))
        <div class="alert-ok">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-err">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="tfa-header">
        <div class="tfa-icon {{ $user->hasTwoFactorEnabled() ? 'enabled' : 'disabled' }}">
            <i class="fa-solid fa-{{ $user->hasTwoFactorEnabled() ? 'shield-check' : 'shield-exclamation' }}"></i>
        </div>
        <div>
            <div style="font-size:17px;font-weight:700;color:#e2eaf5;">Authentification à deux facteurs</div>
            <div class="tfa-status-badge {{ $user->hasTwoFactorEnabled() ? 'on' : 'off' }}">
                <i class="fa-solid fa-circle" style="font-size:7px;"></i>
                {{ $user->hasTwoFactorEnabled() ? 'Activée' : 'Désactivée' }}
            </div>
        </div>
    </div>

    @if ($user->hasTwoFactorEnabled())

        {{-- ── 2FA ACTIVE — afficher désactivation ── --}}
        <div class="tfa-section-title">
            <i class="fa-solid fa-circle-check" style="color:#22c55e;margin-right:8px;"></i>
            La 2FA est active sur ce compte
        </div>
        <div class="tfa-section-body">
            Votre compte est protégé. À chaque connexion, un code à 6 chiffres généré par votre application sera requis.<br>
            Pour désactiver la 2FA, saisissez un code valide ci-dessous.
        </div>

        <form method="POST" action="{{ route('platform.two-factor.disable') }}">
            @csrf
            <label class="field-label">Code de confirmation</label>
            <input
                type="text" name="code"
                class="input-code"
                inputmode="numeric" pattern="\d{6}" maxlength="6"
                autocomplete="one-time-code" placeholder="000000"
            >
            <button type="submit" class="btn-disable">
                <i class="fa-solid fa-shield-xmark" style="margin-right:8px;"></i>
                Désactiver la 2FA
            </button>
        </form>

    @else

        {{-- ── 2FA INACTIVE — afficher activation ── --}}
        <div class="tfa-section-title">
            <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;margin-right:8px;"></i>
            Étape 1 — Scanner le QR code
        </div>
        <div class="tfa-section-body">
            Ouvrez <strong style="color:#e2eaf5;">Google Authenticator</strong>, <strong style="color:#e2eaf5;">Authy</strong> ou toute autre application TOTP, puis scannez ce code.
        </div>

        <div class="qr-wrap">
            <div id="qrcode"></div>
            <div class="secret-row">
                <span id="secret-text">{{ $secret }}</span>
                <button type="button" class="btn-copy" onclick="copySecret()">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
            <div style="font-size:11px;color:#5d7a99;">Saisie manuelle si le QR code ne fonctionne pas</div>
        </div>

        <div class="tfa-section-title">
            <i class="fa-solid fa-keyboard" style="color:#38bdf8;margin-right:8px;"></i>
            Étape 2 — Confirmer avec un code
        </div>
        <div class="tfa-section-body">
            Saisissez le code à 6 chiffres affiché dans votre application pour confirmer la configuration.
        </div>

        <form method="POST" action="{{ route('platform.two-factor.enable') }}">
            @csrf
            <label class="field-label">Code de vérification</label>
            <input
                type="text" name="code"
                class="input-code"
                inputmode="numeric" pattern="\d{6}" maxlength="6"
                autocomplete="one-time-code" autofocus placeholder="000000"
            >
            <button type="submit" class="btn-enable">
                <i class="fa-solid fa-shield-check" style="margin-right:8px;"></i>
                Activer la 2FA
            </button>
        </form>

        <div class="hint">
            <i class="fa-solid fa-circle-info"></i>
            Conservez une copie du secret en lieu sûr. Si vous perdez l'accès à votre application TOTP, un administrateur devra réinitialiser votre compte.
        </div>

    @endif

    <a href="{{ route('platform.users.edit', auth()->user()) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Retour au profil
    </a>

</div>

@if (! $user->hasTwoFactorEnabled() && $qrUri)
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSi2Z1WCT3Pj/gRR4GJE4gEpFBf7+sC59Skg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
new QRCode(document.getElementById('qrcode'), {
    text: @json($qrUri),
    width: 200, height: 200,
    colorDark: '#050f1c',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M,
});

function copySecret() {
    navigator.clipboard.writeText(document.getElementById('secret-text').textContent.trim());
    const btn = document.querySelector('.btn-copy');
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i>'; }, 2000);
}
</script>
@endif

@endsection
