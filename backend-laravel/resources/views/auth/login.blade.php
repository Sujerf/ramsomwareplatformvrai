<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RansomShield — Connexion</title>
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
        }

        html, body {
            height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }

        /* ── animated scanlines ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0, 0, 0, 0.18) 2px,
                rgba(0, 0, 0, 0.18) 4px
            );
            pointer-events: none;
            z-index: 0;
        }

        /* ── hex grid ── */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.03) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        /* ── layout ── */
        .page {
            position: relative;
            z-index: 1;
            display: flex;
            height: 100vh;
        }

        /* ── left panel ── */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 56px;
            border-right: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -200px;
            left: -200px;
            width: 600px;
            height: 600px;
            background: radial-gradient(ellipse, rgba(56, 189, 248, 0.07) 0%, transparent 65%);
            pointer-events: none;
        }

        .shield-wrap {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 48px;
        }

        .shield-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(145deg, #38bdf8, #1d6fa8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #03111f;
            box-shadow: 0 0 32px var(--accent-glow), 0 4px 16px rgba(0,0,0,0.5);
            flex-shrink: 0;
        }

        .shield-title {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text);
            line-height: 1;
        }

        .shield-title span { color: var(--accent); }

        .shield-sub {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--muted);
            margin-top: 5px;
        }

        .left-description {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.7;
            max-width: 360px;
            margin-bottom: 48px;
        }

        .left-description strong {
            color: var(--accent);
            font-weight: 600;
        }

        /* stat pills */
        .stat-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: rgba(56, 189, 248, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
            color: var(--muted);
        }

        .stat-item i {
            font-size: 15px;
            color: var(--accent);
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .stat-item strong {
            color: var(--text);
            font-weight: 600;
        }

        /* ── right panel (form) ── */
        .right-panel {
            width: 420px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 44px;
            background: var(--panel);
            position: relative;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            bottom: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(56, 189, 248, 0.06) 0%, transparent 65%);
            pointer-events: none;
        }

        /* top label */
        .access-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--accent-dim);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 28px;
            width: fit-content;
        }

        .access-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: blink 1.6s ease infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        .form-heading {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }

        .form-sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* error */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: rgba(248, 113, 113, 0.10);
            border: 1px solid rgba(248, 113, 113, 0.28);
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--danger);
        }

        /* fields */
        .field { margin-bottom: 20px; }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
        }

        .input-box i.ico {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--muted);
            pointer-events: none;
            z-index: 2;
        }

        .input-box input {
            display: block;
            width: 100%;
            padding: 13px 16px 13px 42px;
            background: var(--panel-inner);
            border: 1px solid var(--border);
            border-radius: 11px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            position: relative;
            z-index: 1;
        }

        /* kill browser autofill colouring */
        .input-box input:-webkit-autofill,
        .input-box input:-webkit-autofill:hover,
        .input-box input:-webkit-autofill:focus {
            -webkit-text-fill-color: #e2eaf5 !important;
            -webkit-box-shadow: 0 0 0 1000px #0a1628 inset !important;
            caret-color: #e2eaf5;
        }

        .input-box input:focus {
            border-color: var(--border-hi);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .input-box input.err { border-color: rgba(248,113,113,0.45); }

        .btn-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 13px;
            cursor: pointer;
            padding: 4px 6px;
            transition: color 0.2s;
        }
        .btn-eye:hover { color: var(--accent); }

        /* remember */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 26px;
            font-size: 13px;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }

        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
        }

        /* submit */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #38bdf8 0%, #1a7abf 100%);
            border: none;
            border-radius: 11px;
            color: #030f1c;
            font-size: 14px;
            font-weight: 800;
            font-family: inherit;
            letter-spacing: 0.3px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(56, 189, 248, 0.30);
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(56, 189, 248, 0.40);
        }

        .btn-submit:active { transform: translateY(0); }

        /* footer */
        .form-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: var(--muted);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: pulse-g 2s ease infinite;
            flex-shrink: 0;
        }

        @keyframes pulse-g {
            0%, 100% { opacity: 1; box-shadow: 0 0 8px var(--success); }
            50% { opacity: 0.6; box-shadow: 0 0 3px var(--success); }
        }

        /* ── responsive ── */
        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 36px 28px; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── LEFT — branding ── --}}
    <div class="left-panel">
        <div class="shield-wrap">
            <div class="shield-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="shield-title">Ransom<span>Shield</span></div>
                <div class="shield-sub">Security Operations Center</div>
            </div>
        </div>

        <p class="left-description">
            Plateforme de <strong>cybersurveillance en temps réel</strong> orientée ransomware.<br>
            Détection comportementale, corrélation d'incidents et réponse automatisée.
        </p>

        <div class="stat-list">
            <div class="stat-item">
                <i class="fa-solid fa-microchip"></i>
                <div>Surveillance <strong>multi-agents</strong> en continu</div>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-fire"></i>
                <div>Moteur de détection <strong>dynamique</strong></div>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-lock"></i>
                <div>Actions de protection avec <strong>approbation humaine</strong></div>
            </div>
            <div class="stat-item">
                <i class="fa-solid fa-diagram-project"></i>
                <div>Inventaire réseau <strong>automatique</strong></div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT — form ── --}}
    <div class="right-panel">

        <div class="access-badge">
            <span class="access-dot"></span>
            Accès sécurisé
        </div>

        <div class="form-heading">Connexion SOC</div>
        <div class="form-sub">Identifiez-vous pour accéder à la console de surveillance.</div>

        {{-- Bannière quand on arrive depuis une page protégée --}}
        @if (session()->has('url.intended') || request()->query('from') === 'auth')
            <div style="
                display:flex; align-items:center; gap:10px;
                padding:12px 14px; border-radius:14px; margin-bottom:16px;
                background:rgba(56,189,248,.08);
                border:1px solid rgba(56,189,248,.22);
                color:#94c3d8; font-size:13px; font-weight:700;
            ">
                <i class="fa-solid fa-lock" style="color:#38bdf8; font-size:15px; flex-shrink:0;"></i>
                <span>Cette page nécessite une connexion. Identifiez-vous pour continuer.</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('platform.login.post') }}" autocomplete="on">
            @csrf

            <div class="field">
                <label class="field-label" for="email">Adresse e-mail</label>
                <div class="input-box">
                    <i class="fa-solid fa-envelope ico"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="admin@ransomshield.local"
                        autocomplete="email"
                        autofocus
                        class="{{ $errors->has('email') ? 'err' : '' }}"
                    >
                </div>
            </div>

            <div class="field">
                <label class="field-label" for="password">Mot de passe</label>
                <div class="input-box">
                    <i class="fa-solid fa-lock ico"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••••••"
                        autocomplete="current-password"
                    >
                    <button type="button" class="btn-eye" onclick="togglePw()" tabindex="-1">
                        <i class="fa-solid fa-eye" id="eye-ico"></i>
                    </button>
                </div>
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember">
                Se souvenir de moi
            </label>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket" style="margin-right:9px;"></i>
                Accéder au SOC
            </button>
        </form>

        <div class="form-footer">
            <span class="status-dot"></span>
            RansomShield — Tous les accès sont enregistrés et surveillés.
        </div>

    </div>
</div>

<script>
function togglePw() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('eye-ico');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'fa-solid fa-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
