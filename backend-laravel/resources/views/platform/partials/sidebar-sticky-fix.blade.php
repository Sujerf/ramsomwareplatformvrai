<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield Layout Fix
     |--------------------------------------------------------------------------
     | Objectif :
     | - sidebar fixe/sticky
     | - seul le contenu principal scrolle
     | - meilleure respiration visuelle
     */

    html,
    body {
        height: 100%;
        overflow: hidden;
    }

    body {
        margin: 0;
    }

    .soc-shell,
    .soc-layout,
    .app-shell {
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
    }

    .soc-sidebar,
    aside {
        position: sticky !important;
        top: 0 !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        align-self: start !important;
        z-index: 30;
    }

    .soc-main,
    .soc-content,
    main {
        height: 100vh;
        max-height: 100vh;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        scroll-behavior: smooth;
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
        background: color-mix(in srgb, var(--text-muted) 26%, transparent);
        border-radius: 999px;
    }

    .soc-nav a {
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .soc-nav a.active {
        background: color-mix(in srgb, var(--accent) 16%, transparent);
        border-color: color-mix(in srgb, var(--accent) 32%, transparent);
        color: var(--text-main);
    }

    @media (max-width: 980px) {
        html,
        body {
            overflow: auto;
            height: auto;
        }

        .soc-shell,
        .soc-layout,
        .app-shell {
            height: auto;
            max-height: none;
            overflow: visible;
        }

        .soc-sidebar,
        aside {
            position: relative !important;
            height: auto !important;
            max-height: none !important;
        }

        .soc-main,
        .soc-content,
        main {
            height: auto;
            max-height: none;
            overflow: visible !important;
        }
    }
</style>
