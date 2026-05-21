<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield UX Fix
     |--------------------------------------------------------------------------
     | Objectifs :
     | - sidebar rétractable sur desktop
     | - page qui s’agrandit quand le menu est fermé
     | - menu mobile propre
     | - scroll fluide et naturel
     */

    :root {
        --rs-sidebar-open: 310px;
        --rs-sidebar-closed: 86px;
        --rs-scrollbar: rgba(148, 163, 184, .38);
    }

    html {
        scroll-behavior: smooth;
        height: 100%;
    }

    body {
        min-height: 100%;
        overflow: hidden !important;
    }

    .soc-shell,
    .soc-layout {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
        display: grid !important;
        grid-template-columns: var(--rs-sidebar-open) minmax(0, 1fr) !important;
        transition: grid-template-columns .28s ease;
    }

    body.rs-sidebar-collapsed .soc-shell,
    body.rs-sidebar-collapsed .soc-layout {
        grid-template-columns: var(--rs-sidebar-closed) minmax(0, 1fr) !important;
    }

    .soc-sidebar {
        position: relative !important;
        top: auto !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        transition: width .28s ease, padding .28s ease, transform .28s ease;
        scrollbar-width: thin;
        scrollbar-color: var(--rs-scrollbar) transparent;
    }

    .soc-main,
    .soc-content,
    main {
        min-width: 0 !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: var(--rs-scrollbar) transparent;
    }

    .soc-sidebar::-webkit-scrollbar,
    .soc-main::-webkit-scrollbar,
    .soc-content::-webkit-scrollbar,
    main::-webkit-scrollbar {
        width: 8px;
    }

    .soc-sidebar::-webkit-scrollbar-thumb,
    .soc-main::-webkit-scrollbar-thumb,
    .soc-content::-webkit-scrollbar-thumb,
    main::-webkit-scrollbar-thumb {
        background: var(--rs-scrollbar);
        border-radius: 999px;
    }

    .soc-sidebar::-webkit-scrollbar-track,
    .soc-main::-webkit-scrollbar-track,
    .soc-content::-webkit-scrollbar-track,
    main::-webkit-scrollbar-track {
        background: transparent;
    }

    /*
     |--------------------------------------------------------------------------
     | Bouton desktop fermeture/ouverture
     |--------------------------------------------------------------------------
     */

    .rs-sidebar-toggle {
        position: sticky;
        top: 10px;
        z-index: 90;
        width: 100%;
        min-height: 44px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border: 1px solid color-mix(in srgb, var(--accent) 24%, transparent);
        border-radius: 18px;
        background: linear-gradient(
            135deg,
            color-mix(in srgb, var(--accent) 16%, transparent),
            color-mix(in srgb, var(--accent-2) 10%, transparent)
        );
        color: var(--text-main);
        font-weight: 950;
        cursor: pointer;
        transition: .22s ease;
        box-shadow: 0 14px 32px rgba(2, 6, 23, .10);
    }

    .rs-sidebar-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 40px rgba(2, 6, 23, .16);
    }

    .rs-sidebar-toggle-icon {
        width: 28px;
        height: 28px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--accent) 18%, transparent);
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle-text {
        display: none;
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle {
        padding: 8px;
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle-icon {
        transform: rotate(180deg);
    }

    /*
     |--------------------------------------------------------------------------
     | Mode sidebar fermée
     |--------------------------------------------------------------------------
     */

    body.rs-sidebar-collapsed .soc-sidebar {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    body.rs-sidebar-collapsed .soc-brand,
    body.rs-sidebar-collapsed .brand,
    body.rs-sidebar-collapsed .soc-logo {
        padding: 12px !important;
        text-align: center;
    }

    body.rs-sidebar-collapsed .soc-brand-title,
    body.rs-sidebar-collapsed .brand-title,
    body.rs-sidebar-collapsed .soc-brand-subtitle,
    body.rs-sidebar-collapsed .brand-subtitle {
        display: none !important;
    }

    body.rs-sidebar-collapsed .soc-nav-label {
        justify-content: center;
        margin-left: 0 !important;
        font-size: 0 !important;
    }

    body.rs-sidebar-collapsed .soc-nav-label::before {
        width: 9px;
        height: 9px;
    }

    body.rs-sidebar-collapsed .soc-nav a {
        justify-content: center !important;
        padding: 13px 8px !important;
        min-height: 46px;
        border-radius: 16px;
        font-size: 0 !important;
    }

    body.rs-sidebar-collapsed .soc-nav a span {
        display: none !important;
    }

    body.rs-sidebar-collapsed .soc-nav a::before {
        opacity: .35;
    }

    body.rs-sidebar-collapsed .soc-nav a::after {
        display: none !important;
    }

    body.rs-sidebar-collapsed .soc-nav a {
        position: relative;
    }

    body.rs-sidebar-collapsed .soc-nav a::marker {
        display: none;
    }

    body.rs-sidebar-collapsed .soc-nav a::after {
        content: "";
    }

    /*
     |--------------------------------------------------------------------------
     | Tooltip simple en mode fermé
     |--------------------------------------------------------------------------
     */

    body.rs-sidebar-collapsed .soc-nav a:hover {
        transform: none;
    }

    body.rs-sidebar-collapsed .soc-nav a:hover::before {
        opacity: 1;
    }

    /*
     |--------------------------------------------------------------------------
     | Topbar plus stable
     |--------------------------------------------------------------------------
     */

    .soc-page-header,
    .soc-topbar,
    .page-topbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 30 !important;
        backdrop-filter: blur(18px);
    }

    .animated-page {
        animation: rsFadeIn .32s ease both;
    }

    @keyframes rsFadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Mobile
     |--------------------------------------------------------------------------
     */

    .rs-mobile-toggle {
        display: none;
    }

    .rs-menu-overlay {
        display: none;
    }

    @media (max-width: 1100px) {
        body {
            overflow: auto !important;
        }

        .soc-shell,
        .soc-layout {
            display: block !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }

        body.rs-sidebar-collapsed .soc-shell,
        body.rs-sidebar-collapsed .soc-layout {
            display: block !important;
        }

        .soc-main,
        .soc-content,
        main {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }

        .soc-sidebar {
            position: fixed !important;
            left: 12px !important;
            top: 12px !important;
            bottom: 12px !important;
            width: min(88vw, 340px) !important;
            height: auto !important;
            max-height: calc(100vh - 24px) !important;
            border-radius: 28px;
            transform: translateX(calc(-100% - 40px));
            z-index: 100;
            box-shadow: 0 24px 80px rgba(2, 6, 23, .35);
        }

        body.rs-menu-open .soc-sidebar {
            transform: translateX(0);
        }

        body.rs-sidebar-collapsed .soc-sidebar {
            padding-left: 18px !important;
            padding-right: 18px !important;
        }

        body.rs-sidebar-collapsed .soc-brand-title,
        body.rs-sidebar-collapsed .brand-title,
        body.rs-sidebar-collapsed .soc-brand-subtitle,
        body.rs-sidebar-collapsed .brand-subtitle {
            display: block !important;
        }

        body.rs-sidebar-collapsed .soc-nav-label {
            justify-content: flex-start;
            font-size: 11px !important;
        }

        body.rs-sidebar-collapsed .soc-nav a {
            justify-content: space-between !important;
            font-size: 13px !important;
            padding: 12px 13px !important;
        }

        body.rs-sidebar-collapsed .soc-nav a span {
            display: inline-flex !important;
        }

        .rs-sidebar-toggle {
            display: none !important;
        }

        .rs-mobile-toggle {
            display: grid !important;
            place-items: center;
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 120;
            width: 58px;
            height: 58px;
            border-radius: 20px;
            border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            font-size: 22px;
            font-weight: 950;
            box-shadow: 0 18px 50px rgba(2, 6, 23, .30);
            cursor: pointer;
        }

        .rs-menu-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(2, 6, 23, .55);
            backdrop-filter: blur(4px);
        }

        body.rs-menu-open .rs-menu-overlay {
            display: block;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const sidebar = document.querySelector('.soc-sidebar');

        if (!sidebar) {
            return;
        }

        if (!document.getElementById('rsSidebarToggle')) {
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.id = 'rsSidebarToggle';
            toggle.className = 'rs-sidebar-toggle';
            toggle.innerHTML = `
                <span class="rs-sidebar-toggle-icon">‹</span>
                <span class="rs-sidebar-toggle-text">Réduire le menu</span>
            `;

            sidebar.insertBefore(toggle, sidebar.firstChild);
        }

        if (!document.getElementById('rsMobileToggle')) {
            const mobile = document.createElement('button');
            mobile.type = 'button';
            mobile.id = 'rsMobileToggle';
            mobile.className = 'rs-mobile-toggle';
            mobile.innerHTML = '☰';
            document.body.appendChild(mobile);
        }

        if (!document.getElementById('rsMenuOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'rsMenuOverlay';
            overlay.className = 'rs-menu-overlay';
            document.body.appendChild(overlay);
        }

        const desktopToggle = document.getElementById('rsSidebarToggle');
        const mobileToggle = document.getElementById('rsMobileToggle');
        const overlay = document.getElementById('rsMenuOverlay');

        const saved = localStorage.getItem('rs-sidebar-collapsed');

        if (saved === '1' && window.innerWidth > 1100) {
            body.classList.add('rs-sidebar-collapsed');
        }

        function refreshDesktopText() {
            const text = desktopToggle?.querySelector('.rs-sidebar-toggle-text');

            if (!text) {
                return;
            }

            text.textContent = body.classList.contains('rs-sidebar-collapsed')
                ? 'Ouvrir le menu'
                : 'Réduire le menu';
        }

        refreshDesktopText();

        desktopToggle?.addEventListener('click', function () {
            body.classList.toggle('rs-sidebar-collapsed');

            const collapsed = body.classList.contains('rs-sidebar-collapsed');

            localStorage.setItem('rs-sidebar-collapsed', collapsed ? '1' : '0');

            refreshDesktopText();

            window.dispatchEvent(new Event('resize'));
        });

        mobileToggle?.addEventListener('click', function () {
            body.classList.toggle('rs-menu-open');
        });

        overlay?.addEventListener('click', function () {
            body.classList.remove('rs-menu-open');
        });

        document.querySelectorAll('.soc-nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                body.classList.remove('rs-menu-open');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                body.classList.remove('rs-menu-open');
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth <= 1100) {
                body.classList.remove('rs-sidebar-collapsed');
            } else {
                body.classList.remove('rs-menu-open');

                if (localStorage.getItem('rs-sidebar-collapsed') === '1') {
                    body.classList.add('rs-sidebar-collapsed');
                }
            }

            refreshDesktopText();
        });
    });
</script>
