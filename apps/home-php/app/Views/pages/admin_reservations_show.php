<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array{
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
 *     notes: string|null,
 *     created_at: string
 * } $reservation
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

$paymentStatus = $reservation['payment_status'] ?? '';

$displayValue = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$displayDateTime = static function (mixed $value): string {
    if ($value === null || trim((string) $value) === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable((string) $value))->format('d.m.Y H:i');
    } catch (Throwable $exception) {
        return (string) $value;
    }
};
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

                            <h1>Szczegóły rezerwacji</h1>

                            <p>
                                Podgląd pełnych danych rezerwacji, statusu i płatności.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/rezerwacje/edytuj?id=<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Edytuj
                            </a>

                            <a class="button button--secondary" href="/admin/rezerwacje">
                                Wróć do listy
                            </a>
                        </div>
                    </div>

                    <div class="status-list">
                        
                        <div class="status-row">
                            <span>ID z Base44</span>
                            <strong><?= htmlspecialchars($displayValue($reservation['external_id'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

<div class="status-row">
                            <span>Gość z rezerwacji</span>
                            <strong><?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Powiązana karta gościa</span>
                            <strong>
                                <?php if ($reservation['guest_id'] !== null): ?>
                                    <a href="/admin/goscie/pokaz?id=<?= htmlspecialchars((string) $reservation['guest_id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($reservation['linked_guest_name'] ?? 'Gość #' . $reservation['guest_id'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($reservation['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Domek</span>
                            <strong><?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="status-row">
                            <span>Powiązany domek</span>
                            <strong>
                                <a href="/admin/domki/edytuj?id=<?= htmlspecialchars((string) $reservation['cabin_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    Domek #<?= htmlspecialchars((string) $reservation['cabin_id'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </strong>
                        </div>


                        <div class="status-row">
                            <span>Termin</span>
                            <strong>
                                <?= htmlspecialchars(formatDateForDisplay($reservation['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                —
                                <?= htmlspecialchars(formatDateForDisplay($reservation['end_date']), ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                        <div class="status-row">
                            <span>Godzina przyjazdu</span>
                            <strong><?= htmlspecialchars($displayDateTime($reservation['check_in_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Godzina wyjazdu</span>
                            <strong><?= htmlspecialchars($displayDateTime($reservation['check_out_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>


                        <div class="status-row">
                            <span>Liczba nocy</span>
                            <strong><?= htmlspecialchars((string) $reservation['nights'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Liczba osób</span>
                            <strong>
                                <?= htmlspecialchars((string) $reservation['guests'], ENT_QUOTES, 'UTF-8') ?>
                                os. /
                                dorośli:
                                <?= htmlspecialchars((string) $reservation['adults'], ENT_QUOTES, 'UTF-8') ?>,
                                dzieci:
                                <?= htmlspecialchars((string) $reservation['children'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>

                        <div class="status-row">
                            <span>Status</span>
                            <strong><?= htmlspecialchars($statusLabels[$reservation['status']] ?? $reservation['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Płatność</span>
                            <strong><?= htmlspecialchars($paymentLabels[$paymentStatus] ?? ($paymentStatus !== '' ? $paymentStatus : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Kwota</span>
                            <strong><?= htmlspecialchars(formatMoneyForDisplay($reservation['total_price']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Wpłacono</span>
                            <strong><?= htmlspecialchars(formatMoneyForDisplay($reservation['paid_amount']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars($reservation['source'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Utworzono</span>
                            <strong><?= htmlspecialchars(formatDateForDisplay($reservation['created_at']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <?php if ($reservation['notes'] !== null && $reservation['notes'] !== ''): ?>
                        <div class="empty-state">
                            <strong>Notatki</strong>

                            <p>
                                <?= nl2br(htmlspecialchars($reservation['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="admin-actions">
                        <form method="post" action="/admin/rezerwacje/status">
                            <input
                                type="hidden"
                                name="id"
                                value="<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <select name="status">
                                <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                    <option
                                        value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $reservation['status'] === $statusValue ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="button button--primary" type="submit">
                                Zmień status
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

                            <button class="button button--primary" type="submit">
                                Zmień płatność
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

                                <button class="button button--secondary" type="submit">
                                    Anuluj rezerwację
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

                            <button class="button button--secondary" type="submit">
                                Usuń trwale
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>