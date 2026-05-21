<style>
    .config-hero {
        position: relative;
        overflow: hidden;
        padding: 28px;
        border-radius: 32px;
        border: 1px solid var(--border-soft);
        background:
            radial-gradient(circle at 14% 18%, color-mix(in srgb, var(--accent) 16%, transparent), transparent 28%),
            radial-gradient(circle at 86% 10%, color-mix(in srgb, var(--accent-2) 12%, transparent), transparent 32%),
            var(--bg-card);
        box-shadow: var(--shadow-soft);
    }

    .config-hero h2 {
        margin: 0;
        font-size: clamp(38px, 5vw, 68px);
        line-height: .95;
        letter-spacing: -.08em;
        font-weight: 950;
    }

    .config-hero p {
        margin-top: 14px;
        color: var(--text-muted);
        line-height: 1.75;
        max-width: 880px;
    }

    .config-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .config-card {
        position: relative;
        overflow: hidden;
        padding: 18px;
        border-radius: 26px;
        border: 1px solid var(--border-soft);
        background: var(--bg-card);
        box-shadow: var(--shadow-soft);
    }

    .config-card::after {
        content: "";
        position: absolute;
        right: -52px;
        top: -52px;
        width: 130px;
        height: 130px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 8%, transparent);
        pointer-events: none;
    }

    .config-card-head {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .config-title {
        margin: 0;
        font-size: 17px;
        font-weight: 950;
        letter-spacing: -.03em;
    }

    .config-subtitle {
        margin-top: 6px;
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .config-impact {
        position: relative;
        z-index: 1;
        padding: 12px;
        border-radius: 17px;
        background: color-mix(in srgb, var(--accent) 6%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 14%, transparent);
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.6;
        margin: 12px 0;
    }

    .config-form {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 10px;
        margin-top: 12px;
    }

    .config-form-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .config-field label {
        display: block;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: 6px;
    }

    .config-field input,
    .config-field select,
    .config-field textarea {
        width: 100%;
    }

    .config-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .config-actions .action-btn,
    .config-actions form {
        flex: 1 1 auto;
    }

    .score-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 11px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 9%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
        font-size: 12px;
        font-weight: 950;
    }

    .config-mini-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .config-mini {
        padding: 14px;
        border-radius: 22px;
        border: 1px solid var(--border-soft);
        background: var(--bg-card);
        box-shadow: var(--shadow-soft);
    }

    .config-mini small {
        display: block;
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .07em;
    }

    .config-mini strong {
        display: block;
        margin-top: 6px;
        font-size: 28px;
        letter-spacing: -.06em;
        font-weight: 950;
    }

    @media (max-width: 1100px) {
        .config-grid {
            grid-template-columns: 1fr;
        }

        .config-mini-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .config-hero {
            padding: 20px;
            border-radius: 24px;
        }

        .config-form-row,
        .config-mini-grid {
            grid-template-columns: 1fr;
        }

        .config-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .config-actions .action-btn,
        .config-actions form,
        .config-actions button {
            width: 100%;
        }
    }
</style>
