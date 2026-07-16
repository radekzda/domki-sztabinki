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
        $databaseMessage = 'Nie udało się pobrać danych kalendarza: ' . AppErrorHandler::safeMessage($exception);
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

    if ($reservationStatus === 'CANCELLED') {
        $cancelledReservations++;
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



$reservationBarClass = static function (array $reservation): string {
    $total = (float) ($reservation['total_price'] ?? 0);
    $paid = (float) ($reservation['paid_amount'] ?? 0);
    $remaining = max($total - $paid, 0);
    $status = (string) ($reservation['status'] ?? '');

    if ($remaining > 0) {
        return 'calendar-status--remaining';
    }

    return match ($status) {
        'PENDING' => 'calendar-status--pending',
        'CONFIRMED' => 'calendar-status--confirmed',
        'CHECKED_IN' => 'calendar-status--checked-in',
        'CHECKED_OUT', 'COMPLETED' => 'calendar-status--checked-out',
        default => 'calendar-status--pending',
    };
};


$reservationAddressLine = static function (array $reservation): string {
    $linkedGuestAddress = trim((string) ($reservation['linked_guest_address'] ?? ''));

    if ($linkedGuestAddress !== '') {
        return 'Adres: ' . $linkedGuestAddress;
    }

    $parts = [];

    foreach (['street', 'postal_code', 'city'] as $field) {
        $value = trim((string) ($reservation[$field] ?? ''));

        if ($value !== '') {
            $parts[] = $value;
        }
    }

    if ($parts !== []) {
        return 'Adres: ' . implode(', ', $parts);
    }

    return 'Adres: —';
};

$reservationPhoneLine = static function (array $reservation): string {
    $phone = trim((string) ($reservation['phone'] ?? ''));

    if ($phone === '') {
        $phone = trim((string) ($reservation['linked_guest_phone'] ?? ''));
    }

    return $phone !== '' ? 'Tel.: ' . $phone : 'Tel.: —';
};

$paymentStatusLabel = static function (?string $status): string {
    return match ($status) {
        'PAID' => 'Opłacona',
        'PARTIAL' => 'Częściowa',
        'REFUNDED' => 'Zwrócona',
        'PENDING' => 'Oczekuje',
        default => '—',
    };
};

$paymentStatusClass = static function (?string $status): string {
    return match ($status) {
        'PAID' => 'pms-calendar-tooltip__payment--paid',
        'PARTIAL' => 'pms-calendar-tooltip__payment--partial',
        'REFUNDED' => 'pms-calendar-tooltip__payment--refunded',
        'PENDING' => 'pms-calendar-tooltip__payment--pending',
        default => 'pms-calendar-tooltip__payment--pending',
    };
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


    /* M13.60.1 — calendar colors and dropdown tooltip */
    .calendar-status--pending {
        background: #facc15;
        color: #111827;
    }

    .calendar-status--remaining {
        background: #f97316;
        color: #ffffff;
    }

    .calendar-status--confirmed {
        background: #3b82f6;
        color: #ffffff;
    }

    .calendar-status--checked-in {
        background: #22c55e;
        color: #ffffff;
    }

    .calendar-status--checked-out {
        background: #9ca3af;
        color: #ffffff;
    }

    .calendar-status--cancelled {
        display: none;
    }

    .pms-calendar-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
    }

    .pms-calendar-tooltip {
        top: calc(100% + 12px);
        bottom: auto;
        left: 50%;
        transform: translateX(-50%) translateY(-6px);
    }

    .pms-calendar-tooltip::after {
        top: -7px;
        bottom: auto;
        border-right: 0;
        border-bottom: 0;
        border-left: 1px solid rgba(15, 23, 42, 0.12);
        border-top: 1px solid rgba(15, 23, 42, 0.12);
    }

    .pms-calendar-bar:hover .pms-calendar-tooltip {
        transform: translateX(-50%) translateY(0);
    }

    .pms-calendar-bar.calendar-status--pending .pms-calendar-bar__line {
        color: #111827;
    }


    /* M13.60.2 — no-scroll calendar and safer tooltip */
    .pms-calendar-table-wrap {
        overflow: visible !important;
        width: 100%;
    }

    .pms-calendar-table {
        width: 100%;
        min-width: 0 !important;
        table-layout: fixed;
    }

    .pms-calendar-table__cabin {
        width: 150px !important;
        min-width: 150px !important;
    }

    .pms-calendar-day-head strong {
        font-size: 11px;
    }

    .pms-calendar-day-head span {
        font-size: 9px;
    }

    .pms-calendar-row-grid {
        grid-template-columns: repeat(var(--days), minmax(0, 1fr)) !important;
        overflow: visible !important;
    }

    .pms-calendar-row-cell {
        overflow: visible !important;
    }

    .pms-calendar-bg-cell {
        min-width: 0;
    }

    .pms-calendar-bar {
        overflow: visible !important;
        height: 60px;
        padding: 5px 7px;
    }

    .pms-calendar-bar__line {
        font-size: 13px !important;
        font-weight: 400 !important;
        line-height: 1.08;
    }

    .pms-calendar-bar__guest {
        font-size: 14px !important;
        font-weight: 400 !important;
    }

    .pms-calendar-tooltip {
        top: 50%;
        bottom: auto;
        left: 50%;
        width: min(260px, calc(100vw - 48px));
        max-width: 260px;
        gap: 6px;
        padding: 12px 13px;
        transform: translate(-50%, -50%);
    }

    .pms-calendar-tooltip::after {
        display: none;
    }

    .pms-calendar-bar:hover .pms-calendar-tooltip {
        transform: translate(-50%, -50%);
    }

    .pms-calendar-tooltip strong {
        font-size: 15px;
    }

    .pms-calendar-tooltip span {
        gap: 10px;
        padding-top: 5px;
        font-size: 12px;
    }

    .pms-calendar-tooltip b {
        max-width: 145px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 1500px) {
        .pms-calendar-table__cabin {
            width: 130px !important;
            min-width: 130px !important;
            padding: 8px 10px !important;
        }

        .pms-calendar-cabin-name {
            font-size: 12px;
        }

        .pms-calendar-cabin-short {
            font-size: 10px;
        }

        .pms-calendar-bar__line {
            font-size: 11px !important;
        }

        .pms-calendar-bar__guest {
            font-size: 12px !important;
        }
    }


    /* M13.60.3 — tooltip details and payment status */
    .pms-calendar-tooltip__subline {
        display: block;
        overflow: hidden;
        color: #475569;
        font-size: 12px;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pms-calendar-tooltip__payment {
        display: inline-flex !important;
        align-items: center;
        justify-content: center !important;
        margin-top: 4px;
        border-radius: 999px;
        padding: 7px 10px !important;
        font-size: 12px !important;
        font-weight: 800;
        text-align: center;
    }

    .pms-calendar-tooltip__payment--paid {
        background: #dcfce7;
        color: #166534;
    }

    .pms-calendar-tooltip__payment--partial {
        background: #ffedd5;
        color: #9a3412;
    }

    .pms-calendar-tooltip__payment--pending {
        background: #fef3c7;
        color: #92400e;
    }

    .pms-calendar-tooltip__payment--refunded {
        background: #e0f2fe;
        color: #075985;
    }


    /* M13.62 — compact calendar header and quick selection */
    .admin-content .panel > .page-header {
        margin-bottom: 10px !important;
    }

    .admin-content .panel > .page-header h1 {
        margin: 0 !important;
        font-size: 24px !important;
        line-height: 1.05 !important;
    }

    .admin-content .panel > .page-header .page-header__actions {
        gap: 8px !important;
    }

    .admin-content .panel > .page-header .button {
        min-height: 36px !important;
        padding: 0 14px !important;
        font-size: 12px !important;
    }

    .pms-calendar-toolbar--compact {
        margin-top: 8px !important;
        gap: 10px !important;
    }

    .pms-calendar-help {
        color: var(--color-muted);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
    }

    .pms-calendar-toolbar--compact .form-actions {
        gap: 8px !important;
    }

    .pms-calendar-toolbar--compact input[type="month"] {
        min-height: 36px !important;
        max-width: 165px;
        font-size: 13px;
    }

    .pms-calendar-toolbar--compact .button {
        min-height: 36px !important;
        padding: 0 14px !important;
        font-size: 12px !important;
    }

    .pms-calendar-summary {
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        gap: 8px !important;
        margin-top: 10px !important;
    }

    .pms-calendar-summary__card {
        border-radius: 12px !important;
        padding: 8px 10px !important;
    }

    .pms-calendar-summary__card span {
        font-size: 10px !important;
    }

    .pms-calendar-summary__card strong {
        margin-top: 2px !important;
        font-size: 14px !important;
    }

    .pms-calendar-legend {
        gap: 8px !important;
        margin-top: 10px !important;
    }

    .pms-calendar-legend__item {
        font-size: 11px !important;
    }

    .pms-calendar-table-wrap {
        margin-top: 12px !important;
    }

    .pms-calendar-bar,
    .pms-calendar-bar--continues-left,
    .pms-calendar-bar--continues-right {
        border-radius: 13px !important;
    }

    .pms-calendar-bg-cell {
        cursor: crosshair;
    }

    .pms-calendar-bg-cell:hover {
        background: rgba(59, 130, 246, 0.12) !important;
    }

    .pms-calendar-bg-cell--selected-start {
        background: rgba(249, 115, 22, 0.22) !important;
        box-shadow: inset 0 0 0 2px rgba(249, 115, 22, 0.55);
    }

    .pms-calendar-bg-cell--selected-range {
        background: rgba(249, 115, 22, 0.12) !important;
    }


    /* M13.62.2 — legend below calendar */
    .pms-calendar-legend--bottom {
        margin-top: 12px !important;
        margin-bottom: 0 !important;
        padding: 8px 10px;
        border: 1px solid var(--color-border);
        border-radius: 14px;
        background: #ffffff;
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
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--primary"
                                href="/admin/rezerwacje/nowa?return=<?= urlencode('/admin/kalendarz?month=' . $monthStart->format('Y-m')) ?>"
                            >
                                Nowa rezerwacja
                            </a>

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
                                                            data-calendar-date="<?= htmlspecialchars((string) $day['date'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-calendar-cabin-id="<?= htmlspecialchars((string) $cabinId, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-calendar-month="<?= htmlspecialchars($monthStart->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>"
                                                            title="Kliknij, aby wybrać początek albo koniec rezerwacji"
                                                        ></span>
                                                    <?php endforeach; ?>

                                                    <?php foreach ($cabinBars as $bar): ?>
                                                        <?php
                                                        $barReservation = $bar['reservation'];
                                                        $reservationId = (int) ($barReservation['id'] ?? 0);
                                                        $reservationStatus = (string) ($barReservation['status'] ?? '');
                                                        $barClass = 'pms-calendar-bar ' . $reservationBarClass($barReservation);

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
                                                            href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') ?>&return=<?= urlencode('/admin/kalendarz?month=' . $monthStart->format('Y-m')) ?>"
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

                                                                <small class="pms-calendar-tooltip__subline">
                                                                    <?= htmlspecialchars($reservationAddressLine($barReservation), ENT_QUOTES, 'UTF-8') ?>
                                                                </small>

                                                                <small class="pms-calendar-tooltip__subline">
                                                                    <?= htmlspecialchars($reservationPhoneLine($barReservation), ENT_QUOTES, 'UTF-8') ?>
                                                                </small>

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

                    

                    <div class="pms-calendar-legend pms-calendar-legend--bottom">
                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--pending"></i>
                            Oczekuje
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--remaining"></i>
                            Pozostało do zapłaty
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
</div>
            </div>
        </div>
    </div>
</section>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var selected = null;
        var cells = Array.prototype.slice.call(document.querySelectorAll('[data-calendar-date][data-calendar-cabin-id]'));

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function nextDay(dateString) {
            var parts = dateString.split('-').map(Number);
            var date = new Date(parts[0], parts[1] - 1, parts[2]);
            date.setDate(date.getDate() + 1);

            return [
                date.getFullYear(),
                pad(date.getMonth() + 1),
                pad(date.getDate())
            ].join('-');
        }

        function clearSelection() {
            cells.forEach(function (cell) {
                cell.classList.remove('pms-calendar-bg-cell--selected-start');
                cell.classList.remove('pms-calendar-bg-cell--selected-range');
            });
        }

        function markRange(cabinId, startDate, endDate) {
            clearSelection();

            cells.forEach(function (cell) {
                if (cell.dataset.calendarCabinId !== cabinId) {
                    return;
                }

                var date = cell.dataset.calendarDate;

                if (date === startDate) {
                    cell.classList.add('pms-calendar-bg-cell--selected-start');
                }

                if (date >= startDate && date <= endDate) {
                    cell.classList.add('pms-calendar-bg-cell--selected-range');
                }
            });
        }

        function openReservation(cabinId, startDate, endDate, month) {
            if (endDate <= startDate) {
                endDate = nextDay(startDate);
            }

            var returnUrl = '/admin/kalendarz?month=' + month;
            var url = '/admin/rezerwacje/nowa'
                + '?cabin_id=' + encodeURIComponent(cabinId)
                + '&start_date=' + encodeURIComponent(startDate)
                + '&end_date=' + encodeURIComponent(endDate)
                + '&return=' + encodeURIComponent(returnUrl);

            window.location.href = url;
        }

        cells.forEach(function (cell) {
            cell.addEventListener('click', function (event) {
                event.preventDefault();

                var cabinId = cell.dataset.calendarCabinId;
                var date = cell.dataset.calendarDate;
                var month = cell.dataset.calendarMonth;

                if (
                    selected !== null
                    && selected.cabinId === cabinId
                ) {
                    var startDate = selected.date <= date ? selected.date : date;
                    var endDate = selected.date <= date ? date : selected.date;

                    openReservation(cabinId, startDate, endDate, month);
                    return;
                }

                selected = {
                    cabinId: cabinId,
                    date: date,
                    month: month
                };

                markRange(cabinId, date, date);
            });

            cell.addEventListener('dblclick', function (event) {
                event.preventDefault();

                var cabinId = cell.dataset.calendarCabinId;
                var date = cell.dataset.calendarDate;
                var month = cell.dataset.calendarMonth;

                openReservation(cabinId, date, nextDay(date), month);
            });
        });
    });
</script>
