<?php

declare(strict_types=1);

/**
 * @var string $title
 */

$monthParam = isset($_GET['month']) && is_string($_GET['month'])
    ? trim($_GET['month'])
    : '';

if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}

$monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $monthParam . '-01');

if (!$monthStart instanceof DateTimeImmutable) {
    $monthStart = new DateTimeImmutable(date('Y-m-01'));
}

$monthValue = $monthStart->format('Y-m');
$monthEnd = $monthStart->modify('last day of this month');
$nextMonthStart = $monthStart->modify('first day of next month');

$calendarStart = $monthStart;

while ((int) $calendarStart->format('N') !== 1) {
    $calendarStart = $calendarStart->modify('-1 day');
}

$calendarEnd = $monthEnd;

while ((int) $calendarEnd->format('N') !== 7) {
    $calendarEnd = $calendarEnd->modify('+1 day');
}

$previousMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');

$monthNames = [
    '01' => 'styczeń',
    '02' => 'luty',
    '03' => 'marzec',
    '04' => 'kwiecień',
    '05' => 'maj',
    '06' => 'czerwiec',
    '07' => 'lipiec',
    '08' => 'sierpień',
    '09' => 'wrzesień',
    '10' => 'październik',
    '11' => 'listopad',
    '12' => 'grudzień',
];

$dayNames = [
    'Pon',
    'Wt',
    'Śr',
    'Czw',
    'Pt',
    'Sob',
    'Nd',
];

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

$reservations = [];
$monthReservations = [];
$databaseMessage = null;

if (!Database::canAttemptConnection()) {
    $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Kalendarz zostanie pokazany po ustawieniu danych MySQL w pliku .env.';
} else {
    try {
        $reservations = ReservationRepository::all();
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać rezerwacji do kalendarza: ' . $exception->getMessage();
    }
}

$monthStartDate = $monthStart->format('Y-m-d');
$nextMonthStartDate = $nextMonthStart->format('Y-m-d');

foreach ($reservations as $reservation) {
    $startDate = substr($reservation['start_date'], 0, 10);
    $endDate = substr($reservation['end_date'], 0, 10);

    if ($startDate < $nextMonthStartDate && $endDate > $monthStartDate) {
        $monthReservations[] = $reservation;
    }
}

$arrivalsCount = 0;
$departuresCount = 0;
$activeBlockingCount = 0;
$cancelledCount = 0;

foreach ($monthReservations as $reservation) {
    $startDate = substr($reservation['start_date'], 0, 10);
    $endDate = substr($reservation['end_date'], 0, 10);

    if ($startDate >= $monthStartDate && $startDate < $nextMonthStartDate) {
        $arrivalsCount++;
    }

    if ($endDate >= $monthStartDate && $endDate < $nextMonthStartDate) {
        $departuresCount++;
    }

    if (in_array($reservation['status'], ['PENDING', 'CONFIRMED', 'CHECKED_IN'], true)) {
        $activeBlockingCount++;
    }

    if ($reservation['status'] === 'CANCELLED') {
        $cancelledCount++;
    }
}

$getItemsForDate = static function (string $date) use ($monthReservations): array {
    $items = [];

    foreach ($monthReservations as $reservation) {
        $startDate = substr($reservation['start_date'], 0, 10);
        $endDate = substr($reservation['end_date'], 0, 10);

        if ($date === $startDate) {
            $items[] = [
                'type' => 'Przyjazd',
                'reservation' => $reservation,
            ];

            continue;
        }

        if ($date === $endDate) {
            $items[] = [
                'type' => 'Wyjazd',
                'reservation' => $reservation,
            ];

            continue;
        }

        if ($date > $startDate && $date < $endDate) {
            $items[] = [
                'type' => 'Pobyt',
                'reservation' => $reservation,
            ];
        }
    }

    return $items;
};

$getStatusClass = static function (string $status): string {
    if (in_array($status, ['CONFIRMED', 'CHECKED_IN'], true)) {
        return 'status-pill status-pill--success';
    }

    return 'status-pill status-pill--muted';
};

$calendarWeeks = [];
$currentDay = $calendarStart;

while ($currentDay <= $calendarEnd) {
    $week = [];

    for ($day = 1; $day <= 7; $day++) {
        $week[] = $currentDay;
        $currentDay = $currentDay->modify('+1 day');
    }

    $calendarWeeks[] = $week;
}

$monthTitle = ($monthNames[$monthStart->format('m')] ?? $monthStart->format('m')) . ' ' . $monthStart->format('Y');
$today = date('Y-m-d');
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'calendar']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Kalendarz</p>

                            <h1>Kalendarz rezerwacji</h1>

                            <p>
                                Widok miesięczny pokazuje przyjazdy, wyjazdy i pobyty według danych z MySQL.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/kalendarz?month=<?= htmlspecialchars($previousMonth, ENT_QUOTES, 'UTF-8') ?>">
                                Poprzedni
                            </a>

                            <a class="button button--secondary" href="/admin/kalendarz">
                                Bieżący
                            </a>

                            <a class="button button--secondary" href="/admin/kalendarz?month=<?= htmlspecialchars($nextMonth, ENT_QUOTES, 'UTF-8') ?>">
                                Następny
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="dashboard-grid">
                        <div class="stat-card">
                            <span>Miesiąc</span>
                            <strong><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="stat-card">
                            <span>Rezerwacje w miesiącu</span>
                            <strong><?= htmlspecialchars((string) count($monthReservations), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="stat-card">
                            <span>Przyjazdy</span>
                            <strong><?= htmlspecialchars((string) $arrivalsCount, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="stat-card">
                            <span>Wyjazdy</span>
                            <strong><?= htmlspecialchars((string) $departuresCount, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="stat-card">
                            <span>Blokujące termin</span>
                            <strong><?= htmlspecialchars((string) $activeBlockingCount, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="stat-card">
                            <span>Anulowane</span>
                            <strong><?= htmlspecialchars((string) $cancelledCount, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <?php if ($monthReservations === []): ?>
                        <div class="empty-state">
                            <strong>Brak rezerwacji w tym miesiącu</strong>

                            <p>
                                Po dodaniu rezerwacji w wybranym miesiącu pojawią się tutaj przyjazdy,
                                wyjazdy oraz dni pobytu.
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php foreach ($dayNames as $dayName): ?>
                                        <th style="width: 14.285%;">
                                            <?= htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($calendarWeeks as $week): ?>
                                    <tr>
                                        <?php foreach ($week as $day): ?>
                                            <?php
                                            $dateValue = $day->format('Y-m-d');
                                            $isCurrentMonth = $day->format('Y-m') === $monthValue;
                                            $isToday = $dateValue === $today;
                                            $items = $getItemsForDate($dateValue);
                                            ?>

                                            <td style="vertical-align: top; min-width: 150px; height: 130px; <?= $isCurrentMonth ? '' : 'opacity: 0.45;' ?>">
                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars($day->format('d'), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>

                                                    <?php if ($isToday): ?>
                                                        <span class="status-pill status-pill--success">dziś</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($items === []): ?>
                                                    <div style="margin-top: 10px;">
                                                        <span>—</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="margin-top: 10px; display: grid; gap: 8px;">
                                                        <?php foreach ($items as $item): ?>
                                                            <?php
                                                            $reservation = $item['reservation'];
                                                            $status = $reservation['status'];
                                                            $statusLabel = $statusLabels[$status] ?? $status;
                                                            ?>

                                                            <div style="border-top: 1px solid #e5e7eb; padding-top: 8px;">
                                                                <div>
                                                                    <span class="<?= htmlspecialchars($getStatusClass($status), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?>
                                                                    </span>
                                                                </div>

                                                                <div style="margin-top: 4px;">
                                                                    <a href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservation['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                                        <strong>
                                                                            <?= htmlspecialchars($reservation['guest_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                        </strong>
                                                                    </a>
                                                                </div>

                                                                <div>
                                                                    <span>
                                                                        <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                                                    </span>
                                                                </div>

                                                                <div>
                                                                    <span>
                                                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($monthReservations !== []): ?>
                        <div class="page-header">
                            <div>
                                <p class="eyebrow">Lista</p>

                                <h2>Rezerwacje w miesiącu</h2>
                            </div>
                        </div>

                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Termin</th>
                                        <th>Gość</th>
                                        <th>Domek</th>
                                        <th>Status</th>
                                        <th>Płatność</th>
                                        <th>Kwota</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($monthReservations as $reservation): ?>
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
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($reservation['cabin_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>

                                            <td>
                                                <span class="<?= htmlspecialchars($getStatusClass($status), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
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