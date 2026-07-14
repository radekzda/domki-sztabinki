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
                        <div class="table-wrapper">
                            <table class="data-table" style="min-width: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Termin</th>
                                        <th style="width: 22%;">Gość</th>
                                        <th style="width: 13%;">Domek</th>
                                        <th style="width: 15%;">Status / płatność</th>
                                        <th style="width: 13%;">Kwota</th>
                                        <th style="width: 19%;">Akcje</th>
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

                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                    —
                                                    <?= htmlspecialchars(formatDateForDisplay($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div style="margin-top: 6px; color: #6b7280;">
                                                    <?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?>
                                                    noc.
                                                </div>

                                                <div style="margin-top: 6px; color: #6b7280;">
                                                    Źródło:
                                                    <strong><?= htmlspecialchars($reservation['source'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                </div>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div style="margin-top: 6px; color: #6b7280;">
                                                    <?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>

                                                <?php if ($reservation['phone'] !== null && $reservation['phone'] !== ''): ?>
                                                    <div style="margin-top: 4px; color: #6b7280;">
                                                        <?= htmlspecialchars($reservation['phone'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div style="margin-top: 8px;">
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
                                                <strong>
                                                    <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <div style="display: grid; gap: 8px;">
                                                    <?php if ($status === 'CONFIRMED' || $status === 'CHECKED_IN'): ?>
                                                        <span class="status-pill status-pill--success">
                                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-pill status-pill--muted">
                                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <span style="color: #6b7280;">
                                                        Płatność:
                                                        <strong><?= htmlspecialchars($paymentText, ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?>
                                                </strong>

                                                <div style="margin-top: 6px; color: #6b7280;">
                                                    wpłacono:
                                                    <strong>
                                                        <?= htmlspecialchars(formatMoneyForDisplay($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>
                                                </div>
                                            </td>

                                            <td>
                                                <div style="display: grid; gap: 10px; min-width: 190px;">
                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
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

                                                    <form method="post" action="/admin/rezerwacje/status" style="display: grid; grid-template-columns: 1fr auto; gap: 8px; margin: 0;">
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

                                                    <form method="post" action="/admin/rezerwacje/platnosc" style="display: grid; grid-template-columns: 1fr auto; gap: 8px; margin: 0;">
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
                                                            Płatność
                                                        </button>
                                                    </form>

                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                        <?php if ($reservation['status'] !== 'CANCELLED'): ?>
                                                            <form
                                                                method="post"
                                                                action="/admin/rezerwacje/anuluj"
                                                                onsubmit="return confirm('Czy na pewno anulować tę rezerwację?')"
                                                                style="margin: 0;"
                                                            >
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
                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            >

                                                            <button class="button button--secondary button--small" type="submit" style="width: 100%;">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>