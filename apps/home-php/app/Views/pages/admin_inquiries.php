<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     full_name: string,
 *     first_name: string|null,
 *     last_name: string|null,
 *     phone: string,
 *     email: string|null,
 *     cabin_id: int|null,
 *     cabin_name: string|null,
 *     reservation_id: int|null,
 *     linked_cabin_name: string|null,
 *     date_from: string,
 *     date_to: string,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     city: string|null,
 *     country: string|null,
 *     notes: string|null,
 *     status: string,
 *     source: string,
 *     created_at: string
 * }> $inquiries
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

$statusLabels = [
    'NEW' => 'Nowe',
    'IN_PROGRESS' => 'W trakcie',
    'RESOLVED' => 'Obsłużone',
    'CANCELLED' => 'Anulowane',
];

$getStatusClass = static function (string $status): string {
    if ($status === 'RESOLVED') {
        return 'status-pill status-pill--success';
    }

    return 'status-pill status-pill--muted';
};
?>
<style>
    .inquiries-panel {
        padding: 28px;
    }

    .inquiries-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .inquiries-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .inquiries-panel .page-header p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .inquiries-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    /*
     * Statystyki
     */
    .inquiries-stats {
        display: grid;
        grid-template-columns: repeat(
            4,
            minmax(0, 1fr)
        );
        gap: 12px;
        margin-bottom: 20px;
    }

    .inquiries-stats .stat-card {
        min-width: 0;
        min-height: 68px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 6px 16px rgba(15, 23, 42, 0.03);
    }

    .inquiries-stats .stat-card span {
        min-width: 0;
        font-size: 13px;
        line-height: 1.25;
        font-weight: 600;
        color: #6b7280;
    }

    .inquiries-stats .stat-card strong {
        flex-shrink: 0;
        font-size: 24px;
        line-height: 1;
        color: #111827;
    }

    /*
     * Tabela
     */
    .inquiries-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .inquiries-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .inquiries-table thead {
        background: #f8fafc;
    }

    .inquiries-table th {
        padding: 13px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
        line-height: 1.15;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        color: #6b7280;
    }

    .inquiries-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #edf0f2;
        vertical-align: middle;
        font-size: 13px;
        line-height: 1.35;
        color: #374151;
    }

    .inquiries-table tbody tr {
        transition: background 0.15s ease;
    }

    .inquiries-table tbody tr:hover {
        background: #fafbfc;
    }

    /*
     * Dane
     */
    .inquiry-date {
        font-size: 12px;
        color: #6b7280;
    }

    .inquiry-guest {
        display: grid;
        gap: 3px;
    }

    .inquiry-guest strong {
        font-size: 14px;
        line-height: 1.3;
        color: #111827;
    }

    .inquiry-guest span {
        font-size: 12px;
        line-height: 1.3;
        color: #6b7280;
    }

    .inquiry-contact {
        display: grid;
        gap: 3px;
    }

    .inquiry-contact span {
        font-size: 12px;
        line-height: 1.35;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    .inquiry-term {
        font-size: 13px;
        line-height: 1.3;
        color: #111827;
    }

    .inquiry-guests {
        display: grid;
        gap: 3px;
    }

    .inquiry-guests strong {
        font-size: 13px;
        color: #111827;
    }

    .inquiry-guests span {
        font-size: 12px;
        color: #6b7280;
    }

    .inquiry-source {
        font-size: 12px;
        color: #6b7280;
    }

    /*
     * Status
     */
    .inquiries-table .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        font-weight: 700;
    }

    /*
     * Akcje
     */
    .inquiry-actions {
        min-width: 210px;
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px;
    }

    .inquiry-actions > a,
    .inquiry-actions > form {
        min-width: 0;
        margin: 0;
    }

    .inquiry-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 9px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .inquiry-status-form {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns:
            minmax(0, 1fr)
            auto;
        gap: 7px;
        align-items: center;
        padding: 7px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #f8fafc;
    }

    .inquiry-status-form select {
        width: 100%;
        min-width: 0;
        height: 34px;
        padding: 5px 8px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        background: #ffffff;
        font-size: 12px;
        color: #374151;
    }

    .inquiry-status-form .button {
        width: auto;
        min-width: 68px;
    }

    .inquiry-delete-form {
        grid-column: 1 / -1;
    }

    .inquiry-delete-button {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .inquiry-delete-button:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Notatka
     */
    .inquiry-note-row td {
        padding: 10px 14px;
        background: #fffbeb;
        border-bottom: 1px solid #fde68a;
        font-size: 12px;
        color: #6b7280;
    }

    .inquiry-note-row strong {
        color: #92400e;
    }

    /*
     * Responsive
     */
    @media (max-width: 1100px) {
        .inquiries-panel {
            padding: 22px;
        }

        .inquiries-stats {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 700px) {
        .inquiries-panel {
            padding: 16px;
        }

        .inquiries-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .inquiries-panel .page-header h1 {
            font-size: 27px;
        }

        .inquiries-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'inquiries']); ?>

            <div class="admin-content">
                <div class="panel inquiries-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Zapytania</p>

                            <h1>Zapytania</h1>

                            <p>
                                Lista zapytań pobierana z bazy MySQL. Możesz zmieniać status,
                                podejrzeć szczegóły albo utworzyć rezerwację z zapytania.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/system/database">
                                Sprawdź bazę
                            </a>
                        </div>
                    </div>

                    <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($inquiries === []): ?>
                        <div class="empty-state">
                            <strong>Brak zapytań do wyświetlenia</strong>

                            <p>
                                Po uruchomieniu publicznego formularza WWW nowe zapytania będą pojawiały się tutaj.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="inquiries-stats">
                            <div class="stat-card">
                                <span>Wszystkie zapytania</span>
                                <strong><?= htmlspecialchars((string) count($inquiries), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <div class="stat-card">
                                <span>Nowe</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'NEW')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>

                            <div class="stat-card">
                                <span>W trakcie</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'IN_PROGRESS')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>

                            <div class="stat-card">
                                <span>Obsłużone</span>
                                <strong>
                                    <?= htmlspecialchars((string) count(array_filter($inquiries, static fn (array $inquiry): bool => $inquiry['status'] === 'RESOLVED')), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                        </div>

                        <div class="table-wrapper inquiries-table-wrapper">
                            <table class="data-table inquiries-table">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">Data zapytania</th>
                                        <th style="width: 15%;">Gość</th>
                                        <th style="width: 15%;">Kontakt</th>
                                        <th style="width: 12%;">Termin</th>
                                        <th style="width: 8%;">Domek</th>
                                        <th style="width: 9%;">Osoby</th>
                                        <th style="width: 8%;">Status</th>
                                        <th style="width: 7%;">Źródło</th>
                                        <th style="width: 16%;">Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): ?>
                                        <?php
                                        $status = $inquiry['status'];
                                        $linkedReservationId = (int) (
                                            $inquiry['reservation_id']
                                            ?? 0
                                        );
                                        $cabinName = $inquiry['linked_cabin_name']
                                            ?? $inquiry['cabin_name']
                                            ?? 'Dowolny / nie wybrano';
                                        ?>

                                        <tr>
                                            <td>
                                                <span class="inquiry-date">
                                                    <?= htmlspecialchars(formatDateForDisplay($inquiry['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="inquiry-guest">
                                                    <strong>
                                                        <?= htmlspecialchars($inquiry['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>

                                                <?php if ($inquiry['city'] !== null && $inquiry['city'] !== ''): ?>
                                                    <span>
                                                        <?= htmlspecialchars($inquiry['city'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($inquiry['country'] !== null && $inquiry['country'] !== ''): ?>
                                                    <span>
                                                        <?= htmlspecialchars($inquiry['country'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="inquiry-contact">
                                                    <span>
                                                        <?= htmlspecialchars($inquiry['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>

                                                <?php if ($inquiry['email'] !== null && $inquiry['email'] !== ''): ?>
                                                    <span>
                                                        <?= htmlspecialchars($inquiry['email'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                                </div>
                                            </td>

                                            <td>
                                                <strong class="inquiry-term">
                                                    <?= htmlspecialchars(formatDateForDisplay($inquiry['date_from']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay($inquiry['date_to']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($cabinName, ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <div class="inquiry-guests">
                                                    <strong>
                                                        <?= htmlspecialchars((string) $inquiry['guests'], ENT_QUOTES, 'UTF-8') ?>
                                                        os.
                                                    </strong>

                                                    <span>
                                                    dorośli:
                                                    <?= htmlspecialchars((string) $inquiry['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                                        dzieci:
                                                        <?= htmlspecialchars((string) $inquiry['children'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="<?= htmlspecialchars($getStatusClass($status), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="inquiry-source">
                                                    <?= htmlspecialchars($inquiry['source'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="inquiry-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/zapytania/pokaz?id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Szczegóły
                                                    </a>

                                                    <?php if ($linkedReservationId > 0): ?>
                                                        <a
                                                            class="button button--primary button--small"
                                                            href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $linkedReservationId, ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            Rezerwacja #<?= htmlspecialchars((string) $linkedReservationId, ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <a
                                                            class="button button--primary button--small"
                                                            href="/admin/rezerwacje/nowa?inquiry_id=<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            Rezerwacja
                                                        </a>
                                                    <?php endif; ?>

                                                    <form class="inquiry-status-form" method="post" action="/admin/zapytania/status">
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="status">
                                                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                                                <option
                                                                    value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                                                    <?= $inquiry['status'] === $statusValue ? 'selected' : '' ?>
                                                                >
                                                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button class="button button--primary button--small" type="submit">
                                                            Zapisz
                                                        </button>
                                                    </form>

                                                    <form
                                                        class="inquiry-delete-form"
                                                        method="post"
                                                        action="/admin/zapytania/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć to zapytanie?')"
                                                    >
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $inquiry['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--small inquiry-delete-button" type="submit">
                                                            Usuń
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php if ($inquiry['notes'] !== null && $inquiry['notes'] !== ''): ?>
                                            <tr class="inquiry-note-row">
                                                <td colspan="9">
                                                    <strong>Notatka:</strong>
                                                    <?= nl2br(htmlspecialchars($inquiry['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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
