<script>
    document.addEventListener('DOMContentLoaded', function () {
        /*
         * Nettoyage des éventuels anciens boutons créés par les précédentes couches.
         * On garde :
         * - #rsSidebarToggle pour desktop
         * - #rsMobileToggle pour mobile, s'il existe
         */

        const sidebarToggles = document.querySelectorAll('#rsSidebarToggle');
        sidebarToggles.forEach(function (el, index) {
            if (index > 0) el.remove();
        });

        const mobileToggles = document.querySelectorAll('#rsMobileToggle');
        mobileToggles.forEach(function (el, index) {
            if (index > 0) el.remove();
        });

        const overlays = document.querySelectorAll('#rsMenuOverlay');
        overlays.forEach(function (el, index) {
            if (index > 0) el.remove();
        });
    });
</script>

<style>
    /*
     * Dernière priorité : rendre le menu réduit vraiment lisible.
     */

    @media (min-width: 1101px) {
        body.rs-sidebar-collapsed .soc-sidebar {
            width: var(--rs-sidebar-collapsed) !important;
            min-width: var(--rs-sidebar-collapsed) !important;
            max-width: var(--rs-sidebar-collapsed) !important;
        }

        body.rs-sidebar-collapsed .soc-nav a {
            width: 56px !important;
            margin-left: auto;
            margin-right: auto;
        }

        body.rs-sidebar-collapsed .rs-nav-icon {
            margin: 0 auto;
        }

        body.rs-sidebar-collapsed .soc-nav {
            place-items: center;
        }

        body.rs-sidebar-collapsed .soc-nav-section {
            display: grid;
            place-items: center;
        }
    }
</style>
