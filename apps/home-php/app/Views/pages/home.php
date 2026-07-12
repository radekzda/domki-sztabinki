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

$requestedCalendarMonth = isset($_GET['availability_month'])
    ? (string) $_GET['availability_month']
    : date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $requestedCalendarMonth)) {
    $requestedCalendarMonth = date('Y-m');
}

try {
    $calendarMonthStart = new DateTimeImmutable($requestedCalendarMonth . '-01');
} catch (Throwable $exception) {
    $calendarMonthStart = new DateTimeImmutable(date('Y-m-01'));
}

$calendarMonthEnd = $calendarMonthStart->modify('first day of next month');
$previousCalendarMonth = $calendarMonthStart->modify('-1 month')->format('Y-m');
$nextCalendarMonth = $calendarMonthStart->modify('+1 month')->format('Y-m');
$calendarMonthStartString = $calendarMonthStart->format('Y-m-d');
$calendarMonthEndString = $calendarMonthEnd->format('Y-m-d');

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

$calendarMonthLabel = ($monthNames[$calendarMonthStart->format('m')] ?? $calendarMonthStart->format('m'))
    . ' '
    . $calendarMonthStart->format('Y');

$weekdayLabels = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd'];

$calendarCells = [];
$firstWeekday = (int) $calendarMonthStart->format('N');
$daysInMonth = (int) $calendarMonthStart->format('t');

for ($emptyDay = 1; $emptyDay < $firstWeekday; $emptyDay++) {
    $calendarCells[] = null;
}

for ($day = 1; $day <= $daysInMonth; $day++) {
    $calendarCells[] = $calendarMonthStart->setDate(
        (int) $calendarMonthStart->format('Y'),
        (int) $calendarMonthStart->format('m'),
        $day
    )->format('Y-m-d');
}

while (count($calendarCells) % 7 !== 0) {
    $calendarCells[] = null;
}

$calendarWeeks = array_chunk($calendarCells, 7);

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

            if ($endDate <= $calendarMonthStartString || $startDate >= $calendarMonthEndString) {
                continue;
            }

            $rangeStartString = $startDate > $calendarMonthStartString
                ? $startDate
                : $calendarMonthStartString;

            $rangeEndString = $endDate < $calendarMonthEndString
                ? $endDate
                : $calendarMonthEndString;

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
?>
<section class="page-section">
    <div class="container">
        <div class="panel">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Wypoczynek nad jeziorem</p>

                    <h1><?= htmlspecialchars($settings['property_name'], ENT_QUOTES, 'UTF-8') ?></h1>

                    <p>
                        <?= nl2br(htmlspecialchars($settings['public_short_description'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                </div>

                <div class="page-header__actions">
                    <a class="button button--primary" href="#domki">
                        Zobacz domki
                    </a>

                    <a class="button button--secondary" href="#zapytanie">
                        Zapytaj o termin
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
                    <span>Zameldowanie</span>
                    <strong>od <?= htmlspecialchars($settings['check_in_time'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="stat-card">
                    <span>Wymeldowanie</span>
                    <strong>do <?= htmlspecialchars($settings['check_out_time'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="stat-card">
                    <span>Minimum pobytu</span>
                    <strong>
                        <?= htmlspecialchars($settings['minimum_nights'], ENT_QUOTES, 'UTF-8') ?>
                        noce
                    </strong>
                </div>

                <div class="stat-card">
                    <span>Aktywne domki</span>
                    <strong><?= htmlspecialchars((string) count($cabins), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        </div>

        <div class="panel" id="domki">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Oferta</p>

                    <h2>Nasze domki</h2>

                    <p>
                        Poniżej widoczne są tylko domki aktywne w panelu administratora.
                    </p>
                </div>
            </div>

            <?php if ($cabins === []): ?>
                <div class="empty-state">
                    <strong>Brak aktywnych domków do pokazania</strong>

                    <p>
                        Dodaj domek w panelu administratora albo ustaw istniejący domek jako aktywny.
                    </p>

                    <a class="button button--secondary" href="/admin/domki">
                        Przejdź do panelu domków
                    </a>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 24px;">
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
                        $busyRanges = $busyRangesByCabin[$cabinId] ?? [];
                        $busyDates = $busyDatesByCabin[$cabinId] ?? [];
                        ?>

                        <article class="panel" style="margin: 0; box-shadow: none; border: 1px solid #e5e7eb;">
                            <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 420px); gap: 24px; align-items: start;">
                                <div>
                                    <p class="eyebrow">
                                        <?= htmlspecialchars($cabinShortName, ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <h3><?= htmlspecialchars($cabinName, ENT_QUOTES, 'UTF-8') ?></h3>

                                    <p>
                                        <?= nl2br(htmlspecialchars($cabinDescription, ENT_QUOTES, 'UTF-8')) ?>
                                    </p>

                                    <div class="dashboard-grid">
                                        <div class="stat-card">
                                            <span>Maksymalnie</span>
                                            <strong>
                                                <?= htmlspecialchars((string) $cabinInt($offerCabin, 'max_guests', 6), ENT_QUOTES, 'UTF-8') ?>
                                                os.
                                            </strong>
                                        </div>

                                        <div class="stat-card">
                                            <span>Sypialnie</span>
                                            <strong><?= htmlspecialchars((string) $cabinInt($offerCabin, 'bedrooms', 2), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>

                                        <div class="stat-card">
                                            <span>Łazienki</span>
                                            <strong><?= htmlspecialchars((string) $cabinInt($offerCabin, 'bathrooms', 1), ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    </div>

                                    <div class="table-wrapper">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Długość pobytu</th>
                                                    <th>Cena za noc</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td>1 noc</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_one_night', 800), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>2 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_two_nights', 440), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>3 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_three_nights', 430), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>4 noce</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_four_nights', 420), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>5 nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_five_nights', 410), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>6 nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_six_nights', 400), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>

                                                <tr>
                                                    <td>7+ nocy</td>
                                                    <td><?= htmlspecialchars($formatPublicPrice($cabinInt($offerCabin, 'price_seven_plus_nights', 350), $currency), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="empty-state">
                                        <strong>Najbliższe zajęte terminy</strong>

                                        <?php if ($busyRanges === []): ?>
                                            <p>
                                                Brak najbliższych zajętych terminów w systemie.
                                                Wyślij zapytanie, aby potwierdzić dostępność.
                                            </p>
                                        <?php else: ?>
                                            <div class="status-list">
                                                <?php foreach ($busyRanges as $range): ?>
                                                    <div class="status-row">
                                                        <span>
                                                            <?= htmlspecialchars(formatDateForDisplay((string) $range['start_date']), ENT_QUOTES, 'UTF-8') ?>
                                                            –
                                                            <?= htmlspecialchars(formatDateForDisplay((string) $range['end_date']), ENT_QUOTES, 'UTF-8') ?>
                                                        </span>

                                                        <strong>
                                                            <?= htmlspecialchars($statusLabel((string) $range['status']), ENT_QUOTES, 'UTF-8') ?>
                                                        </strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <p>
                                                Lista pokazuje tylko najbliższe terminy blokujące. O pełną dostępność zapytaj przez formularz.
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="empty-state">
                                        <div class="page-header" style="margin-bottom: 12px;">
                                            <div>
                                                <strong>Kalendarz dostępności</strong>

                                                <p>
                                                    <?= htmlspecialchars($calendarMonthLabel, ENT_QUOTES, 'UTF-8') ?>.
                                                    Dzień wyjazdu nie jest oznaczany jako zajęty nocleg.
                                                </p>
                                            </div>

                                            <div class="page-header__actions">
                                                <a
                                                    class="button button--secondary"
                                                    href="/?availability_month=<?= htmlspecialchars($previousCalendarMonth, ENT_QUOTES, 'UTF-8') ?>#domki"
                                                >
                                                    Poprzedni
                                                </a>

                                                <a
                                                    class="button button--secondary"
                                                    href="/?availability_month=<?= htmlspecialchars($nextCalendarMonth, ENT_QUOTES, 'UTF-8') ?>#domki"
                                                >
                                                    Następny
                                                </a>
                                            </div>
                                        </div>

                                        <div class="table-wrapper">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <?php foreach ($weekdayLabels as $weekdayLabel): ?>
                                                            <th><?= htmlspecialchars($weekdayLabel, ENT_QUOTES, 'UTF-8') ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php foreach ($calendarWeeks as $calendarWeek): ?>
                                                        <tr>
                                                            <?php foreach ($calendarWeek as $calendarDate): ?>
                                                                <?php if ($calendarDate === null): ?>
                                                                    <td style="height: 64px; background: #f9fafb;"></td>
                                                                <?php else: ?>
                                                                    <?php
                                                                    $dayNumber = (string) (int) substr((string) $calendarDate, 8, 2);
                                                                    $dayStatus = $busyDates[(string) $calendarDate] ?? null;
                                                                    $isBusy = is_string($dayStatus);
                                                                    ?>

                                                                    <td style="height: 64px; vertical-align: top; <?= $isBusy ? 'background: #fef2f2;' : 'background: #f0fdf4;' ?>">
                                                                        <strong>
                                                                            <?= htmlspecialchars($dayNumber, ENT_QUOTES, 'UTF-8') ?>
                                                                        </strong>

                                                                        <br>

                                                                        <span style="display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 700; <?= $isBusy ? 'color: #991b1b;' : 'color: #166534;' ?>">
                                                                            <?= $isBusy ? 'zajęty' : 'wolny' ?>
                                                                        </span>
                                                                    </td>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <?php if (is_array($image)): ?>
                                        <img
                                            src="<?= htmlspecialchars($image['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($image['alt_text'] ?? $cabinName, ENT_QUOTES, 'UTF-8') ?>"
                                            style="width: 100%; height: 320px; object-fit: cover; border-radius: 18px; border: 1px solid #e5e7eb;"
                                        >
                                    <?php else: ?>
                                        <div
                                            class="empty-state"
                                            style="min-height: 320px; display: flex; flex-direction: column; justify-content: center;"
                                        >
                                            <strong>Brak zdjęcia głównego</strong>

                                            <p>
                                                Dodaj zdjęcie w panelu administratora.
                                            </p>

                                            <a
                                                class="button button--secondary"
                                                href="/admin/domki/zdjecia?id=<?= htmlspecialchars((string) $cabinId, ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                Dodaj zdjęcie
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Dodatkowe informacje</p>

                    <h2>Ceny dodatkowe i zasady</h2>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <span>Łowienie w jeziorze</span>
                    <strong>
                        <?= htmlspecialchars($formatPublicPrice((int) $settings['fishing_price'], $currency), ENT_QUOTES, 'UTF-8') ?>
                        / dzień
                    </strong>
                </div>

                <div class="stat-card">
                    <span>Balia / kubil</span>
                    <strong>
                        <?= htmlspecialchars($formatPublicPrice((int) $settings['hot_tub_price'], $currency), ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            </div>

            <div class="empty-state">
                <strong>Zasady pobytu</strong>

                <p>
                    <?= nl2br(htmlspecialchars($settings['booking_rules'], ENT_QUOTES, 'UTF-8')) ?>
                </p>
            </div>
        </div>

        <div class="panel" id="zapytanie">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Zapytanie</p>

                    <h2>Zapytaj o wolny termin</h2>

                    <p>
                        Wyślij zapytanie o pobyt. Po wysłaniu pojawi się ono w panelu administratora
                        w zakładce „Zapytania”.
                    </p>
                </div>
            </div>

            <?php if (isset($successMessage) && is_string($successMessage) && $successMessage !== ''): ?>
                <div class="alert alert--success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (isset($inquiryMessage) && is_string($inquiryMessage) && $inquiryMessage !== ''): ?>
                <div class="alert alert--warning">
                    <?= htmlspecialchars($inquiryMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="alert alert--danger">
                    Popraw błędy w formularzu.
                </div>
            <?php endif; ?>

            <form class="form form--wide" method="post" action="/zapytanie">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="first_name">Imię</label>
                        <input
                            id="first_name"
                            name="first_name"
                            type="text"
                            value="<?= htmlspecialchars($form['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['first_name'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="last_name">Nazwisko</label>
                        <input
                            id="last_name"
                            name="last_name"
                            type="text"
                            value="<?= htmlspecialchars($form['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['last_name'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="phone">Telefon</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['phone'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <?php if (isset($errors['email'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="cabin_id">Domek</label>
                        <select id="cabin_id" name="cabin_id">
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
                            <span class="form-error"><?= htmlspecialchars($errors['cabin_id'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="date_from">Przyjazd</label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="<?= htmlspecialchars($form['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['date_from'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['date_from'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="date_to">Wyjazd</label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="<?= htmlspecialchars($form['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['date_to'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['date_to'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="adults">Dorośli</label>
                        <input
                            id="adults"
                            name="adults"
                            type="number"
                            min="1"
                            step="1"
                            value="<?= htmlspecialchars($form['adults'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['adults'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['adults'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="children">Dzieci</label>
                        <input
                            id="children"
                            name="children"
                            type="number"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars($form['children'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >

                        <?php if (isset($errors['children'])): ?>
                            <span class="form-error"><?= htmlspecialchars($errors['children'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="city">Miejscowość</label>
                        <input
                            id="city"
                            name="city"
                            type="text"
                            value="<?= htmlspecialchars($form['city'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="form-field">
                        <label for="country">Kraj</label>
                        <input
                            id="country"
                            name="country"
                            type="text"
                            value="<?= htmlspecialchars($form['country'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="form-field form-field--full">
                        <label for="notes">Wiadomość / dodatkowe informacje</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="5"
                        ><?= htmlspecialchars($form['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button button--primary" type="submit">
                        Wyślij zapytanie
                    </button>
                </div>
            </form>
        </div>

        <div class="panel" id="kontakt">
            <div class="page-header">
                <div>
                    <p class="eyebrow">Kontakt</p>

                    <h2>Dane kontaktowe</h2>
                </div>
            </div>

            <div class="status-list">
                <div class="status-row">
                    <span>Obiekt</span>
                    <strong><?= htmlspecialchars($settings['property_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>E-mail</span>
                    <strong><?= htmlspecialchars($settings['contact_email'] !== '' ? $settings['contact_email'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>Telefon</span>
                    <strong><?= htmlspecialchars($settings['contact_phone'] !== '' ? $settings['contact_phone'] : '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>

                <div class="status-row">
                    <span>Adres</span>
                    <strong>
                        <?= htmlspecialchars($settings['address_line'], ENT_QUOTES, 'UTF-8') ?>,
                        <?= htmlspecialchars($settings['postal_code'], ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($settings['city'], ENT_QUOTES, 'UTF-8') ?>,
                        <?= htmlspecialchars($settings['country'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button--secondary" href="/logowanie">
                    Panel administratora
                </a>
            </div>
        </div>
    </div>
</section>