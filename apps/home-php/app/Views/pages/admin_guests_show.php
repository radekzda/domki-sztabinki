<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     notes: string|null,
 *     created_at: string
 * } $guest
 * @var array<int, array{
 *     id: int,
 *     cabin_id: int,
 *     guest_id: int|null,
 *     cabin_name: string|null,
 *     linked_guest_name: string|null,
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
?>
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
                                <?= htmlspecialchars($guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars($guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
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

                    <div class="status-list">
                        <div class="status-row">
                            <span>Imię i nazwisko</span>
                            <strong>
                                <?= htmlspecialchars($guest['first_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars($guest['last_name'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars($guest['email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($guest['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Miejscowość</span>
                            <strong><?= htmlspecialchars($guest['city'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Kraj</span>
                            <strong><?= htmlspecialchars($guest['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>VIP</span>
                            <strong><?= $guest['is_vip'] === 1 ? 'Tak' : 'Nie' ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars($guest['source'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Utworzono</span>
                            <strong><?= htmlspecialchars(formatDateForDisplay($guest['created_at']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <?php if ($guest['notes'] !== null && $guest['notes'] !== ''): ?>
                        <div class="empty-state">
                            <strong>Notatki</strong>

                            <p>
                                <?= nl2br(htmlspecialchars($guest['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="admin-actions">
                        <form method="post" action="/admin/goscie/vip">
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

                            <button class="button button--primary" type="submit">
                                <?= $guest['is_vip'] === 1 ? 'Usuń oznaczenie VIP' : 'Oznacz jako VIP' ?>
                            </button>
                        </form>

                        <form
                            method="post"
                            action="/admin/goscie/usun"
                            onsubmit="return confirm('Czy na pewno usunąć tego gościa? Powiązane rezerwacje zostaną odłączone od karty gościa, ale nie zostaną usunięte.')"
                        >
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
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <br>

                                                <span>
                                                    <?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?>
                                                    noc.
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($statusLabels[$reservation['status']] ?? $reservation['status'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($paymentLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : '—'), ENT_QUOTES, 'UTF-8') ?>
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