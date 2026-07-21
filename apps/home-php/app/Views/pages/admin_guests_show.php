<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, mixed> $guest
 * @var array<int, array<string, mixed>> $reservations
 * @var array<string, mixed> $guestStats
 */

$statusLabels = [
    'PENDING' => 'Oczekuje',
    'CONFIRMED' => 'Potwierdzona',
    'CHECKED_IN' => 'Zameldowany',
    'CHECKED_OUT' => 'Wymeldowany',
    'CANCELLED' => 'Anulowana',
];

$paymentLabels = [
    'PENDING' => 'Oczekuje',
    'PAID' => 'Opłacona',
    'PARTIAL' => 'Częściowa',
    'REFUNDED' => 'Zwrócona',
];

$preferredContactLabels = [
    'PHONE' => 'Telefon',
    'EMAIL' => 'E-mail',
    'SMS' => 'SMS',
    'WHATSAPP' => 'WhatsApp',
];

$displayValue = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$displayDate = static function (mixed $value): string {
    if ($value === null || trim((string) $value) === '') {
        return '—';
    }

    return formatDateForDisplay((string) $value);
};
?>
<style>
    .guest-profile-stats {
        display: grid;
        grid-template-columns: repeat(
            6,
            minmax(0, 1fr)
        );
        gap: 10px;
        margin: 18px 0;
    }

    .guest-profile-stat {
        min-width: 0;
        padding: 12px 14px;
        border: 1px solid rgba(
            15,
            23,
            42,
            0.08
        );
        border-radius: 12px;
        background: #f8fafc;
    }

    .guest-profile-stat:nth-child(1) {
        background: #eff6ff;
    }

    .guest-profile-stat:nth-child(2) {
        background: #f0fdf4;
    }

    .guest-profile-stat:nth-child(3) {
        background: #fefce8;
    }

    .guest-profile-stat:nth-child(4) {
        background: #fff7ed;
    }

    .guest-profile-stat:nth-child(5) {
        background: #faf5ff;
    }

    .guest-profile-stat:nth-child(6) {
        background: #f0fdfa;
    }

    .guest-profile-stat span {
        display: block;
        margin-bottom: 5px;
        font-size: 12px;
        opacity: 0.7;
    }

    .guest-profile-stat strong {
        display: block;
        font-size: 15px;
        line-height: 1.3;
    }

    .guest-profile-stat small {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        line-height: 1.3;
        opacity: 0.7;
    }

    @media (max-width: 1200px) {
        .guest-profile-stats {
            grid-template-columns: repeat(
                3,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 700px) {
        .guest-profile-stats {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }
    }

    @media (max-width: 480px) {
        .guest-profile-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'guests']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Goście</p>

                            <h1>
                                <?= htmlspecialchars((string) $guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) $guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </h1>

                            <p>
                                Szczegóły karty gościa oraz powiązane rezerwacje.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/goscie/edytuj?id=<?= htmlspecialchars((string) $guest['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Edytuj
                            </a>

                            <a class="button button--secondary" href="/admin/goscie">
                                Wróć do listy
                            </a>
                        </div>
                    </div>

                    <div class="guest-profile-stats">
                        <div class="guest-profile-stat">
                            <span>Rezerwacje</span>

                            <strong>
                                <?= (int) (
                                    $guestStats[
                                        'reservations_count'
                                    ]
                                    ?? 0
                                ) ?>
                            </strong>

                            <?php if (
                                (int) (
                                    $guestStats[
                                        'cancelled_count'
                                    ]
                                    ?? 0
                                ) > 0
                            ): ?>
                                <small>
                                    Anulowane:
                                    <?= (int) $guestStats[
                                        'cancelled_count'
                                    ] ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="guest-profile-stat">
                            <span>Zakończone pobyty</span>

                            <strong>
                                <?= (int) (
                                    $guestStats[
                                        'completed_stays'
                                    ]
                                    ?? 0
                                ) ?>
                            </strong>
                        </div>

                        <div class="guest-profile-stat">
                            <span>Łączna wartość</span>

                            <strong>
                                <?= htmlspecialchars(
                                    formatMoneyForDisplay(
                                        $guestStats[
                                            'total_value'
                                        ]
                                        ?? 0
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <div class="guest-profile-stat">
                            <span>Łącznie wpłacono</span>

                            <strong>
                                <?= htmlspecialchars(
                                    formatMoneyForDisplay(
                                        $guestStats[
                                            'total_paid'
                                        ]
                                        ?? 0
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <div class="guest-profile-stat">
                            <span>Ostatni pobyt</span>

                            <?php if (
                                is_array(
                                    $guestStats[
                                        'last_stay'
                                    ]
                                    ?? null
                                )
                            ): ?>
                                <strong>
                                    <?= htmlspecialchars(
                                        formatDateForDisplay(
                                            (string) $guestStats[
                                                'last_stay'
                                            ]['start_date']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $guestStats[
                                                'last_stay'
                                            ]['cabin_name']
                                            ?? 'Domek'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>
                            <?php else: ?>
                                <strong>—</strong>
                            <?php endif; ?>
                        </div>

                        <div class="guest-profile-stat">
                            <span>Najbliższy pobyt</span>

                            <?php if (
                                is_array(
                                    $guestStats[
                                        'next_stay'
                                    ]
                                    ?? null
                                )
                            ): ?>
                                <strong>
                                    <?= htmlspecialchars(
                                        formatDateForDisplay(
                                            (string) $guestStats[
                                                'next_stay'
                                            ]['start_date']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $guestStats[
                                                'next_stay'
                                            ]['cabin_name']
                                            ?? 'Domek'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>
                            <?php else: ?>
                                <strong>—</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="status-list">
                        <div class="status-row">
                            <span>Imię i nazwisko</span>
                            <strong>
                                <?= htmlspecialchars((string) $guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) $guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars((string) $guest['email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($displayValue($guest['phone'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Preferowany kontakt</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $preferredContactLabels[
                                        (string) (
                                            $guest[
                                                'preferred_contact'
                                            ]
                                            ?? ''
                                        )
                                    ]
                                    ?? '—',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>Miejscowość</span>
                            <strong><?= htmlspecialchars($displayValue($guest['city'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Kraj</span>
                            <strong><?= htmlspecialchars($displayValue($guest['country'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Pełny adres</span>
                            <strong><?= htmlspecialchars($displayValue($guest['full_address'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Data urodzenia</span>
                            <strong><?= htmlspecialchars($displayDate($guest['birth_date'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>PESEL</span>
                            <strong><?= htmlspecialchars($displayValue($guest['pesel'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Numer dokumentu</span>
                            <strong><?= htmlspecialchars($displayValue($guest['document_number'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Narodowość</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $displayValue(
                                        $guest[
                                            'nationality'
                                        ]
                                        ?? null
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>VIP</span>
                            <strong><?= (int) $guest['is_vip'] === 1 ? 'Tak' : 'Nie' ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars(sourceLabelForDisplay((string) $guest['source']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>ID z Base44</span>
                            <strong><?= htmlspecialchars($displayValue($guest['external_id'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Utworzono</span>
                            <strong><?= htmlspecialchars(formatDateForDisplay((string) $guest['created_at']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <?php if (
                        ($guest['important_notes'] ?? null) !== null
                        && trim(
                            (string) $guest['important_notes']
                        ) !== ''
                    ): ?>
                        <div class="alert alert--warning">
                            <strong>Ważne informacje o gościu</strong>

                            <br><br>

                            <?= nl2br(
                                htmlspecialchars(
                                    (string) $guest[
                                        'important_notes'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        ($guest['preferences'] ?? null) !== null
                        && trim(
                            (string) $guest['preferences']
                        ) !== ''
                    ): ?>
                        <div class="empty-state">
                            <strong>Preferencje pobytu</strong>

                            <p>
                                <?= nl2br(
                                    htmlspecialchars(
                                        (string) $guest[
                                            'preferences'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (($guest['notes'] ?? null) !== null && (string) $guest['notes'] !== ''): ?>
                        <div class="empty-state">
                            <strong>Notatki</strong>

                            <p>
                                <?= nl2br(htmlspecialchars((string) $guest['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="admin-actions">
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
                                value="<?= (int) $guest['is_vip'] === 1 ? '0' : '1' ?>"
                            >

                            <button class="button button--primary" type="submit">
                                <?= (int) $guest['is_vip'] === 1 ? 'Usuń oznaczenie VIP' : 'Oznacz jako VIP' ?>
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

                            <button class="button button--secondary" type="submit">
                                Usuń gościa
                            </button>
                        </form>
                    </div>

                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Historia</p>

                            <h2>Powiązane rezerwacje</h2>
                        </div>
                    </div>

                    <?php if ($reservations === []): ?>
                        <div class="empty-state">
                            <strong>Brak powiązanych rezerwacji</strong>

                            <p>
                                Ten gość nie ma jeszcze powiązanych rezerwacji.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Termin</th>
                                        <th>Domek</th>
                                        <th>Status</th>
                                        <th>Płatność</th>
                                        <th>Kwota</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <?php $paymentStatus = $reservation['payment_status'] ?? ''; ?>

                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatDateForDisplay((string) $reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay((string) $reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <br>

                                                <span>
                                                    <?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?>
                                                    noc.
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($displayValue($reservation['cabin_name'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($statusLabels[(string) $reservation['status']] ?? (string) $reservation['status'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($paymentLabels[(string) $paymentStatus] ?? ((string) $paymentStatus !== '' ? (string) $paymentStatus : '—'), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <div class="table-actions">
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
