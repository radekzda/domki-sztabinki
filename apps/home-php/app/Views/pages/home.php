<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, string>|null $inquiryForm
 * @var array<string, string>|null $inquiryErrors
 * @var string|null $publicDatabaseMessage
 */

$settings = defaultSettingsForm();
$cabins = [];
$cabinImages = [];
$busyRangesByCabin = [];
$busyDatesByCabin = [];
$databaseMessage = null;

$form = isset($inquiryForm) && is_array($inquiryForm)
    ? $inquiryForm
    : defaultPublicInquiryForm();

$errors = isset($inquiryErrors) && is_array($inquiryErrors)
    ? $inquiryErrors
    : [];

$inquiryMessage = isset($publicDatabaseMessage) && is_string($publicDatabaseMessage)
    ? $publicDatabaseMessage
    : null;

$successMessage = isset($_GET['inquiry_sent'])
    ? 'Dziękujemy. Zapytanie zostało wysłane. Odpowiemy najszybciej jak to możliwe.'
    : null;

$requestedAvailabilityMonth = isset($_GET['availability_month'])
    ? (string) $_GET['availability_month']
    : date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $requestedAvailabilityMonth)) {
    $requestedAvailabilityMonth = date('Y-m');
}

try {
    $availabilityMonthStart = new DateTimeImmutable($requestedAvailabilityMonth . '-01');
} catch (Throwable $exception) {
    $availabilityMonthStart = new DateTimeImmutable(date('Y-m-01'));
}

$availabilityWindowStart = $availabilityMonthStart;
$availabilityWindowEnd = $availabilityMonthStart->modify('+3 months');
$previousAvailabilityMonth = $availabilityMonthStart->modify('-1 month')->format('Y-m');
$nextAvailabilityMonth = $availabilityMonthStart->modify('+1 month')->format('Y-m');
$availabilityWindowStartString = $availabilityWindowStart->format('Y-m-d');
$availabilityWindowEndString = $availabilityWindowEnd->format('Y-m-d');

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

$weekdayLabels = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd'];

$buildCalendarWeeks = static function (DateTimeImmutable $monthStart): array {
    $calendarCells = [];
    $firstWeekday = (int) $monthStart->format('N');
    $daysInMonth = (int) $monthStart->format('t');

    for ($emptyDay = 1; $emptyDay < $firstWeekday; $emptyDay++) {
        $calendarCells[] = null;
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $calendarCells[] = $monthStart->setDate(
            (int) $monthStart->format('Y'),
            (int) $monthStart->format('m'),
            $day
        )->format('Y-m-d');
    }

    while (count($calendarCells) % 7 !== 0) {
        $calendarCells[] = null;
    }

    return array_chunk($calendarCells, 7);
};

$availabilityMonths = [];

for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {
    $monthStart = $availabilityMonthStart->modify('+' . $monthOffset . ' months');
    $monthLabel = ($monthNames[$monthStart->format('m')] ?? $monthStart->format('m'))
        . ' '
        . $monthStart->format('Y');

    $availabilityMonths[] = [
        'start' => $monthStart,
        'label' => $monthLabel,
        'weeks' => $buildCalendarWeeks($monthStart),
    ];
}

if (!Database::canAttemptConnection()) {
    $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Strona pokazuje podstawowe dane domyślne.';
} else {
    try {
        $settings = SettingsRepository::all();
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać ustawień publicznych z bazy: ' . $exception->getMessage();
    }

    try {
        $allCabins = CabinRepository::all();

        foreach ($allCabins as $loadedCabin) {
            if ((int) ($loadedCabin['is_active'] ?? 0) !== 1) {
                continue;
            }

            $cabins[] = $loadedCabin;
        }
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać domków publicznych z bazy: ' . $exception->getMessage();
    }

    foreach ($cabins as $loadedCabin) {
        $loadedCabinId = (int) ($loadedCabin['id'] ?? 0);

        if ($loadedCabinId < 1) {
            continue;
        }

        try {
            $images = CabinImageRepository::allForCabin($loadedCabinId);
            $mainImage = null;

            foreach ($images as $image) {
                if ((int) ($image['is_main'] ?? 0) === 1) {
                    $mainImage = $image;
                    break;
                }
            }

            if ($mainImage === null && $images !== []) {
                $mainImage = $images[0];
            }

            $cabinImages[$loadedCabinId] = $mainImage;
        } catch (Throwable $exception) {
            $cabinImages[$loadedCabinId] = null;
        }
    }

    try {
        $today = date('Y-m-d');
        $reservations = ReservationRepository::all();

        foreach ($reservations as $reservation) {
            $cabinId = (int) ($reservation['cabin_id'] ?? 0);
            $status = (string) ($reservation['status'] ?? '');
            $startDate = substr((string) ($reservation['start_date'] ?? ''), 0, 10);
            $endDate = substr((string) ($reservation['end_date'] ?? ''), 0, 10);

            if ($cabinId < 1) {
                continue;
            }

            if (!reservationStatusBlocks($status)) {
                continue;
            }

            if ($startDate === '' || $endDate === '') {
                continue;
            }

            if ($endDate >= $today) {
                if (!isset($busyRangesByCabin[$cabinId])) {
                    $busyRangesByCabin[$cabinId] = [];
                }

                $busyRangesByCabin[$cabinId][] = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                ];
            }

            if ($endDate <= $availabilityWindowStartString || $startDate >= $availabilityWindowEndString) {
                continue;
            }

            $rangeStartString = $startDate > $availabilityWindowStartString
                ? $startDate
                : $availabilityWindowStartString;

            $rangeEndString = $endDate < $availabilityWindowEndString
                ? $endDate
                : $availabilityWindowEndString;

            try {
                $rangeCurrentDate = new DateTimeImmutable($rangeStartString);
                $rangeEndDate = new DateTimeImmutable($rangeEndString);
            } catch (Throwable $exception) {
                continue;
            }

            if (!isset($busyDatesByCabin[$cabinId])) {
                $busyDatesByCabin[$cabinId] = [];
            }

            while ($rangeCurrentDate < $rangeEndDate) {
                $busyDatesByCabin[$cabinId][$rangeCurrentDate->format('Y-m-d')] = $status;
                $rangeCurrentDate = $rangeCurrentDate->modify('+1 day');
            }
        }

        foreach ($busyRangesByCabin as $cabinId => $ranges) {
            usort($ranges, static function (array $first, array $second): int {
                return strcmp((string) $first['start_date'], (string) $second['start_date']);
            });

            $busyRangesByCabin[$cabinId] = array_slice($ranges, 0, 5);
        }
    } catch (Throwable $exception) {
        $busyRangesByCabin = [];
        $busyDatesByCabin = [];
    }
}

$formatPublicPrice = static function (int $amount, string $currency): string {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
};

$currency = $settings['currency'] !== '' ? $settings['currency'] : 'PLN';

$cabinString = static function (array $cabin, string $key, string $fallback = ''): string {
    if (!array_key_exists($key, $cabin)) {
        return $fallback;
    }

    if ($cabin[$key] === null || $cabin[$key] === '') {
        return $fallback;
    }

    return (string) $cabin[$key];
};

$cabinInt = static function (array $cabin, string $key, int $fallback = 0): int {
    if (!array_key_exists($key, $cabin)) {
        return $fallback;
    }

    if ($cabin[$key] === null || $cabin[$key] === '') {
        return $fallback;
    }

    return (int) $cabin[$key];
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'PENDING' => 'Wstępnie zajęty',
        'CONFIRMED' => 'Zarezerwowany',
        'CHECKED_IN' => 'Trwa pobyt',
        default => 'Zajęty',
    };
};

$availabilityCabinId = 0;

if (isset($_GET['availability_cabin_id']) && is_numeric($_GET['availability_cabin_id'])) {
    $availabilityCabinId = (int) $_GET['availability_cabin_id'];
}

$availableCabinIds = [];

foreach ($cabins as $cabin) {
    $availableCabinIds[] = $cabinInt($cabin, 'id', 0);
}

if (!in_array($availabilityCabinId, $availableCabinIds, true)) {
    $availabilityCabinId = $availableCabinIds[0] ?? 0;
}

$selectedAvailabilityCabin = null;

foreach ($cabins as $cabin) {
    if ($cabinInt($cabin, 'id', 0) === $availabilityCabinId) {
        $selectedAvailabilityCabin = $cabin;
        break;
    }
}

if (
    isset($_GET['inquiry_cabin_id'])
    && is_numeric($_GET['inquiry_cabin_id'])
    && ($form['cabin_id'] ?? '') === ''
) {
    $requestedInquiryCabinId = (int) $_GET['inquiry_cabin_id'];

    if (in_array($requestedInquiryCabinId, $availableCabinIds, true)) {
        $form['cabin_id'] = (string) $requestedInquiryCabinId;
    }
}

$selectedBusyDates = $availabilityCabinId > 0
    ? ($busyDatesByCabin[$availabilityCabinId] ?? [])
    : [];

$selectedCabinName = is_array($selectedAvailabilityCabin)
    ? $cabinString($selectedAvailabilityCabin, 'name', 'Wybrany domek')
    : 'Wybrany domek';

$siteHeroImage = null;
$siteGalleryImages = [];
$siteAttractionImages = [];

if (Database::canAttemptConnection()) {
    try {
        $siteHeroImage = SiteImageRepository::mainByType('HERO');
    } catch (Throwable $exception) {
        $siteHeroImage = null;
    }

    try {
        $siteGalleryImages = SiteImageRepository::allByType('GALLERY');
    } catch (Throwable $exception) {
        $siteGalleryImages = [];
    }

    try {
        $siteAttractionImages = SiteImageRepository::allByType('ATTRACTION');
    } catch (Throwable $exception) {
        $siteAttractionImages = [];
    }
}

$heroImage = null;

foreach ($cabins as $heroCabin) {
    $heroCabinId = $cabinInt($heroCabin, 'id', 0);

    if (isset($cabinImages[$heroCabinId]) && is_array($cabinImages[$heroCabinId])) {
        $heroImage = $cabinImages[$heroCabinId];
        break;
    }
}

$heroImagePath = is_array($siteHeroImage)
    ? (string) ($siteHeroImage['image_url'] ?? '')
    : '';

if ($heroImagePath === '') {
    $heroImagePath = is_array($heroImage)
        ? (string) ($heroImage['image_path'] ?? '')
        : '';
}

$heroBackground = $heroImagePath !== ''
    ? "linear-gradient(90deg, rgba(250,247,239,0.96) 0%, rgba(250,247,239,0.84) 34%, rgba(250,247,239,0.24) 62%, rgba(250,247,239,0.10) 100%), url('" . htmlspecialchars($heroImagePath, ENT_QUOTES, 'UTF-8') . "')"
    : 'linear-gradient(135deg, #f7f2e8 0%, #e9f1e8 55%, #dce9ef 100%)';
?>

<style>
    :root {
        --public-bg: #f8f4ec;
        --public-bg-soft: #fffaf1;
        --public-card: #fffdf8;
        --public-border: #e7ddce;
        --public-green: #164a32;
        --public-green-2: #0f3825;
        --public-muted: #6e756e;
        --public-text: #17231d;
        --public-gold: #b78b4b;
        --public-red-bg: #fde8e5;
        --public-red: #9f2d20;
        --public-green-bg: #e5f3e7;
    }

    .public-page {
        background:
            radial-gradient(circle at top left, rgba(183, 139, 75, 0.14), transparent 32rem),
            radial-gradient(circle at 85% 10%, rgba(22, 74, 50, 0.11), transparent 30rem),
            var(--public-bg);
        color: var(--public-text);
        font-family: Arial, sans-serif;
    }

    .public-wrap {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .public-nav {
        position: sticky;
        top: 12px;
        z-index: 20;
        margin: 14px auto 0;
        width: min(1180px, calc(100% - 32px));
        background: rgba(255, 253, 248, 0.94);
        border: 1px solid rgba(231, 221, 206, 0.9);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(23, 35, 29, 0.12);
        backdrop-filter: blur(12px);
    }

    .public-nav__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 16px 20px;
    }

    .public-logo {
        display: flex;
        flex-direction: column;
        color: var(--public-green);
        text-decoration: none;
        line-height: 1;
    }

    .public-logo strong {
        font-family: Georgia, serif;
        font-size: 24px;
        letter-spacing: -0.04em;
    }

    .public-logo span {
        margin-top: 5px;
        color: var(--public-muted);
        font-size: 13px;
    }

    .public-menu {
        display: flex;
        align-items: center;
        gap: 28px;
        font-size: 14px;
    }

    .public-menu a {
        color: var(--public-text);
        text-decoration: none;
        font-weight: 700;
    }

    .public-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 20px;
        border-radius: 9px;
        border: 1px solid var(--public-green);
        background: var(--public-green);
        color: #ffffff;
        text-decoration: none;
        font-weight: 800;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(22, 74, 50, 0.22);
    }

    .public-button:hover {
        background: var(--public-green-2);
    }

    .public-button--light {
        background: rgba(255, 253, 248, 0.88);
        color: var(--public-green);
        box-shadow: none;
    }

    .public-button--wide {
        width: 100%;
    }

    .public-hero {
        margin-top: -88px;
        min-height: 420px;
        background-image: <?= $heroBackground ?>;
        background-size: cover, cover;
        background-position: center, center 75%;
        background-repeat: no-repeat, no-repeat;
        display: flex;
        align-items: center;
        border-bottom: 1px solid var(--public-border);
    }

    .public-hero__content {
        padding: 112px 0 34px;
        max-width: 520px;
    }

    .public-kicker {
        margin: 0 0 14px;
        color: var(--public-green);
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .public-hero h1 {
        margin: 0;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: clamp(30px, 3.7vw, 46px);
        line-height: 1.04;
        letter-spacing: -0.06em;
    }

    .public-hero p {
        margin: 16px 0 0;
        max-width: 430px;
        color: #34423a;
        font-size: 16px;
        line-height: 1.6;
    }

    .public-hero__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 22px;
    }

    .public-feature-strip {
        position: relative;
        z-index: 4;
        margin-top: -46px;
    }

    .public-feature-strip__box {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0;
        overflow: hidden;
        background: rgba(255, 253, 248, 0.96);
        border: 1px solid var(--public-border);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(23, 35, 29, 0.13);
    }

    .public-feature {
        display: flex;
        gap: 12px;
        align-items: center;
        min-height: 64px;
        padding: 10px 14px;
        border-right: 1px solid var(--public-border);
    }

    .public-feature:last-child {
        border-right: 0;
    }

    .public-feature__icon {
        color: var(--public-green);
        font-size: 24px;
    }

    .public-feature strong {
        display: block;
        color: var(--public-text);
        font-size: 15px;
    }

    .public-feature span {
        display: block;
        margin-top: 2px;
        color: var(--public-muted);
        font-size: 13px;
    }

    .public-section {
        padding: 52px 0 0;
    }

    .public-section__head {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: end;
        margin-bottom: 28px;
    }

    .public-section__head h2 {
        margin: 0;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: clamp(28px, 3vw, 38px);
        letter-spacing: -0.04em;
    }

    .public-section__head p {
        margin: 10px 0 0;
        color: var(--public-muted);
        font-size: 16px;
        line-height: 1.7;
    }

    .public-cabins-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 22px;
    }

    .public-cabin-card {
        overflow: hidden;
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 18px;
        box-shadow: 0 16px 38px rgba(23, 35, 29, 0.08);
    }

    .public-cabin-card__image {
        position: relative;
        height: 165px;
        overflow: hidden;
        background: linear-gradient(135deg, #dae7dc, #f4eadc);
    }

    .public-cabin-card__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .public-cabin-card__badge {
        position: absolute;
        top: 14px;
        left: 14px;
        padding: 8px 11px;
        border-radius: 8px;
        background: var(--public-green);
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
    }

    .public-cabin-card__body {
        padding: 18px;
    }

    .public-cabin-card h3 {
        margin: 0;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: 24px;
    }

    .public-cabin-card p {
        min-height: 72px;
        margin: 10px 0 16px;
        color: var(--public-muted);
        font-size: 14px;
        line-height: 1.6;
    }

    .public-cabin-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
        color: #44534a;
        font-size: 13px;
        font-weight: 800;
    }

    .public-cabin-actions {
        display: grid;
        gap: 10px;
    }

    .public-availability {
        display: grid;
        grid-template-columns: 330px minmax(0, 1fr);
        gap: 26px;
        padding: 22px;
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 22px;
        box-shadow: 0 18px 45px rgba(23, 35, 29, 0.08);
    }

    .public-availability h2 {
        margin: 0 0 12px;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: 32px;
        letter-spacing: -0.04em;
    }

    .public-availability p {
        margin: 0;
        color: var(--public-muted);
        line-height: 1.7;
    }

    .public-availability-form {
        display: grid;
        gap: 14px;
        margin-top: 22px;
    }

    .public-label {
        display: grid;
        gap: 7px;
        color: #34423a;
        font-size: 13px;
        font-weight: 900;
    }

    .public-input,
    .public-select,
    .public-textarea {
        width: 100%;
        border: 1px solid var(--public-border);
        border-radius: 10px;
        background: #ffffff;
        color: var(--public-text);
        font: inherit;
        padding: 13px 14px;
    }

    .public-input::placeholder,
    .public-textarea::placeholder {
        color: #a1a1aa;
        opacity: 1;
    }

    .public-map-card {
        display: block;
        overflow: hidden;
        border: 1px solid var(--public-border);
        border-radius: 16px;
        background: #ffffff;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .public-map-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12);
    }

    .public-map-card iframe {
        display: block;
        width: 100%;
        min-height: 260px;
        border: 0;
        pointer-events: none;
    }

    .public-map-card__caption {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        color: var(--public-muted);
        font-size: 13px;
        line-height: 1.4;
    }

    .public-map-card__caption strong {
        color: var(--public-text);
    }

    .public-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .public-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 18px;
        color: var(--public-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .public-legend span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .public-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-block;
    }

    .public-dot--free {
        background: #8bbf8d;
    }

    .public-dot--busy {
        background: #ed9b93;
    }

    .public-calendar-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .public-calendar {
        overflow: hidden;
        background: #fffdf8;
        border: 1px solid var(--public-border);
        border-radius: 16px;
    }

    .public-calendar__title {
        padding: 14px 16px;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: 20px;
        font-weight: 800;
        text-align: center;
        border-bottom: 1px solid var(--public-border);
        background: #fbf7ef;
    }

    .public-calendar table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .public-calendar th {
        padding: 10px 4px;
        color: var(--public-muted);
        font-size: 12px;
        text-align: center;
    }

    .public-calendar td {
        height: 42px;
        border-top: 1px solid #f0e8dc;
        text-align: center;
        font-size: 13px;
        font-weight: 800;
    }

    .public-calendar__empty {
        background: #faf7f0;
    }

    .public-calendar__free {
        background: var(--public-green-bg);
        color: #195b35;
    }

    .public-calendar__busy {
        background: var(--public-red-bg);
        color: var(--public-red);
    }

    .public-benefits {
        display: grid;
        grid-template-columns: 1.2fr repeat(4, minmax(0, 1fr));
        gap: 20px;
        align-items: stretch;
    }

    .public-benefit-intro,
    .public-benefit {
        padding: 24px;
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 18px;
    }

    .public-benefit-intro h2 {
        margin: 0;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: 30px;
        line-height: 1.1;
    }

    .public-benefit-intro p,
    .public-benefit p {
        color: var(--public-muted);
        line-height: 1.65;
    }

    .public-benefit strong {
        display: block;
        margin-top: 12px;
        color: var(--public-green);
        font-size: 17px;
    }

    .public-benefit__icon {
        color: var(--public-green);
        font-size: 30px;
    }

    .public-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 420px;
        gap: 24px;
    }

    .public-form-card,
    .public-contact-card {
        padding: 22px;
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 22px;
        box-shadow: 0 18px 45px rgba(23, 35, 29, 0.08);
    }

    .public-form-card h2,
    .public-contact-card h2 {
        margin: 0 0 10px;
        color: var(--public-green);
        font-family: Georgia, serif;
        font-size: 30px;
        letter-spacing: -0.04em;
    }

    .public-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 22px;
    }

    .public-form-grid .public-label--full {
        grid-column: 1 / -1;
    }

    .public-error {
        color: var(--public-red);
        font-size: 12px;
        font-weight: 800;
    }

    .public-alert {
        padding: 14px 16px;
        margin: 0 0 18px;
        border-radius: 12px;
        font-weight: 800;
    }

    .public-alert--success {
        background: var(--public-green-bg);
        color: #195b35;
    }

    .public-alert--warning {
        background: #fff3cd;
        color: #7a5a00;
    }

    .public-alert--danger {
        background: var(--public-red-bg);
        color: var(--public-red);
    }

    .public-contact-list {
        display: grid;
        gap: 14px;
        margin-top: 22px;
    }

    .public-contact-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 14px 0;
        border-bottom: 1px solid var(--public-border);
    }

    .public-contact-row span {
        color: var(--public-muted);
    }

    .public-contact-row strong {
        color: var(--public-text);
        text-align: right;
    }

    .public-footer {
        margin-top: 70px;
        padding: 42px 0;
        background: var(--public-green);
        color: #ffffff;
    }

    .public-footer__grid {
        display: grid;
        grid-template-columns: 1.3fr 1fr 1fr;
        gap: 32px;
    }

    .public-footer a {
        color: #ffffff;
        text-decoration: none;
    }

    .public-footer p {
        color: rgba(255, 255, 255, 0.76);
        line-height: 1.7;
    }

    .public-media-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .public-media-card {
        overflow: hidden;
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 18px;
        box-shadow: 0 16px 38px rgba(23, 35, 29, 0.08);
    }

    .public-media-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
    }

    .public-media-card__body {
        padding: 14px 16px;
    }

    .public-media-card__body strong {
        display: block;
        color: var(--public-green);
        font-size: 15px;
    }

    .public-attraction-photos {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 22px;
    }

    .public-attraction-photo {
        overflow: hidden;
        min-height: 210px;
        border: 1px solid var(--public-border);
        border-radius: 18px;
        background: var(--public-card);
        box-shadow: 0 16px 38px rgba(23, 35, 29, 0.08);
    }

    .public-attraction-photo img {
        width: 100%;
        height: 100%;
        min-height: 210px;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 1050px) {
        .public-menu {
            display: none;
        }

        .public-feature-strip__box,
        .public-cabins-grid,
        .public-calendar-grid,
        .public-benefits,
        .public-bottom-grid,
        .public-availability,
        .public-media-grid,
        .public-attraction-photos {
            grid-template-columns: 1fr;
        }

        .public-cabin-card__image {
            height: 230px;
        }

        .public-hero {
            margin-top: -88px;
        }
    }

    @media (max-width: 700px) {
        .public-nav {
            top: 0;
            width: 100%;
            margin-top: 0;
            border-radius: 0;
        }

        .public-nav__inner {
            padding: 12px 16px;
        }

        .public-logo strong {
            font-size: 22px;
        }

        .public-hero {
            min-height: 560px;
        }

        .public-hero__content {
            padding-top: 130px;
        }

        .public-feature {
            border-right: 0;
            border-bottom: 1px solid var(--public-border);
        }

        .public-feature:last-child {
            border-bottom: 0;
        }

        .public-section {
            padding-top: 48px;
        }

        .public-section__head,
        .public-hero__actions {
            display: grid;
        }

        .public-form-grid {
            grid-template-columns: 1fr;
        }

        .public-availability {
            padding: 18px;
        }

        .public-calendar td {
            height: 38px;
            font-size: 12px;
        }

        .public-footer__grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="public-page">
    <nav class="public-nav">
        <div class="public-nav__inner">
            <a class="public-logo" href="/">
                <strong>Domki Sztabinki</strong>
                <span>komfortowe domki nad jeziorem</span>
            </a>

            <div class="public-menu">
                <a href="#domki">Domki</a>
                <a href="#dostepnosc">Dostępność</a>
                <a href="#atrakcje">Atrakcje</a>
                <a href="#galeria">Galeria</a>
                <a href="#cennik">Cennik</a>
                <a href="#kontakt">Kontakt</a>
            </div>

            <a class="public-button" href="#zapytanie">
                Zapytaj o termin
            </a>
        </div>
    </nav>

    <header class="public-hero">
        <div class="public-wrap">
            <div class="public-hero__content">
                <p class="public-kicker">Sztabinki · Sejneńszczyzna · nad jeziorem</p>

                <h1>Twój wypoczynek nad jeziorem Sztabinki</h1>

                <p>
                    Komfortowe domki w spokojnej okolicy, blisko natury i wody.
                    Idealne miejsce na rodzinny wypoczynek, weekend z przyjaciółmi
                    i spokojny odpoczynek z dala od zgiełku.
                </p>

                <div class="public-hero__actions">
                    <a class="public-button" href="#dostepnosc">
                        Sprawdź dostępność
                    </a>

                    <a class="public-button public-button--light" href="#domki">
                        Zobacz domki
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="public-feature-strip">
        <div class="public-wrap">
            <div class="public-feature-strip__box">
                <div class="public-feature">
                    <div class="public-feature__icon">⌂</div>
                    <div>
                        <strong>4 komfortowe domki</strong>
                        <span>kameralny wypoczynek</span>
                    </div>
                </div>

                <div class="public-feature">
                    <div class="public-feature__icon">👥</div>
                    <div>
                        <strong>Do 6 osób</strong>
                        <span>w każdym domku</span>
                    </div>
                </div>

                <div class="public-feature">
                    <div class="public-feature__icon">≈</div>
                    <div>
                        <strong>Jezioro Sztabinki</strong>
                        <span>blisko domków</span>
                    </div>
                </div>

                <div class="public-feature">
                    <div class="public-feature__icon">⌁</div>
                    <div>
                        <strong>Wi-Fi i parking</strong>
                        <span>w cenie pobytu</span>
                    </div>
                </div>

                <div class="public-feature">
                    <div class="public-feature__icon">♨</div>
                    <div>
                        <strong>Grill i altana</strong>
                        <span>przy domkach</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
        <div class="public-wrap public-section">
            <div class="public-alert public-alert--warning">
                <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="public-section" id="domki">
        <div class="public-wrap">
            <div class="public-section__head">
                <div>
                    <p class="public-kicker">Oferta</p>

                    <h2>Nasze domki</h2>

                    <p>
                        Wybierz domek, sprawdź dostępność i wyślij zapytanie.
                        Układ jest przygotowany pod cztery domki.
                    </p>
                </div>

                <a class="public-button public-button--light" href="#dostepnosc">
                    Sprawdź terminy
                </a>
            </div>

            <?php if ($cabins === []): ?>
                <div class="public-form-card">
                    <h2>Brak aktywnych domków</h2>

                    <p>
                        Dodaj domek w panelu administratora albo ustaw istniejący domek jako aktywny.
                    </p>

                    <a class="public-button" href="/admin/domki">
                        Przejdź do panelu domków
                    </a>
                </div>
            <?php else: ?>
                <div class="public-cabins-grid">
                    <?php foreach ($cabins as $offerCabin): ?>
                        <?php
                        $cabinId = $cabinInt($offerCabin, 'id', 0);
                        $cabinName = $cabinString($offerCabin, 'name', 'Domek');
                        $cabinShortName = $cabinString($offerCabin, 'short_name', 'Domek');
                        $cabinDescription = $cabinString(
                            $offerCabin,
                            'description',
                            'Komfortowy domek letniskowy nad jeziorem w spokojnej okolicy.'
                        );
                        $image = $cabinImages[$cabinId] ?? null;
                        ?>

                        <article class="public-cabin-card">
                            <div class="public-cabin-card__image">
                                <span class="public-cabin-card__badge">
                                    <?= htmlspecialchars($cabinShortName, ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <?php if (is_array($image)): ?>
                                    <img
                                        src="<?= htmlspecialchars((string) $image['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars((string) ($image['alt_text'] ?? $cabinName), ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                <?php endif; ?>
                            </div>

                            <div class="public-cabin-card__body">
                                <h3><?= htmlspecialchars($cabinName, ENT_QUOTES, 'UTF-8') ?></h3>

                                <p>
                                    <?= htmlspecialchars($cabinDescription, ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <div class="public-cabin-meta">
                                    <span><?= htmlspecialchars((string) $cabinInt($offerCabin, 'max_guests', 6), ENT_QUOTES, 'UTF-8') ?> osób</span>
                                    <span><?= htmlspecialchars((string) $cabinInt($offerCabin, 'bedrooms', 2), ENT_QUOTES, 'UTF-8') ?> sypialnie</span>
                                    <span>Wi-Fi</span>
                                </div>

                                <div class="public-cabin-actions">
                                    <a
                                        class="public-button public-button--wide"
                                        href="/?availability_cabin_id=<?= htmlspecialchars((string) $cabinId, ENT_QUOTES, 'UTF-8') ?>#dostepnosc"
                                    >
                                        Sprawdź dostępność
                                    </a>

                                    <a
                                        class="public-button public-button--light public-button--wide"
                                        href="/?inquiry_cabin_id=<?= htmlspecialchars((string) $cabinId, ENT_QUOTES, 'UTF-8') ?>#zapytanie"
                                    >
                                        Zapytaj o ten domek
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="public-section" id="dostepnosc">
        <div class="public-wrap">
            <div class="public-availability">
                <div>
                    <p class="public-kicker">Dostępność</p>

                    <h2>Sprawdź dostępność</h2>

                    <p>
                        Wybierz domek i zobacz najbliższe 3 miesiące.
                        Dni oznaczone jako zajęte wynikają z rezerwacji w panelu.
                    </p>

                    <form class="public-availability-form" method="get" action="/#dostepnosc">
                        <label class="public-label" for="availability_cabin_id">
                            Wybierz domek
                            <select class="public-select" id="availability_cabin_id" name="availability_cabin_id">
                                <?php foreach ($cabins as $optionCabin): ?>
                                    <?php
                                    $optionId = $cabinInt($optionCabin, 'id', 0);
                                    $optionName = $cabinString($optionCabin, 'name', 'Domek');
                                    ?>

                                    <option
                                        value="<?= htmlspecialchars((string) $optionId, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $availabilityCabinId === $optionId ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="public-label" for="availability_month">
                            Miesiąc początkowy
                            <input
                                class="public-input"
                                id="availability_month"
                                name="availability_month"
                                type="month"
                                value="<?= htmlspecialchars($availabilityMonthStart->format('Y-m'), ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </label>

                        <button class="public-button public-button--wide" type="submit">
                            Pokaż dostępność
                        </button>

                        <div class="public-hero__actions" style="margin-top: 0;">
                            <a
                                class="public-button public-button--light"
                                href="/?availability_cabin_id=<?= htmlspecialchars((string) $availabilityCabinId, ENT_QUOTES, 'UTF-8') ?>&availability_month=<?= htmlspecialchars($previousAvailabilityMonth, ENT_QUOTES, 'UTF-8') ?>#dostepnosc"
                            >
                                Poprzedni miesiąc
                            </a>

                            <a
                                class="public-button public-button--light"
                                href="/?availability_cabin_id=<?= htmlspecialchars((string) $availabilityCabinId, ENT_QUOTES, 'UTF-8') ?>&availability_month=<?= htmlspecialchars($nextAvailabilityMonth, ENT_QUOTES, 'UTF-8') ?>#dostepnosc"
                            >
                                Następny miesiąc
                            </a>
                        </div>
                    </form>

                    <div class="public-legend">
                        <span><i class="public-dot public-dot--free"></i> Dostępny</span>
                        <span><i class="public-dot public-dot--busy"></i> Zajęty</span>
                    </div>
                </div>

                <div>
                    <div class="public-section__head" style="margin-bottom: 16px;">
                        <div>
                            <p class="public-kicker">Wybrany domek</p>
                            <h2 style="font-size: 32px;"><?= htmlspecialchars($selectedCabinName, ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                    </div>

                    <div class="public-calendar-grid">
                        <?php foreach ($availabilityMonths as $availabilityMonth): ?>
                            <div class="public-calendar">
                                <div class="public-calendar__title">
                                    <?= htmlspecialchars((string) $availabilityMonth['label'], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <table>
                                    <thead>
                                        <tr>
                                            <?php foreach ($weekdayLabels as $weekdayLabel): ?>
                                                <th><?= htmlspecialchars($weekdayLabel, ENT_QUOTES, 'UTF-8') ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($availabilityMonth['weeks'] as $calendarWeek): ?>
                                            <tr>
                                                <?php foreach ($calendarWeek as $calendarDate): ?>
                                                    <?php if ($calendarDate === null): ?>
                                                        <td class="public-calendar__empty"></td>
                                                    <?php else: ?>
                                                        <?php
                                                        $dayNumber = (string) (int) substr((string) $calendarDate, 8, 2);
                                                        $dayStatus = $selectedBusyDates[(string) $calendarDate] ?? null;
                                                        $isBusy = is_string($dayStatus);
                                                        ?>

                                                        <td class="<?= $isBusy ? 'public-calendar__busy' : 'public-calendar__free' ?>">
                                                            <?= htmlspecialchars($dayNumber, ENT_QUOTES, 'UTF-8') ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section" id="atrakcje">
        <div class="public-wrap">
            <div class="public-benefits">
                <div class="public-benefit-intro">
                    <p class="public-kicker">Dlaczego warto?</p>

                    <h2>Spokój, natura i wygoda</h2>

                    <p>
                        Domki Sztabinki to miejsce dla osób, które chcą odpocząć blisko jeziora,
                        w komfortowych warunkach i spokojnej okolicy.
                    </p>
                </div>

                <div class="public-benefit">
                    <div class="public-benefit__icon">≈</div>
                    <strong>Blisko jeziora</strong>
                    <p>Czysta woda, pomost, piękne widoki i możliwość wypoczynku nad brzegiem.</p>
                </div>

                <div class="public-benefit">
                    <div class="public-benefit__icon">⚓</div>
                    <strong>Sprzęt wodny</strong>
                    <p>Łódka, kajak i rowerki wodne dostępne dla gości w cenie pobytu.</p>
                </div>

                <div class="public-benefit">
                    <div class="public-benefit__icon">♨</div>
                    <strong>Grill i altana</strong>
                    <p>Przy każdym domku miejsce do odpoczynku, grillowania i spotkań.</p>
                </div>

                <div class="public-benefit">
                    <div class="public-benefit__icon">♧</div>
                    <strong>Cisza i natura</strong>
                    <p>Okolica sprzyjająca relaksowi, spacerom i spokojnemu wypoczynkowi.</p>
                </div>
            </div>

            <?php if ($siteAttractionImages !== []): ?>
                <div class="public-attraction-photos">
                    <?php foreach (array_slice($siteAttractionImages, 0, 3) as $attractionImage): ?>
                        <figure class="public-attraction-photo">
                            <img
                                src="<?= htmlspecialchars((string) $attractionImage['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string) ($attractionImage['alt_text'] ?? 'Domki Sztabinki - atrakcje'), ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($siteGalleryImages !== []): ?>
        <section class="public-section" id="galeria">
            <div class="public-wrap">
                <div class="public-section__head">
                    <div>
                        <p class="public-kicker">Galeria</p>

                        <h2>Zdjęcia z Domków Sztabinki</h2>

                        <p>
                            Zobacz otoczenie, domki, jezioro i miejsca przygotowane do wypoczynku.
                        </p>
                    </div>

                    <a class="public-button public-button--light" href="#zapytanie">
                        Zapytaj o termin
                    </a>
                </div>

                <div class="public-media-grid">
                    <?php foreach (array_slice($siteGalleryImages, 0, 8) as $galleryImage): ?>
                        <article class="public-media-card">
                            <img
                                src="<?= htmlspecialchars((string) $galleryImage['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string) ($galleryImage['alt_text'] ?? 'Domki Sztabinki - galeria'), ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <div class="public-media-card__body">
                                <strong>
                                    <?= htmlspecialchars((string) ($galleryImage['alt_text'] ?? 'Domki Sztabinki'), ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="public-section" id="cennik">
        <div class="public-wrap">
            <div class="public-section__head">
                <div>
                    <p class="public-kicker">Cennik</p>

                    <h2>Przejrzyste ceny</h2>

                    <p>
                        Cena zależy od długości pobytu. Ceny dotyczą całego domku za dobę.
                    </p>
                </div>
            </div>

            <div class="public-form-card">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Długość pobytu</th>
                                <th>1 noc</th>
                                <th>2 noce</th>
                                <th>3 noce</th>
                                <th>4 noce</th>
                                <th>5 nocy</th>
                                <th>6 nocy</th>
                                <th>7+ nocy</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Cena za noc</td>
                                <td><?= htmlspecialchars($formatPublicPrice(800, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(440, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(430, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(420, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(410, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(400, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatPublicPrice(350, $currency), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p style="color: var(--public-muted); line-height: 1.7;">
                    W cenie pobytu: sprzęt wodny, dostęp do pomostu, Wi-Fi, parking, grill i altana.
                    Szczegóły pobytu oraz dostępność potwierdzamy po wysłaniu zapytania.
                </p>
            </div>
        </div>
    </section>

    <section class="public-section" id="zapytanie">
        <div class="public-wrap">
            <div class="public-bottom-grid">
                <div class="public-form-card">
                    <p class="public-kicker">Zapytanie</p>

                    <h2>Zapytaj o termin</h2>

                    <p style="color: var(--public-muted); line-height: 1.7;">
                        Wypełnij formularz. Zapytanie trafi do panelu administratora,
                        a my odpowiemy z potwierdzeniem dostępności i ceny.
                    </p>

                    <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                        <div class="public-alert public-alert--success">
                            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($inquiryMessage) && is_string($inquiryMessage) && $inquiryMessage !== ''): ?>
                        <div class="public-alert public-alert--warning">
                            <?= htmlspecialchars($inquiryMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="public-alert public-alert--danger">
                            Popraw błędy w formularzu.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/zapytanie#zapytanie">
                        <div class="public-form-grid">
                            <label class="public-label" for="first_name">
                                Imię
                                <input
                                    class="public-input"
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    placeholder="Jan"
                                    value="<?= htmlspecialchars($form['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['first_name'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="last_name">
                                Nazwisko
                                <input
                                    class="public-input"
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    placeholder="Kowalski"
                                    value="<?= htmlspecialchars($form['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['last_name'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="phone">
                                Telefon
                                <input
                                    class="public-input"
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    placeholder="+48 600 000 000"
                                    value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['phone'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="email">
                                E-mail
                                <input
                                    class="public-input"
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="jan.kowalski@example.com"
                                    value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <?php if (isset($errors['email'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="cabin_id">
                                Domek
                                <select class="public-select" id="cabin_id" name="cabin_id">
                                    <option value="">Dowolny / proszę zaproponować</option>

                                    <?php foreach ($cabins as $optionCabin): ?>
                                        <?php
                                        $optionId = $cabinInt($optionCabin, 'id', 0);
                                        $optionName = $cabinString($optionCabin, 'name', 'Domek');
                                        ?>

                                        <option
                                            value="<?= htmlspecialchars((string) $optionId, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= $form['cabin_id'] === (string) $optionId ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php if (isset($errors['cabin_id'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['cabin_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="date_from">
                                Przyjazd
                                <input
                                    class="public-input"
                                    id="date_from"
                                    name="date_from"
                                    type="date"
                                    value="<?= htmlspecialchars($form['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['date_from'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['date_from'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="date_to">
                                Wyjazd
                                <input
                                    class="public-input"
                                    id="date_to"
                                    name="date_to"
                                    type="date"
                                    value="<?= htmlspecialchars($form['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['date_to'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['date_to'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="adults">
                                Dorośli
                                <input
                                    class="public-input"
                                    id="adults"
                                    name="adults"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="<?= htmlspecialchars($form['adults'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['adults'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['adults'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="children">
                                Dzieci
                                <input
                                    class="public-input"
                                    id="children"
                                    name="children"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($form['children'], ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >

                                <?php if (isset($errors['children'])): ?>
                                    <span class="public-error"><?= htmlspecialchars($errors['children'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </label>

                            <label class="public-label" for="city">
                                Adres
                                <input
                                    class="public-input"
                                    id="city"
                                    name="city"
                                    type="text"
                                    placeholder="ul. Słoneczna 12, 53-000 Wrocław"
                                    value="<?= htmlspecialchars($form['city'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </label>

                            <label class="public-label" for="country">
                                Kraj
                                <input
                                    class="public-input"
                                    id="country"
                                    name="country"
                                    type="text"
                                    placeholder="Polska"
                                    value="<?= htmlspecialchars($form['country'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            </label>

                            <label class="public-label public-label--full" for="notes">
                                Wiadomość / dodatkowe informacje
                                <textarea class="public-textarea" id="notes" name="notes" placeholder="Np. godzina przyjazdu, pytania o domek, dodatkowe informacje"><?= htmlspecialchars($form['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                        </div>

                        <div style="margin-top: 18px;">
                            <button class="public-button public-button--wide" type="submit">
                                Wyślij zapytanie
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="public-contact-card" id="kontakt">
                    <p class="public-kicker">Kontakt</p>

                    <h2>Dane kontaktowe</h2>

                        <a
                            class="public-map-card"
                            href="https://www.google.com/maps/dir/?api=1&destination=Domki%20Sztabinki%20%C5%BBegary"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Otwórz dojazd do Domków Sztabinki w Google Maps"
                        >
                            <iframe
                                src="https://www.google.com/maps?q=Domki%20Sztabinki%20%C5%BBegary&output=embed"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Mapa dojazdu do Domków Sztabinki"
                            ></iframe>

                            <span class="public-map-card__caption">
                                <strong>Domki Sztabinki</strong>
                                <span>Kliknij mapę, aby otworzyć dojazd w Google Maps</span>
                            </span>
                        </a>


                    <div class="public-contact-list">
                        <div class="public-contact-row">
                            <span>Obiekt</span>
                            <strong><?= htmlspecialchars($settings['property_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="public-contact-row">
                            <span>Telefon</span>
                            <strong><?= htmlspecialchars($settings['contact_phone'] !== '' ? $settings['contact_phone'] : '+48 502 286 718', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="public-contact-row">
                            <span>E-mail</span>
                            <strong><?= htmlspecialchars($settings['contact_email'] !== '' ? $settings['contact_email'] : 'radekzdancewicz@gmail.com', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>

                        <div class="public-contact-row">
                            <span>Adres</span>
                            <strong>
                                <?= htmlspecialchars($settings['address_line'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars($settings['postal_code'], ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars($settings['city'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <a class="public-button public-button--wide" href="/logowanie">
                            Panel administratora
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <footer class="public-footer">
        <div class="public-wrap">
            <div class="public-footer__grid">
                <div>
                    <a class="public-logo" href="/" style="color: #ffffff;">
                        <strong style="color: #ffffff;">Domki Sztabinki</strong>
                        <span style="color: rgba(255,255,255,0.75);">wypoczynek nad jeziorem</span>
                    </a>

                    <p>
                        Komfortowe domki w sercu Sejneńszczyzny.
                        Natura, cisza i wygoda — wszystko, czego potrzebujesz, by odpocząć.
                    </p>
                </div>

                <div>
                    <strong>Nawigacja</strong>

                    <p>
                        <a href="#domki">Domki</a><br>
                        <a href="#dostepnosc">Dostępność</a><br>
                        <a href="#atrakcje">Atrakcje</a><br>
                        <a href="#galeria">Galeria</a><br>
                        <a href="#zapytanie">Zapytaj o termin</a>
                    </p>
                </div>

                <div>
                    <strong>Kontakt</strong>

                    <p>
                        <?= htmlspecialchars($settings['contact_phone'] !== '' ? $settings['contact_phone'] : '+48 502 286 718', ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($settings['contact_email'] !== '' ? $settings['contact_email'] : 'radekzdancewicz@gmail.com', ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($settings['city'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>

            <p style="margin-top: 34px; font-size: 13px;">
                © <?= htmlspecialchars(date('Y'), ENT_QUOTES, 'UTF-8') ?> Domki Sztabinki. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </footer>
</section>