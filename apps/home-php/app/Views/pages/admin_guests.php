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
                                        <th style="width: 14%;">Gość</th>
                                        <th style="width: 18%;">Kontakt</th>
                                        <th style="width: 19%;">Adres</th>
                                        <th style="width: 8%;">Kraj</th>
                                        <th style="width: 8%;">Źródło</th>
                                        <th style="width: 9%;">Utworzono</th>
                                        <th style="width: 10%;">Ostatnia wizyta</th>
                                        <th style="width: 14%;">Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($guests as $guest): ?>
                                        <tr>
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

                                            <td>
                                                <span class="guest-list-muted">
                                                    <?= htmlspecialchars(formatDateForDisplay($guest['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
