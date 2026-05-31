@php
    use App\Models\SystemSetting;
    use App\Models\Alert;
    use App\Models\Incident;
    use App\Models\ProtectionAction;

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

    $navActiveAlerts    = Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count();
    $navActiveIncidents = Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count();
    $navPendingActions  = ProtectionAction::where('approval_status', 'pending')->count();
    $engineActive       = SystemSetting::where('key', 'protection_execution_enabled')->value('value') === '1';
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'RansomShield SOC')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap">

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
            grid-template-columns: 272px 1fr;
            min-height: 100vh;
        }

        .soc-sidebar {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-soft);
            padding: 18px 12px 12px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 40;
            display: flex;
            flex-direction: column;
        }

        .soc-sidebar-nav {
            flex: 1;
        }

        .soc-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 20px;
            padding: 11px 12px;
            border: 1px solid var(--border-soft);
            border-radius: 18px;
            background: color-mix(in srgb, var(--bg-panel) 84%, transparent);
            box-shadow: var(--shadow-soft);
            text-decoration: none;
        }

        .soc-logo {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 40%, #1d4ed8));
            box-shadow: 0 8px 24px color-mix(in srgb, var(--accent) 38%, transparent),
                        inset 0 1px 0 rgba(255, 255, 255, 0.18);
            font-size: 18px;
            color: var(--accent-contrast);
            flex-shrink: 0;
        }

        .soc-brand-title {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: var(--text-main);
        }

        .soc-brand-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .soc-nav-section {
            margin-top: 4px;
        }

        .soc-nav-section + .soc-nav-section {
            margin-top: 6px;
            padding-top: 14px;
            border-top: 1px solid color-mix(in srgb, var(--border-soft) 60%, transparent);
        }

        .soc-nav-label {
            color: var(--text-muted);
            opacity: 0.55;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 800;
            padding: 0 10px;
            margin-bottom: 5px;
            margin-top: 14px;
        }

        .soc-nav {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .soc-nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            color: var(--text-muted);
            border-radius: 11px;
            transition: all 0.18s ease;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            position: relative;
        }

        .soc-nav-link:hover {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 9%, transparent);
            border-color: color-mix(in srgb, var(--accent) 16%, transparent);
        }

        .soc-nav-link.active {
            color: var(--accent);
            background: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 17%, transparent),
                color-mix(in srgb, var(--accent) 7%, transparent));
            border-color: color-mix(in srgb, var(--accent) 30%, transparent);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--accent) 12%, transparent);
            font-weight: 700;
        }

        .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 12px;
            flex-shrink: 0;
            color: var(--text-muted);
            opacity: 0.65;
            transition: all 0.18s ease;
        }

        .soc-nav-link:hover .nav-icon,
        .soc-nav-link.active .nav-icon {
            color: var(--accent);
            opacity: 1;
        }

        .nav-label {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-badge {
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 1;
        }

        .nav-badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.22);
        }

        .nav-badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.22);
        }

        .nav-badge-accent {
            background: color-mix(in srgb, var(--accent) 16%, transparent);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
        }

        .sidebar-footer {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid color-mix(in srgb, var(--border-soft) 60%, transparent);
        }

        .engine-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 11px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .engine-status.engine-on {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.08);
            border-color: rgba(34, 197, 94, 0.18);
        }

        .engine-status.engine-off {
            color: var(--text-muted);
            background: color-mix(in srgb, var(--bg-panel-soft) 50%, transparent);
            border-color: var(--border-soft);
        }

        .engine-status i {
            font-size: 11px;
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
            background: color-mix(in srgb, var(--bg-panel) 88%, transparent);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 0;
            z-index: 30;
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
            animation: pulse-ring 2s ease infinite;
        }

        .pulse-dot-off {
            background: var(--text-muted);
            box-shadow: none;
            animation: none;
            opacity: .5;
        }

        .soc-pill-off {
            background: color-mix(in srgb, var(--text-muted) 10%, transparent);
            border-color: color-mix(in srgb, var(--text-muted) 18%, transparent);
            color: var(--text-muted);
        }

        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent-2) 18%, transparent); }
            50%       { box-shadow: 0 0 0 7px color-mix(in srgb, var(--accent-2) 8%, transparent); }
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 6px 6px 12px;
            background: color-mix(in srgb, var(--bg-panel-soft) 80%, transparent);
            border: 1px solid var(--border-soft);
            border-radius: 999px;
        }

        .topbar-user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topbar-logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--danger, #f87171) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--danger, #f87171) 25%, transparent);
            color: var(--danger, #f87171);
            font-size: 13px;
            cursor: pointer;
            transition: background var(--transition), box-shadow var(--transition);
        }

        .topbar-logout-btn:hover {
            background: color-mix(in srgb, var(--danger, #f87171) 22%, transparent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--danger, #f87171) 15%, transparent);
        }

        .topbar-alert-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
            color: var(--text-muted);
            font-size: 14px;
            transition: all 0.18s ease;
            text-decoration: none;
        }

        .topbar-alert-btn:hover {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            border-color: color-mix(in srgb, var(--accent) 25%, transparent);
        }

        .topbar-alert-btn.topbar-alert-danger {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.2);
            animation: alert-pulse 2.5s ease infinite;
        }

        @keyframes alert-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            50%       { box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.08); }
        }

        .topbar-alert-count {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 9px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--bg-sidebar);
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
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid color-mix(in srgb, var(--border-soft) 70%, transparent);
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
            padding: 32px 22px;
            border: 1px dashed color-mix(in srgb, var(--text-muted) 22%, transparent);
            border-radius: 20px;
            color: var(--text-muted);
            background: color-mix(in srgb, var(--bg-panel-soft) 38%, transparent);
            line-height: 1.65;
            text-align: center;
        }

        .empty-state strong {
            color: var(--text-main);
            opacity: .7;
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

        /* ── Card hover lift + accent stripe ─────────────────────────── */
        .soc-card {
            position: relative;
            transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
        }

        .soc-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 18%;
            right: 18%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0;
            transition: opacity .22s ease;
            pointer-events: none;
        }

        .soc-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        .soc-card:hover::before {
            opacity: .55;
        }

        /* ── Table row hover ─────────────────────────────────────────── */
        table.soc-table tbody tr {
            transition: background .14s ease;
        }

        table.soc-table tbody tr:hover td {
            background: color-mix(in srgb, var(--accent) 5%, transparent);
        }

        /* ── User avatar chip ────────────────────────────────────────── */
        .user-avatar-chip {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 55%, #8b5cf6));
            color: var(--accent-contrast);
            font-size: 11px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent);
        }

        /* ── Improved flash messages ──────────────────────────────────── */
        .flash {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        /* ── Sidebar nav hover refinement ───────────────────────────── */
        .soc-nav-link {
            letter-spacing: -.01em;
        }

        /* ── Engine status pill ──────────────────────────────────────── */
        .engine-status.engine-on {
            animation: enginePulse 3s ease-in-out infinite;
        }

        @keyframes enginePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
            50%       { box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.08); }
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
            <div class="soc-logo"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <div class="soc-brand-title">RansomShield</div>
                <div class="soc-brand-subtitle">Console SOC</div>
            </div>
        </a>

        <div class="soc-sidebar-nav">
            <div class="soc-nav-section">
                <div class="soc-nav-label">Supervision</div>
                <nav class="soc-nav">
                    <a href="{{ route('platform.home') }}" class="soc-nav-link {{ request()->routeIs('platform.home') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-house-chimney"></i></span>
                        <span class="nav-label">Accueil</span>
                    </a>
                    <a href="{{ route('platform.dashboard') }}" class="soc-nav-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a href="{{ route('platform.local-host.index') }}" class="soc-nav-link {{ request()->routeIs('platform.local-host.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-desktop"></i></span>
                        <span class="nav-label">Machine hôte locale</span>
                    </a>
                </nav>
            </div>

            <div class="soc-nav-section">
                <div class="soc-nav-label">Infrastructure</div>
                <nav class="soc-nav">
                    <a href="{{ route('platform.networks.index') }}" class="soc-nav-link {{ request()->routeIs('platform.networks.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-diagram-project"></i></span>
                        <span class="nav-label">Réseaux surveillés</span>
                    </a>
                    <a href="{{ route('platform.discovered-hosts.index') }}" class="soc-nav-link {{ request()->routeIs('platform.discovered-hosts.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-satellite-dish"></i></span>
                        <span class="nav-label">Hôtes découverts</span>
                    </a>
                    <a href="{{ route('platform.agents.index') }}" class="soc-nav-link {{ request()->routeIs('platform.agents.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-microchip"></i></span>
                        <span class="nav-label">Agents / Machines</span>
                    </a>
                </nav>
            </div>

            <div class="soc-nav-section">
                <div class="soc-nav-label">Détection & réponse</div>
                <nav class="soc-nav">
                    <a href="{{ route('platform.alerts.index') }}" class="soc-nav-link {{ request()->routeIs('platform.alerts.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <span class="nav-label">Alertes</span>
                        @if($navActiveAlerts > 0)
                            <span class="nav-badge nav-badge-danger">{{ $navActiveAlerts }}</span>
                        @endif
                    </a>
                    <a href="{{ route('platform.incidents.index') }}" class="soc-nav-link {{ request()->routeIs('platform.incidents.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-fire"></i></span>
                        <span class="nav-label">Incidents</span>
                        @if($navActiveIncidents > 0)
                            <span class="nav-badge nav-badge-danger">{{ $navActiveIncidents }}</span>
                        @endif
                    </a>
                    <a href="{{ route('platform.events.index') }}" class="soc-nav-link {{ request()->routeIs('platform.events.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-timeline"></i></span>
                        <span class="nav-label">Événements</span>
                    </a>
                    <a href="{{ route('platform.protection-actions.index') }}" class="soc-nav-link {{ request()->routeIs('platform.protection-actions.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-lock"></i></span>
                        <span class="nav-label">Actions de protection</span>
                    </a>
                    <a href="{{ route('platform.approval-queue.index') }}" class="soc-nav-link {{ request()->routeIs('platform.approval-queue.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                        <span class="nav-label">File d'approbation</span>
                        @if($navPendingActions > 0)
                            <span class="nav-badge nav-badge-warning">{{ $navPendingActions }}</span>
                        @endif
                    </a>
                    <a href="{{ route('platform.simulation.index') }}" class="soc-nav-link {{ request()->routeIs('platform.simulation.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-biohazard"></i></span>
                        <span class="nav-label">Simulateur d'attaque</span>
                    </a>
                </nav>
            </div>

            <div class="soc-nav-section">
                <div class="soc-nav-label">Configuration</div>
                <nav class="soc-nav">
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('platform.users.index') }}" class="soc-nav-link {{ request()->routeIs('platform.users.*', 'platform.profile') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                        <span class="nav-label">Utilisateurs</span>
                    </a>
                    @endif
                    <a href="{{ route('platform.configuration.index') }}" class="soc-nav-link {{ request()->routeIs('platform.configuration.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-table-cells-large"></i></span>
                        <span class="nav-label">Centre de configuration</span>
                    </a>
                    <a href="{{ route('platform.detection-rules.index') }}" class="soc-nav-link {{ request()->routeIs('platform.detection-rules.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-filter"></i></span>
                        <span class="nav-label">Règles de détection</span>
                    </a>
                    <a href="{{ route('platform.detection-thresholds.index') }}" class="soc-nav-link {{ request()->routeIs('platform.detection-thresholds.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-wave-square"></i></span>
                        <span class="nav-label">Seuils d'analyse</span>
                    </a>
                    <a href="{{ route('platform.protection-policies.index') }}" class="soc-nav-link {{ request()->routeIs('platform.protection-policies.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-file-shield"></i></span>
                        <span class="nav-label">Politiques</span>
                    </a>
                    <a href="{{ route('platform.system-settings.index') }}" class="soc-nav-link {{ request()->routeIs('platform.system-settings.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-gears"></i></span>
                        <span class="nav-label">Paramètres</span>
                    </a>
                    <a href="{{ route('platform.sensitive-extensions.index') }}" class="soc-nav-link {{ request()->routeIs('platform.sensitive-extensions.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-tags"></i></span>
                        <span class="nav-label">Extensions sensibles</span>
                    </a>
                </nav>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="engine-status {{ $engineActive ? 'engine-on' : 'engine-off' }}">
                <i class="fa-solid {{ $engineActive ? 'fa-circle-play' : 'fa-circle-pause' }}"></i>
                <span>Moteur {{ $engineActive ? 'actif' : 'en pause' }}</span>
            </div>

            <form method="POST" action="{{ route('platform.logout') }}" style="margin-top: 8px;">
                @csrf
                <button type="submit" style="
                    display: flex; align-items: center; gap: 9px; width: 100%;
                    padding: 9px 12px; border-radius: 11px;
                    font-size: 12px; font-weight: 700;
                    color: var(--text-muted); background: none;
                    border: 1px solid transparent; cursor: pointer;
                    transition: color .15s, background .15s, border-color .15s;
                    text-align: left; font-family: inherit;
                "
                onmouseover="this.style.color='#ef4444';this.style.background='rgba(239,68,68,.08)';this.style.borderColor='rgba(239,68,68,.18)';"
                onmouseout="this.style.color='var(--text-muted)';this.style.background='none';this.style.borderColor='transparent';">
                    <i class="fa-solid fa-right-from-bracket" style="width: 14px; text-align: center; font-size: 11px;"></i>
                    Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <main class="soc-main">
        <div class="soc-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-button" type="button" data-open-sidebar aria-label="Ouvrir le menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h1>@yield('page_title', 'RansomShield SOC')</h1>
                    <p>@yield('page_subtitle', 'Plateforme de cybersurveillance orientée ransomware')</p>
                </div>
            </div>

            <div class="topbar-actions">
                @if($navPendingActions > 0)
                <a href="{{ route('platform.approval-queue.index') }}" class="topbar-alert-btn" title="{{ $navPendingActions }} action(s) en attente">
                    <i class="fa-solid fa-list-check"></i>
                    <span class="topbar-alert-count">{{ $navPendingActions }}</span>
                </a>
                @endif

                @if($navActiveAlerts > 0)
                <a href="{{ route('platform.alerts.index') }}" class="topbar-alert-btn topbar-alert-danger" title="{{ $navActiveAlerts }} alerte(s) active(s)">
                    <i class="fa-solid fa-bell"></i>
                    <span class="topbar-alert-count">{{ $navActiveAlerts }}</span>
                </a>
                @endif

                <form method="POST" action="{{ route('platform.appearance.theme') }}" class="theme-form">
                    @csrf
                    <i class="fa-solid fa-palette" style="color: var(--text-muted); font-size: 12px; padding-left: 6px;"></i>
                    <select name="theme" id="theme" onchange="this.form.submit()">
                        <option value="soc_dark" @selected($currentTheme === 'soc_dark')>Dark SOC</option>
                        <option value="soc_light" @selected($currentTheme === 'soc_light')>Light SOC</option>
                        <option value="cyber_blue" @selected($currentTheme === 'cyber_blue')>Cyber Blue</option>
                        <option value="oled_black" @selected($currentTheme === 'oled_black')>OLED Black</option>
                    </select>
                    <noscript><button type="submit">OK</button></noscript>
                </form>

                <div class="soc-status-pill {{ $engineActive ? '' : 'soc-pill-off' }}" title="Moteur {{ $engineActive ? 'actif' : 'en pause' }}">
                    <span class="pulse-dot {{ $engineActive ? '' : 'pulse-dot-off' }}"></span>
                    SOC {{ $engineActive ? 'actif' : 'en pause' }}
                </div>

                <div class="topbar-user" style="position: relative;" id="userMenuWrap">
                    <button type="button" class="topbar-user-name" id="userMenuTrigger"
                            title="{{ auth()->user()->email }}"
                            style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 0; color: var(--text-muted);">
                        <span class="user-avatar-chip">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        {{ auth()->user()->name }}
                        <i class="fa-solid fa-chevron-down" style="font-size: 10px; opacity: .6;"></i>
                    </button>

                    {{-- Dropdown menu --}}
                    <div id="userMenuDropdown" style="
                        display: none;
                        position: absolute;
                        top: calc(100% + 8px);
                        right: 0;
                        min-width: 180px;
                        background: var(--bg-panel);
                        border: 1px solid var(--border-soft);
                        border-radius: 16px;
                        box-shadow: 0 16px 48px rgba(0,0,0,0.4);
                        overflow: hidden;
                        z-index: 100;
                        padding: 6px;
                    ">
                        <a href="{{ route('platform.profile') }}"
                           style="display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 700; color: var(--text-main); text-decoration: none; transition: background .15s;">
                            <i class="fa-solid fa-id-card" style="color: var(--accent); width: 16px; text-align: center;"></i>
                            Mon profil
                        </a>
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('platform.users.index') }}"
                           style="display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 700; color: var(--text-main); text-decoration: none; transition: background .15s;">
                            <i class="fa-solid fa-users-gear" style="color: var(--text-muted); width: 16px; text-align: center;"></i>
                            Utilisateurs
                        </a>
                        @endif
                        <div style="height: 1px; background: var(--border-soft); margin: 4px 0; opacity: .5;"></div>
                        <form method="POST" action="{{ route('platform.logout') }}">
                            @csrf
                            <button type="submit"
                                    style="display: flex; align-items: center; gap: 9px; width: 100%; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 700; color: #ef4444; background: none; border: none; cursor: pointer; transition: background .15s; text-align: left;">
                                <i class="fa-solid fa-right-from-bracket" style="width: 16px; text-align: center;"></i>
                                Se déconnecter
                            </button>
                        </form>
                    </div>
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

    // ── User menu dropdown ──────────────────────────────────────────────────
    const userMenuTrigger  = document.getElementById('userMenuTrigger');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuTrigger && userMenuDropdown) {
        userMenuTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = userMenuDropdown.style.display !== 'none';
            userMenuDropdown.style.display = isOpen ? 'none' : 'block';
        });

        // Close on outside click
        document.addEventListener('click', () => {
            userMenuDropdown.style.display = 'none';
        });

        // Hover highlight on dropdown items
        userMenuDropdown.querySelectorAll('a, button').forEach(el => {
            el.addEventListener('mouseenter', () => {
                el.style.background = 'color-mix(in srgb, var(--accent) 9%, transparent)';
            });
            el.addEventListener('mouseleave', () => {
                el.style.background = 'none';
            });
        });
    }
</script>

@stack('scripts')
    @include('platform.partials.final-sidebar-scroll-ux')

{{-- ── Notifications temps réel ─────────────────────────────────────────── --}}
<style>
    #rs-toast-wrap {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 340px;
        pointer-events: none;
    }
    .rs-toast {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 14px;
        background: var(--bg-panel);
        border: 1px solid var(--border-soft);
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        pointer-events: all;
        animation: rs-toast-in 0.3s ease forwards;
        font-size: 13px;
        line-height: 1.4;
    }
    .rs-toast-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .rs-toast-title { font-weight: 700; color: var(--text-main); margin-bottom: 3px; }
    .rs-toast-msg   { color: var(--text-muted); }
    .rs-toast-close { margin-left: auto; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 14px; padding: 0 0 0 8px; flex-shrink: 0; }
    .rs-toast.risk-critical { border-color: rgba(239,68,68,0.4);  background: color-mix(in srgb, #ef4444 8%, var(--bg-panel)); }
    .rs-toast.risk-high     { border-color: rgba(249,115,22,0.4); background: color-mix(in srgb, #f97316 8%, var(--bg-panel)); }
    .rs-toast.risk-suspect  { border-color: rgba(234,179,8,0.4);  background: color-mix(in srgb, #eab308 8%, var(--bg-panel)); }
    .rs-toast.risk-normal   { border-color: rgba(34,197,94,0.4);  background: color-mix(in srgb, #22c55e 8%, var(--bg-panel)); }
    @keyframes rs-toast-in { from { opacity:0; transform: translateX(20px); } to { opacity:1; transform: translateX(0); } }
    @keyframes rs-toast-out { to { opacity:0; transform: translateX(30px); max-height:0; padding:0; margin:0; overflow:hidden; } }
</style>
<div id="rs-toast-wrap"></div>

<script>
(function () {
    let audioCtx = null;

    function unlockAudio() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
    }
    document.addEventListener('click', unlockAudio, { once: true });

    function playBeep(level) {
        try {
            unlockAudio();
            if (!audioCtx) return;
            const freq = level === 'critical' ? 880 : level === 'high' ? 660 : 440;
            const reps = level === 'critical' ? 3 : 1;
            for (let i = 0; i < reps; i++) {
                const osc  = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain); gain.connect(audioCtx.destination);
                osc.type = 'sine';
                osc.frequency.value = freq;
                const t = audioCtx.currentTime + i * 0.35;
                gain.gain.setValueAtTime(0.25, t);
                gain.gain.exponentialRampToValueAtTime(0.001, t + 0.28);
                osc.start(t); osc.stop(t + 0.28);
            }
        } catch(e) {}
    }

    function riskIcon(level) {
        return { critical: '🔴', high: '🟠', suspect: '🟡', normal: '🟢' }[level] || '🔵';
    }

    function showToast(n) {
        const wrap = document.getElementById('rs-toast-wrap');
        const el = document.createElement('div');
        el.className = `rs-toast risk-${n.risk_level}`;
        el.innerHTML = `
            <span class="rs-toast-icon">${riskIcon(n.risk_level)}</span>
            <div>
                <div class="rs-toast-title">${n.subject}</div>
                <div class="rs-toast-msg">${n.message}</div>
            </div>
            <button class="rs-toast-close" onclick="this.closest('.rs-toast').remove()">✕</button>
        `;
        wrap.appendChild(el);
        setTimeout(() => {
            el.style.animation = 'rs-toast-out 0.4s ease forwards';
            setTimeout(() => el.remove(), 400);
        }, 7000);
    }

    function pollNotifications() {
        fetch('{{ route("platform.notifications.poll") }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data) return;
            (data.notifications || []).forEach(showToast);
            if (data.play_sound) playBeep(data.sound_level || 'high');
        })
        .catch(() => {});
    }

    // Démarrage du polling toutes les 30 secondes
    setTimeout(pollNotifications, 5000);
    setInterval(pollNotifications, 30000);
})();
</script>

{{-- ── LOADING OVERLAY ────────────────────────────────────────────────────── --}}
{{-- Affiché lors des opérations longues : scan réseau, détection, actions SOC --}}
<div id="rsLoadingOverlay" aria-live="polite" style="
    display:none;
    position:fixed; inset:0; z-index:99999;
    background:rgba(9,15,28,.78);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    align-items:center; justify-content:center; flex-direction:column; gap:20px;
">
    <div style="
        width:64px; height:64px; border-radius:50%;
        border:3px solid rgba(56,189,248,.18);
        border-top-color:var(--accent,#38bdf8);
        animation:rsSpinFull .75s linear infinite;
    "></div>
    <div style="text-align:center; max-width:360px; padding:0 24px;">
        <p id="rsLoadingMsg"  style="margin:0; font-size:16px; font-weight:950; letter-spacing:-.03em; color:var(--text-main,#f1f5f9);">
            Traitement en cours…
        </p>
        <p id="rsLoadingHint" style="margin:6px 0 0; font-size:13px; color:var(--text-muted,#64748b); line-height:1.5;">
            Veuillez patienter, cette opération peut prendre quelques secondes.
        </p>
    </div>
</div>

<style>
    @keyframes rsSpinFull { to { transform: rotate(360deg); } }
</style>

<script>
(function () {
    const overlay = document.getElementById('rsLoadingOverlay');
    const msgEl   = document.getElementById('rsLoadingMsg');
    const hintEl  = document.getElementById('rsLoadingHint');
    if (!overlay) return;

    function showOverlay(message, hint) {
        if (msgEl)  msgEl.textContent  = message || 'Traitement en cours…';
        if (hintEl) hintEl.textContent = hint    || 'Veuillez patienter, cette opération peut prendre quelques secondes.';
        overlay.style.display = 'flex';
        // Sécurité : disparaît automatiquement après 45 secondes
        setTimeout(() => { overlay.style.display = 'none'; }, 45000);
    }

    // Déclenché par tout formulaire avec data-loading="Message"
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const btn = form.querySelector('[type=submit]');
            // Ne pas bloquer si le bouton a data-confirm (confirmation déjà gérée)
            if (btn && btn.disabled) return;
            const msg  = form.dataset.loading  || 'Traitement en cours…';
            const hint = form.dataset.loadingHint || 'Veuillez patienter, cette opération peut prendre quelques secondes.';
            showOverlay(msg, hint);
        });
    });

    // Exposé globalement pour les appels manuels depuis d'autres scripts
    window.rsShowLoader = showOverlay;
    window.rsHideLoader = function () { overlay.style.display = 'none'; };
})();
</script>

</body>
</html>
