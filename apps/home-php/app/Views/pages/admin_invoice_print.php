<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $invoice
 */

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatDate = static function (mixed $value): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        substr($value, 0, 10)
    );

    return $date === false
        ? $value
        : $date->format('d.m.Y');
};

$moneyValue = static function (mixed $value): float {
    return round(
        (float) str_replace(
            ',',
            '.',
            (string) $value
        ),
        2
    );
};

$formatMoney = static function (mixed $value): string {
    return number_format(
        (float) $value,
        2,
        ',',
        ' '
    ) . ' zł';
};

$formatQuantity = static function (mixed $value): string {
    $formatted = number_format(
        (float) $value,
        3,
        '.',
        ''
    );

    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');

    return $formatted !== ''
        ? $formatted
        : '0';
};

$pluralForm = static function (
    int $number,
    string $one,
    string $few,
    string $many
): string {
    $number = abs($number);
    $lastTwo = $number % 100;
    $last = $number % 10;

    if ($number === 1) {
        return $one;
    }

    if (
        $last >= 2
        && $last <= 4
        && !($lastTwo >= 12 && $lastTwo <= 14)
    ) {
        return $few;
    }

    return $many;
};

$numberToWords = static function (int $number) use ($pluralForm): string {
    if ($number === 0) {
        return 'zero';
    }

    $ones = [
        0 => '',
        1 => 'jeden',
        2 => 'dwa',
        3 => 'trzy',
        4 => 'cztery',
        5 => 'pięć',
        6 => 'sześć',
        7 => 'siedem',
        8 => 'osiem',
        9 => 'dziewięć',
    ];

    $teens = [
        10 => 'dziesięć',
        11 => 'jedenaście',
        12 => 'dwanaście',
        13 => 'trzynaście',
        14 => 'czternaście',
        15 => 'piętnaście',
        16 => 'szesnaście',
        17 => 'siedemnaście',
        18 => 'osiemnaście',
        19 => 'dziewiętnaście',
    ];

    $tens = [
        0 => '',
        1 => '',
        2 => 'dwadzieścia',
        3 => 'trzydzieści',
        4 => 'czterdzieści',
        5 => 'pięćdziesiąt',
        6 => 'sześćdziesiąt',
        7 => 'siedemdziesiąt',
        8 => 'osiemdziesiąt',
        9 => 'dziewięćdziesiąt',
    ];

    $hundreds = [
        0 => '',
        1 => 'sto',
        2 => 'dwieście',
        3 => 'trzysta',
        4 => 'czterysta',
        5 => 'pięćset',
        6 => 'sześćset',
        7 => 'siedemset',
        8 => 'osiemset',
        9 => 'dziewięćset',
    ];

    $groups = [
        0 => ['', '', ''],
        1 => ['tysiąc', 'tysiące', 'tysięcy'],
        2 => ['milion', 'miliony', 'milionów'],
        3 => ['miliard', 'miliardy', 'miliardów'],
    ];

    $parts = [];
    $groupIndex = 0;

    while ($number > 0) {
        $groupValue = $number % 1000;

        if ($groupValue > 0) {
            $groupParts = [];
            $hundredsDigit = intdiv($groupValue, 100);
            $lastTwo = $groupValue % 100;

            if ($hundredsDigit > 0) {
                $groupParts[] = $hundreds[$hundredsDigit];
            }

            if ($lastTwo >= 10 && $lastTwo <= 19) {
                $groupParts[] = $teens[$lastTwo];
            } else {
                $tensDigit = intdiv($lastTwo, 10);
                $onesDigit = $lastTwo % 10;

                if ($tensDigit > 0) {
                    $groupParts[] = $tens[$tensDigit];
                }

                if ($onesDigit > 0) {
                    if (!(
                        $groupIndex === 1
                        && $groupValue === 1
                    )) {
                        $groupParts[] = $ones[$onesDigit];
                    }
                }
            }

            if ($groupIndex > 0) {
                $groupParts[] = $pluralForm(
                    $groupValue,
                    $groups[$groupIndex][0],
                    $groups[$groupIndex][1],
                    $groups[$groupIndex][2]
                );
            }

            array_unshift(
                $parts,
                implode(' ', $groupParts)
            );
        }

        $number = intdiv($number, 1000);
        $groupIndex++;
    }

    return trim(implode(' ', $parts));
};

$amountInWords = static function (float $amount) use (
    $numberToWords,
    $pluralForm
): string {
    $amount = max(0, round($amount, 2));
    $whole = (int) floor($amount);
    $grosze = (int) round(($amount - $whole) * 100);

    if ($grosze === 100) {
        $whole++;
        $grosze = 0;
    }

    $zlotyWord = $pluralForm(
        $whole,
        'złoty',
        'złote',
        'złotych'
    );

    return ucfirst($numberToWords($whole))
        . ' '
        . $zlotyWord
        . ' '
        . str_pad((string) $grosze, 2, '0', STR_PAD_LEFT)
        . '/100';
};

$paymentMethodLabels = [
    'TRANSFER' => 'Przelew',
    'CASH' => 'Gotówka',
    'CARD' => 'Karta',
    'PLATFORM' => 'Platforma rezerwacyjna',
];

$paymentMethod = strtoupper(
    trim(
        (string) (
            $invoice['payment_method']
            ?? ''
        )
    )
);

$paymentMethodText = $paymentMethodLabels[$paymentMethod]
    ?? ($paymentMethod !== '' ? $paymentMethod : '—');

$items = is_array(
    $invoice['items']
    ?? null
)
    ? $invoice['items']
    : [];

$invoiceId = (int) (
    $invoice['id']
    ?? 0
);

$invoiceNumber = (string) (
    $invoice['invoice_number']
    ?? 'Faktura'
);

$issueDate = (string) (
    $invoice['issue_date']
    ?? ''
);

$saleDate = (string) (
    $invoice['sale_date']
    ?? ''
);

$dueDate = (string) (
    $invoice['due_date']
    ?? ''
);

$sellerCity = trim(
    (string) (
        $invoice['seller_city']
        ?? ''
    )
);

$issueDateAndPlace = $formatDate($issueDate);

if ($sellerCity !== '') {
    $issueDateAndPlace .= ', ' . $sellerCity;
}

$sellerPostalCity = trim(
    (string) (
        $invoice['seller_postal_code']
        ?? ''
    )
    . ' '
    . (string) (
        $invoice['seller_city']
        ?? ''
    )
);

$buyerPostalCity = trim(
    (string) (
        $invoice['buyer_postal_code']
        ?? ''
    )
    . ' '
    . (string) (
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

$showBuyerCountry = $buyerCountry !== ''
    && strtolower($buyerCountry) !== 'polska'
    && strtolower($buyerCountry) !== 'poland';

$bankAccountNumber = trim(
    (string) (
        $invoice['seller_bank_account_number']
        ?? ''
    )
);

$ownerName = trim(
    (string) (
        $invoice['seller_bank_account_holder']
        ?? ''
    )
);

if ($ownerName === '') {
    $ownerName = trim(
        (string) (
            $invoice['seller_name']
            ?? ''
        )
    );
}

$notes = trim(
    (string) (
        $invoice['notes']
        ?? ''
    )
);

$grossTotal = $moneyValue(
    $invoice['gross_total']
    ?? 0
);

$paidAmount = min(
    $grossTotal,
    max(
        0,
        $moneyValue(
            $invoice['paid_amount']
            ?? 0
        )
    )
);

$remainingAmount = max(
    0,
    round(
        $grossTotal - $paidAmount,
        2
    )
);

$paymentDays = null;

if (
    $paymentMethod === 'TRANSFER'
    && $issueDate !== ''
    && $dueDate !== ''
) {
    $issueDateObject = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        substr($issueDate, 0, 10)
    );

    $dueDateObject = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        substr($dueDate, 0, 10)
    );

    if (
        $issueDateObject !== false
        && $dueDateObject !== false
    ) {
        $paymentDays = max(
            0,
            (int) $issueDateObject
                ->diff($dueDateObject)
                ->format('%r%a')
        );
    }
}

$paymentDaysLabel = '';

if ($paymentDays !== null) {
    $paymentDaysLabel = $paymentDays === 1
        ? '1 dzień'
        : $paymentDays . ' dni';
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= $escape($invoiceNumber) ?></title>

    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: #111827;
        }

        .print-toolbar a,
        .print-toolbar button {
            min-height: 38px;
            padding: 8px 16px;
            border: 0;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .print-toolbar button {
            background: #2563eb;
            color: #ffffff;
        }

        .invoice-document {
            width: min(100%, 210mm);
            min-height: 297mm;
            margin: 20px auto;
            padding: 14mm;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #111827;
        }

        .invoice-title {
            margin: 0 0 5px;
            font-size: 27px;
            line-height: 1.1;
        }

        .invoice-number {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }

        .invoice-dates {
            min-width: 285px;
        }

        .invoice-date-row {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            gap: 10px;
            padding: 3px 0;
        }

        .invoice-date-row span:first-child {
            color: #6b7280;
        }

        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 18px;
        }

        .party {
            padding: 14px;
            border: 1px solid #d1d5db;
        }

        .party-label {
            margin: 0 0 9px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .party-name {
            margin: 0 0 7px;
            font-size: 14px;
            font-weight: 700;
        }

        .party-line {
            margin: 2px 0;
        }

        .payment-terms {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 12px;
        }

        .payment-terms__left,
        .payment-terms__right {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
        }

        .payment-terms__right {
            text-align: right;
        }

        .items-wrapper {
            overflow-x: auto;
        }

        .items {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .items th {
            padding: 8px 5px;
            border-top: 1px solid #111827;
            border-bottom: 1px solid #111827;
            background: #f9fafb;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
            vertical-align: bottom;
        }

        .items td {
            padding: 9px 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .items .numeric {
            text-align: right;
            white-space: nowrap;
        }

        .items .center {
            text-align: center;
            white-space: nowrap;
        }

        .payment-summary {
            margin-top: 20px;
            padding: 14px 0;
            border-top: 1px solid #111827;
        }

        .payment-summary-row {
            display: grid;
            grid-template-columns: 175px minmax(0, 1fr);
            gap: 12px;
            padding: 3px 0;
        }

        .payment-summary-row strong {
            font-size: 13px;
        }

        .payment-summary-row--total {
            font-size: 15px;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 70px;
            margin-top: 55px;
        }

        .signature {
            text-align: center;
        }

        .signature-owner {
            min-height: 18px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .signature-line {
            border-top: 1px dotted #111827;
        }

        .signature-label {
            margin-top: 5px;
            font-size: 9px;
            color: #6b7280;
        }

        .notes {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
        }

        .notes strong {
            display: inline-block;
            margin-right: 5px;
        }

        @media print {
            html,
            body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #ffffff;
                font-size: 9.5px;
            }

            .print-toolbar {
                display: none !important;
            }

            .invoice-document {
                width: 100%;
                max-width: none;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .invoice-header {
                gap: 10mm;
            }

            .invoice-dates {
                min-width: 70mm;
            }

            .parties,
            .payment-terms {
                gap: 6mm;
            }

            .party {
                padding: 3mm;
            }

            .items-wrapper {
                width: 100%;
                overflow: visible;
            }

            .items {
                width: 100%;
                min-width: 0 !important;
                table-layout: fixed;
            }

            .items th,
            .items td {
                padding: 1.6mm 1mm;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .items th {
                font-size: 7px;
                line-height: 1.15;
            }

            .items td {
                font-size: 8px;
                line-height: 1.2;
            }

            .items th:nth-child(1),
            .items td:nth-child(1) {
                width: 4%;
            }

            .items th:nth-child(2),
            .items td:nth-child(2) {
                width: 33%;
            }

            .items th:nth-child(3),
            .items td:nth-child(3) {
                width: 6%;
            }

            .items th:nth-child(4),
            .items td:nth-child(4) {
                width: 6%;
            }

            .items th:nth-child(5),
            .items td:nth-child(5) {
                width: 11%;
            }

            .items th:nth-child(6),
            .items td:nth-child(6) {
                width: 11%;
            }

            .items th:nth-child(7),
            .items td:nth-child(7) {
                width: 6%;
            }

            .items th:nth-child(8),
            .items td:nth-child(8) {
                width: 11%;
            }

            .items th:nth-child(9),
            .items td:nth-child(9) {
                width: 12%;
            }

            .payment-summary {
                margin-top: 5mm;
            }

            .signatures {
                gap: 18mm;
                margin-top: 14mm;
            }

            .notes {
                margin-top: 7mm;
            }

            .party,
            .payment-terms,
            .payment-summary,
            .signatures,
            .items tr {
                break-inside: avoid;
            }
        }

        @media screen and (max-width: 760px) {
            .invoice-document {
                margin: 0;
                padding: 20px;
                min-height: 0;
            }

            .invoice-header,
            .parties,
            .payment-terms,
            .signatures {
                grid-template-columns: 1fr;
                display: grid;
            }

            .invoice-dates {
                min-width: 0;
            }

            .payment-terms__right {
                text-align: left;
            }

            .items {
                min-width: 920px;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <a
            href="/admin/faktury/pokaz?id=<?= urlencode(
                (string) $invoiceId
            ) ?>"
        >
            Wróć do faktury
        </a>

        <button
            type="button"
            onclick="window.print()"
        >
            Drukuj / zapisz PDF
        </button>
    </div>

    <main class="invoice-document">
        <header class="invoice-header">
            <div>
                <h1 class="invoice-title">
                    FAKTURA
                </h1>

                <p class="invoice-number">
                    <?= $escape($invoiceNumber) ?>
                </p>
            </div>

            <div class="invoice-dates">
                <div class="invoice-date-row">
                    <span>Data i miejsce wystawienia:</span>
                    <strong><?= $escape(
                        $issueDateAndPlace
                    ) ?></strong>
                </div>

                <div class="invoice-date-row">
                    <span>Data wykonania usługi:</span>
                    <strong><?= $escape(
                        $formatDate($saleDate)
                    ) ?></strong>
                </div>
            </div>
        </header>

        <section class="parties">
            <div class="party">
                <p class="party-label">Sprzedawca</p>

                <p class="party-name">
                    <?= $escape(
                        $invoice['seller_name']
                        ?? '—'
                    ) ?>
                </p>

                <?php if (trim((string) (
                    $invoice['seller_street']
                    ?? ''
                )) !== ''): ?>
                    <p class="party-line">
                        <?= $escape(
                            $invoice['seller_street']
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($sellerPostalCity !== ''): ?>
                    <p class="party-line">
                        <?= $escape($sellerPostalCity) ?>
                    </p>
                <?php endif; ?>

                <?php
                $sellerCountryForPrint = trim(
                    (string) (
                        $invoice['seller_country']
                        ?? ''
                    )
                );

                $showSellerCountry =
                    $sellerCountryForPrint !== ''
                    && !in_array(
                        strtolower(
                            $sellerCountryForPrint
                        ),
                        [
                            'polska',
                            'poland',
                        ],
                        true
                    );
                ?>

                <?php if ($showSellerCountry): ?>
                    <p class="party-line">
                        <?= $escape(
                            $sellerCountryForPrint
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if (trim((string) (
                    $invoice['seller_tax_id']
                    ?? ''
                )) !== ''): ?>
                    <p class="party-line">
                        NIP:
                        <?= $escape(
                            $invoice['seller_tax_id']
                        ) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="party">
                <p class="party-label">Nabywca</p>

                <p class="party-name">
                    <?= $escape(
                        $invoice['buyer_name']
                        ?? '—'
                    ) ?>
                </p>

                <?php if (trim((string) (
                    $invoice['buyer_street']
                    ?? ''
                )) !== ''): ?>
                    <p class="party-line">
                        <?= $escape(
                            $invoice['buyer_street']
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($buyerPostalCity !== ''): ?>
                    <p class="party-line">
                        <?= $escape($buyerPostalCity) ?>
                    </p>
                <?php endif; ?>

                <?php if ($showBuyerCountry): ?>
                    <p class="party-line">
                        <?= $escape($buyerCountry) ?>
                    </p>
                <?php endif; ?>

                <?php if (trim((string) (
                    $invoice['buyer_tax_id']
                    ?? ''
                )) !== ''): ?>
                    <p class="party-line">
                        NIP / ID:
                        <?= $escape(
                            $invoice['buyer_tax_id']
                        ) ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="payment-terms">
            <div class="payment-terms__left">
                <strong>
                    <?= $escape($paymentMethodText) ?>
                    <?php if (
                        $paymentMethod === 'TRANSFER'
                        && $paymentDaysLabel !== ''
                    ): ?>
                        – <?= $escape($paymentDaysLabel) ?>
                    <?php endif; ?>
                </strong>

                <?php if (
                    $paymentMethod === 'TRANSFER'
                    && $bankAccountNumber !== ''
                ): ?>
                    <div>
                        Nr konta:
                        <?= $escape($bankAccountNumber) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="payment-terms__right">
                Termin płatności:
                <strong><?= $escape(
                    $formatDate($dueDate)
                ) ?></strong>
            </div>
        </section>

        <div class="items-wrapper">
            <table class="items">
                <thead>
                    <tr>
                        <th class="center">LP</th>
                        <th>Nazwa towaru lub usługi</th>
                        <th class="center">JM</th>
                        <th class="center">Ilość</th>
                        <th class="numeric">Cena brutto</th>
                        <th class="numeric">Wartość netto</th>
                        <th class="center">VAT</th>
                        <th class="numeric">Wartość VAT</th>
                        <th class="numeric">Wartość brutto</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $items
                        as $index => $item
                    ): ?>
                        <?php
                        $quantity = max(
                            0.001,
                            (float) (
                                $item['quantity']
                                ?? 1
                            )
                        );

                        $grossAmount = $moneyValue(
                            $item['gross_amount']
                            ?? 0
                        );

                        $unitGross = round(
                            $grossAmount / $quantity,
                            2
                        );

                        $vatRateCode = strtoupper(
                            (string) (
                                $item['vat_rate_code']
                                ?? ''
                            )
                        );

                        $vatRateText = ctype_digit(
                            $vatRateCode
                        )
                            ? $vatRateCode . '%'
                            : $vatRateCode;
                        ?>

                        <tr>
                            <td class="center">
                                <?= $escape($index + 1) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $item['name']
                                    ?? '—'
                                ) ?>
                            </td>

                            <td class="center">
                                <?= $escape(
                                    $item['unit']
                                    ?? '—'
                                ) ?>
                            </td>

                            <td class="center">
                                <?= $escape(
                                    $formatQuantity(
                                        $item['quantity']
                                        ?? 0
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney($unitGross)
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney(
                                        $item['net_amount']
                                        ?? 0
                                    )
                                ) ?>
                            </td>

                            <td class="center">
                                <?= $escape($vatRateText) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney(
                                        $item['vat_amount']
                                        ?? 0
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <strong>
                                    <?= $escape(
                                        $formatMoney(
                                            $item['gross_amount']
                                            ?? 0
                                        )
                                    ) ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <section class="payment-summary">
            <div
                class="payment-summary-row payment-summary-row--total"
            >
                <strong>Razem do zapłaty:</strong>
                <strong><?= $escape(
                    $formatMoney($grossTotal)
                ) ?></strong>
            </div>

            <div class="payment-summary-row">
                <span>Słownie:</span>
                <span><?= $escape(
                    $amountInWords($grossTotal)
                ) ?></span>
            </div>

            <div class="payment-summary-row">
                <span>Zapłacono:</span>
                <strong><?= $escape(
                    $formatMoney($paidAmount)
                ) ?></strong>
            </div>

            <div class="payment-summary-row">
                <span>Pozostało do zapłaty:</span>
                <strong><?= $escape(
                    $formatMoney($remainingAmount)
                ) ?></strong>
            </div>
        </section>

        <section class="signatures">
            <div class="signature">
                <div class="signature-owner">&nbsp;</div>
                <div class="signature-line"></div>
                <div class="signature-label">
                    (osoba upoważniona do odbioru dokumentu)
                </div>
            </div>

            <div class="signature">
                <div class="signature-owner">
                    <?= $escape(
                        $ownerName !== ''
                            ? $ownerName
                            : ' '
                    ) ?>
                </div>
                <div class="signature-line"></div>
                <div class="signature-label">
                    (osoba upoważniona do wystawienia dokumentu)
                </div>
            </div>
        </section>

        <section class="notes">
            <strong>Informacje dodatkowe:</strong>
            <span><?= $notes !== ''
                ? nl2br($escape($notes))
                : '—'
            ?></span>
        </section>
    </main>
</body>
</html>
