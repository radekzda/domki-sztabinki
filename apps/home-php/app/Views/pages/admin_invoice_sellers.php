<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<int, array<string, mixed>> $sellers
 * @var string|null $databaseMessage
 * @var string|null $successMessage
 */

$formatAddress = static function (
    array $seller
): string {
    $parts = [];

    $street = trim(
        (string) (
            $seller['street']
            ?? ''
        )
    );

    $postalCode = trim(
        (string) (
            $seller['postal_code']
            ?? ''
        )
    );

    $city = trim(
        (string) (
            $seller['city']
            ?? ''
        )
    );

    $country = trim(
        (string) (
            $seller['country']
            ?? ''
        )
    );

    if ($street !== '') {
        $parts[] = $street;
    }

    $postalCity = trim(
        $postalCode . ' ' . $city
    );

    if ($postalCity !== '') {
        $parts[] = $postalCity;
    }

    if ($country !== '') {
        $parts[] = $country;
    }

    return $parts !== []
        ? implode(', ', $parts)
        : '—';
};
?>

<style>
    .invoice-sellers-table {
        min-width: 980px;
    }

    .invoice-sellers-table td {
        vertical-align: top;
    }

    .invoice-seller-name {
        display: block;
        margin-bottom: 4px;
        font-weight: 800;
        color: #111827;
    }

    .invoice-seller-muted {
        font-size: 12px;
        line-height: 1.45;
        color: #6b7280;
    }

    .invoice-seller-status {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .invoice-seller-status--active {
        background: #dcfce7;
        color: #166534;
    }

    .invoice-seller-status--inactive {
        background: #f1f5f9;
        color: #475569;
    }

    .invoice-seller-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .invoice-seller-actions form {
        margin: 0;
    }

    .invoice-seller-actions .button {
        min-height: 34px;
        padding: 7px 11px;
        font-size: 11px;
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial(
                'partials/admin_sidebar',
                [
                    'active' =>
                        'invoice_sellers',
                ]
            ); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">
                                Faktury
                            </p>

                            <h1>
                                Sprzedawcy faktur
                            </h1>

                            <p>
                                Profile osób lub firm
                                wystawiających faktury.
                                Sprzedawcę przypiszemy
                                później do konkretnego domku.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/sprzedawcy-faktur/nowy"
                            >
                                Dodaj sprzedawcę
                            </a>
                        </div>
                    </div>

                    <?php if (
                        is_string($successMessage)
                        && $successMessage !== ''
                    ): ?>
                        <div class="alert alert--success">
                            <?= htmlspecialchars(
                                $successMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        is_string($databaseMessage)
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

                    <?php if (
                        $databaseMessage === null
                        && $sellers === []
                    ): ?>
                        <div class="empty-state">
                            <strong>
                                Nie ma jeszcze
                                sprzedawców faktur
                            </strong>

                            <p>
                                Dodaj pierwszy profil
                                sprzedawcy. Później
                                przypiszemy go do
                                odpowiednich domków.
                            </p>

                            <a
                                class="button button--primary"
                                href="/admin/sprzedawcy-faktur/nowy"
                            >
                                Dodaj pierwszego sprzedawcę
                            </a>
                        </div>
                    <?php elseif ($sellers !== []): ?>
                        <div class="table-wrapper">
                            <table
                                class="data-table invoice-sellers-table"
                            >
                                <thead>
                                    <tr>
                                        <th>
                                            Sprzedawca
                                        </th>

                                        <th>
                                            Identyfikator
                                        </th>

                                        <th>
                                            Adres
                                        </th>

                                        <th>
                                            Seria
                                        </th>

                                        <th>
                                            Domki
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Akcje
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach (
                                        $sellers
                                        as $seller
                                    ): ?>
                                        <?php
                                        $sellerId = (int) (
                                            $seller['id']
                                            ?? 0
                                        );

                                        $isActive = (int) (
                                            $seller['is_active']
                                            ?? 0
                                        ) === 1;

                                        $taxIdType = trim(
                                            (string) (
                                                $seller[
                                                    'tax_id_type'
                                                ]
                                                ?? ''
                                            )
                                        );

                                        $taxId = trim(
                                            (string) (
                                                $seller['tax_id']
                                                ?? ''
                                            )
                                        );

                                        $email = trim(
                                            (string) (
                                                $seller['email']
                                                ?? ''
                                            )
                                        );
                                        ?>

                                        <tr>
                                            <td>
                                                <span
                                                    class="invoice-seller-name"
                                                >
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $seller['name']
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                                <?php if (
                                                    $email !== ''
                                                ): ?>
                                                    <span
                                                        class="invoice-seller-muted"
                                                    >
                                                        <?= htmlspecialchars(
                                                            $email,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if (
                                                    $taxIdType === 'NONE'
                                                    || $taxId === ''
                                                ): ?>
                                                    —
                                                <?php else: ?>
                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            $taxIdType,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </strong>

                                                    <br>

                                                    <?= htmlspecialchars(
                                                        $taxId,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $formatAddress(
                                                        $seller
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $seller[
                                                                'invoice_series'
                                                            ]
                                                            ?? 'FV'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </strong>

                                                <div
                                                    class="invoice-seller-muted"
                                                >
                                                    np. FV/1/02/2026
                                                </div>
                                            </td>

                                            <td>
                                                <?= (int) (
                                                    $seller[
                                                        'cabins_count'
                                                    ]
                                                    ?? 0
                                                ) ?>
                                            </td>

                                            <td>
                                                <span
                                                    class="invoice-seller-status <?= $isActive
                                                        ? 'invoice-seller-status--active'
                                                        : 'invoice-seller-status--inactive' ?>"
                                                >
                                                    <?= $isActive
                                                        ? 'Aktywny'
                                                        : 'Nieaktywny' ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div
                                                    class="invoice-seller-actions"
                                                >
                                                    <a
                                                        class="button button--secondary"
                                                        href="/admin/sprzedawcy-faktur/edytuj?id=<?= $sellerId ?>"
                                                    >
                                                        Edytuj
                                                    </a>

                                                    <form
                                                        method="post"
                                                        action="/admin/sprzedawcy-faktur/status"
                                                    >
                                                        <?= csrfField() ?>

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= $sellerId ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="is_active"
                                                            value="<?= $isActive
                                                                ? '0'
                                                                : '1' ?>"
                                                        >

                                                        <button
                                                            class="button button--secondary"
                                                            type="submit"
                                                        >
                                                            <?= $isActive
                                                                ? 'Wyłącz'
                                                                : 'Włącz' ?>
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