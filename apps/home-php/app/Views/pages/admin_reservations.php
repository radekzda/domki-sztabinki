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

function formatReservationDate(string $date): string
{
    if ($date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y', $timestamp);
}

function formatReservationMoney(?string $amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }

    return number_format((float) $amount, 0, ',', ' ') . ' zł';
}
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
                                Lista rezerwacji będzie pobierana z bazy MySQL. Możesz dodawać pobyty ręcznie
                                z panelu administratora.
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
                                                    <?= htmlspecialchars(formatReservationDate($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatReservationDate($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
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
                                                <?php elseif ($status === 'CANCELLED'): ?>
                                                    <span class="status-pill status-pill--muted">
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
                                                    <?= htmlspecialchars(formatReservationMoney($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <br>

                                                <span>
                                                    wpłacono:
                                                    <?= htmlspecialchars(formatReservationMoney($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($reservation['source'], ENT_QUOTES, 'UTF-8') ?>
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