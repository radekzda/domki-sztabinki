<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, mixed> $guest
 * @var array<int, array<string, mixed>> $reservations
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
                            <span>Narodowość</span>
                            <strong><?= htmlspecialchars($displayValue($guest['nationality'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
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
                            <span>VIP</span>
                            <strong><?= (int) $guest['is_vip'] === 1 ? 'Tak' : 'Nie' ?></strong>
                        </div>

                        <div class="status-row">
                            <span>Źródło</span>
                            <strong><?= htmlspecialchars((string) $guest['source'], ENT_QUOTES, 'UTF-8') ?></strong>
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