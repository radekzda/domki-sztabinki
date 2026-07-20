<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, mixed> $invoice
 */

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatDate = static function (
    mixed $value
): string {
    $value = trim(
        (string) $value
    );

    if ($value === '') {
        return '—';
    }

    $date =
        DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            substr(
                $value,
                0,
                10
            )
        );

    if ($date === false) {
        return $value;
    }

    return $date->format(
        'd.m.Y'
    );
};

$formatMoney = static function (
    mixed $value,
    string $currency
): string {
    return number_format(
        (float) $value,
        2,
        ',',
        ' '
    )
        . ' '
        . $currency;
};

$formatQuantity = static function (
    mixed $value
): string {
    $formatted = number_format(
        (float) $value,
        3,
        '.',
        ''
    );

    $formatted = rtrim(
        $formatted,
        '0'
    );

    $formatted = rtrim(
        $formatted,
        '.'
    );

    return $formatted !== ''
        ? $formatted
        : '0';
};

$statusLabels = [
    'DRAFT' =>
        'Szkic',

    'ISSUED' =>
        'Wystawiona',

    'CANCELLED' =>
        'Anulowana',
];

$paymentLabels = [
    'UNPAID' =>
        'Nieopłacona',

    'PARTIALLY_PAID' =>
        'Częściowo opłacona',

    'PAID' =>
        'Opłacona',
];

$paymentMethodLabels = [
    'TRANSFER' =>
        'Przelew',

    'CASH' =>
        'Gotówka',

    'CARD' =>
        'Karta',

    'PLATFORM' =>
        'Platforma rezerwacyjna',
];

$status = strtoupper(
    (string) (
        $invoice['status']
        ?? ''
    )
);

$paymentStatus = strtoupper(
    (string) (
        $invoice['payment_status']
        ?? ''
    )
);

$statusText =
    $statusLabels[$status]
    ?? (
        $status !== ''
            ? $status
            : '—'
    );

$paymentText =
    $paymentLabels[$paymentStatus]
    ?? (
        $paymentStatus !== ''
            ? $paymentStatus
            : '—'
    );

$paymentMethod = strtoupper(
    trim(
        (string) (
            $invoice['payment_method']
            ?? ''
        )
    )
);

$paymentMethodText =
    $paymentMethodLabels[$paymentMethod]
    ?? (
        $paymentMethod !== ''
            ? $paymentMethod
            : '—'
    );

$currency = strtoupper(
    (string) (
        $invoice['currency']
        ?? 'PLN'
    )
);

$reservationId = (int) (
    $invoice['reservation_id']
    ?? 0
);

$canDeleteInvoice =
    trim(
        (string) (
            $invoice['ksef_number']
            ?? ''
        )
    ) === ''
    && trim(
        (string) (
            $invoice['ksef_sent_at']
            ?? ''
        )
    ) === '';

$items = is_array(
    $invoice['items']
    ?? null
)
    ? $invoice['items']
    : [];

$sellerAddressParts = [];

$sellerStreet = trim(
    (string) (
        $invoice['seller_street']
        ?? ''
    )
);

$sellerPostalCode = trim(
    (string) (
        $invoice['seller_postal_code']
        ?? ''
    )
);

$sellerCity = trim(
    (string) (
        $invoice['seller_city']
        ?? ''
    )
);

$sellerCountry = trim(
    (string) (
        $invoice['seller_country']
        ?? ''
    )
);

if ($sellerStreet !== '') {
    $sellerAddressParts[] =
        $sellerStreet;
}

$sellerCityLine = trim(
    $sellerPostalCode
    . ' '
    . $sellerCity
);

if ($sellerCityLine !== '') {
    $sellerAddressParts[] =
        $sellerCityLine;
}

if (
    $sellerCountry !== ''
    && !in_array(
        strtolower($sellerCountry),
        [
            'polska',
            'poland',
        ],
        true
    )
) {
    $sellerAddressParts[] =
        $sellerCountry;
}

$buyerAddressParts = [];

$buyerStreet = trim(
    (string) (
        $invoice['buyer_street']
        ?? ''
    )
);

$buyerPostalCode = trim(
    (string) (
        $invoice['buyer_postal_code']
        ?? ''
    )
);

$buyerCity = trim(
    (string) (
        $invoice['buyer_city']
        ?? ''
    )
);

$buyerCountry = trim(
    (string) (
        $invoice['buyer_country']
        ?? ''
    )
);

if ($buyerStreet !== '') {
    $buyerAddressParts[] =
        $buyerStreet;
}

$buyerCityLine = trim(
    $buyerPostalCode
    . ' '
    . $buyerCity
);

if ($buyerCityLine !== '') {
    $buyerAddressParts[] =
        $buyerCityLine;
}

if ($buyerCountry !== '') {
    $buyerAddressParts[] =
        $buyerCountry;
}
?>

<style>
    .invoice-show-panel {
        padding: 28px;
    }

    .invoice-show-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .invoice-show-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .invoice-show-panel .page-header p {
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .invoice-show-panel .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .invoice-show-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    .invoice-show-statuses {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
    }

    .invoice-show-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 5px 11px;
        border-radius: 999px;
        background: #f3f4f6;
        font-size: 12px;
        font-weight: 700;
        color: #4b5563;
    }

    .invoice-show-pill--success {
        background: #dcfce7;
        color: #166534;
    }

    .invoice-show-pill--danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .invoice-show-grid {
        display: grid;
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
        gap: 18px;
        margin-bottom: 22px;
    }

    .invoice-show-card {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
    }

    .invoice-show-card h2 {
        margin: 0 0 16px;
        font-size: 17px;
        color: #111827;
    }

    .invoice-show-detail {
        display: grid;
        grid-template-columns:
            minmax(120px, 160px)
            minmax(0, 1fr);
        gap: 8px 16px;
        padding: 7px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        line-height: 1.45;
    }

    .invoice-show-detail:last-child {
        border-bottom: 0;
    }

    .invoice-show-detail__label {
        color: #6b7280;
    }

    .invoice-show-detail__value {
        color: #111827;
        overflow-wrap: anywhere;
    }

    .invoice-items-wrapper {
        overflow-x: auto;
        margin-bottom: 22px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
    }

    .invoice-items-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .invoice-items-table thead {
        background: #f8fafc;
    }

    .invoice-items-table th {
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        color: #6b7280;
    }

    .invoice-items-table td {
        padding: 15px 14px;
        border-bottom: 1px solid #edf0f2;
        font-size: 13px;
        color: #374151;
    }

    .invoice-items-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .invoice-items-table .amount {
        text-align: right;
        white-space: nowrap;
    }

    .invoice-summary {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 22px;
    }

    .invoice-summary-card {
        width: min(
            100%,
            420px
        );
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f8fafc;
    }

    .invoice-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 7px 0;
        font-size: 14px;
        color: #4b5563;
    }

    .invoice-summary-row strong {
        color: #111827;
    }

    .invoice-summary-row--total {
        margin-top: 8px;
        padding-top: 15px;
        border-top: 1px solid #d1d5db;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }

    .invoice-notes {
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
    }

    .invoice-notes h2 {
        margin: 0 0 12px;
        font-size: 17px;
        color: #111827;
    }

    .invoice-notes p {
        margin: 0;
        white-space: pre-wrap;
        font-size: 13px;
        line-height: 1.6;
        color: #4b5563;
    }

    @media (max-width: 900px) {
        .invoice-show-grid {
            grid-template-columns: 1fr;
        }

        .invoice-show-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .invoice-show-panel .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 700px) {
        .invoice-show-panel {
            padding: 16px;
        }

        .invoice-show-panel .page-header h1 {
            font-size: 27px;
        }

        .invoice-show-panel .page-header__actions {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .invoice-show-panel .page-header__actions .button {
            width: 100%;
            text-align: center;
        }

        .invoice-show-detail {
            grid-template-columns: 1fr;
            gap: 3px;
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
                <div class="panel invoice-show-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Faktura
                            </p>

                            <h1>
                                <?= $escape(
                                    $invoice[
                                        'invoice_number'
                                    ]
                                    ?? 'Faktura'
                                ) ?>
                            </h1>

                            <p>
                                Wystawiono:
                                <?= $escape(
                                    $formatDate(
                                        $invoice[
                                            'issue_date'
                                        ]
                                        ?? ''
                                    )
                                ) ?>
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/faktury/drukuj?id=<?= urlencode(
                                    (string) (
                                        $invoice[
                                            'id'
                                        ]
                                        ?? 0
                                    )
                                ) ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                Drukuj / PDF
                            </a>

                            <?php if (
                                $canDeleteInvoice
                            ): ?>
                                <form
                                    method="post"
                                    action="/admin/faktury/usun"
                                    style="margin: 0;"
                                    onsubmit="return confirm('Czy na pewno usunąć tę fakturę? Tej operacji nie można cofnąć.');"
                                >
                                    <?= csrfField() ?>

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) (
                                            $invoice['id']
                                            ?? 0
                                        ) ?>"
                                    >

                                    <button
                                        class="button button--secondary"
                                        type="submit"
                                        style="border-color: #fecaca; color: #b91c1c;"
                                    >
                                        Usuń fakturę
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a
                                class="button button--secondary"
                                href="/admin/faktury"
                            >
                                Wróć do faktur
                            </a>

                            <?php if (
                                $reservationId > 0
                            ): ?>
                                <a
                                    class="button button--secondary"
                                    href="/admin/rezerwacje/pokaz?id=<?= urlencode(
                                        (string) $reservationId
                                    ) ?>"
                                >
                                    Rezerwacja
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="invoice-show-statuses">
                        <span
                            class="
                                invoice-show-pill
                                <?=
                                    $status === 'ISSUED'
                                        ? 'invoice-show-pill--success'
                                        : (
                                            $status === 'CANCELLED'
                                                ? 'invoice-show-pill--danger'
                                                : ''
                                        )
                                ?>
                            "
                        >
                            <?= $escape(
                                $statusText
                            ) ?>
                        </span>

                        <span
                            class="
                                invoice-show-pill
                                <?=
                                    $paymentStatus === 'PAID'
                                        ? 'invoice-show-pill--success'
                                        : ''
                                ?>
                            "
                        >
                            Płatność:
                            <?= $escape(
                                $paymentText
                            ) ?>
                        </span>
                    </div>

                    <div class="invoice-show-grid">
                        <div class="invoice-show-card">
                            <h2>
                                Dane faktury
                            </h2>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Numer
                                </span>

                                <strong
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'invoice_number'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Data i miejsce wystawienia
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $formatDate(
                                            $invoice[
                                                'issue_date'
                                            ]
                                            ?? ''
                                        )
                                        . (
                                            trim(
                                                (string) (
                                                    $invoice[
                                                        'seller_city'
                                                    ]
                                                    ?? ''
                                                )
                                            ) !== ''
                                                ? ', '
                                                    . trim(
                                                        (string) $invoice[
                                                            'seller_city'
                                                        ]
                                                    )
                                                : ''
                                        )
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Data wykonania usługi
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $formatDate(
                                            $invoice[
                                                'sale_date'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Termin płatności
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $formatDate(
                                            $invoice[
                                                'due_date'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Waluta
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $currency
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Sposób płatności
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $paymentMethodText
                                    ) ?>
                                </span>
                            </div>
                        </div>

                        <div class="invoice-show-card">
                            <h2>
                                Rezerwacja
                            </h2>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Rezerwacja
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $reservationId > 0
                                        ? '#'
                                            . $escape(
                                                $reservationId
                                            )
                                        : '—'
                                    ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Domek
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'cabin_name'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Gość
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'guest_name'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </span>
                            </div>
                        </div>

                        <div class="invoice-show-card">
                            <h2>
                                Sprzedawca
                            </h2>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Nazwa
                                </span>

                                <strong
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'seller_name'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    NIP / ID
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'seller_tax_id'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Adres
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?php if (
                                        $sellerAddressParts
                                        === []
                                    ): ?>
                                        —
                                    <?php else: ?>
                                        <?= $escape(
                                            implode(
                                                ', ',
                                                $sellerAddressParts
                                            )
                                        ) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    E-mail
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'seller_email'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Telefon
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'seller_phone'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </span>
                            </div>
                        </div>

                        <div class="invoice-show-card">
                            <h2>
                                Nabywca
                            </h2>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Nazwa
                                </span>

                                <strong
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        $invoice[
                                            'buyer_name'
                                        ]
                                        ?? '—'
                                    ) ?>
                                </strong>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    NIP / ID
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        trim(
                                            (string) (
                                                $invoice[
                                                    'buyer_tax_id'
                                                ]
                                                ?? ''
                                            )
                                        ) !== ''
                                            ? $invoice[
                                                'buyer_tax_id'
                                            ]
                                            : '—'
                                    ) ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    Adres
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?php if (
                                        $buyerAddressParts
                                        === []
                                    ): ?>
                                        —
                                    <?php else: ?>
                                        <?= $escape(
                                            implode(
                                                ', ',
                                                $buyerAddressParts
                                            )
                                        ) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="invoice-show-detail">
                                <span
                                    class="
                                        invoice-show-detail__label
                                    "
                                >
                                    E-mail
                                </span>

                                <span
                                    class="
                                        invoice-show-detail__value
                                    "
                                >
                                    <?= $escape(
                                        trim(
                                            (string) (
                                                $invoice[
                                                    'buyer_email'
                                                ]
                                                ?? ''
                                            )
                                        ) !== ''
                                            ? $invoice[
                                                'buyer_email'
                                            ]
                                            : '—'
                                    ) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-items-wrapper">
                        <table class="invoice-items-table">
                            <thead>
                                <tr>
                                    <th>
                                        Nazwa
                                    </th>

                                    <th>
                                        Ilość
                                    </th>

                                    <th>
                                        JM
                                    </th>

                                    <th class="amount">
                                        Cena netto
                                    </th>

                                    <th>
                                        VAT
                                    </th>

                                    <th class="amount">
                                        Netto
                                    </th>

                                    <th class="amount">
                                        VAT
                                    </th>

                                    <th class="amount">
                                        Brutto
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach (
                                    $items
                                    as $item
                                ): ?>
                                    <?php
                                    $vatRateCode = strtoupper(
                                        (string) (
                                            $item[
                                                'vat_rate_code'
                                            ]
                                            ?? ''
                                        )
                                    );

                                    $vatRateText =
                                        ctype_digit(
                                            $vatRateCode
                                        )
                                            ? $vatRateCode
                                                . '%'
                                            : $vatRateCode;
                                    ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?= $escape(
                                                    $item[
                                                        'name'
                                                    ]
                                                    ?? '—'
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= $escape(
                                                $formatQuantity(
                                                    $item[
                                                        'quantity'
                                                    ]
                                                    ?? 0
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $escape(
                                                $item[
                                                    'unit'
                                                ]
                                                ?? '—'
                                            ) ?>
                                        </td>

                                        <td class="amount">
                                            <?= $escape(
                                                $formatMoney(
                                                    $item[
                                                        'unit_net'
                                                    ]
                                                    ?? 0,
                                                    $currency
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $escape(
                                                $vatRateText
                                            ) ?>
                                        </td>

                                        <td class="amount">
                                            <?= $escape(
                                                $formatMoney(
                                                    $item[
                                                        'net_amount'
                                                    ]
                                                    ?? 0,
                                                    $currency
                                                )
                                            ) ?>
                                        </td>

                                        <td class="amount">
                                            <?= $escape(
                                                $formatMoney(
                                                    $item[
                                                        'vat_amount'
                                                    ]
                                                    ?? 0,
                                                    $currency
                                                )
                                            ) ?>
                                        </td>

                                        <td class="amount">
                                            <strong>
                                                <?= $escape(
                                                    $formatMoney(
                                                        $item[
                                                            'gross_amount'
                                                        ]
                                                        ?? 0,
                                                        $currency
                                                    )
                                                ) ?>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="invoice-summary">
                        <div class="invoice-summary-card">
                            <div class="invoice-summary-row">
                                <span>
                                    Netto
                                </span>

                                <strong>
                                    <?= $escape(
                                        $formatMoney(
                                            $invoice[
                                                'net_total'
                                            ]
                                            ?? 0,
                                            $currency
                                        )
                                    ) ?>
                                </strong>
                            </div>

                            <div class="invoice-summary-row">
                                <span>
                                    VAT
                                </span>

                                <strong>
                                    <?= $escape(
                                        $formatMoney(
                                            $invoice[
                                                'vat_total'
                                            ]
                                            ?? 0,
                                            $currency
                                        )
                                    ) ?>
                                </strong>
                            </div>

                            <div
                                class="
                                    invoice-summary-row
                                    invoice-summary-row--total
                                "
                            >
                                <span>
                                    Brutto
                                </span>

                                <strong>
                                    <?= $escape(
                                        $formatMoney(
                                            $invoice[
                                                'gross_total'
                                            ]
                                            ?? 0,
                                            $currency
                                        )
                                    ) ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <?php if (
                        trim(
                            (string) (
                                $invoice['notes']
                                ?? ''
                            )
                        ) !== ''
                    ): ?>
                        <div class="invoice-notes">
                            <h2>
                                Informacje dodatkowe
                            </h2>

                            <p><?= $escape(
                                $invoice[
                                    'notes'
                                ]
                            ) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
