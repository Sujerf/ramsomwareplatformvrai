<style>
    .animated-page {
        animation: pageFadeUp .55s ease both;
    }

    @keyframes pageFadeUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .analysis-hero {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        padding: clamp(22px, 4vw, 34px);
        background:
            radial-gradient(circle at top right, color-mix(in srgb, var(--accent) 22%, transparent), transparent 34%),
            radial-gradient(circle at bottom left, color-mix(in srgb, var(--accent-2) 16%, transparent), transparent 30%),
            var(--bg-card);
        border: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
        box-shadow: var(--shadow-soft);
        margin-bottom: 18px;
    }

    .analysis-hero::after {
        content: "";
        position: absolute;
        right: -70px;
        top: -70px;
        width: 210px;
        height: 210px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 16%, transparent);
        animation: orbFloat 8s ease-in-out infinite alternate;
    }

    @keyframes orbFloat {
        from {
            transform: translate3d(0, 0, 0) scale(1);
        }
        to {
            transform: translate3d(-24px, 20px, 0) scale(1.08);
        }
    }

    .analysis-hero-content {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 24px;
        align-items: center;
    }

    .analysis-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 13%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
        color: var(--text-main);
        font-size: 12px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 16px;
    }

    .analysis-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--accent-2);
        box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent-2) 14%, transparent);
        animation: pulseDot 1.7s ease-in-out infinite;
    }

    @keyframes pulseDot {
        0%, 100% {
            opacity: .7;
            transform: scale(.9);
        }
        50% {
            opacity: 1;
            transform: scale(1.15);
        }
    }

    .analysis-hero h2 {
        margin: 0;
        font-size: clamp(30px, 5vw, 54px);
        letter-spacing: -0.07em;
        line-height: .96;
    }

    .analysis-hero p {
        margin: 16px 0 0;
        color: var(--text-muted);
        line-height: 1.75;
        max-width: 760px;
    }

    .network-orbit {
        position: relative;
        min-height: 250px;
        display: grid;
        place-items: center;
    }

    .orbit-core {
        width: 112px;
        height: 112px;
        border-radius: 34px;
        display: grid;
        place-items: center;
        background: var(--accent);
        color: var(--accent-contrast);
        font-weight: 950;
        font-size: 24px;
        box-shadow: 0 20px 70px color-mix(in srgb, var(--accent) 30%, transparent);
        z-index: 2;
    }

    .orbit-ring {
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        border: 1px dashed color-mix(in srgb, var(--accent) 35%, transparent);
        animation: rotateRing 18s linear infinite;
    }

    .orbit-ring:nth-child(2) {
        width: 170px;
        height: 170px;
        animation-duration: 12s;
        animation-direction: reverse;
    }

    @keyframes rotateRing {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .orbit-node {
        position: absolute;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: var(--accent-2);
        box-shadow: 0 0 0 7px color-mix(in srgb, var(--accent-2) 12%, transparent);
    }

    .orbit-node.n1 { top: 18px; right: 80px; }
    .orbit-node.n2 { bottom: 34px; left: 70px; background: var(--accent); }
    .orbit-node.n3 { top: 120px; left: 24px; background: #f59e0b; }

    .smart-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .smart-stat {
        position: relative;
        overflow: hidden;
        padding: 18px;
        border-radius: 24px;
        background: var(--bg-card);
        border: 1px solid var(--border-soft);
        box-shadow: var(--shadow-soft);
        animation: pageFadeUp .55s ease both;
    }

    .smart-stat:nth-child(2) { animation-delay: .05s; }
    .smart-stat:nth-child(3) { animation-delay: .1s; }
    .smart-stat:nth-child(4) { animation-delay: .15s; }

    .smart-stat::after {
        content: "";
        position: absolute;
        right: -44px;
        bottom: -48px;
        width: 120px;
        height: 120px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 11%, transparent);
    }

    .smart-stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--accent) 12%, transparent);
        color: var(--accent);
        font-size: 15px;
        margin-bottom: 10px;
    }

    .smart-stat-label {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .smart-stat-value {
        margin-top: 10px;
        font-size: clamp(26px, 4vw, 38px);
        font-weight: 950;
        letter-spacing: -.06em;
    }

    .smart-stat-hint {
        margin-top: 8px;
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .smart-card {
        border-radius: 26px;
        background: var(--bg-card);
        border: 1px solid var(--border-soft);
        box-shadow: var(--shadow-soft);
        padding: 20px;
        animation: pageFadeUp .55s ease both;
    }

    .smart-card-title {
        margin: 0;
        font-size: 17px;
        font-weight: 950;
        letter-spacing: -.03em;
    }

    .smart-card-subtitle {
        margin: 6px 0 0;
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.55;
    }

    .progress-stack {
        display: grid;
        gap: 14px;
        margin-top: 18px;
    }

    .progress-row {
        display: grid;
        gap: 7px;
    }

    .progress-meta {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        color: var(--text-muted);
        font-size: 13px;
    }

    .soft-progress {
        height: 11px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--text-muted) 14%, transparent);
        overflow: hidden;
    }

    .soft-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--accent), var(--accent-2));
        width: 0;
        animation: growBar .9s ease forwards;
    }

    @keyframes growBar {
        from { width: 0; }
    }

    .recommendation-box {
        border-radius: 22px;
        border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        background: color-mix(in srgb, var(--accent) 8%, transparent);
        padding: 18px;
        color: var(--text-muted);
        line-height: 1.7;
    }

    .recommendation-box strong {
        color: var(--text-main);
    }

    .responsive-cards-table {
        display: none;
    }

    .host-card-list {
        display: none;
    }

    .mini-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 9px;
        border-radius: 999px;
        border: 1px solid var(--border-soft);
        background: color-mix(in srgb, var(--bg-panel-soft) 65%, transparent);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 800;
    }

    .mini-chip-dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: var(--accent-2);
    }

    @media (max-width: 1150px) {
        .analysis-hero-content {
            grid-template-columns: 1fr;
        }

        .network-orbit {
            min-height: 210px;
        }

        .smart-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .smart-stats {
            grid-template-columns: 1fr;
        }

        .desktop-table-prefer {
            display: none;
        }

        .responsive-cards-table,
        .host-card-list {
            display: grid;
            gap: 14px;
        }

        .mobile-data-card {
            border-radius: 22px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            padding: 16px;
            box-shadow: var(--shadow-soft);
        }

        .mobile-data-card h3 {
            margin: 0 0 10px;
            font-size: 16px;
            letter-spacing: -.03em;
        }

        .mobile-data-row {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 10px;
            padding: 8px 0;
            border-top: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-size: 13px;
        }

        .mobile-data-row strong {
            color: var(--text-main);
            overflow-wrap: anywhere;
        }

        .inline-actions {
            gap: 6px;
        }

        .action-btn {
            min-height: 36px;
        }

        .analysis-hero {
            border-radius: 22px;
        }

        .analysis-hero h2 {
            font-size: 34px;
        }

        .network-orbit {
            display: none;
        }
    }
</style>
