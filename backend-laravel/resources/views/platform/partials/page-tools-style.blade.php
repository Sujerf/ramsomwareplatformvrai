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
    }

    .form-control:focus {
        border-color: color-mix(in srgb, var(--accent) 48%, transparent);
    }

    .inline-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .action-btn {
        min-height: 34px;
        padding: 0 11px;
        border-radius: 12px;
        border: 1px solid var(--border-soft);
        color: var(--text-main);
        background: color-mix(in srgb, var(--bg-panel-soft) 78%, transparent);
        cursor: pointer;
        font-size: 12px;
        font-weight: 850;
    }

    .action-btn.primary {
        background: var(--accent);
        color: var(--accent-contrast);
        border-color: color-mix(in srgb, var(--accent) 45%, transparent);
    }

    .action-btn.success {
        background: rgba(34, 197, 94, 0.14);
        color: #22c55e;
        border-color: rgba(34, 197, 94, 0.28);
    }

    .action-btn.warning {
        background: rgba(245, 158, 11, 0.14);
        color: #f59e0b;
        border-color: rgba(245, 158, 11, 0.28);
    }

    .action-btn.danger {
        background: rgba(239, 68, 68, 0.14);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.28);
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
