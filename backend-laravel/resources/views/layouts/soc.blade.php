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
    <title>@yield('title', 'RansomShield SOC')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --font-main: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 22px;
            --radius-xl: 30px;
            --transition: 0.22s ease;
        }

        body.theme-soc-dark {
            --bg-main: #07111f;
            --bg-sidebar: #050b14;
            --bg-panel: #0d1b2e;
            --bg-panel-soft: #10243d;
            --bg-card: linear-gradient(180deg, rgba(16, 36, 61, 0.94), rgba(13, 27, 46, 0.96));
            --border-soft: rgba(148, 163, 184, 0.18);
            --text-main: #e5edf7;
            --text-muted: #8ea2bd;
            --accent: #38bdf8;
            --accent-contrast: #03111f;
            --accent-2: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-soft: 0 20px 60px rgba(0, 0, 0, 0.35);
            --hero-bg:
                linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(34, 197, 94, 0.10)),
                linear-gradient(180deg, rgba(16, 36, 61, 0.92), rgba(13, 27, 46, 0.96));
            --body-bg:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.16), transparent 34%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.10), transparent 32%),
                var(--bg-main);
        }

        body.theme-soc-light {
            --bg-main: #eef4fb;
            --bg-sidebar: #ffffff;
            --bg-panel: #ffffff;
            --bg-panel-soft: #f4f8fc;
            --bg-card: linear-gradient(180deg, #ffffff, #f8fbff);
            --border-soft: rgba(15, 23, 42, 0.11);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #2563eb;
            --accent-contrast: #ffffff;
            --accent-2: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --shadow-soft: 0 22px 50px rgba(15, 23, 42, 0.10);
            --hero-bg:
                linear-gradient(135deg, rgba(37, 99, 235, 0.11), rgba(22, 163, 74, 0.08)),
                linear-gradient(180deg, #ffffff, #f8fbff);
            --body-bg:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(22, 163, 74, 0.09), transparent 32%),
                var(--bg-main);
        }

        body.theme-cyber-blue {
            --bg-main: #031a33;
            --bg-sidebar: #021124;
            --bg-panel: #06264a;
            --bg-panel-soft: #083460;
            --bg-card: linear-gradient(180deg, rgba(8, 52, 96, 0.96), rgba(6, 38, 74, 0.96));
            --border-soft: rgba(125, 211, 252, 0.20);
            --text-main: #e0f2fe;
            --text-muted: #93c5fd;
            --accent: #00d9ff;
            --accent-contrast: #00111f;
            --accent-2: #2dd4bf;
            --warning: #facc15;
            --danger: #fb7185;
            --shadow-soft: 0 25px 75px rgba(0, 217, 255, 0.10);
            --hero-bg:
                linear-gradient(135deg, rgba(0, 217, 255, 0.24), rgba(45, 212, 191, 0.10)),
                linear-gradient(180deg, rgba(8, 52, 96, 0.96), rgba(6, 38, 74, 0.96));
            --body-bg:
                radial-gradient(circle at top left, rgba(0, 217, 255, 0.22), transparent 34%),
                radial-gradient(circle at bottom right, rgba(45, 212, 191, 0.16), transparent 30%),
                var(--bg-main);
        }

        body.theme-oled-black {
            --bg-main: #000000;
            --bg-sidebar: #050505;
            --bg-panel: #090909;
            --bg-panel-soft: #111111;
            --bg-card: linear-gradient(180deg, #111111, #070707);
            --border-soft: rgba(255, 255, 255, 0.12);
            --text-main: #f8fafc;
            --text-muted: #a1a1aa;
            --accent: #22d3ee;
            --accent-contrast: #000000;
            --accent-2: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-soft: 0 26px 80px rgba(0, 0, 0, 0.88);
            --hero-bg:
                linear-gradient(135deg, rgba(34, 211, 238, 0.16), rgba(34, 197, 94, 0.08)),
                linear-gradient(180deg, #111111, #050505);
            --body-bg:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.14), transparent 28%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.08), transparent 28%),
                var(--bg-main);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--body-bg);
            color: var(--text-main);
            font-family: var(--font-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        select,
        input,
        textarea {
            font-family: inherit;
        }

        .mobile-overlay {
            display: none;
        }

        .soc-shell {
            display: grid;
            grid-template-columns: 286px 1fr;
            min-height: 100vh;
        }

        .soc-sidebar {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-soft);
            padding: 22px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 40;
        }

        .soc-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 26px;
            padding: 12px;
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            background: color-mix(in srgb, var(--bg-panel) 84%, transparent);
            box-shadow: var(--shadow-soft);
        }

        .soc-logo {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 35%, #1d4ed8));
            box-shadow: 0 12px 34px color-mix(in srgb, var(--accent) 28%, transparent);
            font-weight: 950;
            color: var(--accent-contrast);
        }

        .soc-brand-title {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.035em;
        }

        .soc-brand-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .soc-nav-section {
            margin-top: 23px;
        }

        .soc-nav-label {
            color: var(--text-muted);
            opacity: 0.78;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 850;
            margin: 0 10px 10px;
        }

        .soc-nav {
            display: grid;
            gap: 7px;
        }

        .soc-nav a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 13px;
            color: var(--text-muted);
            border-radius: 16px;
            transition: var(--transition);
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 650;
        }

        .soc-nav a:hover,
        .soc-nav a.active {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            border-color: color-mix(in srgb, var(--accent) 23%, transparent);
            transform: translateX(2px);
        }

        .soc-main {
            min-width: 0;
            padding: 24px;
        }

        .soc-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding: 15px 16px;
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            background: color-mix(in srgb, var(--bg-panel) 82%, transparent);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-soft);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .mobile-menu-button {
            display: none;
            width: 44px;
            height: 44px;
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
            color: var(--text-main);
            font-size: 22px;
            cursor: pointer;
        }

        .soc-topbar h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: -0.04em;
            line-height: 1.1;
        }

        .soc-topbar p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .soc-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 13px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent-2) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent-2) 28%, transparent);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pulse-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--accent-2);
            box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent-2) 14%, transparent);
        }

        .theme-form {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            border-radius: 999px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 78%, transparent);
        }

        .theme-form label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
            padding-left: 8px;
        }

        .theme-form select {
            min-height: 34px;
            border: 0;
            outline: none;
            border-radius: 999px;
            background: var(--bg-panel);
            color: var(--text-main);
            padding: 0 10px;
            font-weight: 750;
            max-width: 145px;
        }

        .theme-form button {
            min-height: 34px;
            border: 0;
            border-radius: 999px;
            background: var(--accent);
            color: var(--accent-contrast);
            font-weight: 900;
            padding: 0 11px;
            cursor: pointer;
        }

        .soc-content {
            max-width: 1540px;
            margin: 0 auto;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .grid-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .soc-card {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 20px;
        }

        .soc-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }

        .soc-card-title {
            font-size: 16px;
            font-weight: 900;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .soc-card-subtitle {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            min-height: 136px;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            inset: auto -42px -54px auto;
            width: 135px;
            height: 135px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 750;
        }

        .stat-value {
            font-size: clamp(30px, 4vw, 40px);
            font-weight: 950;
            margin-top: 10px;
            letter-spacing: -0.06em;
        }

        .stat-hint {
            margin-top: 10px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .hero {
            padding: clamp(24px, 5vw, 42px);
            border-radius: var(--radius-xl);
            background: var(--hero-bg);
            border: 1px solid color-mix(in srgb, var(--accent) 24%, transparent);
            box-shadow: var(--shadow-soft);
            margin-bottom: 22px;
            overflow: hidden;
            position: relative;
        }

        .hero::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -70px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 14%, transparent);
            filter: blur(4px);
        }

        .hero > * {
            position: relative;
            z-index: 1;
        }

        .hero-kicker {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 13%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .hero h2 {
            margin: 0;
            font-size: clamp(34px, 6vw, 64px);
            line-height: 0.95;
            letter-spacing: -0.075em;
            max-width: 850px;
        }

        .hero p {
            margin: 18px 0 0;
            max-width: 820px;
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.75;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            padding: 0 16px;
            border-radius: 15px;
            border: 1px solid var(--border-soft);
            font-weight: 900;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            color: var(--accent-contrast);
            background: var(--accent);
            border-color: color-mix(in srgb, var(--accent) 45%, transparent);
        }

        .btn-soft {
            background: color-mix(in srgb, var(--bg-panel-soft) 84%, transparent);
            color: var(--text-main);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 16px;
        }

        table.soc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 620px;
        }

        table.soc-table th,
        table.soc-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-soft);
            text-align: left;
            vertical-align: top;
        }

        table.soc-table th {
            color: var(--text-muted);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 900;
        }

        table.soc-table td {
            color: var(--text-main);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 850;
            border: 1px solid var(--border-soft);
            color: var(--text-muted);
            background: color-mix(in srgb, var(--bg-panel-soft) 76%, transparent);
            white-space: nowrap;
        }

        .badge-normal {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.11);
            border-color: rgba(34, 197, 94, 0.25);
        }

        .badge-suspect {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.25);
        }

        .badge-high {
            color: #f97316;
            background: rgba(249, 115, 22, 0.12);
            border-color: rgba(249, 115, 22, 0.25);
        }

        .badge-critical {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.25);
        }

        .empty-state {
            padding: 22px;
            border: 1px dashed color-mix(in srgb, var(--text-muted) 28%, transparent);
            border-radius: 18px;
            color: var(--text-muted);
            background: color-mix(in srgb, var(--bg-panel-soft) 48%, transparent);
            line-height: 1.65;
        }

        .flash {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            font-weight: 800;
        }

        .flash-success {
            background: rgba(34, 197, 94, 0.12);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.25);
        }

        .flash-error {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.25);
        }

        .section-gap {
            margin-top: 18px;
        }

        .muted {
            color: var(--text-muted);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        @media (max-width: 1250px) {
            .grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .soc-shell {
                grid-template-columns: 1fr;
            }

            .mobile-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.55);
                z-index: 35;
                opacity: 0;
                pointer-events: none;
                transition: var(--transition);
            }

            body.sidebar-open .mobile-overlay {
                opacity: 1;
                pointer-events: auto;
            }

            .soc-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: min(88vw, 320px);
                height: 100vh;
                transform: translateX(-105%);
                transition: var(--transition);
            }

            body.sidebar-open .soc-sidebar {
                transform: translateX(0);
            }

            .mobile-menu-button {
                display: inline-grid;
                place-items: center;
                flex: 0 0 auto;
            }

            .soc-main {
                padding: 16px;
            }

            .soc-topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar-actions {
                width: 100%;
                justify-content: space-between;
            }

            .theme-form {
                width: 100%;
                border-radius: 18px;
                flex-wrap: wrap;
            }

            .theme-form select {
                flex: 1;
                max-width: none;
            }

            .grid-4,
            .grid-3,
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .soc-main {
                padding: 12px;
            }

            .soc-topbar {
                padding: 13px;
                border-radius: 18px;
            }

            .soc-card {
                padding: 16px;
                border-radius: 18px;
            }

            .hero {
                border-radius: 22px;
            }

            .btn {
                width: 100%;
            }

            .soc-status-pill {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    @stack('styles')
    @include('platform.partials.ui-stability-fixes')
    @include('platform.partials.table-form-link-fix')
</head>
<body class="{{ $themeClass }}">
<div class="mobile-overlay" data-close-sidebar></div>

<div class="soc-shell">
    <aside class="soc-sidebar" id="socSidebar">
        <a href="{{ route('platform.home') }}" class="soc-brand">
            <div class="soc-logo">RS</div>
            <div>
                <div class="soc-brand-title">RansomShield</div>
                <div class="soc-brand-subtitle">Console SOC Laravel</div>
            </div>
        </a>

        <div class="soc-nav-section">
            <div class="soc-nav-label">Supervision</div>
            <nav class="soc-nav">
                <a href="{{ route('platform.home') }}" class="{{ request()->routeIs('platform.home') ? 'active' : '' }}">
                    Accueil plateforme
                    <span>↗</span>
                </a>
                <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                    Dashboard / Console
                    <span>▣</span>
                </a>
                <a href="{{ route('platform.local-host.index') }}" class="{{ request()->routeIs('platform.local-host.*') ? 'active' : '' }}">
                    Machine hôte locale
                    <span>⌁</span>
                </a>
            </nav>
        </div>

        <div class="soc-nav-section">
            <div class="soc-nav-label">Infrastructure</div>
            <nav class="soc-nav">
                <a href="{{ route('platform.networks.index') }}" class="{{ request()->routeIs('platform.networks.*') ? 'active' : '' }}">Réseaux surveillés <span>→</span></a>
                <a href="{{ route('platform.discovered-hosts.index') }}" class="{{ request()->routeIs('platform.discovered-hosts.*') ? 'active' : '' }}">Hôtes découverts <span>→</span></a>
                <a href="{{ route('platform.agents.index') }}" class="{{ request()->routeIs('platform.agents.*') ? 'active' : '' }}">Machines surveillées <span>→</span></a>
            </nav>
        </div>

        <div class="soc-nav-section">
            <div class="soc-nav-label">Détection & réponse</div>
            <nav class="soc-nav">
                <a href="{{ route('platform.alerts.index') }}" class="{{ request()->routeIs('platform.alerts.*') ? 'active' : '' }}">Alertes <span>→</span></a>
                <a href="{{ route('platform.incidents.index') }}" class="{{ request()->routeIs('platform.incidents.*') ? 'active' : '' }}">Incidents <span>→</span></a>
                <a href="{{ route('platform.events.index') }}" class="{{ request()->routeIs('platform.events.*') ? 'active' : '' }}">Événements <span>→</span></a>
                <a href="{{ route('platform.protection-actions.index') }}" class="{{ request()->routeIs('platform.protection-actions.*') ? 'active' : '' }}">Actions de protection <span>→</span></a>
                <a href="{{ route('platform.approval-queue.index') }}" class="{{ request()->routeIs('platform.approval-queue.*') ? 'active' : '' }}">File d'approbation <span>→</span></a>
            </nav>
        </div>

        <div class="soc-nav-section">
            <div class="soc-nav-label">Configuration</div>
            <nav class="soc-nav" style="margin-bottom:10px;">
                <a href="{{ route('platform.configuration.index') }}" class="{{ request()->routeIs('platform.configuration.*') ? 'active' : '' }}">Centre configuration <span>→</span></a>
            </nav>
            <nav class="soc-nav">
                <a href="{{ route('platform.detection-rules.index') }}" class="{{ request()->routeIs('platform.detection-rules.*') ? 'active' : '' }}">Règles de détection <span>→</span></a>
                <a href="{{ route('platform.detection-thresholds.index') }}" class="{{ request()->routeIs('platform.detection-thresholds.*') ? 'active' : '' }}">Seuils d'analyse <span>→</span></a>
                <a href="{{ route('platform.protection-policies.index') }}" class="{{ request()->routeIs('platform.protection-policies.*') ? 'active' : '' }}">Politiques <span>→</span></a>
                <a href="{{ route('platform.system-settings.index') }}" class="{{ request()->routeIs('platform.system-settings.*') ? 'active' : '' }}">Paramètres <span>→</span></a>
                <a href="{{ route('platform.sensitive-extensions.index') }}" class="{{ request()->routeIs('platform.sensitive-extensions.*') ? 'active' : '' }}">Extensions <span>→</span></a>
            </nav>
        </div>
    </aside>

    <main class="soc-main">
        <div class="soc-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-button" type="button" data-open-sidebar aria-label="Ouvrir le menu">☰</button>
                <div>
                    <h1>@yield('page_title', 'RansomShield SOC')</h1>
                    <p>@yield('page_subtitle', 'Plateforme de cybersurveillance orientée ransomware')</p>
                </div>
            </div>

            <div class="topbar-actions">
                <form method="POST" action="{{ route('platform.appearance.theme') }}" class="theme-form">
                    @csrf
                    <label for="theme">Mode</label>
                    <select name="theme" id="theme" onchange="this.form.submit()">
                        <option value="soc_dark" @selected($currentTheme === 'soc_dark')>Dark SOC</option>
                        <option value="soc_light" @selected($currentTheme === 'soc_light')>Light SOC</option>
                        <option value="cyber_blue" @selected($currentTheme === 'cyber_blue')>Cyber Blue</option>
                        <option value="oled_black" @selected($currentTheme === 'oled_black')>OLED Black</option>
                    </select>
                    <noscript><button type="submit">OK</button></noscript>
                </form>

                <div class="soc-status-pill" title="Thème actuel : {{ $themeLabel }}">
                    <span class="pulse-dot"></span>
                    {{ $themeLabel }}
                </div>
            </div>
        </div>

        <div class="soc-content">
            @if(session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="flash flash-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script>
    const body = document.body;
    const openSidebarButton = document.querySelector('[data-open-sidebar]');
    const closeSidebarTargets = document.querySelectorAll('[data-close-sidebar]');

    if (openSidebarButton) {
        openSidebarButton.addEventListener('click', () => {
            body.classList.add('sidebar-open');
        });
    }

    closeSidebarTargets.forEach((target) => {
        target.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
        });
    });

    document.querySelectorAll('.soc-sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
        });
    });
</script>

@stack('scripts')
    @include('platform.partials.final-sidebar-scroll-ux')
</body>
</html>
