<style>
    /*
     |--------------------------------------------------------------------------
     | RansomShield UI stability fixes
     |--------------------------------------------------------------------------
     | Corrige :
     | - flèches pagination Laravel géantes
     | - tableaux trop larges
     | - formulaires cassés dans les tables
     | - scroll horizontal incontrôlé
     | - affichage mobile/tablette
     */

    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .soc-main,
    .soc-content,
    .soc-page,
    main {
        min-width: 0;
        max-width: 100%;
    }

    .soc-content {
        overflow-x: hidden;
    }

    .soc-card,
    .smart-card,
    .analysis-hero,
    .mobile-data-card {
        max-width: 100%;
    }

    /*
     |--------------------------------------------------------------------------
     | Pagination Laravel
     |--------------------------------------------------------------------------
     | Laravel/Tailwind injecte des SVG. Sans CSS Tailwind complet,
     | les flèches peuvent devenir énormes.
     */

    .pagination-wrap {
        width: 100%;
        margin-top: 18px;
        overflow-x: auto;
    }

    .pagination-wrap nav {
        width: 100%;
        max-width: 100%;
    }

    .pagination-wrap svg,
    nav[role="navigation"] svg,
    .pagination svg {
        width: 18px !important;
        height: 18px !important;
        max-width: 18px !important;
        max-height: 18px !important;
        display: inline-block !important;
        vertical-align: middle;
    }

    .pagination-wrap nav > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .pagination-wrap nav p {
        margin: 0;
        color: var(--text-muted);
        font-size: 13px;
    }

    .pagination-wrap nav a,
    .pagination-wrap nav span {
        border-radius: 12px !important;
        font-size: 13px !important;
        line-height: 1.2 !important;
    }

    /*
     |--------------------------------------------------------------------------
     | Tables
     |--------------------------------------------------------------------------
     */

    .table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        border-radius: 18px;
    }

    .soc-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        table-layout: auto;
    }

    .soc-table th,
    .soc-table td {
        vertical-align: top;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .soc-table th {
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .soc-table td {
        font-size: 13px;
        line-height: 1.45;
    }

    .soc-table .mono,
    .mono {
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    /*
     |--------------------------------------------------------------------------
     | Formulaires dans les tableaux
     |--------------------------------------------------------------------------
     */

    .soc-table form.inline-actions,
    .soc-table .inline-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-width: 220px;
    }

    .soc-table .form-control {
        min-height: 36px;
        width: auto;
        max-width: 180px;
        font-size: 12px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .soc-table input[type="number"].form-control {
        max-width: 100px;
    }

    .soc-table select.form-control {
        max-width: 190px;
    }

    .action-btn,
    .btn {
        white-space: nowrap;
    }

    .inline-actions {
        min-width: 0;
    }

    /*
     |--------------------------------------------------------------------------
     | Cartes statistiques
     |--------------------------------------------------------------------------
     */

    .smart-stats {
        align-items: stretch;
    }

    .smart-stat {
        min-width: 0;
    }

    .smart-stat-value {
        overflow-wrap: anywhere;
    }

    /*
     |--------------------------------------------------------------------------
     | Hero responsive
     |--------------------------------------------------------------------------
     */

    .analysis-hero-content {
        min-width: 0;
    }

    .analysis-hero h2 {
        overflow-wrap: anywhere;
    }

    /*
     |--------------------------------------------------------------------------
     | Responsive desktop/tablette/mobile
     |--------------------------------------------------------------------------
     */

    @media (max-width: 1280px) {
        .soc-table {
            min-width: 1080px;
        }

        .soc-table .form-control {
            max-width: 150px;
        }
    }

    @media (max-width: 980px) {
        .soc-layout,
        .soc-shell {
            grid-template-columns: 1fr !important;
        }

        .soc-sidebar {
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
        }

        .smart-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .grid-2,
        .grid-3,
        .grid-4,
        .detail-grid,
        .chart-grid {
            grid-template-columns: 1fr !important;
        }

        .analysis-hero-content {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 760px) {
        .desktop-table-prefer {
            display: none !important;
        }

        .host-card-list,
        .responsive-cards-table {
            display: grid !important;
            gap: 14px;
        }

        .smart-stats {
            grid-template-columns: 1fr !important;
        }

        .analysis-hero {
            padding: 20px !important;
            border-radius: 22px !important;
        }

        .analysis-hero h2 {
            font-size: 34px !important;
            line-height: 1.03 !important;
            letter-spacing: -0.05em !important;
        }

        .network-orbit {
            display: none !important;
        }

        .mobile-data-card {
            width: 100%;
        }

        .mobile-data-row {
            grid-template-columns: 1fr !important;
        }

        .inline-actions {
            width: 100%;
        }

        .inline-actions .action-btn,
        .inline-actions form,
        .inline-actions a {
            flex: 1 1 auto;
        }

        .action-btn {
            width: 100%;
            justify-content: center;
            text-align: center;
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Sécurité visuelle
     |--------------------------------------------------------------------------
     */

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    pre.json-box {
        max-width: 100%;
        overflow-x: auto;
    }
</style>
