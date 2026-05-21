<style>
    .edit-inline-box {
        padding: 10px;
        border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
        background: color-mix(in srgb, var(--accent) 5%, transparent);
        border-radius: 16px;
        min-width: 280px;
    }

    .edit-inline-box .inline-actions {
        gap: 8px;
        align-items: center;
    }

    .edit-inline-box .form-control {
        background: var(--bg-card);
        border-color: color-mix(in srgb, var(--accent) 22%, transparent);
    }

    .soc-table tr:hover {
        background: color-mix(in srgb, var(--accent) 5%, transparent);
    }

    .soc-table td:last-child {
        min-width: 330px;
    }

    @media (max-width: 760px) {
        .edit-inline-box {
            min-width: 0;
            width: 100%;
        }

        .edit-inline-box .inline-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .edit-inline-box .form-control,
        .edit-inline-box .action-btn {
            width: 100%;
            max-width: 100%;
        }
    }
</style>
