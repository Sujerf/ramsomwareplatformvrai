<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RansomShield') — Erreur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:         #060e1c;
            --bg-card:    #0d1b2e;
            --border:     rgba(148,163,184,.10);
            --accent:     #38bdf8;
            --text:       #f1f5f9;
            --muted:      #64748b;
            --danger:     #ef4444;
            --warning:    #f59e0b;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 32px 24px;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(56,189,248,.06) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 80%, rgba(34,197,94,.04) 0%, transparent 55%),
                var(--bg);
        }

        .error-card {
            width: 100%;
            max-width: 540px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px 36px;
            box-shadow: 0 32px 80px rgba(2,6,23,.45);
            text-align: center;
            animation: fadeUp .45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .error-code {
            font-size: clamp(72px, 15vw, 112px);
            font-weight: 950;
            letter-spacing: -.08em;
            line-height: 1;
            background: linear-gradient(135deg, var(--accent) 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-code.danger {
            background: linear-gradient(135deg, var(--danger) 0%, #f97316 100%);
            -webkit-background-clip: text;
            background-clip: text;
        }

        .error-code.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #f97316 100%);
            -webkit-background-clip: text;
            background-clip: text;
        }

        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .error-icon.blue   { background: rgba(56,189,248,.12); color: var(--accent); }
        .error-icon.red    { background: rgba(239,68,68,.12);  color: var(--danger); }
        .error-icon.orange { background: rgba(245,158,11,.12); color: var(--warning); }

        .error-title {
            font-size: 22px;
            font-weight: 950;
            letter-spacing: -.04em;
            margin-top: 12px;
        }

        .error-desc {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.75;
            margin-top: 12px;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }

        .error-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 850;
            text-decoration: none;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: .18s ease;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--accent);
            color: #060e1c;
            border-color: var(--accent);
        }

        .btn-primary:hover { opacity: .88; transform: translateY(-1px); }

        .btn-soft {
            background: rgba(148,163,184,.08);
            color: var(--text);
        }

        .btn-soft:hover {
            background: rgba(148,163,184,.14);
            transform: translateY(-1px);
        }

        .brand {
            margin-top: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .55; transform: scale(.85); }
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }
    </style>
</head>
<body>
    <div class="error-card">
        @yield('content')
    </div>

    <div class="brand">
        <span class="brand-dot"></span>
        RansomShield SOC Platform
    </div>
</body>
</html>
