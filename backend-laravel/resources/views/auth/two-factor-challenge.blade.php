<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RansomShield — Vérification 2FA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #050f1c;
            --panel:        #080f1d;
            --panel-inner:  #0a1628;
            --border:       rgba(56, 189, 248, 0.14);
            --border-hi:    rgba(56, 189, 248, 0.35);
            --accent:       #38bdf8;
            --accent-dim:   rgba(56, 189, 248, 0.18);
            --accent-glow:  rgba(56, 189, 248, 0.28);
            --text:         #e2eaf5;
            --muted:        #5d7a99;
            --danger:       #f87171;
            --success:      #22c55e;
            --warning:      #f59e0b;
        }

        html, body {
            height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.18) 2px, rgba(0,0,0,0.18) 4px);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(56,189,248,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56,189,248,0.03) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .card {
            position: relative;
            z-index: 1;
            width: 420px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 44px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.5);
        }

        .shield-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 32px;
        }

        .shield-icon {
            width: 46px; height: 46px;
            border-radius: 13px;
            background: linear-gradient(145deg, #38bdf8, #1d6fa8);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #03111f;
            box-shadow: 0 0 24px var(--accent-glow);
        }

        .shield-title { font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
        .shield-title span { color: var(--accent); }
        .shield-sub { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); margin-top: 3px; }

        .otp-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border-radius: 999px;
            border: 1px solid rgba(245,158,11,0.3);
            background: rgba(245,158,11,0.08);
            font-size: 11px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--warning);
            margin-bottom: 24px;
        }

        .heading { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
        .sub { font-size: 13px; color: var(--muted); line-height: 1.5; margin-bottom: 28px; }

        .alert-error {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px;
            background: rgba(248,113,113,0.10);
            border: 1px solid rgba(248,113,113,0.28);
            border-radius: 10px; margin-bottom: 20px;
            font-size: 13px; color: var(--danger);
        }

        .field { margin-bottom: 22px; }
        .field-label {
            display: block; font-size: 11px; font-weight: 700;
            letter-spacing: 0.8px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }

        /* OTP code — grand et centré */
        .input-code {
            display: block; width: 100%;
            padding: 16px;
            background: var(--panel-inner);
            border: 1px solid var(--border);
            border-radius: 13px;
            color: var(--text);
            font-size: 28px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            letter-spacing: 10px;
            text-align: center;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-code:focus {
            border-color: var(--border-hi);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }
        .input-code.err { border-color: rgba(248,113,113,0.45); }

        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #38bdf8 0%, #1a7abf 100%);
            border: none; border-radius: 11px;
            color: #030f1c; font-size: 14px; font-weight: 800;
            font-family: inherit; cursor: pointer;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(56,189,248,0.30);
        }
        .btn-submit:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(56,189,248,0.40); }

        .back-link {
            display: block; margin-top: 20px; text-align: center;
            font-size: 13px; color: var(--muted); text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--accent); }

        .hint {
            margin-top: 24px; padding: 14px 16px;
            background: rgba(56,189,248,0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 12px; color: var(--muted); line-height: 1.6;
        }
        .hint i { color: var(--accent); margin-right: 6px; }
    </style>
</head>
<body>

<div class="card">
    <div class="shield-wrap">
        <div class="shield-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <div class="shield-title">Ransom<span>Shield</span></div>
            <div class="shield-sub">Security Operations Center</div>
        </div>
    </div>

    <div class="otp-badge">
        <i class="fa-solid fa-mobile-screen-button"></i>
        Vérification en 2 étapes
    </div>

    <div class="heading">Code d'authentification</div>
    <div class="sub">Saisissez le code à 6 chiffres affiché dans votre application d'authentification (Google Authenticator, Authy…)</div>

    @if ($errors->any())
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('platform.2fa.verify') }}">
        @csrf

        <div class="field">
            <label class="field-label" for="code">Code TOTP</label>
            <input
                type="text"
                id="code"
                name="code"
                class="input-code {{ $errors->has('code') ? 'err' : '' }}"
                inputmode="numeric"
                pattern="\d{6}"
                maxlength="6"
                autocomplete="one-time-code"
                autofocus
                placeholder="000000"
            >
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-check-circle" style="margin-right:9px;"></i>
            Vérifier et accéder
        </button>
    </form>

    <a href="{{ route('platform.login') }}" class="back-link">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i>
        Retour à la connexion
    </a>

    <div class="hint">
        <i class="fa-solid fa-circle-info"></i>
        Le code est valable 30 secondes. Si votre code est refusé, vérifiez que l'heure de votre téléphone est synchronisée.
    </div>
</div>

<script>
// Auto-format : espace au milieu pour lisibilité
const inp = document.getElementById('code');
inp.addEventListener('input', () => {
    inp.value = inp.value.replace(/\D/g, '').slice(0, 6);
    if (inp.value.length === 6) {
        inp.closest('form').submit();
    }
});
</script>
</body>
</html>
