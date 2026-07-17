<?php

declare(strict_types=1);

?>
<style>
    .system-panel {
        padding: 28px;
    }

    /*
     * Nagłówek strony
     */
    .system-panel > .eyebrow {
        display: none;
    }

    .system-panel > h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
        color: #111827;
    }

    .system-panel > h1 + p {
        max-width: 780px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .system-panel code {
        padding: 3px 6px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #334155;
        font-family:
            Consolas,
            "Courier New",
            monospace;
        font-size: 12px;
    }

    /*
     * Komunikaty
     */
    .system-panel > .alert {
        margin-top: 18px;
        border-radius: 11px;
        font-size: 13px;
        line-height: 1.45;
    }

    /*
     * Lista kontroli środowiska
     */
    .system-status-list {
        margin-top: 22px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .system-status-list .status-row {
        min-height: 54px;
        padding: 12px 16px;
        display: grid;
        grid-template-columns:
            minmax(180px, 0.8fr)
            minmax(0, 1.2fr);
        align-items: center;
        gap: 18px;
        border: 0;
        border-bottom: 1px solid #edf0f2;
        border-radius: 0;
        background: #ffffff;
    }

    .system-status-list .status-row:last-child {
        border-bottom: 0;
    }

    .system-status-list .status-row:nth-child(even) {
        background: #fafbfc;
    }

    .system-status-list .status-row:hover {
        background: #f8fafc;
    }

    .system-status-list .status-row > span:first-child {
        min-width: 0;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 650;
        color: #6b7280;
    }

    .system-status-list .status-row > strong {
        min-width: 0;
        font-size: 13px;
        line-height: 1.35;
        font-weight: 700;
        text-align: right;
        color: #111827;
        overflow-wrap: anywhere;
    }

    /*
     * Wartość oraz oznaczenie wyniku diagnostyki
     */
    .system-status-list .status-row__right {
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .system-status-list .status-row__right strong {
        min-width: 0;
        font-size: 13px;
        line-height: 1.35;
        font-weight: 700;
        text-align: right;
        color: #111827;
        overflow-wrap: anywhere;
    }

    .system-status-list .status-badge {
        flex-shrink: 0;
        min-width: 52px;
        min-height: 25px;
        padding: 4px 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-size: 10px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: 0.03em;
    }

    .system-status-list .status-badge--success {
        background: #dcfce7;
        color: #166534;
    }

    .system-status-list .status-badge--warning {
        background: #fef3c7;
        color: #92400e;
    }

    .system-status-list .status-badge--danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .system-status-list .status-badge--neutral {
        background: #e0f2fe;
        color: #075985;
    }

    /*
     * Przyciski i formularze
     */
    .system-actions {
        margin-top: 20px;
        padding-top: 18px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        border-top: 1px solid #edf0f2;
    }

    .system-actions .button {
        min-height: 38px;
        padding: 8px 15px;
        border-radius: 9px;
        font-size: 12px;
        line-height: 1.2;
    }

    .system-form {
        margin: 0;
    }

    .system-form .system-actions {
        margin-bottom: 0;
    }

    /*
     * Wyraźne rozróżnienie operacji administracyjnych
     */
    .system-form button[type="submit"] {
        box-shadow:
            0 4px 10px rgba(21, 128, 61, 0.12);
    }

    .system-form button[disabled] {
        cursor: not-allowed;
        box-shadow: none;
        opacity: 0.55;
    }

    /*
     * Responsywność
     */
    @media (max-width: 800px) {
        .system-panel {
            padding: 22px;
        }

        .system-status-list .status-row {
            grid-template-columns:
                minmax(130px, 0.75fr)
                minmax(0, 1.25fr);
        }
    }

    @media (max-width: 600px) {
        .system-panel {
            padding: 16px;
        }

        .system-panel > h1 {
            font-size: 27px;
        }

        .system-status-list .status-row {
            padding: 12px;
            grid-template-columns: 1fr;
            gap: 6px;
        }

        .system-status-list .status-row > strong {
            text-align: left;
        }

        .system-status-list .status-row__right {
            align-items: flex-start;
            justify-content: space-between;
        }

        .system-status-list .status-row__right strong {
            text-align: right;
        }

        .system-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .system-actions .button,
        .system-actions form,
        .system-actions form .button {
            width: 100%;
            text-align: center;
        }
    }
</style>
