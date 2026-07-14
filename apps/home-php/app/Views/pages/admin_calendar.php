<?php

declare(strict_types=1);

/**
 * @var string $title
 */

$databaseMessage = null;
$cabins = [];
$reservations = [];

$requestedMonth = isset($_GET['month'])
    ? (string) $_GET['month']
    : date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
    $requestedMonth = date('Y-m');
}

try {
    $monthStart = new DateTimeImmutable($requestedMonth . '-01');
} catch (Throwable $exception) {
    $monthStart = new DateTimeImmutable(date('Y-m-01'));
}

$monthEnd = $monthStart->modify('first day of next month');
$previousMonth = $monthStart->modify('-1 month')->format('Y-m');
$currentMonth = date('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');

$monthStartString = $monthStart->format('Y-m-d');
$monthEndString = $monthEnd->format('Y-m-d');
$daysInMonth = (int) $monthStart->format('t');

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

$weekdayShortNames = [
    '1' => 'Pn',
    '2' => 'Wt',
    '3' => 'Śr',
    '4' => 'Cz',
    '5' => 'Pt',
    '6' => 'So',
    '7' => 'Nd',
];

$monthLabel = ($monthNames[$monthStart->format('m')] ?? $monthStart->format('m'))
    . ' '
    . $monthStart->format('Y');

$days = [];

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = $monthStart->setDate(
        (int) $monthStart->format('Y'),
        (int) $monthStart->format('m'),
        $day
    );

    $days[] = [
        'date' => $date->format('Y-m-d'),
        'day' => $date->format('d'),
        'weekday' => $weekdayShortNames[$date->format('N')] ?? '',
        'is_weekend' => in_array($date->format('N'), ['6', '7'], true),
        'is_today' => $date->format('Y-m-d') === date('Y-m-d'),
    ];
}

if (!Database::canAttemptConnection()) {
    $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana.';
} else {
    try {
        $allCabins = CabinRepository::all();

        foreach ($allCabins as $cabin) {
            if ((int) ($cabin['is_active'] ?? 0) !== 1) {
                continue;
            }

            $cabins[] = $cabin;
        }

        $reservations = ReservationRepository::all();
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać danych kalendarza: ' . $exception->getMessage();
        $cabins = [];
        $reservations = [];
    }
}

$statusLabels = [
    'PENDING' => 'Oczekuje',
    'CONFIRMED' => 'Potwierdzona',
    'CHECKED_IN' => 'Zameldowany',
    'CHECKED_OUT' => 'Wymeldowany',
    'COMPLETED' => 'Wymeldowany',
    'CANCELLED' => 'Anulowana',
];

$statusClasses = [
    'PENDING' => 'calendar-status--pending',
    'CONFIRMED' => 'calendar-status--confirmed',
    'CHECKED_IN' => 'calendar-status--checked-in',
    'CHECKED_OUT' => 'calendar-status--checked-out',
    'COMPLETED' => 'calendar-status--checked-out',
    'CANCELLED' => 'calendar-status--cancelled',
];

$blockingStatuses = [
    'PENDING',
    'CONFIRMED',
    'CHECKED_IN',
];

$calendarByCabin = [];
$monthReservations = [];
$arrivals = 0;
$departures = 0;
$blockingReservations = 0;
$cancelledReservations = 0;

foreach ($cabins as $cabin) {
    $calendarByCabin[(int) $cabin['id']] = [];
}

foreach ($reservations as $reservation) {
    $reservationCabinId = (int) ($reservation['cabin_id'] ?? 0);
    $reservationStatus = (string) ($reservation['status'] ?? '');
    $reservationStart = substr((string) ($reservation['start_date'] ?? ''), 0, 10);
    $reservationEnd = substr((string) ($reservation['end_date'] ?? ''), 0, 10);

    if ($reservationCabinId < 1 || $reservationStart === '' || $reservationEnd === '') {
        continue;
    }

    if ($reservationEnd <= $monthStartString || $reservationStart >= $monthEndString) {
        continue;
    }

    $monthReservations[] = $reservation;

    if ($reservationStart >= $monthStartString && $reservationStart < $monthEndString) {
        $arrivals++;
    }

    if ($reservationEnd > $monthStartString && $reservationEnd <= $monthEndString) {
        $departures++;
    }

    if (in_array($reservationStatus, $blockingStatuses, true)) {
        $blockingReservations++;
    }

    if ($reservationStatus === 'CANCELLED') {
        $cancelledReservations++;
    }

    if (!isset($calendarByCabin[$reservationCabinId])) {
        continue;
    }

    $rangeStartString = $reservationStart > $monthStartString
        ? $reservationStart
        : $monthStartString;

    $rangeEndString = $reservationEnd < $monthEndString
        ? $reservationEnd
        : $monthEndString;

    try {
        $currentDate = new DateTimeImmutable($rangeStartString);
        $rangeEndDate = new DateTimeImmutable($rangeEndString);
    } catch (Throwable $exception) {
        continue;
    }

    while ($currentDate <= $rangeEndDate) {
        $dateKey = $currentDate->format('Y-m-d');

        if ($dateKey < $monthStartString || $dateKey >= $monthEndString) {
            $currentDate = $currentDate->modify('+1 day');
            continue;
        }

        $dayType = 'STAY';

        if ($dateKey === $reservationStart) {
            $dayType = 'ARRIVAL';
        } elseif ($dateKey === $reservationEnd) {
            $dayType = 'DEPARTURE';
        }

        if ($dayType === 'DEPARTURE' && $reservationEnd >= $monthEndString) {
            $currentDate = $currentDate->modify('+1 day');
            continue;
        }

        if (!isset($calendarByCabin[$reservationCabinId][$dateKey])) {
            $calendarByCabin[$reservationCabinId][$dateKey] = [];
        }

        $calendarByCabin[$reservationCabinId][$dateKey][] = [
            'reservation' => $reservation,
            'type' => $dayType,
        ];

        $currentDate = $currentDate->modify('+1 day');
    }
}

usort($monthReservations, static function (array $first, array $second): int {
    return strcmp((string) ($first['start_date'] ?? ''), (string) ($second['start_date'] ?? ''));
});

$formatDate = static function (string $date): string {
    if ($date === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($date))->format('d.m.Y');
    } catch (Throwable $exception) {
        return $date;
    }
};

$statusLabel = static function (string $status) use ($statusLabels): string {
    return $statusLabels[$status] ?? $status;
};

$statusClass = static function (string $status) use ($statusClasses): string {
    return $statusClasses[$status] ?? 'calendar-status--pending';
};

$reservationGuestName = static function (array $reservation): string {
    $guestName = trim((string) ($reservation['guest_name'] ?? ''));

    if ($guestName !== '') {
        return $guestName;
    }

    $linkedGuestName = trim((string) ($reservation['linked_guest_name'] ?? ''));

    if ($linkedGuestName !== '') {
        return $linkedGuestName;
    }

    return 'Gość';
};

$reservationCabinName = static function (array $reservation): string {
    $cabinName = trim((string) ($reservation['cabin_name'] ?? ''));

    if ($cabinName !== '') {
        return $cabinName;
    }

    return 'Domek';
};

$cellTitle = static function (array $reservation) use ($reservationGuestName, $formatDate, $statusLabel): string {
    $guest = $reservationGuestName($reservation);
    $start = $formatDate(substr((string) ($reservation['start_date'] ?? ''), 0, 10));
    $end = $formatDate(substr((string) ($reservation['end_date'] ?? ''), 0, 10));
    $status = $statusLabel((string) ($reservation['status'] ?? ''));

    return $guest . ' | ' . $start . ' - ' . $end . ' | ' . $status;
};

$summaryCards = [
    [
        'label' => 'Miesiąc',
        'value' => $monthLabel,
    ],
    [
        'label' => 'Rezerwacje',
        'value' => (string) count($monthReservations),
    ],
    [
        'label' => 'Przyjazdy',
        'value' => (string) $arrivals,
    ],
    [
        'label' => 'Wyjazdy',
        'value' => (string) $departures,
    ],
    [
        'label' => 'Blokujące',
        'value' => (string) $blockingReservations,
    ],
    [
        'label' => 'Anulowane',
        'value' => (string) $cancelledReservations,
    ],
];
?>

<style>
    .pms-calendar-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        margin-top: 24px;
    }

    .pms-calendar-summary {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
        margin-top: 24px;
    }

    .pms-calendar-summary__card {
        border: 1px solid var(--color-border);
        border-radius: 16px;
        background: #ffffff;
        padding: 14px 16px;
    }

    .pms-calendar-summary__card span {
        display: block;
        color: var(--color-muted);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pms-calendar-summary__card strong {
        display: block;
        margin-top: 6px;
        color: var(--color-text);
        font-size: 18px;
    }

    .pms-calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .pms-calendar-legend__item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--color-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .pms-calendar-legend__dot {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        display: inline-block;
    }

    .pms-calendar-table-wrap {
        margin-top: 24px;
        overflow-x: auto;
        border: 1px solid var(--color-border);
        border-radius: 18px;
        background: #ffffff;
    }

    .pms-calendar-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .pms-calendar-table th,
    .pms-calendar-table td {
        border-bottom: 1px solid var(--color-border);
        border-right: 1px solid var(--color-border);
        text-align: center;
        vertical-align: middle;
    }

    .pms-calendar-table th:last-child,
    .pms-calendar-table td:last-child {
        border-right: 0;
    }

    .pms-calendar-table tr:last-child td {
        border-bottom: 0;
    }

    .pms-calendar-table th {
        background: #fafafa;
        color: var(--color-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 9px 4px;
    }

    .pms-calendar-table__cabin {
        position: sticky;
        left: 0;
        z-index: 3;
        width: 170px;
        min-width: 170px;
        background: #ffffff;
        text-align: left !important;
        padding: 12px 14px !important;
    }

    .pms-calendar-table th.pms-calendar-table__cabin {
        z-index: 5;
        background: #fafafa;
    }

    .pms-calendar-cabin-name {
        display: block;
        color: var(--color-text);
        font-size: 14px;
        font-weight: 900;
    }

    .pms-calendar-cabin-short {
        display: block;
        margin-top: 4px;
        color: var(--color-muted);
        font-size: 12px;
        font-weight: 700;
    }

    .pms-calendar-day-head {
        display: grid;
        gap: 2px;
    }

    .pms-calendar-day-head strong {
        color: var(--color-text);
        font-size: 12px;
    }

    .pms-calendar-day-head span {
        color: var(--color-muted);
        font-size: 10px;
    }

    .pms-calendar-day-head--today strong,
    .pms-calendar-day-head--today span {
        color: var(--color-primary);
    }

    .pms-calendar-cell {
        height: 54px;
        padding: 4px;
        background: #ffffff;
    }

    .pms-calendar-cell--weekend {
        background: #fbfbfb;
    }

    .pms-calendar-cell--today {
        box-shadow: inset 0 0 0 2px rgba(21, 128, 61, 0.28);
    }

    .pms-calendar-cell__free {
        display: block;
        width: 100%;
        height: 28px;
        border-radius: 10px;
        background: #f7faf8;
    }

    .pms-calendar-cell__booking {
        display: grid;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 34px;
        border-radius: 10px;
        color: #ffffff;
        font-size: 10px;
        font-weight: 900;
        line-height: 1.05;
        text-decoration: none;
        overflow: hidden;
        white-space: nowrap;
        text-align: center;
        padding: 3px 4px;
    }

    .pms-calendar-cell__booking small {
        display: block;
        margin-top: 2px;
        color: rgba(255, 255, 255, 0.88);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.03em;
    }

    .pms-calendar-cell__booking--arrival {
        border-radius: 10px 4px 4px 10px;
        box-shadow: inset 4px 0 0 rgba(255, 255, 255, 0.62);
    }

    .pms-calendar-cell__booking--stay {
        border-radius: 4px;
        opacity: 0.92;
    }

    .pms-calendar-cell__booking--departure {
        border-radius: 4px 10px 10px 4px;
        box-shadow: inset -4px 0 0 rgba(255, 255, 255, 0.62);
    }

    .calendar-status--pending {
        background: #f59e0b;
    }

    .calendar-status--confirmed {
        background: #15803d;
    }

    .calendar-status--checked-in {
        background: #2563eb;
    }

    .calendar-status--checked-out {
        background: #6b7280;
    }

    .calendar-status--cancelled {
        background: #dc2626;
        opacity: 0.78;
    }

    .pms-calendar-list {
        margin-top: 26px;
    }

    .pms-calendar-list h2 {
        margin-bottom: 14px;
    }

    .pms-calendar-list .data-table {
        min-width: 760px;
    }

    @media (max-width: 1200px) {
        .pms-calendar-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .pms-calendar-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pms-calendar-table__cabin {
            width: 140px;
            min-width: 140px;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'calendar']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Kalendarz</p>

                            <h1>Kalendarz</h1>

                            <p>
                                Widok PMS pokazuje domki w wierszach oraz dni miesiąca w kolumnach.
                                Zajęte dni wynikają z rezerwacji w systemie.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a class="button button--secondary" href="/admin/kalendarz?month=<?= htmlspecialchars($previousMonth, ENT_QUOTES, 'UTF-8') ?>">
                                Poprzedni
                            </a>

                            <a class="button button--secondary" href="/admin/kalendarz?month=<?= htmlspecialchars($currentMonth, ENT_QUOTES, 'UTF-8') ?>">
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

                    <div class="pms-calendar-toolbar">
                        <div>
                            <h2><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></h2>

                            <p style="margin: 6px 0 0;">
                                Kliknij zajęty dzień, aby przejść do szczegółów rezerwacji.
                            </p>
                        </div>

                        <form method="get" action="/admin/kalendarz" class="form-actions" style="margin-top: 0;">
                            <input
                                type="month"
                                name="month"
                                value="<?= htmlspecialchars($monthStart->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>"
                                style="min-height: 42px; border: 1px solid var(--color-border); border-radius: 999px; padding: 0 14px;"
                            >

                            <button class="button button--primary" type="submit">
                                Pokaż miesiąc
                            </button>
                        </form>
                    </div>

                    <div class="pms-calendar-summary">
                        <?php foreach ($summaryCards as $card): ?>
                            <div class="pms-calendar-summary__card">
                                <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pms-calendar-legend">
                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--pending"></i>
                            Oczekuje
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--confirmed"></i>
                            Potwierdzona
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--checked-in"></i>
                            Zameldowany
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--checked-out"></i>
                            Wymeldowany
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--cancelled"></i>
                            Anulowana
                        </span>

                        <span class="pms-calendar-legend__item">
                            <strong>Prz.</strong>
                            Przyjazd
                        </span>

                        <span class="pms-calendar-legend__item">
                            <strong>Pob.</strong>
                            Pobyt
                        </span>

                        <span class="pms-calendar-legend__item">
                            <strong>Wyj.</strong>
                            Wyjazd
                        </span>
                    </div>

                    <?php if ($cabins === []): ?>
                        <div class="empty-state">
                            <strong>Brak aktywnych domków</strong>

                            <p>
                                Dodaj domki albo ustaw istniejące domki jako aktywne, aby zobaczyć kalendarz.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="pms-calendar-table-wrap">
                            <table class="pms-calendar-table">
                                <thead>
                                    <tr>
                                        <th class="pms-calendar-table__cabin">Domek</th>

                                        <?php foreach ($days as $day): ?>
                                            <th>
                                                <span class="pms-calendar-day-head <?= $day['is_today'] ? 'pms-calendar-day-head--today' : '' ?>">
                                                    <strong><?= htmlspecialchars($day['day'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span><?= htmlspecialchars($day['weekday'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </span>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cabins as $cabin): ?>
                                        <?php
                                        $cabinId = (int) ($cabin['id'] ?? 0);
                                        $cabinCalendar = $calendarByCabin[$cabinId] ?? [];
                                        ?>

                                        <tr>
                                            <td class="pms-calendar-table__cabin">
                                                <span class="pms-calendar-cabin-name">
                                                    <?= htmlspecialchars((string) ($cabin['name'] ?? 'Domek'), ENT_QUOTES, 'UTF-8') ?>
                                                </span>

                                                <span class="pms-calendar-cabin-short">
                                                    <?= htmlspecialchars((string) ($cabin['short_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>

                                            <?php foreach ($days as $day): ?>
                                                <?php
                                                $date = (string) $day['date'];
                                                $dayReservations = $cabinCalendar[$date] ?? [];
                                                $firstReservation = $dayReservations[0] ?? null;
                                                $cellClass = 'pms-calendar-cell';

                                                if ($day['is_weekend']) {
                                                    $cellClass .= ' pms-calendar-cell--weekend';
                                                }

                                                if ($day['is_today']) {
                                                    $cellClass .= ' pms-calendar-cell--today';
                                                }
                                                ?>

                                                <td class="<?= htmlspecialchars($cellClass, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?php if (is_array($firstReservation)): ?>
                                                        <?php
                                                        $calendarEntry = $firstReservation;
                                                        $entryReservation = isset($calendarEntry['reservation']) && is_array($calendarEntry['reservation'])
                                                            ? $calendarEntry['reservation']
                                                            : $calendarEntry;
                                                        $entryType = isset($calendarEntry['type']) ? (string) $calendarEntry['type'] : 'STAY';

                                                        $reservationId = (int) ($entryReservation['id'] ?? 0);
                                                        $reservationStatus = (string) ($entryReservation['status'] ?? '');
                                                        $extraCount = count($dayReservations) - 1;

                                                        $entryLabel = match ($entryType) {
                                                            'ARRIVAL' => 'Prz.',
                                                            'DEPARTURE' => 'Wyj.',
                                                            default => 'Pob.',
                                                        };

                                                        $entryFullLabel = match ($entryType) {
                                                            'ARRIVAL' => 'Przyjazd',
                                                            'DEPARTURE' => 'Wyjazd',
                                                            default => 'Pobyt',
                                                        };

                                                        $entryClass = match ($entryType) {
                                                            'ARRIVAL' => 'pms-calendar-cell__booking--arrival',
                                                            'DEPARTURE' => 'pms-calendar-cell__booking--departure',
                                                            default => 'pms-calendar-cell__booking--stay',
                                                        };

                                                        $tooltip = $entryFullLabel . ' | ' . $cellTitle($entryReservation);
                                                        ?>

                                                        <a
                                                            class="pms-calendar-cell__booking <?= htmlspecialchars($statusClass($reservationStatus), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($entryClass, ENT_QUOTES, 'UTF-8') ?>"
                                                            href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') ?>"
                                                            title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            <?= htmlspecialchars($entryLabel, ENT_QUOTES, 'UTF-8') ?>
                                                            <?php if ($extraCount > 0): ?>
                                                                <small>+<?= htmlspecialchars((string) $extraCount, ENT_QUOTES, 'UTF-8') ?></small>
                                                            <?php else: ?>
                                                                <small><?= htmlspecialchars($statusLabel($reservationStatus), ENT_QUOTES, 'UTF-8') ?></small>
                                                            <?php endif; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="pms-calendar-cell__free"></span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="pms-calendar-list">
                        <h2>Rezerwacje w miesiącu</h2>

                        <?php if ($monthReservations === []): ?>
                            <div class="empty-state">
                                <strong>Brak rezerwacji w tym miesiącu</strong>

                                <p>
                                    W wybranym miesiącu nie ma rezerwacji blokujących ani anulowanych.
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
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($monthReservations as $reservation): ?>
                                            <?php
                                            $status = (string) ($reservation['status'] ?? '');
                                            $reservationId = (int) ($reservation['id'] ?? 0);
                                            ?>

                                            <tr>
                                                <td>
                                                    <strong>
                                                        <?= htmlspecialchars($formatDate(substr((string) ($reservation['start_date'] ?? ''), 0, 10)), ENT_QUOTES, 'UTF-8') ?>
                                                        —
                                                        <?= htmlspecialchars($formatDate(substr((string) ($reservation['end_date'] ?? ''), 0, 10)), ENT_QUOTES, 'UTF-8') ?>
                                                    </strong>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($reservationGuestName($reservation), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($reservationCabinName($reservation), ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td>
                                                    <span class="status-pill status-pill--muted">
                                                        <?= htmlspecialchars($statusLabel($status), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <a
                                                        class="button button--secondary button--small"
                                                        href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        Szczegóły
                                                    </a>
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
    </div>
</section>