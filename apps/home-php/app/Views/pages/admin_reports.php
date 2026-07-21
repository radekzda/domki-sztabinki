<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $dateFrom
 * @var string $dateTo
 * @var array{
 *     reservations_count:int,
 *     total_value:float,
 *     paid_value:float,
 *     remaining_value:float,
 *     nights_count:int,
 *     guests_count:int
 * } $summary
 * @var string|null $databaseMessage
 */

$formatMoney = static function (
    float $value
): string {
    return number_format(
        $value,
        0,
        ',',
        ' '
    ) . ' zł';
};

$formatNumber = static function (
    int $value
): string {
    return number_format(
        $value,
        0,
        ',',
        ' '
    );
};

$monthNames = [
    '01' => 'Styczeń',
    '02' => 'Luty',
    '03' => 'Marzec',
    '04' => 'Kwiecień',
    '05' => 'Maj',
    '06' => 'Czerwiec',
    '07' => 'Lipiec',
    '08' => 'Sierpień',
    '09' => 'Wrzesień',
    '10' => 'Październik',
    '11' => 'Listopad',
    '12' => 'Grudzień',
];

$formatMonth = static function (
    string $monthKey
) use (
    $monthNames
): string {
    if (
        preg_match(
            '/^(\d{4})-(\d{2})$/',
            $monthKey,
            $matches
        ) !== 1
    ) {
        return $monthKey;
    }

    $year = $matches[1];
    $month = $matches[2];

    return (
        $monthNames[$month]
        ?? $month
    )
        . ' '
        . $year;
};

$formatReportStatus = static function (
    string $status
): string {
    return match ($status) {
        'PENDING' => 'Oczekuje',
        'CONFIRMED' => 'Potwierdzona',
        'CHECKED_IN' => 'Zameldowany',
        'CHECKED_OUT' => 'Wymeldowany',
        'CANCELLED' => 'Anulowana',
        default => $status !== ''
            ? $status
            : 'Nieznany',
    };
};

$formatSource = static function (
    string $source
): string {
    return sourceLabelForDisplay($source);
};

$summaryCards = [
    [
        'label' => 'Rezerwacje',
        'value' => $formatNumber(
            $summary['reservations_count']
            ?? 0
        ),
        'class' => 'reports-card--reservations',
    ],
    [
        'label' => 'Wartość rezerwacji',
        'value' => $formatMoney(
            (float) (
                $summary['total_value']
                ?? 0
            )
        ),
        'class' => 'reports-card--value',
    ],
    [
        'label' => 'Wpłacono',
        'value' => $formatMoney(
            (float) (
                $summary['paid_value']
                ?? 0
            )
        ),
        'class' => 'reports-card--paid',
    ],
    [
        'label' => 'Do zapłaty',
        'value' => $formatMoney(
            (float) (
                $summary['remaining_value']
                ?? 0
            )
        ),
        'class' => 'reports-card--remaining',
    ],
    [
        'label' => 'Noclegi',
        'value' => $formatNumber(
            $summary['nights_count']
            ?? 0
        ),
        'class' => 'reports-card--nights',
    ],
    [
        'label' => 'Osoby',
        'value' => $formatNumber(
            $summary['guests_count']
            ?? 0
        ),
        'class' => 'reports-card--guests',
    ],
];
?>

<style>
    .reports-panel {
        padding: 28px;
    }

    .reports-header {
        margin-bottom: 22px;
        align-items: flex-start;
        gap: 24px;
    }

    .reports-header .eyebrow {
        display: none;
    }

    .reports-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
        color: #111827;
    }

    .reports-header p {
        max-width: 760px;
        margin: 0;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .reports-filter {
        margin-bottom: 20px;
        padding: 16px;
        display: grid;
        grid-template-columns:
            minmax(160px, 1fr)
            minmax(160px, 1fr)
            auto;
        align-items: end;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .reports-filter__field {
        display: grid;
        gap: 6px;
    }

    .reports-filter__field label {
        color: #374151;
        font-size: 12px;
        font-weight: 700;
    }

    .reports-filter__field input {
        width: 100%;
        min-height: 40px;
        padding: 8px 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        font-size: 13px;
    }

    .reports-filter .button {
        min-height: 40px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
    }

    .reports-summary {
        display: grid;
        grid-template-columns: repeat(
            3,
            minmax(0, 1fr)
        );
        gap: 12px;
    }

    .reports-card {
        min-width: 0;
        min-height: 86px;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border: 1px solid #e5e7eb;
        border-top: 3px solid #94a3b8;
        border-radius: 12px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .reports-card span {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 650;
    }

    .reports-card strong {
        flex-shrink: 0;
        color: #111827;
        font-size: 22px;
        line-height: 1;
        font-weight: 800;
        text-align: right;
    }

    .reports-card--reservations {
        border-top-color: #3b82f6;
    }

    .reports-card--value {
        border-top-color: #6366f1;
    }

    .reports-card--paid {
        border-top-color: #22c55e;
    }

    .reports-card--remaining {
        border-top-color: #f59e0b;
    }

    .reports-card--nights {
        border-top-color: #8b5cf6;
    }

    .reports-card--guests {
        border-top-color: #14b8a6;
    }

    .reports-info {
        margin-top: 20px;
        padding: 16px 18px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .reports-info strong {
        display: block;
        margin-bottom: 5px;
        color: #111827;
        font-size: 13px;
    }

    .reports-info p {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }

    @media (max-width: 1000px) {
        .reports-summary {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 700px) {
        .reports-panel {
            padding: 18px;
        }

        .reports-header h1 {
            font-size: 27px;
        }

        .reports-filter {
            grid-template-columns: 1fr;
        }

        .reports-summary {
            grid-template-columns: 1fr;
        }

        .reports-card {
            min-height: 72px;
        }
    }

    /* M13.94.2 — wyniki według domków */

    .reports-section-header {
        margin: 28px 0 12px;
        padding-bottom: 9px;
        border-bottom: 1px solid #edf0f2;
    }

    .reports-section-header h2 {
        margin: 0 0 4px;
        color: #111827;
        font-size: 19px;
        line-height: 1.2;
    }

    .reports-section-header p {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .reports-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #ffffff;
    }

    .reports-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 12px;
    }

    .reports-table th {
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #4b5563;
        font-size: 10px;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.035em;
        white-space: nowrap;
    }

    .reports-table td {
        padding: 11px 12px;
        border-bottom: 1px solid #edf0f2;
        color: #374151;
        line-height: 1.35;
        white-space: nowrap;
    }

    .reports-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .reports-table tbody tr:hover {
        background: #fafbfc;
    }

    .reports-table__name {
        min-width: 180px;
        color: #111827 !important;
        font-weight: 700;
        white-space: normal !important;
    }

    .reports-table__money {
        text-align: right;
        font-weight: 650;
    }

    .reports-table__paid {
        color: #166534 !important;
    }

    .reports-table__remaining {
        color: #92400e !important;
    }

</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                [
                    'active' => 'reports',
                ]
            ); ?>

            <div class="admin-content">
                <div class="panel reports-panel">
                    <div class="page-header reports-header">
                        <div>
                            <p class="eyebrow">
                                Raporty
                            </p>

                            <h1>
                                Raporty
                            </h1>

                            <p>
                                Podsumowanie rezerwacji i płatności
                                dla wybranego okresu.
                            </p>
                        </div>
                    </div>

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

                    <form
                        class="reports-filter"
                        method="get"
                        action="/admin/raporty"
                    >
                        <div class="reports-filter__field">
                            <label for="date_from">
                                Od
                            </label>

                            <input
                                id="date_from"
                                name="date_from"
                                type="date"
                                value="<?= htmlspecialchars(
                                    $dateFrom,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="reports-filter__field">
                            <label for="date_to">
                                Do
                            </label>

                            <input
                                id="date_to"
                                name="date_to"
                                type="date"
                                value="<?= htmlspecialchars(
                                    $dateTo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>

                        <button
                            class="button button--primary"
                            type="submit"
                        >
                            Pokaż raport
                        </button>
                    </form>

                    <div class="reports-summary">
                        <?php foreach (
                            $summaryCards
                            as $card
                        ): ?>
                            <div
                                class="reports-card <?= htmlspecialchars(
                                    $card['class'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <span>
                                    <?= htmlspecialchars(
                                        $card['label'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $card['value'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="reports-section-header">
                        <h2>
                            Wyniki według statusu
                        </h2>

                        <p>
                            Liczba i wartość rezerwacji według aktualnego statusu.
                            Zestawienie obejmuje również rezerwacje anulowane.
                        </p>
                    </div>

                    <?php if ($statusRows === []): ?>
                        <div class="alert alert--warning">
                            Brak rezerwacji w wybranym okresie.
                        </div>
                    <?php else: ?>
                        <div class="reports-table-wrapper">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Rezerwacje</th>
                                        <th>Noclegi</th>
                                        <th>Osoby</th>
                                        <th>Wartość</th>
                                        <th>Wpłacono</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($statusRows as $row): ?>
                                        <tr>
                                            <td class="reports-table__name">
                                                <?= htmlspecialchars(
                                                    $formatReportStatus(
                                                        (string) $row['status_key']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['reservations_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['nights_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['guests_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['total_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__paid">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['paid_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="reports-section-header">
                        <h2>
                            Wyniki według źródła
                        </h2>

                        <p>
                            Porównanie rezerwacji według kanału,
                            z którego zostały pozyskane.
                        </p>
                    </div>

                    <?php if ($sourceRows === []): ?>
                        <div class="alert alert--warning">
                            Brak danych o źródłach rezerwacji
                            w wybranym okresie.
                        </div>
                    <?php else: ?>
                        <div class="reports-table-wrapper">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Źródło</th>
                                        <th>Rezerwacje</th>
                                        <th>Noclegi</th>
                                        <th>Osoby</th>
                                        <th>Wartość</th>
                                        <th>Wpłacono</th>
                                        <th>Do zapłaty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach (
                                        $sourceRows
                                        as $row
                                    ): ?>
                                        <tr>
                                            <td class="reports-table__name">
                                                <?= htmlspecialchars(
                                                    $formatSource(
                                                        (string) $row['source_key']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['reservations_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['nights_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['guests_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['total_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__paid">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['paid_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__remaining">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['remaining_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="reports-section-header">
                        <h2>
                            Wyniki miesięczne
                        </h2>

                        <p>
                            Podsumowanie nieanulowanych rezerwacji
                            według miesiąca rozpoczęcia pobytu.
                        </p>
                    </div>

                    <?php if ($monthRows === []): ?>
                        <div class="alert alert--warning">
                            Brak danych miesięcznych
                            w wybranym okresie.
                        </div>
                    <?php else: ?>
                        <div class="reports-table-wrapper">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Miesiąc</th>
                                        <th>Rezerwacje</th>
                                        <th>Noclegi</th>
                                        <th>Osoby</th>
                                        <th>Wartość</th>
                                        <th>Wpłacono</th>
                                        <th>Do zapłaty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach (
                                        $monthRows
                                        as $row
                                    ): ?>
                                        <tr>
                                            <td class="reports-table__name">
                                                <?= htmlspecialchars(
                                                    $formatMonth(
                                                        (string) $row['month_key']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['reservations_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['nights_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['guests_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['total_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__paid">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['paid_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__remaining">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['remaining_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="reports-section-header">
                        <h2>
                            Wyniki według domków
                        </h2>

                        <p>
                            Podsumowanie nieanulowanych rezerwacji
                            rozpoczętych w wybranym okresie.
                        </p>
                    </div>

                    <?php if ($cabinRows === []): ?>
                        <div class="alert alert--warning">
                            Brak rezerwacji w wybranym okresie.
                        </div>
                    <?php else: ?>
                        <div class="reports-table-wrapper">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Domek</th>
                                        <th>Rezerwacje</th>
                                        <th>Noclegi</th>
                                        <th>Osoby</th>
                                        <th>Wartość</th>
                                        <th>Wpłacono</th>
                                        <th>Do zapłaty</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cabinRows as $row): ?>
                                        <tr>
                                            <td class="reports-table__name">
                                                <?= htmlspecialchars(
                                                    (string) $row['cabin_name'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['reservations_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['nights_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatNumber(
                                                        (int) $row['guests_count']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['total_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__paid">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['paid_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td class="reports-table__money reports-table__remaining">
                                                <?= htmlspecialchars(
                                                    $formatMoney(
                                                        (float) $row['remaining_value']
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="reports-info">
                        <strong>
                            Zakres raportu
                        </strong>

                        <p>
                            Raport obejmuje nieanulowane rezerwacje,
                            których data rozpoczęcia pobytu przypada
                            od
                            <strong>
                                <?= htmlspecialchars(
                                    $dateFrom,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                            do
                            <strong>
                                <?= htmlspecialchars(
                                    $dateTo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>.
                            Wartość „Do zapłaty” nie może być
                            mniejsza niż zero.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
