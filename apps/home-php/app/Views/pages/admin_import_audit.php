<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string|null $databaseMessage
 * @var array<string, int> $summary
 * @var array<int, array<string, mixed>> $reservationStatuses
 * @var array<int, array<string, mixed>> $paymentStatuses
 * @var array<int, array<string, mixed>> $reservationSources
 * @var array<int, array<string, mixed>> $reservationsWithoutGuest
 * @var array<int, array<string, mixed>> $duplicateGuestEmails
 * @var array<int, array<string, mixed>> $duplicateCabinShortNames
 */

$number = static function (
    mixed $value
): string {
    return number_format(
        (int) $value,
        0,
        ',',
        ' '
    );
};

$value = static function (
    mixed $value
): string {
    if ($value === null) {
        return '—';
    }

    $text = trim(
        (string) $value
    );

    return $text !== ''
        ? $text
        : '—';
};

$cards = [
    [
        'label' => 'Goście',
        'value' =>
            $summary[
                'guests_count'
            ]
            ?? 0,
    ],
    [
        'label' => 'Domki',
        'value' =>
            $summary[
                'cabins_count'
            ]
            ?? 0,
    ],
    [
        'label' => 'Rezerwacje',
        'value' =>
            $summary[
                'reservations_count'
            ]
            ?? 0,
    ],
    [
        'label' =>
            'Rezerwacje bez gościa',
        'value' =>
            $summary[
                'reservations_without_guest'
            ]
            ?? 0,
    ],
    [
        'label' =>
            'Niepełne adresy gości',
        'value' =>
            $summary[
                'guests_incomplete_address'
            ]
            ?? 0,
    ],
    [
        'label' =>
            'Stare e-maile @base44.local',
        'value' =>
            $summary[
                'legacy_placeholder_emails'
            ]
            ?? 0,
    ],
    [
        'label' =>
            'Błędne źródła rezerwacji',
        'value' =>
            $summary[
                'invalid_reservation_sources'
            ]
            ?? 0,
    ],
    [
        'label' =>
            'Błędne źródła gości',
        'value' =>
            $summary[
                'invalid_guest_sources'
            ]
            ?? 0,
    ],
];
?>
<style>
    .import-audit-grid {
        display: grid;
        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );
        gap: 12px;
        margin: 20px 0;
    }

    .import-audit-card {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }

    .import-audit-card span {
        display: block;
        margin-bottom: 8px;
        color: #6b7280;
        font-size: 12px;
    }

    .import-audit-card strong {
        font-size: 24px;
        color: #111827;
    }

    .import-audit-section {
        margin-top: 28px;
    }

    .import-audit-section h2 {
        margin-bottom: 10px;
        font-size: 18px;
    }

    @media (max-width: 900px) {
        .import-audit-grid {
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }
    }

    @media (max-width: 600px) {
        .import-audit-grid {
            grid-template-columns:
                1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'imports']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">System</p>

                            <h1>Kontrola danych i importów</h1>

                            <p>
                                Kontrola jakości danych po neutralnych
                                importach CSV. Nowe importy nie wymagają
                                identyfikatorów Base44.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/goscie/import">
                                Import gości
                            </a>

                            <a class="button button--secondary" href="/admin/domki/import">
                                Import domków
                            </a>

                            <a class="button button--secondary" href="/admin/rezerwacje/import">
                                Import rezerwacji
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="import-audit-grid">
                        <?php foreach ($cards as $card): ?>
                            <div class="import-audit-card">
                                <span>
                                    <?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <strong>
                                    <?= htmlspecialchars($number($card['value']), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="import-audit-section">
                        <h2>Rozkład rezerwacji</h2>

                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Statusy</th>
                                        <th>Płatności</th>
                                        <th>Źródła</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>
                                            <?php foreach ($reservationStatuses as $row): ?>
                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars($value($row['item_value'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>

                                        <td>
                                            <?php foreach ($paymentStatuses as $row): ?>
                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars($value($row['item_value'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>

                                        <td>
                                            <?php foreach ($reservationSources as $row): ?>
                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars(sourceLabelForDisplay((string) ($row['item_value'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="import-audit-section">
                        <h2>Rezerwacje bez powiązanego gościa</h2>

                        <?php if ($reservationsWithoutGuest === []): ?>
                            <div class="alert alert--success">
                                Wszystkie rezerwacje mają powiązanego gościa.
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Gość</th>
                                            <th>E-mail</th>
                                            <th>Domek</th>
                                            <th>Termin</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($reservationsWithoutGuest as $row): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($value($row['guest_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($value($row['email'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($value($row['cabin_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars(formatDateForDisplay((string) ($row['start_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay((string) ($row['end_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="import-audit-section">
                        <h2>Możliwe duplikaty</h2>

                        <?php if ($duplicateGuestEmails === [] && $duplicateCabinShortNames === []): ?>
                            <div class="alert alert--success">
                                Nie znaleziono duplikatów e-maili gości ani skrótów domków.
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Wartość</th>
                                            <th>Liczba</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($duplicateGuestEmails as $row): ?>
                                            <tr>
                                                <td>E-mail gościa</td>

                                                <td>
                                                    <?= htmlspecialchars($value($row['email'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <?php foreach ($duplicateCabinShortNames as $row): ?>
                                            <tr>
                                                <td>Skrót domku</td>

                                                <td>
                                                    <?= htmlspecialchars($value($row['short_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
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
    </div>
</section>
