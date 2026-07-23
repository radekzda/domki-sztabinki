<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     full_address: string|null,
 *     is_vip: int,
 *     source: string,
 *     created_at: string
 * }> $guests
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */
?>
<style>
    .guests-panel {
        padding: 28px;
    }

    .guests-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .guests-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .guests-panel .page-header p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .guests-panel .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .guests-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    .guests-list-search {
        width: min(100%, 560px);
        margin-top: 16px;
    }

    .guests-list-search input {
        width: 100%;
        min-height: 42px;
        padding: 9px 13px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
    }

    .guests-list-search input:focus {
        outline: 2px solid rgba(37, 99, 235, 0.16);
        outline-offset: 1px;
        border-color: #93c5fd;
    }

    .guests-list-search input::placeholder {
        color: #9ca3af;
    }

    .guest-sort-button {
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

    .guest-sort-button:hover {
        color: #111827;
    }

    .guest-sort-indicator {
        min-width: 12px;
        color: #9ca3af;
        font-size: 10px;
        line-height: 1;
    }

    .guest-sort-button.is-active .guest-sort-indicator {
        color: #2563eb;
    }

    .guests-search-empty {
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
    .guests-table-wrapper {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .guests-table {
        width: 100%;
        min-width: 1050px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .guests-table thead {
        background: #f8fafc;
    }

    .guests-table th {
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
        line-height: 1.2;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
        color: #6b7280;
    }

    .guests-table td {
        padding: 14px;
        border-bottom: 1px solid #edf0f2;
        vertical-align: middle;
        font-size: 13px;
        line-height: 1.35;
        color: #374151;
    }

    .guests-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .guests-table tbody tr {
        transition: background 0.15s ease;
    }

    .guests-table tbody tr:hover {
        background: #fafbfc;
    }

    /*
     * Dane goscia
     */
    .guest-list-name {
        display: block;
        font-size: 14px;
        line-height: 1.3;
        font-weight: 700;
        color: #111827;
    }

    .guest-list-contact {
        display: grid;
        gap: 3px;
        color: #6b7280;
    }

    .guest-list-contact span {
        display: block;
        font-size: 12px;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .guest-list-address {
        font-size: 12px;
        line-height: 1.4;
        color: #6b7280;
        overflow-wrap: anywhere;
    }

    .guest-address-incomplete {
        background: #fff1f2;
    }

    .guest-address-incomplete .guest-list-address {
        color: #9f1239;
    }

    .guest-list-muted {
        font-size: 12px;
        color: #6b7280;
    }

    /*
     * VIP
     */
    .guests-table .status-pill {
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
    .guests-actions {
        min-width: 170px;
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px;
    }

    .guests-actions > a,
    .guests-actions > form {
        min-width: 0;
        margin: 0;
    }

    .guests-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 9px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .guest-delete-button {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .guest-delete-button:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Responsive
     */
    @media (max-width: 1100px) {
        .guests-panel {
            padding: 22px;
        }

        .guests-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .guests-panel .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 700px) {
        .guests-panel {
            padding: 16px;
        }

        .guests-panel .page-header h1 {
            font-size: 27px;
        }

        .guests-panel .page-header__actions {
            display: grid;
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
            width: 100%;
        }

        .guests-panel .page-header__actions .button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'guests']); ?>

            <div class="admin-content">
                <div class="panel guests-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Goście</p>

                            <h1>Goście</h1>

                            <p>
                                Lista gości pobierana z bazy MySQL. Goście mogą być ręcznie dodawani
                                albo tworzeni automatycznie podczas zapisu rezerwacji.
                            </p>

                            <div class="guests-list-search">
                                <input
                                    id="guests-list-search"
                                    type="search"
                                    placeholder="Szukaj gościa…"
                                    aria-label="Szukaj gościa"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/goscie/import">
                                Import
                            </a>

                            <a class="button button--primary" href="/admin/goscie/nowy">
                                Dodaj gościa
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

                    <?php if ($guests === []): ?>
                        <div class="empty-state">
                            <strong>Brak gości do wyświetlenia</strong>

                            <p>
                                Po skonfigurowaniu MySQL i dodaniu pierwszych gości pojawią się tutaj dane kontaktowe,
                                źródło pozyskania oraz historię pobytów.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper guests-table-wrapper">
                            <table class="data-table guests-table">
                                <thead>
                                    <tr>
                                        <th style="width: 14%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="0" data-sort-type="text">
                                                Gość
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 18%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="1" data-sort-type="text">
                                                Kontakt
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 19%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="2" data-sort-type="text">
                                                Adres
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 8%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="3" data-sort-type="text">
                                                Kraj
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 8%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="4" data-sort-type="text">
                                                Źródło
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 9%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="5" data-sort-type="date">
                                                Utworzono
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 10%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="6" data-sort-type="date">
                                                Ostatnia wizyta
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>

                                        <th style="width: 14%;" aria-sort="none">
                                            <button class="guest-sort-button" type="button" data-sort-index="7" data-sort-type="id">
                                                Akcje
                                                <span class="guest-sort-indicator" aria-hidden="true">↕</span>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($guests as $guest): ?>
                                        <tr data-row-id="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td>
                                                <strong class="guest-list-name">
                                                    <?= htmlspecialchars($guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?= htmlspecialchars($guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <div class="guest-list-contact">
                                                    <span>
                                                        <?= htmlspecialchars($guest['email'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>

                                                    <?php if ($guest['phone'] !== null && $guest['phone'] !== ''): ?>
                                                        <span>
                                                            <?= htmlspecialchars($guest['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <?php
                                            $guestAddress = trim(
                                                (string) (
                                                    $guest[
                                                        'full_address'
                                                    ]
                                                    ?? ''
                                                )
                                            );

                                            if (
                                                $guestAddress === ''
                                            ) {
                                                $guestAddress =
                                                    guestFullAddressFromFields(
                                                        (string) (
                                                            $guest[
                                                                'street'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        (string) (
                                                            $guest[
                                                                'postal_code'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        (string) (
                                                            $guest[
                                                                'city'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        (string) (
                                                            $guest[
                                                                'country'
                                                            ]
                                                            ?? ''
                                                        )
                                                    );
                                            }

                                            $missingAddressFields = [];

                                            foreach (
                                                [
                                                    'street' =>
                                                        'ulica',
                                                    'postal_code' =>
                                                        'kod pocztowy',
                                                    'city' =>
                                                        'miejscowość',
                                                ]
                                                as $field =>
                                                    $label
                                            ) {
                                                if (
                                                    trim(
                                                        (string) (
                                                            $guest[
                                                                $field
                                                            ]
                                                            ?? ''
                                                        )
                                                    ) === ''
                                                ) {
                                                    $missingAddressFields[] =
                                                        $label;
                                                }
                                            }

                                            $addressIncomplete =
                                                $missingAddressFields
                                                !== [];

                                            $addressTitle =
                                                $addressIncomplete
                                                    ? 'Braki w adresie: '
                                                        . implode(
                                                            ', ',
                                                            $missingAddressFields
                                                        )
                                                    : '';
                                            ?>

                                            <td
                                                class="<?= $addressIncomplete ? 'guest-address-incomplete' : '' ?>"
                                                <?= $addressTitle !== '' ? 'title="' . htmlspecialchars($addressTitle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                                            >
                                                <span class="guest-list-address">
                                                    <?= htmlspecialchars($guestAddress !== '' ? $guestAddress : '—', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($guest['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <span class="guest-list-muted">
                                                    <?= htmlspecialchars(sourceLabelForDisplay((string) $guest['source']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td data-sort-value="<?= htmlspecialchars((string) ($guest['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="guest-list-muted">
                                                    <?= htmlspecialchars(formatDateForDisplay($guest['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td data-sort-value="<?= htmlspecialchars((string) ($guest['last_visit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="guest-list-muted">
                                                    <?= htmlspecialchars(
                                                        ($guest['last_visit'] ?? null) !== null
                                                            ? formatDateForDisplay((string) $guest['last_visit'])
                                                            : '—',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="guests-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/goscie/pokaz?id=<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Szczegóły
                                                    </a>

                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/goscie/edytuj?id=<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <form method="post" action="/admin/goscie/vip">
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="is_vip"
                                                            value="<?= $guest['is_vip'] === 1 ? '0' : '1' ?>"
                                                        >

                                                        <button class="button button--primary button--small" type="submit">
                                                            <?= $guest['is_vip'] === 1 ? 'Usuń VIP' : 'VIP' ?>
                                                        </button>
                                                    </form>

                                                    <form
                                                        method="post"
                                                        action="/admin/goscie/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć tego gościa? Powiązane rezerwacje zostaną odłączone od karty gościa, ale nie zostaną usunięte.')"
                                                    >
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--small guest-delete-button" type="submit">
                                                            Usuń
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div
                            id="guests-search-empty"
                            class="guests-search-empty"
                        >
                            Brak gości pasujących do wyszukiwania.
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
                    'guests-list-search'
                );
            var table =
                document.querySelector(
                    '.guests-table'
                );
            var emptyState =
                document.getElementById(
                    'guests-search-empty'
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
                        '.guest-sort-button'
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
                                                '.guest-sort-indicator'
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
                                        '.guest-sort-indicator'
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

