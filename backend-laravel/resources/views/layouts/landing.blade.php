@php
    use App\Models\SystemSetting;

    $currentTheme = SystemSetting::where('key', 'ui_theme')->value('value') ?: 'soc_dark';

    $themeClass = match ($currentTheme) {
        'soc_light' => 'theme-soc-light',
        'cyber_blue' => 'theme-cyber-blue',
        'oled_black' => 'theme-oled-black',
        default => 'theme-soc-dark',
    };

    $themeLabel = match ($currentTheme) {
        'soc_light' => 'Light SOC',
        'cyber_blue' => 'Cyber Blue',
        'oled_black' => 'OLED Black',
        default => 'Dark SOC',
    };
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'RansomShield — Plateforme SOC')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">

    <style>
        :root {
            --font-main: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --radius-lg: 24px;
            --radius-xl: 34px;
            --transition: 0.25s ease;
        }

        body.theme-soc-dark {
            --bg-main: #07111f;
            --bg-panel: rgba(13, 27, 46, 0.86);
            --bg-card: rgba(16, 36, 61, 0.76);
            --border-soft: rgba(148, 163, 184, 0.18);
            --text-main: #e5edf7;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --accent-2: #22c55e;
            --danger: #ef4444;
            --accent-contrast: #03111f;
            --body-bg:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.12), transparent 32%),
                #07111f;
            --shadow-soft: 0 28px 90px rgba(0, 0, 0, 0.38);
        }

        body.theme-soc-light {
            --bg-main: #eef4fb;
            --bg-panel: rgba(255, 255, 255, 0.88);
            --bg-card: rgba(255, 255, 255, 0.78);
            --border-soft: rgba(15, 23, 42, 0.12);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #2563eb;
            --accent-2: #16a34a;
            --danger: #dc2626;
            --accent-contrast: #ffffff;
            --body-bg:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(22, 163, 74, 0.10), transparent 32%),
                #eef4fb;
            --shadow-soft: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        body.theme-cyber-blue {
            --bg-main: #031a33;
            --bg-panel: rgba(6, 38, 74, 0.88);
            --bg-card: rgba(8, 52, 96, 0.76);
            --border-soft: rgba(125, 211, 252, 0.22);
            --text-main: #e0f2fe;
            --text-muted: #93c5fd;
            --accent: #00d9ff;
            --accent-2: #2dd4bf;
            --danger: #fb7185;
            --accent-contrast: #00111f;
            --body-bg:
                radial-gradient(circle at top left, rgba(0, 217, 255, 0.22), transparent 34%),
                radial-gradient(circle at bottom right, rgba(45, 212, 191, 0.16), transparent 30%),
                #031a33;
            --shadow-soft: 0 28px 90px rgba(0, 217, 255, 0.11);
        }

        body.theme-oled-black {
            --bg-main: #000000;
            --bg-panel: rgba(8, 8, 8, 0.92);
            --bg-card: rgba(17, 17, 17, 0.84);
            --border-soft: rgba(255, 255, 255, 0.13);
            --text-main: #f8fafc;
            --text-muted: #a1a1aa;
            --accent: #22d3ee;
            --accent-2: #22c55e;
            --danger: #ef4444;
            --accent-contrast: #000000;
            --body-bg:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.14), transparent 30%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.10), transparent 28%),
                #000000;
            --shadow-soft: 0 30px 90px rgba(0, 0, 0, 0.9);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--body-bg);
            color: var(--text-main);
            font-family: var(--font-main);
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .landing-wrap {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .landing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: var(--accent-contrast);
            font-weight: 950;
            box-shadow: 0 16px 40px color-mix(in srgb, var(--accent) 25%, transparent);
        }

        .brand-title {
            font-size: 19px;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .landing-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .theme-form {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--border-soft);
            background: var(--bg-panel);
            border-radius: 999px;
            backdrop-filter: blur(20px);
        }

        .theme-form span {
            font-size: 12px;
            font-weight: 850;
            color: var(--text-muted);
            padding-left: 8px;
        }

        .theme-form select {
            height: 36px;
            border: none;
            outline: none;
            border-radius: 999px;
            background: var(--bg-card);
            color: var(--text-main);
            padding: 0 12px;
            font-weight: 800;
        }

        .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 999px;
            font-weight: 900;
            border: 1px solid var(--border-soft);
            transition: var(--transition);
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--accent);
            color: var(--accent-contrast);
            border-color: color-mix(in srgb, var(--accent) 45%, transparent);
        }

        .btn-soft {
            background: var(--bg-panel);
            color: var(--text-main);
            backdrop-filter: blur(20px);
        }

        .hero {
            min-height: calc(100vh - 98px);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 34px;
            padding: 40px 0 60px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 13%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 26%, transparent);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 20px;
        }

        .hero-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent-2);
            box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent-2) 14%, transparent);
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(44px, 8vw, 86px);
            line-height: 0.88;
            letter-spacing: -0.085em;
            max-width: 850px;
        }

        .hero h1 span {
            color: var(--accent);
        }

        .hero-text {
            margin: 24px 0 0;
            max-width: 760px;
            color: var(--text-muted);
            line-height: 1.8;
            font-size: 17px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .trust-row {
            margin-top: 34px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            max-width: 760px;
        }

        .trust-card {
            padding: 16px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-soft);
        }

        .trust-number {
            font-size: 28px;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .trust-label {
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
            margin-top: 5px;
        }

        .visual-panel {
            position: relative;
            border: 1px solid var(--border-soft);
            border-radius: 34px;
            background: var(--bg-panel);
            box-shadow: var(--shadow-soft);
            padding: 20px;
            overflow: hidden;
            backdrop-filter: blur(24px);
        }

        .visual-panel::before {
            content: "";
            position: absolute;
            inset: -80px -60px auto auto;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 20%, transparent);
            filter: blur(3px);
        }

        .soc-screen {
            position: relative;
            z-index: 1;
            border: 1px solid var(--border-soft);
            border-radius: 26px;
            background: color-mix(in srgb, var(--bg-main) 78%, transparent);
            overflow: hidden;
        }

        .screen-top {
            height: 48px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 16px;
            border-bottom: 1px solid var(--border-soft);
        }

        .screen-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: var(--danger);
            opacity: 0.8;
        }

        .screen-dot:nth-child(2) {
            background: #f59e0b;
        }

        .screen-dot:nth-child(3) {
            background: var(--accent-2);
        }

        .screen-body {
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        .risk-card {
            padding: 16px;
            border-radius: 18px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        }

        .risk-title {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .risk-pill {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.24);
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
        }

        .bar {
            height: 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--text-muted) 18%, transparent);
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            width: 72%;
            background: linear-gradient(90deg, var(--accent), var(--danger));
            border-radius: inherit;
        }

        .flow-list {
            display: grid;
            gap: 10px;
        }

        .flow-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px;
            border-radius: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: 13px;
        }

        .flow-item strong {
            color: var(--text-main);
        }

        .sections {
            padding: 20px 0 70px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .feature-card {
            border: 1px solid var(--border-soft);
            background: var(--bg-panel);
            border-radius: 26px;
            padding: 22px;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(20px);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            margin-bottom: 16px;
        }

        .feature-card h3 {
            margin: 0;
            font-size: 18px;
            letter-spacing: -0.03em;
        }

        .feature-card p {
            margin: 10px 0 0;
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 14px;
        }

        @media (max-width: 950px) {
            .landing-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .landing-nav {
                justify-content: flex-start;
                width: 100%;
            }

            .theme-form {
                width: 100%;
                border-radius: 18px;
            }

            .theme-form select {
                flex: 1;
            }

            .hero {
                grid-template-columns: 1fr;
                min-height: auto;
                padding-top: 26px;
            }

            .trust-row,
            .sections {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .landing-wrap {
                width: min(100% - 22px, 1180px);
            }

            .hero h1 {
                font-size: 46px;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .visual-panel {
                border-radius: 24px;
                padding: 12px;
            }
        }
    </style>
</head>
<body class="{{ $themeClass }}">
<header class="landing-wrap landing-header">
    <a href="{{ route('platform.home') }}" class="brand">
        <div class="brand-logo">RS</div>
        <div>
            <div class="brand-title">RansomShield</div>
            <div class="brand-subtitle">Cybersurveillance anti-ransomware</div>
        </div>
    </a>

    <nav class="landing-nav">
        <form method="POST" action="{{ route('platform.appearance.theme') }}" class="theme-form">
            @csrf
            <span>Mode</span>
            <select name="theme" onchange="this.form.submit()">
                <option value="soc_dark" @selected($currentTheme === 'soc_dark')>Dark SOC</option>
                <option value="soc_light" @selected($currentTheme === 'soc_light')>Light SOC</option>
                <option value="cyber_blue" @selected($currentTheme === 'cyber_blue')>Cyber Blue</option>
                <option value="oled_black" @selected($currentTheme === 'oled_black')>OLED Black</option>
            </select>
            <noscript><button type="submit">OK</button></noscript>
        </form>

        <a href="{{ route('platform.dashboard') }}" class="btn btn-soft">Console SOC</a>
        <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">Démarrer</a>
    </nav>
</header>

<main>
    @yield('content')
</main>
</body>
</html>
