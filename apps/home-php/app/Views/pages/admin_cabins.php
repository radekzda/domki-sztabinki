<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     cleaning_status: string,
 *     cleaning_updated_at: string|null,
 *     sort_order: int,
 *     created_at: string
 * }> $cabins
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

// M13.70 cabin delete messages
if (isset($_GET['deleted'])) {
    $successMessage = 'Domek został usunięty.';
}

if (isset($_GET['delete_blocked'])) {
    $successMessage = 'Nie można usunąć domku, ponieważ ma rezerwacje. Możesz go ukryć.';
}

if (isset($_GET['cleaning_changed'])) {
    $successMessage = 'Status sprzątania domku został zmieniony.';
}

$cleaningStatusLabels = [
    'READY' => 'Gotowy',
    'DIRTY' => 'Do sprzątania',
    'CLEANING' => 'Sprzątanie w toku',
];

?>

<style>
    .cabins-panel {
        padding: 28px;
    }

    .cabins-panel .page-header {
        margin-bottom: 22px;
        align-items: flex-start;
    }

    .cabins-panel .page-header h1 {
        margin: 0 0 8px;
        font-size: 32px;
        line-height: 1.1;
    }

    .cabins-panel .page-header p {
        max-width: 760px;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
        color: #6b7280;
    }

    .cabins-panel .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .cabins-panel .page-header__actions .button {
        min-height: 38px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
    }

    /*
     * Tabela domków
     */
    .cabins-table-wrapper {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.035);
    }

    .cabins-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .cabins-table thead {
        background: #f8fafc;
    }

    .cabins-table th {
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

    .cabins-table td {
        padding: 16px;
        border-bottom: 1px solid #edf0f2;
        vertical-align: middle;
        font-size: 13px;
        line-height: 1.4;
        color: #374151;
    }

    .cabins-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .cabins-table tbody tr {
        transition: background 0.15s ease;
    }

    .cabins-table tbody tr:hover {
        background: #fafbfc;
    }

    /*
     * Nazwa domku
     */
    .cabins-table td:first-child > strong {
        display: block;
        margin-bottom: 5px;
        font-size: 15px;
        color: #111827;
    }

    .cabins-table td:first-child div {
        margin-top: 3px !important;
        font-size: 12px !important;
        line-height: 1.35;
        color: #9ca3af !important;
    }

    /*
     * Parametry i ceny
     */
    .cabins-table td:nth-child(2) > div,
    .cabins-table td:nth-child(3) > div {
        gap: 4px !important;
    }

    .cabins-table td:nth-child(2) span,
    .cabins-table td:nth-child(3) span {
        font-size: 13px;
        line-height: 1.35;
    }

    .cabins-table td:nth-child(2) strong,
    .cabins-table td:nth-child(3) strong {
        color: #111827;
    }

    /*
     * Status
     */
    .cabins-status-cell > div {
        gap: 7px !important;
    }

    .cabins-status-cell .status-pill {
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
    .cabins-actions {
        min-width: 190px;
        display: grid;
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
        gap: 7px;
    }

    .cabins-actions > a,
    .cabins-actions > form {
        min-width: 0;
        margin: 0;
    }

    .cabins-actions .button {
        width: 100%;
        min-height: 34px;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.2;
    }

    .cabins-cleaning-form {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns:
            minmax(0, 1fr)
            auto;
        gap: 7px;
        align-items: center;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #f8fafc;
    }

    .cabins-cleaning-form select {
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

    .cabins-cleaning-form .button {
        width: auto;
        white-space: nowrap;
    }

    .cabins-actions form[action="/admin/domki/status"] {
        grid-column: 1 / 2;
    }

    .cabins-actions form[action="/admin/domki/usun"] {
        grid-column: 2 / 3;
    }

    .button--danger {
        background: #ef4444;
        border-color: #ef4444;
        color: #ffffff;
    }

    .button--danger:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    /*
     * Responsywność
     */
    @media (max-width: 1100px) {
        .cabins-panel {
            padding: 22px;
        }

        .cabins-panel .page-header {
            flex-direction: column;
            gap: 16px;
        }

        .cabins-panel .page-header__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 700px) {
        .cabins-panel {
            padding: 16px;
        }

        .cabins-panel .page-header h1 {
            font-size: 27px;
        }

        .cabins-panel .page-header__actions {
            display: grid;
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
            width: 100%;
        }

        .cabins-panel .page-header__actions .button {
            width: 100%;
            text-align: center;
        }
    }
</style>


<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'cabins']); ?>

            <div class="admin-content">
                <div class="panel cabins-panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Domki</p>

                            <h1>Domki</h1>

                            <p>
                                Lista domków pobierana jest z bazy MySQL. Z tego miejsca możesz edytować domek,
                                zarządzać zdjęciami oraz aktywować lub ukrywać domek na stronie publicznej.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/domki/import">
                                Import
                            </a>

                            <a class="button button--primary" href="/admin/domki/nowy">
                                Dodaj domek
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

                    <?php if ($cabins === []): ?>
                        <div class="empty-state">
                            <strong>Brak domków do wyświetlenia</strong>

                            <p>
                                Jeżeli baza danych nie jest jeszcze skonfigurowana, ustaw dane MySQL w pliku
                                <strong>.env</strong>, uruchom instalator struktury bazy i później dodasz pierwszy domek.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper cabins-table-wrapper">
                            <table class="data-table cabins-table">
                                <thead>
                                    <tr>
                                        <th style="width: 23%;">Domek</th>
                                        <th style="width: 18%;">Parametry</th>
                                        <th style="width: 21%;">Ceny</th>
                                        <th style="width: 14%;">Status</th>
                                        <th style="width: 24%;">Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cabins as $cabin): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($cabin['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div style="margin-top: 6px; color: #6b7280; font-size: 13px;">
                                                    Skrót:
                                                    <?= htmlspecialchars($cabin['short_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                                </div>

                                                <div style="margin-top: 6px; color: #6b7280; font-size: 13px;">
                                                    Kolejność:
                                                    <?= htmlspecialchars((string) $cabin['sort_order'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: grid; gap: 6px;">
                                                    <span>
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['max_guests'], ENT_QUOTES, 'UTF-8') ?>
                                                        </strong>
                                                        osób
                                                    </span>

                                                    <span>
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['bedrooms'], ENT_QUOTES, 'UTF-8') ?>
                                                        </strong>
                                                        sypialnie
                                                    </span>

                                                    <span>
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['bathrooms'], ENT_QUOTES, 'UTF-8') ?>
                                                        </strong>
                                                        łazienka
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: grid; gap: 6px;">
                                                    <span>
                                                        Domyślna:
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['price_per_night'], ENT_QUOTES, 'UTF-8') ?>
                                                            zł
                                                        </strong>
                                                    </span>

                                                    <span>
                                                        1 noc:
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['price_one_night'], ENT_QUOTES, 'UTF-8') ?>
                                                            zł
                                                        </strong>
                                                    </span>

                                                    <span>
                                                        7+ nocy:
                                                        <strong>
                                                            <?= htmlspecialchars((string) $cabin['price_seven_plus_nights'], ENT_QUOTES, 'UTF-8') ?>
                                                            zł
                                                        </strong>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="cabins-status-cell">
                                                <div style="display: grid; gap: 8px;">
                                                    <div>
                                                        <?php if ($cabin['is_active'] === 1): ?>
                                                            <span class="status-pill status-pill--success">
                                                                Aktywny
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-pill status-pill--muted">
                                                                Ukryty
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php
                                                    $cleaningStatus = (string) (
                                                        $cabin['cleaning_status']
                                                        ?? 'READY'
                                                    );

                                                    $cleaningLabel = $cleaningStatusLabels[
                                                        $cleaningStatus
                                                    ] ?? $cleaningStatus;
                                                    ?>

                                                    <div>
                                                        <?php if ($cleaningStatus === 'READY'): ?>
                                                            <span
                                                                class="status-pill status-pill--success"
                                                                style="background: #dcfce7; color: #166534;"
                                                            >
                                                                <?= htmlspecialchars(
                                                                    $cleaningLabel,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>
                                                            </span>
                                                        <?php elseif ($cleaningStatus === 'DIRTY'): ?>
                                                            <span
                                                                class="status-pill"
                                                                style="background: #fee2e2; color: #991b1b;"
                                                            >
                                                                <?= htmlspecialchars(
                                                                    $cleaningLabel,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span
                                                                class="status-pill"
                                                                style="background: #fef3c7; color: #92400e;"
                                                            >
                                                                <?= htmlspecialchars(
                                                                    $cleaningLabel,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="cabins-actions">
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/domki/edytuj?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/domki/zdjecia?id=<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Zdjęcia
                                                    </a>

                                                    <form
                                                        class="cabins-cleaning-form"
                                                        method="post"
                                                        action="/admin/domki/sprzatanie"
                                                    >
                                                        <?= csrfField() ?>

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select
                                                            name="cleaning_status"
                                                            style="width: 100%;"
                                                        >
                                                            <option
                                                                value="READY"
                                                                <?= ($cabin['cleaning_status'] ?? 'READY') === 'READY' ? 'selected' : '' ?>
                                                            >
                                                                Gotowy
                                                            </option>

                                                            <option
                                                                value="DIRTY"
                                                                <?= ($cabin['cleaning_status'] ?? '') === 'DIRTY' ? 'selected' : '' ?>
                                                            >
                                                                Do sprzątania
                                                            </option>

                                                            <option
                                                                value="CLEANING"
                                                                <?= ($cabin['cleaning_status'] ?? '') === 'CLEANING' ? 'selected' : '' ?>
                                                            >
                                                                Sprzątanie w toku
                                                            </option>
                                                        </select>

                                                        <button
                                                            class="button button--secondary button--small"
                                                            type="submit"
                                                            style="width: 100%;"
                                                        >
                                                            Zapisz
                                                        </button>
                                                    </form>

                                                    <form method="post" action="/admin/domki/status">
    <?= csrfField() ?>
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <?php if ($cabin['is_active'] === 1): ?>
                                                            <input type="hidden" name="is_active" value="0">

                                                            <button class="button button--secondary button--small" type="submit" style="width: 100%;">
                                                                Ukryj
                                                            </button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="is_active" value="1">

                                                            <button class="button button--primary button--small" type="submit" style="width: 100%;">
                                                                Aktywuj
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>

                                                    <form
                                                        method="post"
                                                        action="/admin/domki/usun"
                                                        onsubmit="return confirm('Czy na pewno usunąć ten domek? Tej operacji nie można cofnąć.')"
                                                    >
                                                        <?= csrfField() ?>

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $cabin['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--danger button--small" type="submit" style="width: 100%;">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
