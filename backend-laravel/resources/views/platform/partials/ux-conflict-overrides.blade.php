<style>
    /*
     |--------------------------------------------------------------------------
     | Dernière couche anti-conflit UX
     |--------------------------------------------------------------------------
     | Cette couche passe après les anciens correctifs et force :
     | - sidebar rétractable
     | - scroll fluide du contenu
     | - pas de double scroll cassé
     */

    @media (min-width: 1101px) {
        body {
            overflow: hidden !important;
        }

        .soc-shell,
        .soc-layout {
            height: 100vh !important;
            overflow: hidden !important;
        }

        .soc-sidebar {
            position: relative !important;
            height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            transform: none !important;
        }

        .soc-main,
        .soc-content,
        main {
            height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        body.rs-sidebar-collapsed .soc-shell,
        body.rs-sidebar-collapsed .soc-layout {
            grid-template-columns: 86px minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 1100px) {
        body {
            overflow: auto !important;
        }

        .soc-shell,
        .soc-layout {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }

        .soc-sidebar {
            position: fixed !important;
        }

        .soc-main,
        .soc-content,
        main {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
    }
</style>
