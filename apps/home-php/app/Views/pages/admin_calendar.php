<?php

declare(strict_types=1);

/**
 * @var string $title
 */

$databaseMessage = null;
$cabins = [];
$reservations = [];
$availabilityPeriods = [];

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
        'is_saturday' => $date->format('N') === '6',
        'is_sunday' => $date->format('N') === '7',
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
        $availabilityPeriods = AvailabilityRepository::calendarPeriods(
            $monthStartString,
            $monthEndString
        );
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać danych kalendarza: ' . AppErrorHandler::safeMessage($exception);
        $cabins = [];
        $reservations = [];
        $availabilityPeriods = [];
    }
}

$statusLabels = [
    'PENDING' => 'Oczekuje',
    'CONFIRMED' => 'Potwierdzona',
    'CHECKED_IN' => 'Zameldowany',
    'CHECKED_OUT' => 'Wymeldowany',
    'COMPLETED' => 'Wymeldowany',
    'CANCELLED' => 'Anulowana',
    'ICAL' => 'Blokada iCal',
];

$statusClasses = [
    'PENDING' => 'calendar-status--pending',
    'CONFIRMED' => 'calendar-status--confirmed',
    'CHECKED_IN' => 'calendar-status--checked-in',
    'CHECKED_OUT' => 'calendar-status--checked-out',
    'COMPLETED' => 'calendar-status--checked-out',
    'CANCELLED' => 'calendar-status--cancelled',
    'ICAL' => 'calendar-status--ical',
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
        'kind' => 'RESERVATION',
        'reservation' => $reservation,
        'left_percent' => max(0, min(100, $leftPercent)),
        'width_percent' => max(0.35, min(100, $widthPercent)),
        'starts_before_month' => $reservationStartMoment < $monthStartMoment,
        'ends_after_month' => $reservationEndMoment > $monthEndMoment,
    ];
}

foreach ($availabilityPeriods as $period) {
    if ((string) ($period['kind'] ?? '') !== 'ICAL') {
        continue;
    }

    $cabinId = (int) ($period['cabin_id'] ?? 0);
    $startDate = substr((string) ($period['start_date'] ?? ''), 0, 10);
    $endDate = substr((string) ($period['end_date'] ?? ''), 0, 10);
    $source = strtoupper(trim((string) ($period['source'] ?? 'ICAL')));

    if (!isset($calendarBarsByCabin[$cabinId])) {
        continue;
    }

    if ($startDate === '' || $endDate === '') {
        continue;
    }

    try {
        $startMoment = new DateTimeImmutable($startDate . ' 00:00:00');
        $endMoment = new DateTimeImmutable($endDate . ' 00:00:00');
    } catch (Throwable $exception) {
        continue;
    }

    $visibleStartMoment = $startMoment < $monthStartMoment
        ? $monthStartMoment
        : $startMoment;

    $visibleEndMoment = $endMoment > $monthEndMoment
        ? $monthEndMoment
        : $endMoment;

    if ($visibleEndMoment <= $visibleStartMoment) {
        continue;
    }

    $leftPercent = (($visibleStartMoment->getTimestamp() - $monthStartMoment->getTimestamp()) / $totalMonthSeconds) * 100;
    $widthPercent = (($visibleEndMoment->getTimestamp() - $visibleStartMoment->getTimestamp()) / $totalMonthSeconds) * 100;

    $calendarBarsByCabin[$cabinId][] = [
        'kind' => 'ICAL',
        'source' => $source !== '' ? $source : 'ICAL',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'ical_event_id' => (int) ($period['ical_event_id'] ?? 0),
        'left_percent' => max(0, min(100, $leftPercent)),
        'width_percent' => max(0.35, min(100, $widthPercent)),
        'starts_before_month' => $startMoment < $monthStartMoment,
        'ends_after_month' => $endMoment > $monthEndMoment,
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
        'ICAL' => 'calendar-status--ical',
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

    .calendar-status--ical {
        background: #7c3aed;
        color: #ffffff;
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



    /* M13.93.11 — professional calendar layout */

    .calendar-panel {
        padding: 28px;
    }

    .calendar-panel > .page-header {
        margin-bottom: 18px !important;
        align-items: center;
    }

    .calendar-panel > .page-header h1 {
        margin: 0 !important;
        font-size: 32px !important;
        line-height: 1.1 !important;
    }

    .calendar-panel > .page-header .eyebrow {
        display: none;
    }

    .calendar-panel > .page-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px !important;
    }

    .calendar-panel > .page-header .button {
        min-height: 38px !important;
        padding: 8px 15px !important;
        border-radius: 10px;
        font-size: 12px !important;
    }

    /*
     * Miesiac i wybor miesiaca
     */
    .pms-calendar-toolbar--compact {
        margin-top: 0 !important;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px !important;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 8px 20px rgba(15, 23, 42, 0.03);
    }

    .pms-calendar-month-heading {
        min-width: 0;
    }

    .pms-calendar-month-heading h2 {
        margin: 0 0 5px;
        font-size: 22px;
        line-height: 1.1;
        color: #111827;
        text-transform: capitalize;
    }

    .pms-calendar-help {
        max-width: 780px;
        margin: 0 !important;
        font-size: 12px !important;
        line-height: 1.4 !important;
        font-weight: 400 !important;
        color: #9ca3af !important;
    }

    .pms-calendar-month-form {
        flex-shrink: 0;
        margin: 0 !important;
        display: flex;
        align-items: center;
        gap: 8px !important;
    }

    .pms-calendar-month-form input[type="month"] {
        width: 160px;
        min-height: 38px !important;
        padding: 0 11px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #ffffff;
        font-size: 12px !important;
        color: #374151;
    }

    .pms-calendar-month-form .button {
        min-height: 38px !important;
        padding: 7px 14px !important;
        border-radius: 9px;
        font-size: 12px !important;
    }

    /*
     * Statystyki miesiaca
     */
    .pms-calendar-summary {
        grid-template-columns: repeat(
            5,
            minmax(0, 1fr)
        ) !important;
        gap: 10px !important;
        margin-top: 14px !important;
    }

    .pms-calendar-summary__card {
        min-height: 62px;
        padding: 12px 14px !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.015),
            0 6px 14px rgba(15, 23, 42, 0.025);
    }

    .pms-calendar-summary__card span {
        min-width: 0;
        font-size: 11px !important;
        line-height: 1.2;
        font-weight: 700 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        color: #6b7280 !important;
    }

    .pms-calendar-summary__card strong {
        flex-shrink: 0;
        margin: 0 !important;
        font-size: 18px !important;
        line-height: 1;
        font-weight: 750;
        color: #111827 !important;
    }

    /*
     * Glowny kalendarz
     */
    .pms-calendar-table-wrap {
        margin-top: 14px !important;
        overflow: visible !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 14px !important;
        background: #ffffff;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.02),
            0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .pms-calendar-table {
        border-radius: 14px;
    }

    .pms-calendar-table th {
        height: 46px;
        padding: 6px 3px !important;
        background: #f8fafc !important;
        border-color: #e5e7eb !important;
    }

    .pms-calendar-table__cabin {
        width: 145px !important;
        min-width: 145px !important;
        padding: 10px 14px !important;
    }

    .pms-calendar-table th.pms-calendar-table__cabin {
        background: #f8fafc !important;
    }

    .pms-calendar-cabin-name {
        font-size: 13px !important;
        line-height: 1.25;
        font-weight: 750 !important;
        color: #111827 !important;
    }

    .pms-calendar-cabin-short {
        margin-top: 3px !important;
        font-size: 10px !important;
        font-weight: 600 !important;
        color: #9ca3af !important;
    }

    /*
     * Dni
     */
    .pms-calendar-day-head {
        gap: 1px !important;
    }

    .pms-calendar-day-head strong {
        font-size: 11px !important;
        line-height: 1;
        color: #374151;
    }

    .pms-calendar-day-head span {
        font-size: 8px !important;
        line-height: 1;
        color: #9ca3af;
    }

    .pms-calendar-day-head--today strong,
    .pms-calendar-day-head--today span {
        color: #15803d !important;
        font-weight: 800;
    }

    /*
     * Wiersze kalendarza
     */
    .pms-calendar-row-grid {
        grid-template-rows: 76px !important;
        min-height: 76px !important;
    }

    .pms-calendar-bg-cell {
        min-height: 76px !important;
        border-color: #edf0f2 !important;
    }

    .pms-calendar-bg-cell--weekend {
        background: #fafbfc !important;
    }

    .pms-calendar-bg-cell--today {
        background: rgba(
            21,
            128,
            61,
            0.055
        ) !important;
        box-shadow:
            inset 2px 0 0 rgba(21, 128, 61, 0.2),
            inset -2px 0 0 rgba(21, 128, 61, 0.2) !important;
    }

    /*
     * Paski rezerwacji
     */
    .pms-calendar-bar {
        top: 10px !important;
        height: 56px !important;
        padding: 6px 8px !important;
        gap: 2px !important;
        border-radius: 9px !important;
        box-shadow:
            0 4px 10px rgba(15, 23, 42, 0.11) !important;
        transition:
            filter 0.15s ease,
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .pms-calendar-bar:hover {
        z-index: 20;
        filter: brightness(0.97);
        transform: translateY(-1px);
        box-shadow:
            0 7px 16px rgba(15, 23, 42, 0.16) !important;
    }

    .pms-calendar-bar__guest {
        font-size: 12px !important;
        line-height: 1.05 !important;
        font-weight: 650 !important;
    }

    .pms-calendar-bar__line {
        font-size: 10px !important;
        line-height: 1.05 !important;
        font-weight: 450 !important;
    }

    /*
     * Tooltip
     */
    .pms-calendar-tooltip {
        width: min(
            270px,
            calc(100vw - 48px)
        ) !important;
        max-width: 270px !important;
        padding: 13px 14px !important;
        gap: 6px !important;
        border-radius: 12px !important;
        box-shadow:
            0 18px 40px rgba(15, 23, 42, 0.2) !important;
    }

    .pms-calendar-tooltip strong {
        font-size: 15px !important;
        line-height: 1.25;
    }

    /*
     * Legenda
     */
    .pms-calendar-legend--bottom {
        margin-top: 12px !important;
        padding: 10px 12px !important;
        display: flex;
        align-items: center;
        gap: 14px !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
    }

    .pms-calendar-legend__item {
        gap: 5px !important;
        font-size: 10px !important;
        font-weight: 600 !important;
        color: #6b7280 !important;
    }

    .pms-calendar-legend__dot {
        width: 10px !important;
        height: 10px !important;
    }

    /*
     * Responsive
     */
    @media (max-width: 1200px) {
        .calendar-panel {
            padding: 22px;
        }

        .calendar-panel > .page-header {
            align-items: flex-start;
        }

        .pms-calendar-toolbar--compact {
            align-items: flex-start;
            flex-direction: column;
        }

        .pms-calendar-month-form {
            width: 100%;
        }

        .pms-calendar-summary {
            grid-template-columns: repeat(
                3,
                minmax(0, 1fr)
            ) !important;
        }
    }

    @media (max-width: 700px) {
        .calendar-panel {
            padding: 16px;
        }

        .calendar-panel > .page-header {
            flex-direction: column;
            gap: 14px;
        }

        .calendar-panel > .page-header__actions {
            justify-content: flex-start;
        }

        .pms-calendar-month-form {
            align-items: stretch;
            flex-direction: column;
        }

        .pms-calendar-month-form input[type="month"],
        .pms-calendar-month-form .button {
            width: 100%;
            max-width: none;
        }

        .pms-calendar-summary {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            ) !important;
        }
    }



    /* M13.93.12 — final calendar readability */

    /*
     * Wybor miesiaca w gornym pasku
     */
    .calendar-panel > .page-header__actions {
        align-items: center;
    }

    .pms-calendar-month-form--header {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0;
    }

    .pms-calendar-month-form--header input[type="month"] {
        width: 145px;
        height: 38px;
        min-height: 38px;
        padding: 0 10px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        font-size: 12px;
        color: #111827;
    }

    .pms-calendar-month-form--header .button {
        min-height: 38px !important;
        height: 38px;
        padding: 0 13px !important;
        white-space: nowrap;
    }

    /*
     * Statystyki od razu pod naglowkiem
     */
    .pms-calendar-summary {
        margin-top: 0 !important;
        margin-bottom: 14px;
    }

    /*
     * Wiersz dopasowany do paska.
     * Pasek pozostaje 56 px.
     * 6 px wolnego miejsca u gory i na dole.
     */
    .pms-calendar-row-grid {
        grid-template-rows: 68px !important;
        min-height: 68px !important;
    }

    .pms-calendar-bg-cell {
        min-height: 68px !important;
    }

    .pms-calendar-bar {
        top: 6px !important;
        height: 56px !important;
        padding: 5px 8px !important;
    }

    /*
     * Czytelniejszy czarny tekst na paskach
     */
    .pms-calendar-bar > .pms-calendar-bar__line {
        color: #111827 !important;
        font-family:
            Inter,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            Arial,
            sans-serif;
        text-shadow: none !important;
    }

    .pms-calendar-bar__guest {
        font-size: 13px !important;
        line-height: 1.08 !important;
        font-weight: 700 !important;
    }

    .pms-calendar-bar__line {
        font-size: 11px !important;
        line-height: 1.08 !important;
        font-weight: 550 !important;
    }

    /*
     * Nazwa domku bez D1 / D2
     */
    .pms-calendar-table__cabin {
        vertical-align: middle !important;
    }

    .pms-calendar-cabin-name {
        margin: 0;
        font-size: 13px !important;
        line-height: 1.2;
    }

    /*
     * Legenda tylko statusy
     */
    .pms-calendar-legend--bottom {
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    @media (max-width: 1200px) {
        .calendar-panel > .page-header__actions {
            width: 100%;
        }

        .pms-calendar-month-form--header {
            order: 10;
        }
    }

    @media (max-width: 700px) {
        .pms-calendar-month-form--header {
            width: 100%;
        }

        .pms-calendar-month-form--header input[type="month"] {
            flex: 1;
            width: auto;
        }
    }



    /* M13.93.13 — pionowe centrowanie paskow rezerwacji */

    .pms-calendar-row-cell {
        height: 68px !important;
        vertical-align: middle !important;
    }

    .pms-calendar-row-grid {
        height: 68px !important;
        min-height: 68px !important;
        grid-template-rows: 68px !important;
    }

    .pms-calendar-bg-cell {
        height: 68px !important;
        min-height: 68px !important;
    }

    .pms-calendar-bar {
        top: 50% !important;
        height: 56px !important;
        transform: translateY(-50%);
    }

    .pms-calendar-bar:hover {
        transform: translateY(calc(-50% - 1px));
    }



    /* M13.93.14 — precyzyjne centrowanie paskow w wierszach */

    .pms-calendar-row-cell {
        height: 68px !important;
        min-height: 68px !important;
        padding: 0 !important;
    }

    .pms-calendar-row-grid {
        position: relative !important;
        height: 68px !important;
        min-height: 68px !important;
        grid-template-rows: 68px !important;
    }

    .pms-calendar-bg-cell {
        height: 68px !important;
        min-height: 68px !important;
        box-sizing: border-box !important;
    }

    .pms-calendar-bar {
        top: 6px !important;
        bottom: auto !important;
        height: 56px !important;
        min-height: 56px !important;
        box-sizing: border-box !important;
        transform: none !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .pms-calendar-bar:hover {
        transform: translateY(-1px) !important;
    }



    /* M13.93.15 — tooltip zawsze wewnatrz okna */

    .pms-calendar-tooltip {
        position: fixed !important;
        top: 0;
        left: 0;
        z-index: 9999 !important;
        width: min(
            270px,
            calc(100vw - 24px)
        ) !important;
        max-width: 270px !important;
        transform: none !important;
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
    }

    .pms-calendar-bar:hover .pms-calendar-tooltip {
        transform: none !important;
    }



    /* M13.93.16 — tooltip pozycjonowany wzgledem ekranu */

    .pms-calendar-bar,
    .pms-calendar-bar:hover {
        transform: none !important;
    }

    .pms-calendar-bar:hover {
        filter: brightness(0.97);
        box-shadow:
            0 7px 16px rgba(15, 23, 42, 0.16) !important;
    }

    .pms-calendar-tooltip {
        position: fixed !important;
        transform: none !important;
    }

    .pms-calendar-bar:hover .pms-calendar-tooltip {
        transform: none !important;
    }



    /* M13.93.17 — tooltip niezalezny od paska rezerwacji */

    .pms-calendar-bar,
    .pms-calendar-bar:hover {
        transform: none !important;
        filter: none !important;
    }

    body > .pms-calendar-tooltip {
        position: fixed !important;
        z-index: 99999 !important;
        margin: 0 !important;
        transform: none !important;
        pointer-events: none !important;
    }

    /*
     * Wyraźne oznaczenie sobót, niedziel i dzisiejszego dnia.
     */
    .pms-calendar-day-head--saturday,
    .pms-calendar-day-head--sunday,
    .pms-calendar-day-head--today {
        margin: -4px -2px;
        padding: 5px 2px;
        border-radius: 7px;
    }

    .pms-calendar-day-head--saturday {
        background: #dbeafe;
        box-shadow: inset 0 0 0 1px #93c5fd;
    }

    .pms-calendar-day-head--saturday strong,
    .pms-calendar-day-head--saturday span {
        color: #1d4ed8 !important;
        font-weight: 850 !important;
    }

    .pms-calendar-day-head--sunday {
        background: #fee2e2;
        box-shadow: inset 0 0 0 1px #fca5a5;
    }

    .pms-calendar-day-head--sunday strong,
    .pms-calendar-day-head--sunday span {
        color: #b91c1c !important;
        font-weight: 850 !important;
    }

    .pms-calendar-day-head--today {
        background: #dcfce7 !important;
        box-shadow:
            inset 0 0 0 2px #15803d,
            0 0 0 1px rgba(21, 128, 61, 0.12) !important;
    }

    .pms-calendar-day-head--today strong,
    .pms-calendar-day-head--today span {
        color: #166534 !important;
        font-weight: 950 !important;
    }

    .pms-calendar-bg-cell--saturday {
        background: #eff6ff !important;
        box-shadow:
            inset 1px 0 0 #bfdbfe,
            inset -1px 0 0 #bfdbfe;
    }

    .pms-calendar-bg-cell--sunday {
        background: #fff1f2 !important;
        box-shadow:
            inset 1px 0 0 #fecdd3,
            inset -1px 0 0 #fecdd3;
    }

    .pms-calendar-bg-cell--today {
        background: #dcfce7 !important;
        box-shadow:
            inset 3px 0 0 #15803d,
            inset -3px 0 0 #15803d !important;
    }

    .pms-calendar-print-header {
        display: none;
    }

    /*
     * M6.15.12 — druk miesięcznego kalendarza rezerwacji
     */
    @media print {
        @page {
            size: A4 landscape;
            margin: 7mm;
        }

        html,
        body {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .admin-sidebar,
        .calendar-panel > .page-header,
        body > .pms-calendar-tooltip {
            display: none !important;
        }

        .page-section,
        .container,
        .admin-shell,
        .admin-content,
        .panel,
        .calendar-panel {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #ffffff !important;
        }

        .pms-calendar-print-header {
            display: flex !important;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8mm;
            margin: 0 0 4mm;
            padding: 0 0 2.5mm;
            border-bottom: 0.4mm solid #111827;
        }

        .pms-calendar-print-header h1 {
            margin: 0;
            font-size: 16pt;
            line-height: 1.1;
            color: #111827;
        }

        .pms-calendar-print-header p {
            margin: 0;
            font-size: 8pt;
            font-weight: 700;
            color: #4b5563;
            white-space: nowrap;
        }

        .alert {
            margin: 0 0 3mm !important;
            padding: 2mm 3mm !important;
            font-size: 8pt !important;
        }

        .pms-calendar-summary {
            grid-template-columns: repeat(
                5,
                minmax(0, 1fr)
            ) !important;
            gap: 2mm !important;
            margin: 0 0 3mm !important;
        }

        .pms-calendar-summary__card {
            min-height: 0 !important;
            padding: 2mm 2.5mm !important;
            border: 0.25mm solid #d1d5db !important;
            border-radius: 1.5mm !important;
            box-shadow: none !important;
        }

        .pms-calendar-summary__card span {
            font-size: 6.5pt !important;
        }

        .pms-calendar-summary__card strong {
            font-size: 9pt !important;
        }

        .pms-calendar-table-wrap {
            margin: 0 !important;
            overflow: visible !important;
            border: 0.25mm solid #9ca3af !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .pms-calendar-table {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-radius: 0 !important;
        }

        .pms-calendar-table thead {
            display: table-header-group;
        }

        .pms-calendar-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .pms-calendar-table th {
            height: 7mm !important;
            padding: 1mm 0.4mm !important;
            border-color: #cbd5e1 !important;
            background: #f3f4f6 !important;
        }

        .pms-calendar-table__cabin {
            width: 25mm !important;
            min-width: 25mm !important;
            padding: 1.5mm 2mm !important;
        }

        .pms-calendar-cabin-name {
            font-size: 7.5pt !important;
            line-height: 1.1 !important;
        }

        .pms-calendar-day-head strong {
            font-size: 6.5pt !important;
        }

        .pms-calendar-day-head span {
            font-size: 5.5pt !important;
        }

        .pms-calendar-row-cell {
            height: 15mm !important;
            min-height: 15mm !important;
            padding: 0 !important;
        }

        .pms-calendar-row-grid {
            height: 15mm !important;
            min-height: 15mm !important;
            grid-template-rows: 15mm !important;
        }

        .pms-calendar-bg-cell {
            height: 15mm !important;
            min-height: 15mm !important;
            border-color: #e5e7eb !important;
        }

        .pms-calendar-day-head--saturday {
            background: #dbeafe !important;
            box-shadow: inset 0 0 0 0.3mm #60a5fa !important;
        }

        .pms-calendar-day-head--sunday {
            background: #fee2e2 !important;
            box-shadow: inset 0 0 0 0.3mm #f87171 !important;
        }

        .pms-calendar-day-head--today {
            background: #dcfce7 !important;
            box-shadow: inset 0 0 0 0.55mm #15803d !important;
        }

        .pms-calendar-bg-cell--saturday {
            background: #eff6ff !important;
            box-shadow:
                inset 0.25mm 0 0 #93c5fd,
                inset -0.25mm 0 0 #93c5fd !important;
        }

        .pms-calendar-bg-cell--sunday {
            background: #fff1f2 !important;
            box-shadow:
                inset 0.25mm 0 0 #fda4af,
                inset -0.25mm 0 0 #fda4af !important;
        }

        .pms-calendar-bg-cell--today {
            background: #dcfce7 !important;
            box-shadow:
                inset 0.7mm 0 0 #15803d,
                inset -0.7mm 0 0 #15803d !important;
        }

        .pms-calendar-bar {
            top: 1.5mm !important;
            bottom: auto !important;
            height: 12mm !important;
            min-height: 12mm !important;
            padding: 1mm 1.2mm !important;
            gap: 0.5mm !important;
            border-radius: 1.5mm !important;
            box-shadow: none !important;
            transform: none !important;
            filter: none !important;
            text-decoration: none !important;
            overflow: hidden !important;
        }

        .pms-calendar-bar:hover {
            transform: none !important;
            filter: none !important;
            box-shadow: none !important;
        }

        .pms-calendar-bar__guest {
            font-size: 6.8pt !important;
            line-height: 1.05 !important;
        }

        .pms-calendar-bar__line {
            font-size: 5.8pt !important;
            line-height: 1.05 !important;
        }

        .pms-calendar-tooltip {
            display: none !important;
        }

        .pms-calendar-legend--bottom {
            margin: 3mm 0 0 !important;
            padding: 2mm 2.5mm !important;
            gap: 2mm 4mm !important;
            border: 0.25mm solid #d1d5db !important;
            border-radius: 1.5mm !important;
            background: #f8fafc !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .pms-calendar-legend__item {
            gap: 1mm !important;
            font-size: 6.5pt !important;
        }

        .pms-calendar-legend__dot {
            width: 2.5mm !important;
            height: 2.5mm !important;
        }

        a,
        a:visited {
            color: inherit !important;
            text-decoration: none !important;
        }
    }

</style>

<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'calendar']); ?>

            <div class="admin-content">
                <div class="panel calendar-panel">
                    <div
                        class="pms-calendar-print-header"
                        aria-hidden="true"
                    >
                        <h1>
                            Kalendarz rezerwacji —
                            <?= htmlspecialchars(ucfirst($monthLabel), ENT_QUOTES, 'UTF-8') ?>
                        </h1>

                        <p>Domki Sztabinki</p>
                    </div>

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

                            <button
                                class="button button--secondary pms-calendar-print-button"
                                type="button"
                                onclick="window.print()"
                            >
                                Drukuj miesiąc
                            </button>

                            <form
                                method="get"
                                action="/admin/kalendarz"
                                class="pms-calendar-month-form pms-calendar-month-form--header"
                            >
                                <input
                                    type="month"
                                    name="month"
                                    value="<?= htmlspecialchars($monthStart->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="Wybierz miesiąc"
                                >

                                <button
                                    class="button button--primary"
                                    type="submit"
                                >
                                    Pokaż miesiąc
                                </button>
                            </form>

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
                                                <span class="pms-calendar-day-head <?= $day['is_saturday'] ? 'pms-calendar-day-head--saturday' : '' ?> <?= $day['is_sunday'] ? 'pms-calendar-day-head--sunday' : '' ?> <?= $day['is_today'] ? 'pms-calendar-day-head--today' : '' ?>">
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

                                                        if ($day['is_saturday']) {
                                                            $dayClass .= ' pms-calendar-bg-cell--saturday';
                                                        }

                                                        if ($day['is_sunday']) {
                                                            $dayClass .= ' pms-calendar-bg-cell--sunday';
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
                                                        $barKind = (string) ($bar['kind'] ?? 'RESERVATION');
                                                        $isIcalBar = $barKind === 'ICAL';
                                                        $barClass = 'pms-calendar-bar';

                                                        if ($isIcalBar) {
                                                            $barClass .= ' calendar-status--ical';
                                                        } else {
                                                            $barReservation = is_array($bar['reservation'] ?? null)
                                                                ? $bar['reservation']
                                                                : [];
                                                            $barClass .= ' ' . $reservationBarClass($barReservation);
                                                        }

                                                        if ((bool) ($bar['starts_before_month'] ?? false)) {
                                                            $barClass .= ' pms-calendar-bar--continues-left';
                                                        }

                                                        if ((bool) ($bar['ends_after_month'] ?? false)) {
                                                            $barClass .= ' pms-calendar-bar--continues-right';
                                                        }

                                                        $barStyle = 'left: '
                                                            . number_format((float) $bar['left_percent'], 4, '.', '')
                                                            . '%; width: '
                                                            . number_format((float) $bar['width_percent'], 4, '.', '')
                                                            . '%;';
                                                        ?>

                                                        <?php if ($isIcalBar): ?>
                                                            <?php
                                                            $icalSource = strtoupper(trim((string) ($bar['source'] ?? 'ICAL')));
                                                            $icalLabel = match ($icalSource) {
                                                                'BOOKING' => 'Booking / iCal',
                                                                'AIRBNB' => 'Airbnb / iCal',
                                                                'OTHER',
                                                                'ICAL',
                                                                '' => 'iCal — inne',
                                                                default => $icalSource . ' / iCal',
                                                            };
                                                            $icalStart = (string) ($bar['start_date'] ?? '');
                                                            $icalEnd = (string) ($bar['end_date'] ?? '');
                                                            ?>

                                                            <?php
                                                            $icalEventId = (int) (
                                                                $bar['ical_event_id']
                                                                ?? 0
                                                            );

                                                            $icalReservationUrl =
                                                                '/admin/rezerwacje/nowa?ical_event_id='
                                                                . $icalEventId
                                                                . '&return='
                                                                . urlencode(
                                                                    '/admin/kalendarz?month='
                                                                    . $monthStart->format('Y-m')
                                                                );
                                                            ?>

                                                            <a
                                                                class="<?= htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8') ?>"
                                                                style="<?= htmlspecialchars($barStyle, ENT_QUOTES, 'UTF-8') ?>"
                                                                href="<?= htmlspecialchars($icalReservationUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                                aria-label="<?= htmlspecialchars('Uzupełnij dane rezerwacji z ' . $icalLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                            >
                                                                <span class="pms-calendar-bar__line pms-calendar-bar__guest">
                                                                    <?= htmlspecialchars($icalLabel, ENT_QUOTES, 'UTF-8') ?>
                                                                </span>

                                                                <span class="pms-calendar-bar__line">
                                                                    Zajęte
                                                                </span>

                                                                <span class="pms-calendar-bar__line">
                                                                    Kliknij i uzupełnij dane
                                                                </span>

                                                                <span class="pms-calendar-tooltip">
                                                                    <strong><?= htmlspecialchars($icalLabel, ENT_QUOTES, 'UTF-8') ?></strong>

                                                                    <small class="pms-calendar-tooltip__subline">
                                                                        Kliknij, aby utworzyć pełną rezerwację i dodać dane gościa.
                                                                    </small>

                                                                    <span>
                                                                        <em>Od</em>
                                                                        <b><?= htmlspecialchars($formatDate($icalStart), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Do</em>
                                                                        <b><?= htmlspecialchars($formatDate($icalEnd), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Status</em>
                                                                        <b>Blokada iCal</b>
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        <?php else: ?>
                                                            <?php
                                                            $barReservation = is_array($bar['reservation'] ?? null)
                                                                ? $bar['reservation']
                                                                : [];
                                                            $reservationId = (int) ($barReservation['id'] ?? 0);
                                                            $reservationStatus = (string) ($barReservation['status'] ?? '');
                                                            $remainingAmount = max(
                                                                (float) ($barReservation['total_price'] ?? 0) - (float) ($barReservation['paid_amount'] ?? 0),
                                                                0
                                                            );
                                                            ?>

                                                            <a
                                                                class="<?= htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8') ?>"
                                                                style="<?= htmlspecialchars($barStyle, ENT_QUOTES, 'UTF-8') ?>"
                                                                href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') ?>&return=<?= urlencode('/admin/kalendarz?month=' . $monthStart->format('Y-m')) ?>"
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
                                                                        <em>Zapłacono</em>
                                                                        <b><?= htmlspecialchars(formatMoneyForDisplay($barReservation['paid_amount'] ?? 0), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Pozostało</em>
                                                                        <b><?= htmlspecialchars(formatMoneyForDisplay($remainingAmount), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Status</em>
                                                                        <b><?= htmlspecialchars($statusLabel($reservationStatus), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Status płatności</em>
                                                                        <b><?= htmlspecialchars($paymentStatusLabel((string) ($barReservation['payment_status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>

                                                                    <span>
                                                                        <em>Źródło</em>
                                                                        <b><?= htmlspecialchars(sourceLabelForDisplay((string) ($barReservation['source'] ?? '')), ENT_QUOTES, 'UTF-8') ?></b>
                                                                    </span>
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>
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
                            <i class="pms-calendar-legend__dot calendar-status--ical"></i>
                            Booking / iCal
                        </span>

                        <span class="pms-calendar-legend__item">
                            <i class="pms-calendar-legend__dot calendar-status--cancelled"></i>
                            Anulowana
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


<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            var calendarBars = document.querySelectorAll(
                '.pms-calendar-bar'
            );

            function hideTooltip(tooltip) {
                if (!tooltip) {
                    return;
                }

                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            }

            function positionTooltip(bar, tooltip) {
                if (!bar || !tooltip) {
                    return;
                }

                var viewportPadding = 12;
                var gap = 10;

                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
                tooltip.style.left = '0px';
                tooltip.style.top = '0px';

                var barRect = bar.getBoundingClientRect();

                var tooltipWidth =
                    tooltip.offsetWidth;

                var tooltipHeight =
                    tooltip.offsetHeight;

                var left =
                    barRect.left
                    + (
                        barRect.width
                        / 2
                    )
                    - (
                        tooltipWidth
                        / 2
                    );

                var maxLeft =
                    window.innerWidth
                    - tooltipWidth
                    - viewportPadding;

                left = Math.max(
                    viewportPadding,
                    Math.min(
                        left,
                        maxLeft
                    )
                );

                var top =
                    barRect.bottom
                    + gap;

                if (
                    top
                    + tooltipHeight
                    > window.innerHeight
                    - viewportPadding
                ) {
                    top =
                        barRect.top
                        - tooltipHeight
                        - gap;
                }

                if (top < viewportPadding) {
                    top = viewportPadding;
                }

                tooltip.style.left =
                    left + 'px';

                tooltip.style.top =
                    top + 'px';

                tooltip.style.visibility =
                    'visible';

                tooltip.style.opacity =
                    '1';
            }

            calendarBars.forEach(
                function (bar) {
                    var tooltip =
                        bar.querySelector(
                            '.pms-calendar-tooltip'
                        );

                    if (!tooltip) {
                        return;
                    }

                    /*
                     * Tooltip musi byc poza paskiem rezerwacji.
                     * Dzieki temu position: fixed jest liczony
                     * wzgledem calego okna przegladarki.
                     */
                    document.body.appendChild(
                        tooltip
                    );

                    bar.addEventListener(
                        'mouseenter',
                        function () {
                            positionTooltip(
                                bar,
                                tooltip
                            );
                        }
                    );

                    bar.addEventListener(
                        'mouseleave',
                        function () {
                            hideTooltip(
                                tooltip
                            );
                        }
                    );
                }
            );

            window.addEventListener(
                'scroll',
                function () {
                    document
                        .querySelectorAll(
                            '.pms-calendar-tooltip'
                        )
                        .forEach(
                            hideTooltip
                        );
                },
                true
            );

            window.addEventListener(
                'resize',
                function () {
                    document
                        .querySelectorAll(
                            '.pms-calendar-tooltip'
                        )
                        .forEach(
                            hideTooltip
                        );
                }
            );
        }
    );
</script>
