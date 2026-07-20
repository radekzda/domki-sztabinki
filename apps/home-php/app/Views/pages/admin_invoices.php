<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $invoices
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

$statusLabels = [
    'DRAFT' => 'Szkic',
    'ISSUED' => 'Wystawiona',
    'CANCELLED' => 'Anulowana',
];

$paymentLabels = [
    'UNPAID' => 'Nieopłacona',
    'PARTIALLY_PAID' => 'Częściowo opłacona',
    'PAID' => 'Opłacona',
];
?>

<style>
    .invoices-panel {
        padding: 28px;
    }

    .invoices-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .invoices-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .invoices-panel .page-header p {
        max-width: 720px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .invoices-panel .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .invoices-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    .invoices-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .invoices-table {
        width: 100%;
        min-width: 1050px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .invoices-table thead {
        background: #f8fafc;
    }

    .invoices-table th {
        padding: 13px 16px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
        line-height: 1.2;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        color: #6b7280;
    }

    .invoices-table td {
        padding: 16px;
        border-bottom: 1px solid #edf0f2;
        vertical-align: middle;
        font-size: 13px;
        line-height: 1.35;
        color: #374151;
    }

    .invoices-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .invoices-table tbody tr {
        transition: background 0.15s ease;
    }

    .invoices-table tbody tr:hover {
        background: #fafbfc;
    }

    .invoice-number {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #111827;
    }

    .invoice-meta {
        margin-top: 3px;
        font-size: 12px;
        line-height: 1.35;
        color: #9ca3af;
    }

    .invoice-buyer {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #111827;
    }

    .invoice-cabin {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #111827;
    }

    .invoice-amount {
        display: block;
        font-size: 14px;
        color: #111827;
    }

    .invoice-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        font-weight: 700;
        background: #f3f4f6;
        color: #4b5563;
    }

    .invoice-pill--success {
        background: #dcfce7;
        color: #166534;
    }

    .invoice-pill--danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .invoice-status-group {
        display: grid;
        gap: 7px;
        justify-items: start;
    }

    .invoice-payment {
        font-size: 12px;
        color: #6b7280;
    }

    .invoice-actions {
        display: grid;
        gap: 7px;
    }

    .invoice-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
    }

    @media (max-width: 1100px) {
        .invoices-panel {
            padding: 22px;
        }

        .invoices-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .invoices-panel .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 700px) {
        .invoices-panel {
            padding: 16px;
        }

        .invoices-panel .page-header h1 {
            font-size: 27px;
        }

        .invoices-panel .page-header__actions {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .invoices-panel .page-header__actions .button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                [
                    'active' =>
                        'invoices',
                ]
            ); ?>

            <div class="admin-content">
                <div class="panel invoices-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Faktury
                            </p>

                            <h1>
                                Faktury
                            </h1>

                            <p>
                                Lista wystawionych faktur powiązanych
                                z rezerwacjami i sprzedawcami.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/rezerwacje"
                            >
                                Rezerwacje
                            </a>

                            <a
                                class="button button--secondary"
                                href="/admin/sprzedawcy-faktur"
                            >
                                Sprzedawcy faktur
                            </a>
                        </div>
                    </div>

                    <?php if (
                        isset($successMessage)
                        && is_string($successMessage)
                        && $successMessage !== ''
                    ): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars(
                                $successMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (
                        isset($databaseMessage)
                        && is_string($databaseMessage)
                        && $databaseMessage !== ''
                    ): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars(
                                $databaseMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($invoices === []): ?>
                        <div class="empty-state">
                            <strong>
                                Brak faktur do wyświetlenia
                            </strong>

                            <p>
                                Faktury wystawione z poziomu
                                szczegółów rezerwacji pojawią się
                                w tym miejscu.
                            </p>
                        </div>
                    <?php else: ?>
                        <div
                            class="
                                table-wrapper
                                invoices-table-wrapper
                            "
                        >
                            <table
                                class="
                                    data-table
                                    invoices-table
                                "
                            >
                                <thead>
                                    <tr>
                                        <th style="width: 16%;">
                                            Numer
                                        </th>

                                        <th style="width: 12%;">
                                            Data
                                        </th>

                                        <th style="width: 19%;">
                                            Nabywca
                                        </th>

                                        <th style="width: 16%;">
                                            Rezerwacja
                                        </th>

                                        <th style="width: 15%;">
                                            Status
                                        </th>

                                        <th style="width: 12%;">
                                            Brutto
                                        </th>

                                        <th style="width: 10%;">
                                            Akcje
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach (
                                        $invoices
                                        as $invoice
                                    ): ?>
                                        <?php
                                        $status = strtoupper(
                                            (string) (
                                                $invoice['status']
                                                ?? ''
                                            )
                                        );

                                        $paymentStatus =
                                            strtoupper(
                                                (string) (
                                                    $invoice[
                                                        'payment_status'
                                                    ]
                                                    ?? ''
                                                )
                                            );

                                        $statusText =
                                            $statusLabels[
                                                $status
                                            ]
                                            ?? (
                                                $status !== ''
                                                    ? $status
                                                    : '—'
                                            );

                                        $paymentText =
                                            $paymentLabels[
                                                $paymentStatus
                                            ]
                                            ?? (
                                                $paymentStatus
                                                !== ''
                                                    ? $paymentStatus
                                                    : '—'
                                            );

                                        $currency =
                                            strtoupper(
                                                (string) (
                                                    $invoice[
                                                        'currency'
                                                    ]
                                                    ?? 'PLN'
                                                )
                                            );

                                        $reservationId =
                                            (int) (
                                                $invoice[
                                                    'reservation_id'
                                                ]
                                                ?? 0
                                            );
                                        ?>

                                        <tr>
                                            <td>
                                                <strong
                                                    class="invoice-number"
                                                >
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $invoice[
                                                                'invoice_number'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <div
                                                    class="invoice-meta"
                                                >
                                                    ID:
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $invoice[
                                                                'id'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </div>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        formatDateForDisplay(
                                                            (string) (
                                                                $invoice[
                                                                    'issue_date'
                                                                ]
                                                                ?? ''
                                                            )
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <div
                                                    class="invoice-meta"
                                                >
                                                    Sprzedaż:
                                                    <?= htmlspecialchars(
                                                        formatDateForDisplay(
                                                            (string) (
                                                                $invoice[
                                                                    'sale_date'
                                                                ]
                                                                ?? ''
                                                            )
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </div>
                                            </td>

                                            <td>
                                                <strong
                                                    class="invoice-buyer"
                                                >
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $invoice[
                                                                'buyer_name'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <?php if (
                                                    trim(
                                                        (string) (
                                                            $invoice[
                                                                'buyer_tax_id'
                                                            ]
                                                            ?? ''
                                                        )
                                                    ) !== ''
                                                ): ?>
                                                    <div
                                                        class="invoice-meta"
                                                    >
                                                        NIP / ID:
                                                        <?= htmlspecialchars(
                                                            (string) $invoice[
                                                                'buyer_tax_id'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <strong
                                                    class="invoice-cabin"
                                                >
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $invoice[
                                                                'cabin_name'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <?php if (
                                                    $reservationId > 0
                                                ): ?>
                                                    <div
                                                        class="invoice-meta"
                                                    >
                                                        Rezerwacja
                                                        #<?= htmlspecialchars(
                                                            (string) $reservationId,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div
                                                    class="
                                                        invoice-status-group
                                                    "
                                                >
                                                    <span
                                                        class="
                                                            invoice-pill
                                                            <?=
                                                                $status
                                                                === 'ISSUED'
                                                                    ? 'invoice-pill--success'
                                                                    : (
                                                                        $status
                                                                        === 'CANCELLED'
                                                                            ? 'invoice-pill--danger'
                                                                            : ''
                                                                    )
                                                            ?>
                                                        "
                                                    >
                                                        <?= htmlspecialchars(
                                                            $statusText,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>

                                                    <div
                                                        class="
                                                            invoice-payment
                                                        "
                                                    >
                                                        Płatność:
                                                        <strong>
                                                            <?= htmlspecialchars(
                                                                $paymentText,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </strong>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <strong
                                                    class="invoice-amount"
                                                >
                                                    <?= htmlspecialchars(
                                                        number_format(
                                                            (float) (
                                                                $invoice[
                                                                    'gross_total'
                                                                ]
                                                                ?? 0
                                                            ),
                                                            2,
                                                            ',',
                                                            ' '
                                                        )
                                                        . ' '
                                                        . $currency,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <div
                                                    class="invoice-meta"
                                                >
                                                    Netto:
                                                    <?= htmlspecialchars(
                                                        number_format(
                                                            (float) (
                                                                $invoice[
                                                                    'net_total'
                                                                ]
                                                                ?? 0
                                                            ),
                                                            2,
                                                            ',',
                                                            ' '
                                                        )
                                                        . ' '
                                                        . $currency,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div
                                                    class="invoice-actions"
                                                >
                                                    <a
                                                        class="
                                                            button
                                                            button--primary
                                                        "
                                                        href="/admin/faktury/pokaz?id=<?= urlencode(
                                                            (string) (
                                                                $invoice[
                                                                    'id'
                                                                ]
                                                                ?? 0
                                                            )
                                                        ) ?>"
                                                    >
                                                        Podgląd
                                                    </a>

                                                    <?php if (
                                                        $reservationId > 0
                                                    ): ?>
                                                        <a
                                                            class="
                                                                button
                                                                button--secondary
                                                            "
                                                            href="/admin/rezerwacje/pokaz?id=<?= urlencode(
                                                                (string) $reservationId
                                                            ) ?>"
                                                        >
                                                            Rezerwacja
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
