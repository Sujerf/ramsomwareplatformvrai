<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield Premium SOC UI
     |--------------------------------------------------------------------------
     | Améliore :
     | - menu dashboard
     | - responsive mobile
     | - cartes
     | - boutons
     | - tables
     | - lisibilité générale
     */

    :root {
        --rs-radius-xl: 28px;
        --rs-radius-lg: 22px;
        --rs-radius-md: 16px;
        --rs-menu-width: 310px;
    }

    body {
        background:
            radial-gradient(circle at top left, color-mix(in srgb, var(--accent) 14%, transparent), transparent 34%),
            radial-gradient(circle at bottom right, color-mix(in srgb, var(--accent-2) 10%, transparent), transparent 30%),
            var(--bg-main);
    }

    /*
     |--------------------------------------------------------------------------
     | Shell / sidebar
     |--------------------------------------------------------------------------
     */

    .soc-shell,
    .soc-layout {
        display: grid;
        grid-template-columns: var(--rs-menu-width) minmax(0, 1fr);
        min-height: 100vh;
        background: transparent;
    }

    .soc-sidebar {
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--bg-card) 94%, transparent), color-mix(in srgb, var(--bg-panel-soft) 94%, transparent));
        border-right: 1px solid var(--border-soft);
        box-shadow: 18px 0 50px rgba(2, 6, 23, .08);
        padding: 18px;
    }

    .soc-brand,
    .brand,
    .soc-logo {
        border-radius: 24px;
        padding: 16px;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--accent) 14%, transparent), color-mix(in srgb, var(--accent-2) 10%, transparent));
        border: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
        box-shadow: var(--shadow-soft);
        margin-bottom: 18px;
    }

    .soc-brand-title,
    .brand-title {
        font-size: 19px;
        font-weight: 950;
        letter-spacing: -.04em;
    }

    .soc-brand-subtitle,
    .brand-subtitle {
        margin-top: 5px;
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .soc-nav-section {
        margin: 18px 0;
    }

    .soc-nav-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 9px 4px;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: .10em;
        text-transform: uppercase;
    }

    .soc-nav-label::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: var(--accent);
        box-shadow: 0 0 0 5px color-mix(in srgb, var(--accent) 14%, transparent);
    }

    .soc-nav {
        display: grid;
        gap: 7px;
    }

    .soc-nav a {
        position: relative;
        overflow: hidden;
        min-height: 45px;
        padding: 12px 13px;
        border-radius: 16px;
        text-decoration: none;
        color: var(--text-muted);
        border: 1px solid transparent;
        background: transparent;
        font-size: 13px;
        font-weight: 850;
        transition: .22s ease;
    }

    .soc-nav a::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 16%, transparent), transparent);
        opacity: 0;
        transition: .22s ease;
    }

    .soc-nav a span,
    .soc-nav a {
        z-index: 1;
    }

    .soc-nav a:hover {
        color: var(--text-main);
        transform: translateX(3px);
        border-color: color-mix(in srgb, var(--accent) 18%, transparent);
        background: color-mix(in srgb, var(--accent) 5%, transparent);
    }

    .soc-nav a:hover::before {
        opacity: 1;
    }

    .soc-nav a.active {
        color: var(--text-main);
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--accent) 20%, transparent), color-mix(in srgb, var(--accent-2) 9%, transparent));
        border-color: color-mix(in srgb, var(--accent) 35%, transparent);
        box-shadow: 0 14px 30px color-mix(in srgb, var(--accent) 12%, transparent);
    }

    .soc-nav a.active::after {
        content: "";
        position: absolute;
        left: 0;
        top: 12px;
        bottom: 12px;
        width: 4px;
        border-radius: 999px;
        background: var(--accent);
    }

    /*
     |--------------------------------------------------------------------------
     | Header top
     |--------------------------------------------------------------------------
     */

    .soc-main,
    .soc-content {
        min-width: 0;
    }

    .soc-topbar,
    .page-topbar,
    .soc-page-header {
        position: sticky;
        top: 0;
        z-index: 20;
        backdrop-filter: blur(18px);
        background: color-mix(in srgb, var(--bg-main) 78%, transparent);
        border-bottom: 1px solid color-mix(in srgb, var(--border-soft) 80%, transparent);
    }

    .soc-page-header {
        padding: 18px 24px;
    }

    .soc-page-title,
    .page-title {
        font-size: clamp(25px, 3vw, 38px);
        line-height: 1;
        letter-spacing: -.06em;
        font-weight: 950;
    }

    .soc-page-subtitle,
    .page-subtitle {
        color: var(--text-muted);
        margin-top: 6px;
        font-size: 14px;
    }

    /*
     |--------------------------------------------------------------------------
     | Cards globales
     |--------------------------------------------------------------------------
     */

    .analysis-hero,
    .smart-card,
    .soc-card,
    .smart-stat,
    .mobile-data-card,
    .setting-card,
    .link-card,
    .chart-card {
        border-radius: var(--rs-radius-xl);
        border: 1px solid color-mix(in srgb, var(--border-soft) 88%, transparent);
        box-shadow:
            0 18px 55px rgba(2, 6, 23, .08),
            inset 0 1px 0 color-mix(in srgb, white 10%, transparent);
    }

    .analysis-hero {
        position: relative;
        overflow: hidden;
    }

    .analysis-hero::after {
        content: "";
        position: absolute;
        right: -90px;
        top: -90px;
        width: 240px;
        height: 240px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 12%, transparent);
        filter: blur(2px);
    }

    .analysis-hero h2 {
        font-size: clamp(36px, 5vw, 64px);
        letter-spacing: -.075em;
        line-height: .95;
    }

    .analysis-kicker {
        width: fit-content;
        padding: 8px 12px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 9%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
    }

    .smart-stat {
        position: relative;
        overflow: hidden;
        transition: .2s ease;
    }

    .smart-stat:hover,
    .smart-card:hover,
    .soc-card:hover {
        transform: translateY(-2px);
    }

    .smart-stat::after {
        content: "";
        position: absolute;
        right: -30px;
        bottom: -30px;
        width: 100px;
        height: 100px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 8%, transparent);
    }

    .smart-stat-value {
        font-size: clamp(28px, 4vw, 44px);
        letter-spacing: -.07em;
    }

    /*
     |--------------------------------------------------------------------------
     | Boutons
     |--------------------------------------------------------------------------
     */

    .btn,
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 15px;
        min-height: 40px;
        padding: 10px 14px;
        border: 1px solid var(--border-soft);
        font-weight: 900;
        font-size: 13px;
        text-decoration: none;
        transition: .2s ease;
        cursor: pointer;
    }

    .btn:hover,
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(2, 6, 23, .12);
    }

    .btn-primary,
    .action-btn.primary {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        color: white;
        border-color: transparent;
    }

    .action-btn.success {
        background: color-mix(in srgb, #22c55e 18%, transparent);
        color: var(--text-main);
        border-color: color-mix(in srgb, #22c55e 30%, transparent);
    }

    .action-btn.warning {
        background: color-mix(in srgb, #f59e0b 18%, transparent);
        color: var(--text-main);
        border-color: color-mix(in srgb, #f59e0b 32%, transparent);
    }

    .action-btn.danger {
        background: color-mix(in srgb, #ef4444 16%, transparent);
        color: var(--text-main);
        border-color: color-mix(in srgb, #ef4444 32%, transparent);
    }

    /*
     |--------------------------------------------------------------------------
     | Tables
     |--------------------------------------------------------------------------
     */

    .soc-table {
        border-spacing: 0 8px;
        border-collapse: separate;
    }

    .soc-table thead th {
        background: transparent;
        color: var(--text-muted);
        padding: 10px 14px;
    }

    .soc-table tbody tr {
        transition: .18s ease;
    }

    .soc-table tbody td {
        background: color-mix(in srgb, var(--bg-card) 92%, transparent);
        border-top: 1px solid var(--border-soft);
        border-bottom: 1px solid var(--border-soft);
        padding: 14px;
    }

    .soc-table tbody td:first-child {
        border-left: 1px solid var(--border-soft);
        border-radius: 18px 0 0 18px;
    }

    .soc-table tbody td:last-child {
        border-right: 1px solid var(--border-soft);
        border-radius: 0 18px 18px 0;
    }

    .soc-table tbody tr:hover td {
        background: color-mix(in srgb, var(--accent) 7%, var(--bg-card));
    }

    /*
     |--------------------------------------------------------------------------
     | Badges
     |--------------------------------------------------------------------------
     */

    .badge {
        border-radius: 999px;
        padding: 7px 10px;
        font-size: 11px;
        font-weight: 950;
        border: 1px solid var(--border-soft);
        background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
    }

    .badge-normal {
        background: color-mix(in srgb, #22c55e 15%, transparent);
        border-color: color-mix(in srgb, #22c55e 30%, transparent);
    }

    .badge-suspect {
        background: color-mix(in srgb, #f59e0b 15%, transparent);
        border-color: color-mix(in srgb, #f59e0b 30%, transparent);
    }

    .badge-high {
        background: color-mix(in srgb, #fb923c 17%, transparent);
        border-color: color-mix(in srgb, #fb923c 30%, transparent);
    }

    .badge-critical {
        background: color-mix(in srgb, #ef4444 16%, transparent);
        border-color: color-mix(in srgb, #ef4444 32%, transparent);
    }

    /*
     |--------------------------------------------------------------------------
     | Mobile menu
     |--------------------------------------------------------------------------
     */

    .rs-mobile-toggle {
        display: none;
        position: fixed;
        left: 16px;
        bottom: 16px;
        z-index: 100;
        width: 56px;
        height: 56px;
        border-radius: 18px;
        border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        color: white;
        font-weight: 950;
        box-shadow: 0 18px 50px rgba(2, 6, 23, .28);
        cursor: pointer;
    }

    .rs-menu-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, .55);
        backdrop-filter: blur(3px);
        z-index: 70;
    }

    body.rs-menu-open .rs-menu-overlay {
        display: block;
    }

    /*
     |--------------------------------------------------------------------------
     | Responsive
     |--------------------------------------------------------------------------
     */

    @media (max-width: 1100px) {
        .soc-shell,
        .soc-layout {
            grid-template-columns: 1fr !important;
        }

        .rs-mobile-toggle {
            display: grid;
            place-items: center;
        }

        .soc-sidebar {
            position: fixed !important;
            left: 12px;
            top: 12px;
            bottom: 12px;
            width: min(88vw, 330px) !important;
            height: auto !important;
            max-height: calc(100vh - 24px) !important;
            border-radius: 28px;
            transform: translateX(calc(-100% - 30px));
            transition: transform .26s ease;
            z-index: 80;
            box-shadow: 0 24px 80px rgba(2, 6, 23, .28);
        }

        body.rs-menu-open .soc-sidebar {
            transform: translateX(0);
        }

        .soc-main,
        .soc-content,
        main {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }

        .soc-page-header {
            padding-left: 24px;
            padding-right: 24px;
        }
    }

    @media (max-width: 760px) {
        .soc-page-header {
            padding: 16px;
        }

        .animated-page {
            padding: 12px !important;
        }

        .analysis-hero,
        .smart-card,
        .soc-card {
            border-radius: 22px;
        }

        .analysis-hero h2 {
            font-size: 38px !important;
        }

        .smart-stats {
            grid-template-columns: 1fr !important;
        }

        .btn-row,
        .inline-actions,
        .setting-actions {
            display: grid !important;
            grid-template-columns: 1fr !important;
            width: 100%;
        }

        .btn,
        .action-btn,
        .btn-row form,
        .inline-actions form {
            width: 100%;
        }

        .desktop-table-prefer {
            display: none !important;
        }

        .host-card-list {
            display: grid !important;
            gap: 14px;
        }
    }
</style>

<button type="button" class="rs-mobile-toggle" id="rsMobileToggle" aria-label="Menu">
    ☰
</button>

<div class="rs-menu-overlay" id="rsMenuOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('rsMobileToggle');
        const overlay = document.getElementById('rsMenuOverlay');

        function closeMenu() {
            document.body.classList.remove('rs-menu-open');
        }

        function toggleMenu() {
            document.body.classList.toggle('rs-menu-open');
        }

        if (button) {
            button.addEventListener('click', toggleMenu);
        }

        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }

        document.querySelectorAll('.soc-nav a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });
</script>
