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
