<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     cabin_id: int,
 *     cabin_name: string|null,
 *     guest_name: string,
 *     email: string,
 *     phone: string|null,
 *     start_date: string,
 *     end_date: string,
 *     nights: int,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     status: string,
 *     source: string,
 *     payment_status: string|null,
 *     total_price: string|null,
 *     paid_amount: string|null,
 *     created_at: string
 * }> $reservations
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

$statusLabels = [
    'PENDING' => 'Oczekuje',
    'CONFIRMED' => 'Potwierdzona',
    'CHECKED_IN' => 'Zameldowany',
    'CHECKED_OUT' => 'Wymeldowany',
    'CANCELLED' => 'Anulowana',
    'COMPLETED' => 'Wymeldowany',
];

$paymentLabels = [
    'PENDING' => 'Oczekuje',
    'PAID' => 'Opłacona',
    'PARTIAL' => 'Częściowa',
    'REFUNDED' => 'Zwrócona',
];
?>
<style>
    .reservations-panel {
        padding: 28px;
    }

    .reservations-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .reservations-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .reservations-panel .page-header p {
        max-width: 720px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .reservations-panel .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .reservations-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    .reservations-list-search {
        width: min(100%, 560px);
        margin-top: 16px;
    }

    .reservations-list-search input {
        width: 100%;
        min-height: 42px;
        padding: 9px 13px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
    }

    .reservations-list-search input:focus {
        outline: 2px solid rgba(37, 99, 235, 0.16);
        outline-offset: 1px;
        border-color: #93c5fd;
    }

    .reservations-list-search input::placeholder {
        color: #9ca3af;
    }

    .reservation-sort-button {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: inherit;
        text-align: left;
        cursor: pointer;
    }

    .reservation-sort-button:hover {
        color: #111827;
    }

    .reservation-sort-indicator {
        min-width: 12px;
        color: #9ca3af;
        font-size: 10px;
        line-height: 1;
    }

    .reservation-sort-button.is-active .reservation-sort-indicator {
        color: #2563eb;
    }

    .reservations-search-empty {
        display: none;
        margin-top: 16px;
        padding: 18px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: #f9fafb;
        color: #6b7280;
        text-align: center;
    }

    /*
     * Tabela
     */
    .reservations-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .reservations-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .reservations-table thead {
        background: #f8fafc;
    }

    .reservations-table th {
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

    .reservations-table td {
        padding: 16px;
        border-bottom: 1px solid #edf0f2;
        vertical-align: middle;
        font-size: 13px;
        line-height: 1.35;
        color: #374151;
    }

    .reservations-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .reservations-table tbody tr {
        transition: background 0.15s ease;
    }

    .reservations-table tbody tr:hover {
        background: #fafbfc;
    }

    /*
     * Termin
     */
    .reservation-date {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        line-height: 1.25;
        color: #111827;
    }

    .reservation-meta {
        margin-top: 3px !important;
        font-size: 12px;
        line-height: 1.35;
        color: #9ca3af !important;
    }

    .reservation-meta strong {
        color: #6b7280;
    }

    /*
     * Gość
     */
    .reservation-guest-name {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #111827;
    }

    .reservation-contact {
        margin-top: 3px !important;
        font-size: 12px;
        line-height: 1.35;
        color: #6b7280 !important;
        overflow-wrap: anywhere;
    }

    .reservation-guests {
        margin-top: 8px !important;
        font-size: 12px;
        line-height: 1.35;
    }

    /*
     * Domek
     */
    .reservation-cabin {
        font-size: 14px;
        color: #111827;
    }

    /*
     * Status
     */
    .reservation-status {
        display: grid;
        gap: 7px;
        justify-items: start;
    }

    .reservation-status .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        font-weight: 700;
    }

    .reservation-payment-label {
        font-size: 12px;
        line-height: 1.35;
        color: #6b7280;
    }

    .reservation-payment-label strong {
        color: #374151;
    }

    /*
     * Kwoty
     */
    .reservation-amount > strong {
        display: block;
        font-size: 14px;
        color: #111827;
    }

    .reservation-paid {
        margin-top: 5px !important;
        font-size: 12px;
        line-height: 1.35;
        color: #9ca3af !important;
    }

    .reservation-paid strong {
        color: #6b7280;
    }

    /*
     * Akcje
     */
    .reservation-actions {
        min-width: 230px;
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px;
    }

    .reservation-actions > a,
    .reservation-actions > form,
    .reservation-actions > div {
        min-width: 0;
        margin: 0;
    }

    .reservation-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
    }

    .reservation-actions-top,
    .reservation-actions-bottom {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px;
    }

    .reservation-actions-form {
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

    .reservation-actions-form select {
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

    .reservation-actions-form .button {
        width: auto;
        min-width: 68px;
        white-space: nowrap;
    }

    .reservation-delete-button {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .reservation-delete-button:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Responsive
     */
    @media (max-width: 1100px) {
        .reservations-panel {
            padding: 22px;
        }

        .reservations-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .reservations-panel .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 700px) {
        .reservations-panel {
            padding: 16px;
        }

        .reservations-panel .page-header h1 {
            font-size: 27px;
        }

        .reservations-panel .page-header__actions {
            display: grid;
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
            width: 100%;
        }

        .reservations-panel .page-header__actions .button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel reservations-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Rezerwacje</h1>

                            <p>
                                Lista rezerwacji pobierana z bazy MySQL. Możesz dodawać, edytować,
                                anulować i obsługiwać płatności.
                            </p>

                            <div class="reservations-list-search">
                                <input
                                    id="reservations-list-search"
                                    type="search"
                                    placeholder="Szukaj rezerwacji…"
                                    aria-label="Szukaj rezerwacji"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/rezerwacje/import">
                                Import
                            </a>

                            <a class="button button--primary" href="/admin/rezerwacje/nowa">
                                Dodaj rezerwację
                            </a>

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

                    <?php if ($reservations === []): ?>
                        <div class="empty-state">
                            <strong>Brak rezerwacji do wyświetlenia</strong>

                            <p>
                                Po skonfigurowaniu MySQL i dodaniu pierwszej rezerwacji pojawi się tutaj lista pobytów,
                                statusy, źródło rezerwacji oraz płatności.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper reservations-table-wrapper">
                            <table class="data-table reservations-table">
                                <thead>
                                    <tr>
                                        <th style="width: 17%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="0" data-sort-type="date">
                                                Termin
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 21%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="1" data-sort-type="text">
                                                Gość
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 11%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="2" data-sort-type="text">
                                                Domek
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 15%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="3" data-sort-type="text">
                                                Status / płatność
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 12%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="4" data-sort-type="number">
                                                Kwota
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 24%;" aria-sort="none">
                                            <button class="reservation-sort-button" type="button" data-sort-index="5" data-sort-type="id">
                                                Akcje
                                                <span class="reservation-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <?php
                                        $status = $reservation['status'];
                                        $paymentStatus = $reservation['payment_status'] ?? '';
                                        $statusText = $statusLabels[$status] ?? $status;
                                        $paymentText = $paymentLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : '—');
                                        ?>

                                        <tr data-row-id="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td data-sort-value="<?= htmlspecialchars((string) ($reservation['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <strong class="reservation-date">
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div class="reservation-meta">
                                                    <?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?>
                                                    noc.
                                                </div>

                                                <div class="reservation-meta">
                                                    Źródło:
                                                    <strong><?= htmlspecialchars(sourceLabelForDisplay((string) $reservation['source']), ENT_QUOTES, 'UTF-8') ?></strong>
                                                </div>
                                            </td>

                                            <td>
                                                <strong class="reservation-guest-name">
                                                    <?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div class="reservation-contact">
                                                    <?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>

                                                <?php if ($reservation['phone'] !== null && $reservation['phone'] !== ''): ?>
                                                    <div class="reservation-contact">
                                                        <?= htmlspecialchars($reservation['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="reservation-guests">
                                                    <strong>
                                                        <?= htmlspecialchars((string) $reservation['guests'], ENT_QUOTES, 'UTF-8') ?>
                                                        os.
                                                    </strong>
                                                    <span style="color: #6b7280;">
                                                        dorośli:
                                                        <?= htmlspecialchars((string) $reservation['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                                        dzieci:
                                                        <?= htmlspecialchars((string) $reservation['children'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <strong class="reservation-cabin">
                                                    <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <div class="reservation-status">
                                                    <?php if ($status === 'CONFIRMED' || $status === 'CHECKED_IN'): ?>
                                                        <span class="status-pill status-pill--success">
                                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-pill status-pill--muted">
                                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <span class="reservation-payment-label">
                                                        Płatność:
                                                        <strong><?= htmlspecialchars($paymentText, ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </span>
                                                </div>
                                            </td>

                                            <td
                                                class="reservation-amount"
                                                data-sort-value="<?= htmlspecialchars((string) ($reservation['total_price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <strong>
                                                    <?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div class="reservation-paid">
                                                    wpłacono:
                                                    <strong>
                                                        <?= htmlspecialchars(formatMoneyForDisplay($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="reservation-actions">
                                                    <div class="reservation-actions-top">
                                                        <a
                                                            class="button button--secondary button--small"
                                                            href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            Szczegóły
                                                        </a>

                                                        <a
                                                            class="button button--secondary button--small"
                                                            href="/admin/rezerwacje/edytuj?id=<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            Edytuj
                                                        </a>
                                                    </div>

                                                    <form class="reservation-actions-form" method="post" action="/admin/rezerwacje/status">
                                                    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="status" aria-label="Status rezerwacji">
                                                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                                                <?php if ($statusValue !== 'COMPLETED'): ?>
                                                                    <option
                                                                        value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                                                        <?= $reservation['status'] === $statusValue ? 'selected' : '' ?>
                                                                    >
                                                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button class="button button--primary button--small" type="submit">
                                                            Zmień
                                                        </button>
                                                    </form>

                                                    <form class="reservation-actions-form" method="post" action="/admin/rezerwacje/platnosc">
                                                    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="payment_status" aria-label="Status płatności">
                                                            <?php foreach ($paymentLabels as $paymentValue => $paymentLabel): ?>
                                                                <option
                                                                    value="<?= htmlspecialchars($paymentValue, ENT_QUOTES, 'UTF-8') ?>"
                                                                    <?= $reservation['payment_status'] === $paymentValue ? 'selected' : '' ?>
                                                                >
                                                                    <?= htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button class="button button--primary button--small" type="submit">
                                                            Zapisz
                                                        </button>
                                                    </form>

                                                    <div class="reservation-actions-bottom">
                                                        <?php if ($reservation['status'] !== 'CANCELLED'): ?>
                                                            <form
                                                                method="post"
                                                                action="/admin/rezerwacje/anuluj"
                                                                onsubmit="return confirm('Czy na pewno anulować tę rezerwację?')"
                                                                style="margin: 0;"
                                                            >
                                                    <?= csrfField() ?>
                                                                <input
                                                                    type="hidden"
                                                                    name="id"
                                                                    value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                >

                                                                <button class="button button--secondary button--small" type="submit" style="width: 100%;">
                                                                    Anuluj
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span></span>
                                                        <?php endif; ?>

                                                        <form
                                                            method="post"
                                                            action="/admin/rezerwacje/usun"
                                                            onsubmit="return confirm('Czy na pewno trwale usunąć tę rezerwację? Tej operacji nie można cofnąć.')"
                                                            style="margin: 0;"
                                                        >
                                                    <?= csrfField() ?>
                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            >

                                                            <button class="button button--small reservation-delete-button" type="submit" style="width: 100%;">
                                                                Usuń
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div
                            id="reservations-search-empty"
                            class="reservations-search-empty"
                        >
                            Brak rezerwacji pasujących do wyszukiwania.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            var searchInput =
                document.getElementById(
                    'reservations-list-search'
                );
            var table =
                document.querySelector(
                    '.reservations-table'
                );
            var emptyState =
                document.getElementById(
                    'reservations-search-empty'
                );

            if (
                !searchInput
                || !table
            ) {
                return;
            }

            var tbody =
                table.querySelector(
                    'tbody'
                );
            var sortButtons =
                Array.from(
                    table.querySelectorAll(
                        '.reservation-sort-button'
                    )
                );
            var collator =
                new Intl.Collator(
                    'pl',
                    {
                        numeric: true,
                        sensitivity: 'base'
                    }
                );

            if (!tbody) {
                return;
            }

            function normalizeSearchValue(
                value
            ) {
                return String(
                    value || ''
                )
                    .toLocaleLowerCase(
                        'pl-PL'
                    )
                    .normalize(
                        'NFD'
                    )
                    .replace(
                        /[\u0300-\u036f]/g,
                        ''
                    )
                    .replace(
                        /ł/g,
                        'l'
                    );
            }

            function applySearch() {
                var query =
                    normalizeSearchValue(
                        searchInput.value
                    );

                var visibleCount = 0;

                Array.from(
                    tbody.querySelectorAll(
                        'tr'
                    )
                ).forEach(
                    function (row) {
                        var haystack =
                            normalizeSearchValue(
                                row.textContent
                                || ''
                            );

                        var visible =
                            query === ''
                            || haystack.includes(
                                query
                            );

                        row.hidden =
                            !visible;

                        if (visible) {
                            visibleCount++;
                        }
                    }
                );

                if (emptyState) {
                    emptyState.style.display =
                        visibleCount === 0
                            ? 'block'
                            : 'none';
                }
            }

            function readSortValue(
                row,
                index,
                type
            ) {
                if (type === 'id') {
                    return (
                        row.dataset.rowId
                        || ''
                    ).trim();
                }

                var cell =
                    row.cells[index];

                if (!cell) {
                    return '';
                }

                return (
                    cell.dataset.sortValue
                    || cell.textContent
                    || ''
                ).trim();
            }

            function compareValues(
                left,
                right,
                type
            ) {
                var leftEmpty =
                    left === '';
                var rightEmpty =
                    right === '';

                if (
                    leftEmpty
                    && rightEmpty
                ) {
                    return 0;
                }

                if (leftEmpty) {
                    return 1;
                }

                if (rightEmpty) {
                    return -1;
                }

                if (
                    type === 'number'
                    || type === 'id'
                ) {
                    var leftNumber =
                        Number.parseFloat(
                            String(left)
                                .replace(
                                    /\s/g,
                                    ''
                                )
                                .replace(
                                    ',',
                                    '.'
                                )
                        );
                    var rightNumber =
                        Number.parseFloat(
                            String(right)
                                .replace(
                                    /\s/g,
                                    ''
                                )
                                .replace(
                                    ',',
                                    '.'
                                )
                        );

                    return (
                        Number.isNaN(
                            leftNumber
                        )
                            ? 0
                            : leftNumber
                    ) - (
                        Number.isNaN(
                            rightNumber
                        )
                            ? 0
                            : rightNumber
                    );
                }

                if (type === 'date') {
                    var leftTime =
                        Date.parse(
                            left
                        );
                    var rightTime =
                        Date.parse(
                            right
                        );

                    return (
                        Number.isNaN(
                            leftTime
                        )
                            ? 0
                            : leftTime
                    ) - (
                        Number.isNaN(
                            rightTime
                        )
                            ? 0
                            : rightTime
                    );
                }

                return collator.compare(
                    left,
                    right
                );
            }

            sortButtons.forEach(
                function (button) {
                    button.addEventListener(
                        'click',
                        function () {
                            var index =
                                Number.parseInt(
                                    button.dataset
                                        .sortIndex
                                    || '0',
                                    10
                                );
                            var type =
                                button.dataset
                                    .sortType
                                || 'text';
                            var currentDirection =
                                button.dataset
                                    .direction
                                || '';
                            var direction =
                                currentDirection
                                === 'asc'
                                    ? 'desc'
                                    : 'asc';

                            sortButtons.forEach(
                                function (
                                    otherButton
                                ) {
                                    otherButton
                                        .classList
                                        .remove(
                                            'is-active'
                                        );
                                    otherButton
                                        .dataset
                                        .direction =
                                            '';

                                    var otherIndicator =
                                        otherButton
                                            .querySelector(
                                                '.reservation-sort-indicator'
                                            );

                                    if (
                                        otherIndicator
                                    ) {
                                        otherIndicator
                                            .textContent =
                                                '↕';
                                    }

                                    var otherHeader =
                                        otherButton
                                            .closest(
                                                'th'
                                            );

                                    if (
                                        otherHeader
                                    ) {
                                        otherHeader
                                            .setAttribute(
                                                'aria-sort',
                                                'none'
                                            );
                                    }
                                }
                            );

                            button
                                .classList
                                .add(
                                    'is-active'
                                );
                            button.dataset
                                .direction =
                                    direction;

                            var indicator =
                                button
                                    .querySelector(
                                        '.reservation-sort-indicator'
                                    );

                            if (indicator) {
                                indicator.textContent =
                                    direction
                                    === 'asc'
                                        ? '▲'
                                        : '▼';
                            }

                            var header =
                                button.closest(
                                    'th'
                                );

                            if (header) {
                                header.setAttribute(
                                    'aria-sort',
                                    direction
                                    === 'asc'
                                        ? 'ascending'
                                        : 'descending'
                                );
                            }

                            var rows =
                                Array.from(
                                    tbody.querySelectorAll(
                                        'tr'
                                    )
                                );

                            rows.sort(
                                function (
                                    leftRow,
                                    rightRow
                                ) {
                                    var result =
                                        compareValues(
                                            readSortValue(
                                                leftRow,
                                                index,
                                                type
                                            ),
                                            readSortValue(
                                                rightRow,
                                                index,
                                                type
                                            ),
                                            type
                                        );

                                    return direction
                                    === 'asc'
                                        ? result
                                        : -result;
                                }
                            );

                            rows.forEach(
                                function (row) {
                                    tbody.appendChild(
                                        row
                                    );
                                }
                            );
                        }
                    );
                }
            );

            searchInput.addEventListener(
                'input',
                applySearch
            );

            applySearch();
        }
    );
</script>

