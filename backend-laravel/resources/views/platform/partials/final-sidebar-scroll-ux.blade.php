<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield Final UX Controller
     |--------------------------------------------------------------------------
     | Une seule couche finale pour :
     | - menu rétractable desktop
     | - icônes menu réduit
     | - scroll complet jusqu'en bas
     | - suppression des conflits précédents
     */

    :root {
        --rs-sidebar-open-width: 310px;
        --rs-sidebar-closed-width: 82px;
        --rs-transition: 260ms cubic-bezier(.2, .8, .2, 1);
    }

    html {
        height: 100%;
        scroll-behavior: smooth;
    }

    body {
        min-height: 100%;
        overflow: hidden !important;
    }

    @media (min-width: 1101px) {
        .soc-shell,
        .soc-layout {
            display: grid !important;
            grid-template-columns: var(--rs-sidebar-open-width) minmax(0, 1fr) !important;
            width: 100% !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow: hidden !important;
            transition: grid-template-columns var(--rs-transition) !important;
        }

        body.rs-sidebar-collapsed .soc-shell,
        body.rs-sidebar-collapsed .soc-layout {
            grid-template-columns: var(--rs-sidebar-closed-width) minmax(0, 1fr) !important;
        }

        .soc-sidebar {
            position: relative !important;
            inset: auto !important;
            transform: none !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: visible !important;
            padding: 16px !important;
            z-index: 40 !important;
            scrollbar-width: thin;
        }

        .soc-main,
        .soc-content,
        main {
            position: relative !important;
            width: 100% !important;
            min-width: 0 !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scroll-behavior: smooth;
            overscroll-behavior: contain;
            padding-bottom: 120px !important;
            scrollbar-width: thin;
        }

        .animated-page {
            padding-bottom: 120px !important;
        }

        .soc-card:last-child,
        .smart-card:last-child,
        section:last-child {
            margin-bottom: 90px !important;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Bouton réduire / ouvrir
     |--------------------------------------------------------------------------
     */

    .rs-final-sidebar-toggle {
        width: 100%;
        min-height: 46px;
        margin-bottom: 16px;
        border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
        border-radius: 18px;
        background: linear-gradient(
            135deg,
            color-mix(in srgb, var(--accent) 18%, transparent),
            color-mix(in srgb, var(--accent-2) 10%, transparent)
        );
        color: var(--text-main);
        font-weight: 950;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 14px 32px rgba(2, 6, 23, .10);
        transition: .2s ease;
    }

    .rs-final-sidebar-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 20px 45px rgba(2, 6, 23, .16);
    }

    .rs-final-sidebar-toggle-icon {
        width: 30px;
        height: 30px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--accent) 20%, transparent);
        transition: transform var(--rs-transition);
        font-size: 20px;
        line-height: 1;
    }

    body.rs-sidebar-collapsed .rs-final-sidebar-toggle {
        width: 54px;
        min-height: 50px;
        padding: 8px;
        margin-left: auto;
        margin-right: auto;
    }

    body.rs-sidebar-collapsed .rs-final-sidebar-toggle-text {
        display: none !important;
    }

    body.rs-sidebar-collapsed .rs-final-sidebar-toggle-icon {
        transform: rotate(180deg);
    }

    /*
     |--------------------------------------------------------------------------
     | Navigation avec icônes
     |--------------------------------------------------------------------------
     */

    .rs-final-nav-left {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .rs-final-nav-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
        font-size: 16px;
    }

    .rs-final-nav-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .soc-nav a.active .rs-final-nav-icon {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        color: white;
        border-color: transparent;
    }

    @media (min-width: 1101px) {
        body.rs-sidebar-collapsed .soc-sidebar {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        body.rs-sidebar-collapsed .soc-brand,
        body.rs-sidebar-collapsed .brand,
        body.rs-sidebar-collapsed .soc-logo {
            height: 58px !important;
            padding: 10px !important;
            display: grid !important;
            place-items: center !important;
            overflow: hidden !important;
        }

        body.rs-sidebar-collapsed .soc-brand-title,
        body.rs-sidebar-collapsed .brand-title,
        body.rs-sidebar-collapsed .soc-brand-subtitle,
        body.rs-sidebar-collapsed .brand-subtitle {
            display: none !important;
        }

        body.rs-sidebar-collapsed .soc-brand::after,
        body.rs-sidebar-collapsed .brand::after,
        body.rs-sidebar-collapsed .soc-logo::after {
            content: "🛡";
            font-size: 25px;
        }

        body.rs-sidebar-collapsed .soc-nav-section {
            margin: 14px 0 !important;
            display: grid !important;
            place-items: center !important;
        }

        body.rs-sidebar-collapsed .soc-nav-label {
            height: 18px !important;
            margin: 8px 0 !important;
            font-size: 0 !important;
            justify-content: center !important;
        }

        body.rs-sidebar-collapsed .soc-nav-label::before {
            width: 8px !important;
            height: 8px !important;
            margin: 0 !important;
        }

        body.rs-sidebar-collapsed .soc-nav {
            display: grid !important;
            place-items: center !important;
            width: 100% !important;
        }

        body.rs-sidebar-collapsed .soc-nav a {
            position: relative !important;
            width: 56px !important;
            min-height: 50px !important;
            padding: 8px !important;
            justify-content: center !important;
            overflow: visible !important;
            border-radius: 18px !important;
        }

        body.rs-sidebar-collapsed .rs-final-nav-left {
            gap: 0 !important;
        }

        body.rs-sidebar-collapsed .rs-final-nav-icon {
            width: 38px !important;
            height: 38px !important;
            min-width: 38px !important;
            border-radius: 16px !important;
        }

        body.rs-sidebar-collapsed .rs-final-nav-text,
        body.rs-sidebar-collapsed .soc-nav a > span:not(.rs-final-nav-left):not(.rs-final-nav-icon):not(.rs-final-nav-text) {
            display: none !important;
        }

        body.rs-sidebar-collapsed .soc-nav a::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%) translateX(-6px);
            opacity: 0;
            pointer-events: none;
            white-space: nowrap;
            padding: 9px 12px;
            border-radius: 14px;
            background: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-soft);
            box-shadow: 0 18px 50px rgba(2, 6, 23, .20);
            font-size: 12px;
            font-weight: 900;
            z-index: 9999;
            transition: .16s ease;
        }

        body.rs-sidebar-collapsed .soc-nav a:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Mobile
     |--------------------------------------------------------------------------
     */

    .rs-final-mobile-toggle,
    .rs-final-menu-overlay {
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

        .soc-main,
        .soc-content,
        main {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            padding-bottom: 100px !important;
        }

        .animated-page {
            padding-bottom: 110px !important;
        }

        .soc-sidebar {
            position: fixed !important;
            left: 12px !important;
            top: 12px !important;
            bottom: 12px !important;
            width: min(88vw, 340px) !important;
            height: auto !important;
            max-height: calc(100vh - 24px) !important;
            border-radius: 28px !important;
            transform: translateX(calc(-100% - 40px)) !important;
            z-index: 100 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            transition: transform var(--rs-transition) !important;
        }

        body.rs-menu-open .soc-sidebar {
            transform: translateX(0) !important;
        }

        .rs-final-sidebar-toggle {
            display: none !important;
        }

        .rs-final-mobile-toggle {
            display: grid !important;
            place-items: center;
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 130;
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

        .rs-final-menu-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(2, 6, 23, .55);
            backdrop-filter: blur(4px);
        }

        body.rs-menu-open .rs-final-menu-overlay {
            display: block;
        }
    }
</style>

<script>
(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        const body = document.body;
        const sidebar = document.querySelector('.soc-sidebar');

        if (!sidebar) return;

        /*
         * Nettoyage des anciens boutons/scripts visuels.
         */
        document.querySelectorAll('#rsSidebarToggle, .rs-sidebar-toggle, #rsMobileToggle, .rs-mobile-toggle, #rsMenuOverlay, .rs-menu-overlay')
            .forEach(el => el.remove());

        /*
         * Bouton desktop final.
         */
        let toggle = document.getElementById('rsFinalSidebarToggle');

        if (!toggle) {
            toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.id = 'rsFinalSidebarToggle';
            toggle.className = 'rs-final-sidebar-toggle';
            toggle.innerHTML = `
                <span class="rs-final-sidebar-toggle-icon">‹</span>
                <span class="rs-final-sidebar-toggle-text">Réduire le menu</span>
            `;

            sidebar.insertBefore(toggle, sidebar.firstChild);
        }

        /*
         * Bouton mobile final.
         */
        let mobile = document.getElementById('rsFinalMobileToggle');

        if (!mobile) {
            mobile = document.createElement('button');
            mobile.type = 'button';
            mobile.id = 'rsFinalMobileToggle';
            mobile.className = 'rs-final-mobile-toggle';
            mobile.innerHTML = '☰';
            document.body.appendChild(mobile);
        }

        let overlay = document.getElementById('rsFinalMenuOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'rsFinalMenuOverlay';
            overlay.className = 'rs-final-menu-overlay';
            document.body.appendChild(overlay);
        }

        /*
         * Icônes automatiques.
         */
        const iconMap = [
            ['dashboard', '📊'],
            ['accueil', '🏠'],
            ['configuration', '⚙️'],
            ['paramètre', '🔧'],
            ['parametre', '🔧'],
            ['réseau', '🌐'],
            ['reseau', '🌐'],
            ['hôte', '🖥️'],
            ['hote', '🖥️'],
            ['machine', '💻'],
            ['agent', '🛰️'],
            ['alerte', '🚨'],
            ['incident', '🔥'],
            ['action', '🛡️'],
            ['approbation', '✅'],
            ['règle', '🧠'],
            ['regle', '🧠'],
            ['seuil', '📈'],
            ['politique', '🧩'],
            ['extension', '🧬'],
            ['détection', '🎯'],
            ['detection', '🎯'],
            ['infrastructure', '🏗️'],
            ['historique', '🕘'],
        ];

        function pickIcon(label) {
            const normalized = label.toLowerCase();

            for (const item of iconMap) {
                if (normalized.includes(item[0])) return item[1];
            }

            return '•';
        }

        document.querySelectorAll('.soc-nav a').forEach(link => {
            if (link.dataset.finalEnhanced === '1') return;

            const rawLabel = link.textContent.replace('→', '').trim();
            const icon = pickIcon(rawLabel);

            link.dataset.label = rawLabel;
            link.dataset.finalEnhanced = '1';

            link.innerHTML = `
                <span class="rs-final-nav-left">
                    <span class="rs-final-nav-icon">${icon}</span>
                    <span class="rs-final-nav-text">${rawLabel}</span>
                </span>
                <span>→</span>
            `;
        });

        function refreshText() {
            const txt = toggle.querySelector('.rs-final-sidebar-toggle-text');
            if (!txt) return;

            txt.textContent = body.classList.contains('rs-sidebar-collapsed')
                ? 'Ouvrir le menu'
                : 'Réduire le menu';
        }

        if (window.innerWidth > 1100 && localStorage.getItem('rs-sidebar-collapsed') === '1') {
            body.classList.add('rs-sidebar-collapsed');
        }

        refreshText();

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            body.classList.toggle('rs-sidebar-collapsed');

            localStorage.setItem(
                'rs-sidebar-collapsed',
                body.classList.contains('rs-sidebar-collapsed') ? '1' : '0'
            );

            refreshText();

            setTimeout(() => window.dispatchEvent(new Event('resize')), 280);
        });

        mobile.addEventListener('click', function () {
            body.classList.toggle('rs-menu-open');
        });

        overlay.addEventListener('click', function () {
            body.classList.remove('rs-menu-open');
        });

        document.querySelectorAll('.soc-nav a').forEach(link => {
            link.addEventListener('click', () => body.classList.remove('rs-menu-open'));
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth <= 1100) {
                body.classList.remove('rs-sidebar-collapsed');
            } else if (localStorage.getItem('rs-sidebar-collapsed') === '1') {
                body.classList.add('rs-sidebar-collapsed');
            }

            refreshText();
        });
    });
})();
</script>
