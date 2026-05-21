<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield Final Sidebar UX
     |--------------------------------------------------------------------------
     | Objectifs :
     | - menu réduit propre avec icônes
     | - tooltip au survol
     | - largeur réduite plus fine
     | - scroll principal fluide
     | - pas de saccades dans pages longues
     */

    :root {
        --rs-sidebar-open: 310px;
        --rs-sidebar-collapsed: 78px;
        --rs-sidebar-transition: 260ms cubic-bezier(.2, .8, .2, 1);
    }

    @media (min-width: 1101px) {
        html,
        body {
            height: 100%;
            overflow: hidden !important;
        }

        .soc-shell,
        .soc-layout {
            height: 100vh !important;
            max-height: 100vh !important;
            overflow: hidden !important;
            display: grid !important;
            grid-template-columns: var(--rs-sidebar-open) minmax(0, 1fr) !important;
            transition: grid-template-columns var(--rs-sidebar-transition);
            will-change: grid-template-columns;
        }

        body.rs-sidebar-collapsed .soc-shell,
        body.rs-sidebar-collapsed .soc-layout {
            grid-template-columns: var(--rs-sidebar-collapsed) minmax(0, 1fr) !important;
        }

        .soc-sidebar {
            position: relative !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: visible !important;
            padding: 16px !important;
            transition:
                padding var(--rs-sidebar-transition),
                width var(--rs-sidebar-transition);
            scrollbar-width: thin;
            overscroll-behavior: contain;
        }

        .soc-main,
        .soc-content,
        main {
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scroll-behavior: smooth;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            will-change: scroll-position;
        }

        .soc-main *,
        .soc-content *,
        main * {
            scroll-margin-top: 90px;
        }

        body.rs-sidebar-collapsed .soc-sidebar {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Bouton réduire / ouvrir plus propre
     |--------------------------------------------------------------------------
     */

    .rs-sidebar-toggle {
        position: sticky !important;
        top: 8px !important;
        z-index: 95 !important;
        width: 100%;
        min-height: 44px;
        margin-bottom: 16px;
        border-radius: 18px;
        border: 1px solid color-mix(in srgb, var(--accent) 26%, transparent);
        background:
            linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 16%, transparent),
                color-mix(in srgb, var(--accent-2) 10%, transparent)
            );
        color: var(--text-main);
        font-weight: 950;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        box-shadow: 0 12px 30px rgba(2, 6, 23, .10);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .rs-sidebar-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 42px rgba(2, 6, 23, .16);
        border-color: color-mix(in srgb, var(--accent) 42%, transparent);
    }

    .rs-sidebar-toggle-icon {
        width: 28px;
        height: 28px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: color-mix(in srgb, var(--accent) 18%, transparent);
        transition: transform var(--rs-sidebar-transition);
        font-size: 18px;
        line-height: 1;
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle {
        min-height: 46px;
        padding: 8px;
        border-radius: 18px;
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle-text {
        display: none !important;
    }

    body.rs-sidebar-collapsed .rs-sidebar-toggle-icon {
        transform: rotate(180deg);
    }

    /*
     |--------------------------------------------------------------------------
     | Icônes de navigation
     |--------------------------------------------------------------------------
     */

    .soc-nav a {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 10px !important;
        min-height: 46px;
    }

    .rs-nav-left {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .rs-nav-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--accent) 9%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 16%, transparent);
        font-size: 15px;
        line-height: 1;
        transition: .2s ease;
    }

    .rs-nav-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .soc-nav a.active .rs-nav-icon {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        color: white;
        border-color: transparent;
        box-shadow: 0 10px 25px color-mix(in srgb, var(--accent) 22%, transparent);
    }

    .soc-nav a:hover .rs-nav-icon {
        transform: scale(1.05);
        background: color-mix(in srgb, var(--accent) 16%, transparent);
    }

    /*
     |--------------------------------------------------------------------------
     | Menu réduit : icônes seules + tooltip
     |--------------------------------------------------------------------------
     */

    @media (min-width: 1101px) {
        body.rs-sidebar-collapsed .soc-brand,
        body.rs-sidebar-collapsed .brand,
        body.rs-sidebar-collapsed .soc-logo {
            height: 58px;
            padding: 10px !important;
            display: grid;
            place-items: center;
            overflow: hidden;
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
            font-size: 24px;
            line-height: 1;
        }

        body.rs-sidebar-collapsed .soc-nav-section {
            margin: 14px 0;
        }

        body.rs-sidebar-collapsed .soc-nav-label {
            height: 18px;
            margin: 8px 0 !important;
            justify-content: center;
            font-size: 0 !important;
        }

        body.rs-sidebar-collapsed .soc-nav-label::before {
            width: 8px;
            height: 8px;
            margin: 0;
        }

        body.rs-sidebar-collapsed .soc-nav a {
            position: relative;
            justify-content: center !important;
            padding: 8px !important;
            min-height: 48px;
            border-radius: 18px;
            overflow: visible !important;
        }

        body.rs-sidebar-collapsed .rs-nav-left {
            gap: 0;
        }

        body.rs-sidebar-collapsed .rs-nav-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 16px;
            font-size: 17px;
        }

        body.rs-sidebar-collapsed .rs-nav-text,
        body.rs-sidebar-collapsed .soc-nav a > span:not(.rs-nav-left):not(.rs-nav-icon):not(.rs-nav-text) {
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
            box-shadow: 0 18px 50px rgba(2, 6, 23, .18);
            font-size: 12px;
            font-weight: 900;
            z-index: 999;
            transition: .16s ease;
        }

        body.rs-sidebar-collapsed .soc-nav a:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Scroll plus fluide dans les pages longues
     |--------------------------------------------------------------------------
     */

    .animated-page,
    .soc-card,
    .smart-card,
    .analysis-hero,
    .table-wrap,
    .setting-grid,
    .link-grid,
    .chart-grid {
        transform: translateZ(0);
    }

    .soc-main,
    .soc-content,
    main {
        contain: layout paint;
    }

    .table-wrap {
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .soc-card,
    .smart-card,
    .smart-stat {
        transition: transform .16s ease, box-shadow .16s ease;
    }

    /*
     * On réduit les animations trop lourdes quand l’utilisateur scrolle.
     */
    body.rs-is-scrolling .soc-card:hover,
    body.rs-is-scrolling .smart-card:hover,
    body.rs-is-scrolling .smart-stat:hover {
        transform: none !important;
    }

    body.rs-is-scrolling * {
        scroll-behavior: auto !important;
    }

    /*
     |--------------------------------------------------------------------------
     | Mobile : pas de menu réduit, menu drawer normal
     |--------------------------------------------------------------------------
     */

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
            contain: none !important;
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
            overflow-y: auto !important;
        }

        body.rs-menu-open .soc-sidebar {
            transform: translateX(0);
        }

        body.rs-sidebar-collapsed .soc-nav a,
        body.rs-sidebar-collapsed .soc-nav-label,
        body.rs-sidebar-collapsed .rs-nav-text {
            font-size: inherit !important;
            display: flex !important;
        }

        body.rs-sidebar-collapsed .rs-nav-text {
            display: inline !important;
        }

        body.rs-sidebar-collapsed .soc-nav a::after {
            display: none !important;
        }

        .rs-sidebar-toggle {
            display: none !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const sidebar = document.querySelector('.soc-sidebar');

        if (!sidebar) return;

        const iconMap = [
            ['dashboard', '📊'],
            ['accueil', '🏠'],
            ['configuration', '⚙️'],
            ['paramètre', '🔧'],
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

            for (const [key, icon] of iconMap) {
                if (normalized.includes(key)) {
                    return icon;
                }
            }

            return '•';
        }

        function enhanceNavLinks() {
            document.querySelectorAll('.soc-nav a').forEach(function (link) {
                if (link.dataset.enhanced === '1') return;

                const rawLabel = link.textContent.replace('→', '').trim();
                const icon = pickIcon(rawLabel);

                link.dataset.label = rawLabel;
                link.dataset.enhanced = '1';

                const originalArrow = link.querySelector('span')?.outerHTML || '<span>→</span>';

                link.innerHTML = `
                    <span class="rs-nav-left">
                        <span class="rs-nav-icon">${icon}</span>
                        <span class="rs-nav-text">${rawLabel}</span>
                    </span>
                    ${originalArrow}
                `;
            });
        }

        function ensureToggle() {
            if (document.getElementById('rsSidebarToggle')) return;

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

        function refreshToggleText() {
            const text = document.querySelector('.rs-sidebar-toggle-text');
            if (!text) return;

            text.textContent = body.classList.contains('rs-sidebar-collapsed')
                ? 'Ouvrir le menu'
                : 'Réduire le menu';
        }

        function applySavedState() {
            if (window.innerWidth > 1100 && localStorage.getItem('rs-sidebar-collapsed') === '1') {
                body.classList.add('rs-sidebar-collapsed');
            }

            if (window.innerWidth <= 1100) {
                body.classList.remove('rs-sidebar-collapsed');
            }

            refreshToggleText();
        }

        function bindToggle() {
            const toggle = document.getElementById('rsSidebarToggle');

            if (!toggle || toggle.dataset.bound === '1') return;

            toggle.dataset.bound = '1';

            toggle.addEventListener('click', function () {
                body.classList.toggle('rs-sidebar-collapsed');

                localStorage.setItem(
                    'rs-sidebar-collapsed',
                    body.classList.contains('rs-sidebar-collapsed') ? '1' : '0'
                );

                refreshToggleText();

                setTimeout(function () {
                    window.dispatchEvent(new Event('resize'));
                }, 280);
            });
        }

        function improveScroll() {
            const scrollContainers = [
                document.querySelector('.soc-main'),
                document.querySelector('.soc-content'),
                document.querySelector('main')
            ].filter(Boolean);

            let timer = null;

            scrollContainers.forEach(function (container) {
                container.addEventListener('scroll', function () {
                    body.classList.add('rs-is-scrolling');

                    clearTimeout(timer);

                    timer = setTimeout(function () {
                        body.classList.remove('rs-is-scrolling');
                    }, 140);
                }, { passive: true });
            });
        }

        enhanceNavLinks();
        ensureToggle();
        bindToggle();
        applySavedState();
        improveScroll();

        window.addEventListener('resize', applySavedState);
    });
</script>
