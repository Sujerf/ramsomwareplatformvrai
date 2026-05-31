<style>
    .page-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .page-toolbar h2 {
        margin: 0;
        font-size: 24px;
        letter-spacing: -0.04em;
    }

    .page-toolbar p {
        margin: 5px 0 0;
        color: var(--text-muted);
        font-size: 13px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        align-items: end;
    }

    .form-group {
        display: grid;
        gap: 7px;
    }

    .form-group label {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 850;
    }

    .form-control {
        width: 100%;
        min-height: 42px;
        border: 1px solid var(--border-soft);
        border-radius: 14px;
        background: color-mix(in srgb, var(--bg-panel-soft) 76%, transparent);
        color: var(--text-main);
        padding: 0 12px;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .form-control:focus {
        border-color: color-mix(in srgb, var(--accent) 48%, transparent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 12%, transparent);
    }

    .inline-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 36px;
        padding: 0 15px;
        border-radius: 12px;
        border: 1px solid var(--border-soft);
        color: var(--text-main);
        background: color-mix(in srgb, var(--bg-panel-soft) 78%, transparent);
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: transform .15s ease, border-color .15s ease, background .15s ease, color .15s ease, box-shadow .15s ease;
        letter-spacing: -.01em;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--accent) 35%, transparent);
        color: var(--accent);
        box-shadow: 0 4px 12px color-mix(in srgb, var(--accent) 10%, transparent);
    }

    /* ── Variantes couleurs ────────────────────────────────────────────── */
    .action-btn.primary {
        background: var(--accent);
        color: #fff;
        border-color: color-mix(in srgb, var(--accent) 50%, transparent);
    }

    .action-btn.primary:hover {
        background: color-mix(in srgb, var(--accent) 85%, #000);
        color: #fff;
        border-color: var(--accent);
    }

    .action-btn.success {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
        border-color: rgba(34, 197, 94, 0.26);
    }

    .action-btn.success:hover {
        background: #22c55e;
        color: #fff;
        border-color: #22c55e;
    }

    .action-btn.warning {
        background: rgba(245, 158, 11, 0.10);
        color: #f59e0b;
        border-color: rgba(245, 158, 11, 0.24);
    }

    .action-btn.warning:hover {
        background: #f59e0b;
        color: #111;
        border-color: #f59e0b;
    }

    .action-btn.danger {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.26);
    }

    .action-btn.danger:hover {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
    }

    /* ── Taille large (décisions importantes) ─────────────────────────── */
    .action-btn.lg {
        min-height: 44px;
        padding: 0 22px;
        font-size: 14px;
        border-radius: 14px;
        letter-spacing: -.02em;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .detail-list {
        display: grid;
        gap: 10px;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 170px 1fr;
        gap: 12px;
        padding: 12px;
        border: 1px solid var(--border-soft);
        border-radius: 14px;
        background: color-mix(in srgb, var(--bg-panel-soft) 48%, transparent);
    }

    .detail-label {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 850;
    }

    .detail-value {
        color: var(--text-main);
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .pagination-wrap {
        margin-top: 18px;
        color: var(--text-muted);
    }

    .json-box {
        white-space: pre-wrap;
        overflow-x: auto;
        padding: 14px;
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        background: color-mix(in srgb, var(--bg-main) 70%, transparent);
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    @media (max-width: 980px) {
        .form-grid,
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .detail-row {
            grid-template-columns: 1fr;
        }
    }
</style>
