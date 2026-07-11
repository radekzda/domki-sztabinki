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
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Rezerwacje</h1>

                            <p>
                                Lista rezerwacji pobierana z bazy MySQL. Możesz dodawać, edytować,
                                anulować i obsługiwać płatności.
                            </p>
                        </div>

                        <div class="page-header__actions">
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
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Termin</th>
                                        <th>Gość</th>
                                        <th>Domek</th>
                                        <th>Osoby</th>
                                        <th>Status</th>
                                        <th>Płatność</th>
                                        <th>Kwota</th>
                                        <th>Źródło</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <?php
                                        $status = $reservation['status'];
                                        $paymentStatus = $reservation['payment_status'] ?? '';
                                        ?>

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
                                                <strong>
                                                    <?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <br>

                                                <span>
                                                    <?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>

                                                <?php if ($reservation['phone'] !== null && $reservation['phone'] !== ''): ?>
                                                    <br>

                                                    <span>
                                                        <?= htmlspecialchars($reservation['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars((string) $reservation['guests'], ENT_QUOTES, 'UTF-8') ?>
                                                os.

                                                <br>

                                                <span>
                                                    dorośli:
                                                    <?= htmlspecialchars((string) $reservation['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                                    dzieci:
                                                    <?= htmlspecialchars((string) $reservation['children'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($status === 'CONFIRMED' || $status === 'CHECKED_IN'): ?>
                                                    <span class="status-pill status-pill--success">
                                                        <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-pill status-pill--muted">
                                                        <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($paymentLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : '—'), ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <br>

                                                <span>
                                                    wpłacono:
                                                    <?= htmlspecialchars(formatMoneyForDisplay($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($reservation['source'], ENT_QUOTES, 'UTF-8') ?>
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

                                                    <form method="post" action="/admin/rezerwacje/status">
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="status">
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
                                                            Status
                                                        </button>
                                                    </form>

                                                    <form method="post" action="/admin/rezerwacje/platnosc">
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <select name="payment_status">
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
                                                            Płatność
                                                        </button>
                                                    </form>

                                                    <?php if ($reservation['status'] !== 'CANCELLED'): ?>
                                                        <form
                                                            method="post"
                                                            action="/admin/rezerwacje/anuluj"
                                                            onsubmit="return confirm('Czy na pewno anulować tę rezerwację?')"
                                                        >
                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            >

                                                            <button class="button button--secondary button--small" type="submit">
                                                                Anuluj
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form
                                                        method="post"
                                                        action="/admin/rezerwacje/usun"
                                                        onsubmit="return confirm('Czy na pewno trwale usunąć tę rezerwację? Tej operacji nie można cofnąć.')"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                        >

                                                        <button class="button button--secondary button--small" type="submit">
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