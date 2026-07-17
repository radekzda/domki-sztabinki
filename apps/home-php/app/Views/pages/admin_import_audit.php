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
 * @var array<int, array<string, mixed>> $reservationsWithoutExternalId
 * @var array<int, array<string, mixed>> $guestsWithoutExternalId
 * @var array<int, array<string, mixed>> $cabinsWithoutExternalId
 */

$number = static function (mixed $value): string {
    return number_format((int) $value, 0, ',', ' ');
};

$value = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $text = trim((string) $value);

    return $text !== '' ? $text : '—';
};

$summaryCards = [
    ['label' => 'Goście', 'value' => $summary['guests_count'] ?? 0],
    ['label' => 'Domki', 'value' => $summary['cabins_count'] ?? 0],
    ['label' => 'Rezerwacje', 'value' => $summary['reservations_count'] ?? 0],
    ['label' => 'Goście Base44', 'value' => $summary['guests_base44_count'] ?? 0],
    ['label' => 'Domki z external_id', 'value' => $summary['cabins_with_external_id'] ?? 0],
    ['label' => 'Rezerwacje z external_id', 'value' => $summary['reservations_with_external_id'] ?? 0],
];

$warningCards = [
    ['label' => 'Rezerwacje bez gościa', 'value' => $summary['reservations_without_guest'] ?? 0],
    ['label' => 'Rezerwacje bez external_id', 'value' => $summary['reservations_without_external_id'] ?? 0],
    ['label' => 'Goście bez external_id', 'value' => $summary['guests_without_external_id'] ?? 0],
    ['label' => 'Domki bez external_id', 'value' => $summary['cabins_without_external_id'] ?? 0],
];
?>
<style>
    .import-audit-panel {
        padding: 28px;
    }

    /*
     * Naglowek strony
     */
    .import-audit-header {
        margin-bottom: 22px;
        align-items: flex-start;
        gap: 24px;
    }

    .import-audit-header .eyebrow {
        display: none;
    }

    .import-audit-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
        color: #111827;
    }

    .import-audit-header p {
        max-width: 720px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .import-audit-header .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 7px;
    }

    .import-audit-header .button {
        min-height: 36px;
        padding: 7px 12px;
        border-radius: 9px;
        font-size: 11px;
        line-height: 1.2;
    }

    /*
     * Podsumowanie importu
     */
    .import-audit-summary {
        margin-top: 20px;
        display: grid;
        grid-template-columns: repeat(
            3,
            minmax(0, 1fr)
        );
        gap: 10px;
    }

    .import-audit-summary
    .pms-calendar-summary__card {
        min-width: 0;
        min-height: 64px;
        padding: 13px 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 6px 16px rgba(15, 23, 42, 0.025);
    }

    .import-audit-summary
    .pms-calendar-summary__card span {
        min-width: 0;
        font-size: 11px;
        line-height: 1.3;
        font-weight: 650;
        color: #6b7280;
    }

    .import-audit-summary
    .pms-calendar-summary__card strong {
        flex-shrink: 0;
        font-size: 21px;
        line-height: 1;
        font-weight: 800;
        color: #111827;
    }

    /*
     * Ostrzezenia danych
     */
    .import-audit-summary--warnings {
        margin-top: 10px;
        grid-template-columns: repeat(
            4,
            minmax(0, 1fr)
        );
    }

    .import-audit-summary--warnings
    .pms-calendar-summary__card {
        min-height: 58px;
        border-top: 3px solid #f59e0b;
        background: #fffdf7;
    }

    .import-audit-summary--warnings
    .pms-calendar-summary__card strong {
        font-size: 19px;
        color: #92400e;
    }

    /*
     * Naglowki poszczegolnych kontroli
     */
    .import-audit-section-header {
        margin: 26px 0 10px !important;
        padding-bottom: 9px;
        border-bottom: 1px solid #edf0f2;
    }

    .import-audit-section-header h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
        color: #111827;
    }

    /*
     * Tabele
     */
    .import-audit-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #ffffff;
    }

    .import-audit-table-wrapper .data-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 12px;
    }

    .import-audit-table-wrapper
    .data-table th {
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #4b5563;
        font-size: 10px;
        line-height: 1.25;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.035em;
    }

    .import-audit-table-wrapper
    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #edf0f2;
        color: #374151;
        line-height: 1.4;
        vertical-align: top;
    }

    .import-audit-table-wrapper
    .data-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .import-audit-table-wrapper
    .data-table tbody tr:hover {
        background: #fafbfc;
    }

    /*
     * Rozklad rezerwacji
     */
    .import-audit-table-wrapper
    .data-table td > div {
        margin-bottom: 4px;
    }

    .import-audit-table-wrapper
    .data-table td > div:last-child {
        margin-bottom: 0;
    }

    .import-audit-table-wrapper
    .data-table td > div strong {
        font-size: 11px;
        color: #111827;
    }

    /*
     * Komunikaty o poprawnosci danych
     */
    .import-audit-panel
    .import-audit-section-header
    + .alert {
        margin-top: 0;
        border-radius: 10px;
        font-size: 12px;
    }

    /*
     * Responsywnosc
     */
    @media (max-width: 1100px) {
        .import-audit-summary {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }

        .import-audit-summary--warnings {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 800px) {
        .import-audit-panel {
            padding: 22px;
        }

        .import-audit-header {
            flex-direction: column;
        }

        .import-audit-header
        .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 600px) {
        .import-audit-panel {
            padding: 16px;
        }

        .import-audit-header h1 {
            font-size: 27px;
        }

        .import-audit-summary,
        .import-audit-summary--warnings {
            grid-template-columns: 1fr;
        }

        .import-audit-header
        .page-header__actions {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr;
        }

        .import-audit-header .button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'imports']); ?>

            <div class="admin-content">
                <div class="panel import-audit-panel">
                    <div class="page-header import-audit-header">
                        <div>
                            <p class="eyebrow">System</p>

                            <h1>Kontrola importu Base44</h1>

                            <p>
                                Szybka diagnostyka danych po imporcie gości, domków i rezerwacji.
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

                    <div class="import-audit-summary">
                        <?php foreach ($summaryCards as $card): ?>
                            <div class="pms-calendar-summary__card">
                                <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= htmlspecialchars($number($card['value']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="import-audit-summary import-audit-summary--warnings">
                        <?php foreach ($warningCards as $card): ?>
                            <div class="pms-calendar-summary__card">
                                <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= htmlspecialchars($number($card['value']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="page-header import-audit-section-header">
                        <div>
                            <h2>Rozkład rezerwacji</h2>
                        </div>
                    </div>

                    <div class="table-wrapper import-audit-table-wrapper">
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
                                        <?php if ($reservationStatuses === []): ?>
                                            —
                                        <?php else: ?>
                                            <?php foreach ($reservationStatuses as $row): ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($value($row['item_value'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($paymentStatuses === []): ?>
                                            —
                                        <?php else: ?>
                                            <?php foreach ($paymentStatuses as $row): ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($value($row['item_value'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($reservationSources === []): ?>
                                            —
                                        <?php else: ?>
                                            <?php foreach ($reservationSources as $row): ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($value($row['item_value'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>:
                                                    <?= htmlspecialchars($number($row['item_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="page-header import-audit-section-header">
                        <div>
                            <h2>Rezerwacje bez gościa</h2>
                        </div>
                    </div>

                    <?php if ($reservationsWithoutGuest === []): ?>
                        <div class="alert alert--success">
                            Wszystkie rezerwacje są powiązane z kartą gościa.
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper import-audit-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>External ID</th>
                                        <th>Gość</th>
                                        <th>Domek</th>
                                        <th>Termin</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reservationsWithoutGuest as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['external_id'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['guest_name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['cabin_name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($value($row['start_date'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                —
                                                <?= htmlspecialchars($value($row['end_date'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="page-header import-audit-section-header">
                        <div>
                            <h2>Rezerwacje bez external_id</h2>
                        </div>
                    </div>

                    <?php if ($reservationsWithoutExternalId === []): ?>
                        <div class="alert alert--success">
                            Wszystkie rezerwacje z importu mają external_id albo nie ma ręcznych rezerwacji bez ID.
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper import-audit-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Gość</th>
                                        <th>Domek</th>
                                        <th>Termin</th>
                                        <th>Źródło</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reservationsWithoutExternalId as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['guest_name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['cabin_name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($value($row['start_date'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                —
                                                <?= htmlspecialchars($value($row['end_date'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td><?= htmlspecialchars($value($row['source'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="page-header import-audit-section-header">
                        <div>
                            <h2>Goście bez external_id</h2>
                        </div>
                    </div>

                    <?php if ($guestsWithoutExternalId === []): ?>
                        <div class="alert alert--success">
                            Wszyscy goście mają external_id.
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper import-audit-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Gość</th>
                                        <th>E-mail</th>
                                        <th>Telefon</th>
                                        <th>Źródło</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($guestsWithoutExternalId as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($value($row['first_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                <?= htmlspecialchars($value($row['last_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td><?= htmlspecialchars($value($row['email'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['phone'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['source'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="page-header import-audit-section-header">
                        <div>
                            <h2>Domki bez external_id</h2>
                        </div>
                    </div>

                    <?php if ($cabinsWithoutExternalId === []): ?>
                        <div class="alert alert--success">
                            Wszystkie domki mają external_id.
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper import-audit-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Domek</th>
                                        <th>Skrót</th>
                                        <th>Kolejność</th>
                                        <th>Aktywny</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cabinsWithoutExternalId as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['short_name'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($value($row['sort_order'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Tak' : 'Nie' ?></td>
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
