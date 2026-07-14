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


$calendarBarsByCabin = [];

foreach ($cabins as $cabin) {
    $cabinId = (int) ($cabin['id'] ?? 0);
    $calendarBarsByCabin[$cabinId] = [];
}

$barReservations = $monthReservations;

usort($barReservations, static function (array $first, array $second): int {
    $firstCabin = (int) ($first['cabin_id'] ?? 0);
    $secondCabin = (int) ($second['cabin_id'] ?? 0);

    if ($firstCabin !== $secondCabin) {
        return $firstCabin <=> $secondCabin;
    }

    $dateCompare = strcmp((string) ($first['start_date'] ?? ''), (string) ($second['start_date'] ?? ''));

    if ($dateCompare !== 0) {
        return $dateCompare;
    }

    return strcmp((string) ($first['end_date'] ?? ''), (string) ($second['end_date'] ?? ''));
});

$monthStartMoment = new DateTimeImmutable($monthStartString . ' 00:00:00');
$monthEndMoment = new DateTimeImmutable($monthEndString . ' 00:00:00');
$totalMonthSeconds = max(1, $monthEndMoment->getTimestamp() - $monthStartMoment->getTimestamp());

foreach ($barReservations as $reservation) {
    $reservationCabinId = (int) ($reservation['cabin_id'] ?? 0);
    $reservationStart = substr((string) ($reservation['start_date'] ?? ''), 0, 10);
    $reservationEnd = substr((string) ($reservation['end_date'] ?? ''), 0, 10);

    if (!isset($calendarBarsByCabin[$reservationCabinId])) {
        continue;
    }

    if ($reservationStart === '' || $reservationEnd === '') {
        continue;
    }

    $checkInAt = trim((string) ($reservation['check_in_at'] ?? ''));
    $checkOutAt = trim((string) ($reservation['check_out_at'] ?? ''));

    try {
        $reservationStartMoment = $checkInAt !== ''
            ? new DateTimeImmutable($checkInAt)
            : new DateTimeImmutable($reservationStart . ' 00:00:00');

        $reservationEndMoment = $checkOutAt !== ''
            ? new DateTimeImmutable($checkOutAt)
            : new DateTimeImmutable($reservationEnd . ' 00:00:00');
    } catch (Throwable $exception) {
        continue;
    }

    $visibleStartMoment = $reservationStartMoment < $monthStartMoment
        ? $monthStartMoment
        : $reservationStartMoment;

    $visibleEndMoment = $reservationEndMoment > $monthEndMoment
        ? $monthEndMoment
        : $reservationEndMoment;

    if ($visibleEndMoment <= $visibleStartMoment) {
        continue;
    }

    $leftPercent = (($visibleStartMoment->getTimestamp() - $monthStartMoment->getTimestamp()) / $totalMonthSeconds) * 100;
    $widthPercent = (($visibleEndMoment->getTimestamp() - $visibleStartMoment->getTimestamp()) / $totalMonthSeconds) * 100;

    $calendarBarsByCabin[$reservationCabinId][] = [
        'reservation' => $reservation,
        'left_percent' => max(0, min(100, $leftPercent)),
        'width_percent' => max(0.35, min(100, $widthPercent)),
        'starts_before_month' => $reservationStartMoment < $monthStartMoment,
        'ends_after_month' => $reservationEndMoment > $monthEndMoment,
    ];
}

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

$formatDateTime = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i');
    } catch (Throwable $exception) {
        return $value;
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

$cellTitle = static function (array $reservation) use ($reservationGuestName, $formatDate, $formatDateTime, $statusLabel): string {
    $guest = $reservationGuestName($reservation);
    $start = $formatDate(substr((string) ($reservation['start_date'] ?? ''), 0, 10));
    $end = $formatDate(substr((string) ($reservation['end_date'] ?? ''), 0, 10));
    $checkIn = $formatDateTime($reservation['check_in_at'] ?? null);
    $checkOut = $formatDateTime($reservation['check_out_at'] ?? null);
    $status = $statusLabel((string) ($reservation['status'] ?? ''));

    return $guest
        . ' | termin: ' . $start . ' - ' . $end
        . ' | przyjazd: ' . $checkIn
        . ' | wyjazd: ' . $checkOut
        . ' | status: ' . $status;
};


$reservationAmountLine = static function (array $reservation): string {
    $total = (float) ($reservation['total_price'] ?? 0);
    $paid = (float) ($reservation['paid_amount'] ?? 0);
    $remaining = max($total - $paid, 0);

    return 'Wart.: ' . formatMoneyForDisplay($total)
        . ' | Poz.: ' . formatMoneyForDisplay($remaining);
};

$reservationPeopleLine = static function (array $reservation): string {
    $guests = (int) ($reservation['guests'] ?? 0);
    $adults = (int) ($reservation['adults'] ?? 0);
    $children = (int) ($reservation['children'] ?? 0);

    return 'os.: ' . $guests . ' | dor.: ' . $adults . ' | dz.: ' . $children;
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

    .pms-calendar-table {
        table-layout: fixed;
    }

    .pms-calendar-row-cell {
        position: relative;
        padding: 0 !important;
        background: #ffffff;
    }

    .pms-calendar-row-grid {
        position: relative;
        display: grid;
        grid-template-columns: repeat(var(--days), minmax(34px, 1fr));
        grid-auto-rows: 64px;
        align-items: stretch;
        width: 100%;
    }

    .pms-calendar-bg-cell {
        position: relative;
        z-index: 1;
        border-right: 1px solid var(--color-border);
        background: #ffffff;
    }

    .pms-calendar-bg-cell:last-of-type {
        border-right: 0;
    }

    .pms-calendar-bg-cell--weekend {
        background: #fbfbfb;
    }

    .pms-calendar-bg-cell--today {
        background: rgba(21, 128, 61, 0.06);
        box-shadow: inset 0 0 0 2px rgba(21, 128, 61, 0.22);
    }

    .pms-calendar-bar {
        position: relative;
        z-index: 3;
        display: grid;
        align-content: center;
        gap: 2px;
        min-width: 0;
        margin: 7px 2px;
        border-radius: 10px;
        color: #ffffff;
        padding: 6px 8px;
        text-align: left;
        text-decoration: none;
        overflow: hidden;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
    }

    .pms-calendar-bar:hover {
        filter: brightness(0.95);
    }

    .pms-calendar-bar--continues-left {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .pms-calendar-bar--continues-right {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .pms-calendar-bar__line {
        min-width: 0;
        overflow: hidden;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pms-calendar-bar__guest {
        font-size: 11px;
        font-weight: 950;
    }


    /* M13.60.1 — continuous time-based reservation bars */
    .pms-calendar-row-grid {
        position: relative;
        display: grid;
        grid-template-columns: repeat(var(--days), minmax(34px, 1fr));
        grid-template-rows: 88px;
        width: 100%;
        overflow: visible;
    }

    .pms-calendar-row-cell {
        position: relative;
        overflow: visible;
        padding: 0 !important;
        background: #ffffff;
    }

    .pms-calendar-bg-cell {
        position: relative;
        z-index: 1;
        min-height: 88px;
        border-right: 1px solid var(--color-border);
        background: #ffffff;
    }

    .pms-calendar-bg-cell--weekend {
        background: #f8fafc;
    }

    .pms-calendar-bg-cell--today {
        background: rgba(37, 99, 235, 0.07);
        box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.22);
    }

    .pms-calendar-bar {
        position: absolute;
        top: 12px;
        z-index: 5;
        display: grid;
        align-content: center;
        gap: 3px;
        height: 64px;
        min-width: 18px;
        border-radius: 0;
        color: #ffffff;
        padding: 7px 9px;
        text-align: left;
        text-decoration: none;
        overflow: visible;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
    }

    .pms-calendar-bar:hover {
        z-index: 20;
        filter: brightness(0.98);
    }

    .pms-calendar-bar--continues-left {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .pms-calendar-bar--continues-right {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .pms-calendar-bar__line {
        min-width: 0;
        overflow: hidden;
        font-size: 15px;
        font-weight: 400;
        line-height: 1.08;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pms-calendar-bar__guest {
        font-size: 16px;
        font-weight: 400;
    }

    .pms-calendar-tooltip {
        position: absolute;
        left: 50%;
        bottom: calc(100% + 12px);
        z-index: 50;
        display: grid;
        width: 320px;
        gap: 8px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 14px;
        background: #ffffff;
        color: #111827;
        padding: 14px 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
        opacity: 0;
        pointer-events: none;
        transform: translateX(-50%) translateY(6px);
        transition: opacity 0.12s ease, transform 0.12s ease;
    }

    .pms-calendar-tooltip::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -7px;
        width: 14px;
        height: 14px;
        background: #ffffff;
        border-right: 1px solid rgba(15, 23, 42, 0.12);
        border-bottom: 1px solid rgba(15, 23, 42, 0.12);
        transform: translateX(-50%) rotate(45deg);
    }

    .pms-calendar-bar:hover .pms-calendar-tooltip {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .pms-calendar-tooltip strong {
        display: block;
        margin-bottom: 2px;
        font-size: 18px;
        font-weight: 800;
    }

    .pms-calendar-tooltip span {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        border-top: 1px solid #eef2f7;
        padding-top: 7px;
        font-size: 14px;
    }

    .pms-calendar-tooltip em {
        color: #64748b;
        font-style: normal;
    }

    .pms-calendar-tooltip b {
        font-weight: 700;
        text-align: right;
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
                                Godziny przyjazdu i wyjazdu pochodzą z importu Base44 lub ręcznej edycji rezerwacji.
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
                                        $cabinBars = $calendarBarsByCabin[$cabinId] ?? [];
                                        $rowHeight = 88;
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

                                            <td
                                                class="pms-calendar-row-cell"
                                                colspan="<?= htmlspecialchars((string) $daysInMonth, ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <div
                                                    class="pms-calendar-row-grid"
                                                    style="--days: <?= htmlspecialchars((string) $daysInMonth, ENT_QUOTES, 'UTF-8') ?>; min-height: <?= htmlspecialchars((string) $rowHeight, ENT_QUOTES, 'UTF-8') ?>px;"
                                                >
                                                    <?php foreach ($days as $dayIndex => $day): ?>
                                                        <?php
                                                        $dayColumn = $dayIndex + 1;
                                                        $dayClass = 'pms-calendar-bg-cell';

                                                        if ($day['is_weekend']) {
                                                            $dayClass .= ' pms-calendar-bg-cell--weekend';
                                                        }

                                                        if ($day['is_today']) {
                                                            $dayClass .= ' pms-calendar-bg-cell--today';
                                                        }
                                                        ?>

                                                        <span
                                                            class="<?= htmlspecialchars($dayClass, ENT_QUOTES, 'UTF-8') ?>"
                                                            style="grid-column: <?= htmlspecialchars((string) $dayColumn, ENT_QUOTES, 'UTF-8') ?>; grid-row: 1;"
                                                        ></span>
                                                    <?php endforeach; ?>

                                                    <?php foreach ($cabinBars as $bar): ?>
                                                        <?php
                                                        $barReservation = $bar['reservation'];
                                                        $reservationId = (int) ($barReservation['id'] ?? 0);
                                                        $reservationStatus = (string) ($barReservation['status'] ?? '');
                                                        $barClass = 'pms-calendar-bar ' . $statusClass($reservationStatus);

                                                        if ((bool) ($bar['starts_before_month'] ?? false)) {
                                                            $barClass .= ' pms-calendar-bar--continues-left';
                                                        }

                                                        if ((bool) ($bar['ends_after_month'] ?? false)) {
                                                            $barClass .= ' pms-calendar-bar--continues-right';
                                                        }

                                                        $remainingAmount = max(
                                                            (float) ($barReservation['total_price'] ?? 0) - (float) ($barReservation['paid_amount'] ?? 0),
                                                            0
                                                        );

                                                        $barStyle = 'left: '
                                                            . number_format((float) $bar['left_percent'], 4, '.', '')
                                                            . '%; width: '
                                                            . number_format((float) $bar['width_percent'], 4, '.', '')
                                                            . '%;';
                                                        ?>
                                                        <a
                                                            class="<?= htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8') ?>"
                                                            style="<?= htmlspecialchars($barStyle, ENT_QUOTES, 'UTF-8') ?>"
                                                            href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') ?>"
                                                            title="<?= htmlspecialchars($cellTitle($barReservation), ENT_QUOTES, 'UTF-8') ?>"
                                                        >
                                                            <span class="pms-calendar-bar__line pms-calendar-bar__guest">
                                                                <?= htmlspecialchars($reservationGuestName($barReservation), ENT_QUOTES, 'UTF-8') ?>
                                                            </span>

                                                            <span class="pms-calendar-bar__line">
                                                                <?= htmlspecialchars(formatMoneyForDisplay($barReservation['total_price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                                /
                                                                poz.
                                                                <?= htmlspecialchars(formatMoneyForDisplay($remainingAmount), ENT_QUOTES, 'UTF-8') ?>
                                                            </span>

                                                            <span class="pms-calendar-bar__line">
                                                                os.:
                                                                <?= htmlspecialchars((string) ($barReservation['guests'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                                |
                                                                dor.:
                                                                <?= htmlspecialchars((string) ($barReservation['adults'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                                |
                                                                dz.:
                                                                <?= htmlspecialchars((string) ($barReservation['children'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                            </span>

                                                            <span class="pms-calendar-tooltip">
                                                                <strong><?= htmlspecialchars($reservationGuestName($barReservation), ENT_QUOTES, 'UTF-8') ?></strong>

                                                                <span>
                                                                    <em>Zameldowanie</em>
                                                                    <b><?= htmlspecialchars($formatDateTime($barReservation['check_in_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></b>
                                                                </span>

                                                                <span>
                                                                    <em>Wymeldowanie</em>
                                                                    <b><?= htmlspecialchars($formatDateTime($barReservation['check_out_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></b>
                                                                </span>

                                                                <span>
                                                                    <em>Osoby</em>
                                                                    <b>
                                                                        <?= htmlspecialchars((string) ($barReservation['guests'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                                        /
                                                                        dor.:
                                                                        <?= htmlspecialchars((string) ($barReservation['adults'] ?? 0), ENT_QUOTES, 'UTF-8') ?>,
                                                                        dzieci:
                                                                        <?= htmlspecialchars((string) ($barReservation['children'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                                                    </b>
                                                                </span>

                                                                <span>
                                                                    <em>Wartość</em>
                                                                    <b><?= htmlspecialchars(formatMoneyForDisplay($barReservation['total_price'] ?? 0), ENT_QUOTES, 'UTF-8') ?></b>
                                                                </span>

                                                                <span>
                                                                    <em>Pozostało</em>
                                                                    <b><?= htmlspecialchars(formatMoneyForDisplay($remainingAmount), ENT_QUOTES, 'UTF-8') ?></b>
                                                                </span>

                                                                <span>
                                                                    <em>Status</em>
                                                                    <b><?= htmlspecialchars($statusLabel($reservationStatus), ENT_QUOTES, 'UTF-8') ?></b>
                                                                </span>
                                                            </span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
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
                                            <th>Godziny</th>
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
                                                    <strong>Przyjazd</strong><br>
                                                    <?= htmlspecialchars($formatDateTime($reservation['check_in_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>

                                                    <br>

                                                    <strong>Wyjazd</strong><br>
                                                    <?= htmlspecialchars($formatDateTime($reservation['check_out_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
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