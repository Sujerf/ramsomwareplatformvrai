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
            --bg-main:       #07111f;
            --bg-panel:      #0d1b2e;
            --bg-panel-soft: #10243d;
            --border-soft:   rgba(148, 163, 184, 0.18);
            --text-main:     #e5edf7;
            --text-muted:    #8ea2bd;
            --accent:        #38bdf8;
            --accent-glow:   rgba(56, 189, 248, 0.22);
            --accent-2:      #22c55e;
            --danger:        #f87171;
            --radius:        14px;
            --transition:    0.22s ease;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(56, 189, 248, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* ambient glow */
        body::after {
            content: '';
            position: fixed;
            top: -160px;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 400px;
            background: radial-gradient(ellipse at center, rgba(56, 189, 248, 0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        /* brand */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 40%, #1d4ed8));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #03111f;
            margin: 0 auto 16px;
            box-shadow: 0 8px 32px var(--accent-glow);
        }

        .brand-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-main);
        }

        .brand-name span {
            color: var(--accent);
        }

        .brand-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* card */
        .card {
            background: var(--bg-panel);
            border: 1px solid var(--border-soft);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        /* error box */
        .error-box {
            background: color-mix(in srgb, var(--danger) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--danger) 30%, transparent);
            border-radius: var(--radius);
            padding: 12px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--danger);
        }

        /* form */
        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            background: var(--bg-panel-soft);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            color: var(--text-main);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .input-wrap input:focus {
            border-color: color-mix(in srgb, var(--accent) 50%, transparent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .input-wrap input.is-invalid {
            border-color: color-mix(in srgb, var(--danger) 50%, transparent);
        }

        /* show password toggle */
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
            padding: 4px;
            transition: color var(--transition);
        }

        .toggle-pw:hover { color: var(--accent); }

        /* remember */
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
            cursor: pointer;
        }

        .remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        /* submit */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 50%, #1d4ed8));
            border: none;
            border-radius: var(--radius);
            color: #03111f;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: opacity var(--transition), transform var(--transition), box-shadow var(--transition);
            box-shadow: 0 4px 16px var(--accent-glow);
        }

        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px var(--accent-glow);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: var(--text-muted);
            opacity: 0.6;
        }

        .status-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-2);
            margin-right: 6px;
            box-shadow: 0 0 6px var(--accent-2);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">

    <div class="brand">
        <div class="brand-logo">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="brand-name">Ransom<span>Shield</span></div>
        <div class="brand-sub">Security Operations Center</div>
    </div>

    <div class="card">
        <div class="card-title">Connexion</div>
        <div class="card-sub">Accès réservé aux analystes SOC autorisés.</div>

        @if ($errors->any())
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('platform.login') }}">
            @csrf

            <div class="field">
                <label for="email">Adresse e-mail</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="admin@ransomshield.local"
                        autocomplete="email"
                        autofocus
                        class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                    >
                </div>
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••••••"
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-pw" onclick="togglePw()" title="Afficher / masquer">
                        <i class="fa-solid fa-eye" id="pw-eye"></i>
                    </button>
                </div>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember">
                Se souvenir de moi
            </label>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket" style="margin-right:8px;"></i>
                Accéder au SOC
            </button>
        </form>
    </div>

    <div class="login-footer">
        <span class="status-dot"></span>RansomShield — Système de protection contre les ransomwares
    </div>

</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    const eye   = document.getElementById('pw-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
