<?php

declare(strict_types=1);

function defaultCabinForm(): array
{
    return [
        'name' => '',
        'short_name' => '',
        'description' => '',
        'max_guests' => '6',
        'bedrooms' => '2',
        'bathrooms' => '1',
        'price_per_night' => '440',
        'price_one_night' => '800',
        'price_two_nights' => '440',
        'price_three_nights' => '430',
        'price_four_nights' => '420',
        'price_five_nights' => '410',
        'price_six_nights' => '400',
        'price_seven_plus_nights' => '350',
        'is_active' => '1',
        'sort_order' => '0',
    ];
}

/**
 * @return array<string, string>
 */
function cabinFormFromPost(): array
{
    $defaults = defaultCabinForm();
    $form = [];

    foreach ($defaults as $key => $defaultValue) {
        $value = $_POST[$key] ?? $defaultValue;
        $form[$key] = is_string($value) ? trim($value) : $defaultValue;
    }

    return $form;
}

/**
 * @param array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     description: string,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * } $cabin
 * @return array<string, string>
 */
function cabinFormFromCabin(array $cabin): array
{
    return [
        'name' => $cabin['name'],
        'short_name' => $cabin['short_name'] ?? '',
        'description' => $cabin['description'],
        'max_guests' => (string) $cabin['max_guests'],
        'bedrooms' => (string) $cabin['bedrooms'],
        'bathrooms' => (string) $cabin['bathrooms'],
        'price_per_night' => (string) $cabin['price_per_night'],
        'price_one_night' => (string) $cabin['price_one_night'],
        'price_two_nights' => (string) $cabin['price_two_nights'],
        'price_three_nights' => (string) $cabin['price_three_nights'],
        'price_four_nights' => (string) $cabin['price_four_nights'],
        'price_five_nights' => (string) $cabin['price_five_nights'],
        'price_six_nights' => (string) $cabin['price_six_nights'],
        'price_seven_plus_nights' => (string) $cabin['price_seven_plus_nights'],
        'is_active' => (string) $cabin['is_active'],
        'sort_order' => (string) $cabin['sort_order'],
    ];
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateCabinForm(array $form): array
{
    $errors = [];

    if ($form['name'] === '') {
        $errors['name'] = 'Podaj nazwę domku.';
    }

    if ($form['description'] === '') {
        $errors['description'] = 'Podaj opis domku.';
    }

    $integerFields = [
        'max_guests' => 'Maksymalna liczba osób',
        'bedrooms' => 'Liczba sypialni',
        'bathrooms' => 'Liczba łazienek',
        'price_per_night' => 'Cena domyślna',
        'price_one_night' => 'Cena za 1 noc',
        'price_two_nights' => 'Cena za 2 noce',
        'price_three_nights' => 'Cena za 3 noce',
        'price_four_nights' => 'Cena za 4 noce',
        'price_five_nights' => 'Cena za 5 nocy',
        'price_six_nights' => 'Cena za 6 nocy',
        'price_seven_plus_nights' => 'Cena za 7+ nocy',
        'sort_order' => 'Kolejność',
    ];

    foreach ($integerFields as $field => $label) {
        if (!ctype_digit($form[$field])) {
            $errors[$field] = $label . ' musi być liczbą całkowitą.';
        }
    }

    if (isset($errors['max_guests']) === false && (int) $form['max_guests'] < 1) {
        $errors['max_guests'] = 'Maksymalna liczba osób musi być większa od zera.';
    }

    if (!in_array($form['is_active'], ['0', '1'], true)) {
        $errors['is_active'] = 'Nieprawidłowy status widoczności.';
    }

    return $errors;
}

/**
 * @param array<string, string> $form
 * @return array{
 *     name: string,
 *     short_name: string|null,
 *     description: string,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int
 * }
 */
function cabinDataFromForm(array $form): array
{
    return [
        'name' => $form['name'],
        'short_name' => $form['short_name'] !== '' ? $form['short_name'] : null,
        'description' => $form['description'],
        'max_guests' => (int) $form['max_guests'],
        'bedrooms' => (int) $form['bedrooms'],
        'bathrooms' => (int) $form['bathrooms'],
        'price_per_night' => (int) $form['price_per_night'],
        'price_one_night' => (int) $form['price_one_night'],
        'price_two_nights' => (int) $form['price_two_nights'],
        'price_three_nights' => (int) $form['price_three_nights'],
        'price_four_nights' => (int) $form['price_four_nights'],
        'price_five_nights' => (int) $form['price_five_nights'],
        'price_six_nights' => (int) $form['price_six_nights'],
        'price_seven_plus_nights' => (int) $form['price_seven_plus_nights'],
        'is_active' => (int) $form['is_active'],
        'sort_order' => (int) $form['sort_order'],
    ];
}

function cabinIdFromQuery(): ?int
{
    $value = $_GET['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function cabinIdFromPost(): ?int
{
    $value = $_POST['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function activeStatusFromPost(): ?bool
{
    $value = $_POST['is_active'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    if ((string) $value === '1') {
        return true;
    }

    if ((string) $value === '0') {
        return false;
    }

    return null;
}

function defaultReservationForm(): array
{
    return [
        'guest_id' => '',
        'cabin_id' => '',
        'guest_name' => '',
        'email' => '',
        'phone' => '',
        'start_date' => '',
        'end_date' => '',
        'check_in_time' => '15:00',
        'check_out_time' => '11:00',
        'adults' => '2',
        'children' => '0',
        'status' => 'PENDING',
        'payment_status' => 'PENDING',
        'paid_amount' => '0',
        'source' => 'MANUAL',
        'notes' => '',
    ];
}

/**
 * @return array<string, string>
 */


function reservationNormalizeTime(mixed $value, string $fallback): string
{
    if (!is_string($value) && !is_int($value)) {
        return $fallback;
    }

    $value = trim((string) $value);

    if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
        return $value;
    }

    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
        return substr($value, 0, 5);
    }

    return $fallback;
}

function reservationTimeFromDateTime(mixed $value, string $fallback): string
{
    if ($value === null) {
        return $fallback;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable($value))->format('H:i');
    } catch (Throwable $exception) {
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }
}

function reservationFormFromPost(): array
{
    $defaults = defaultReservationForm();
    $form = [];

    foreach ($defaults as $key => $defaultValue) {
        $value = $_POST[$key] ?? $defaultValue;
        $form[$key] = is_string($value) ? trim($value) : $defaultValue;
    }

    $form['check_in_time'] = reservationNormalizeTime($_POST['check_in_time'] ?? null, '15:00');
    $form['check_out_time'] = reservationNormalizeTime($_POST['check_out_time'] ?? null, '11:00');

    return $form;
}

/**
 * @param array{
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
 * @return array<string, string>
 */
function reservationFormFromReservation(array $reservation): array
{
    return [
        'guest_id' => $reservation['guest_id'] !== null ? (string) $reservation['guest_id'] : '',
        'cabin_id' => (string) $reservation['cabin_id'],
        'guest_name' => $reservation['guest_name'],
        'email' => $reservation['email'],
        'phone' => $reservation['phone'] ?? '',
        'start_date' => substr($reservation['start_date'], 0, 10),
        'end_date' => substr($reservation['end_date'], 0, 10),
        'check_in_time' => reservationTimeFromDateTime($reservation['check_in_at'] ?? null, '15:00'),
        'check_out_time' => reservationTimeFromDateTime($reservation['check_out_at'] ?? null, '11:00'),
        'adults' => (string) $reservation['adults'],
        'children' => (string) $reservation['children'],
        'status' => $reservation['status'],
        'payment_status' => $reservation['payment_status'] ?? 'PENDING',
        'paid_amount' => $reservation['paid_amount'] !== null ? (string) (int) $reservation['paid_amount'] : '0',
        'source' => $reservation['source'],
        'notes' => $reservation['notes'] ?? '',
    ];
}

/**
 * @param array{
 *     id: int,
 *     full_name: string,
 *     first_name: string|null,
 *     last_name: string|null,
 *     phone: string,
 *     email: string|null,
 *     cabin_id: int|null,
 *     cabin_name: string|null,
 *     linked_cabin_name: string|null,
 *     date_from: string,
 *     date_to: string,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     city: string|null,
 *     country: string|null,
 *     notes: string|null,
 *     status: string,
 *     source: string,
 *     created_at: string
 * } $inquiry
 * @return array<string, string>
 */

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function reservationFormFromCalendarQuery(array $form): array
{
    $cabinId = $_GET['cabin_id'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    if ((is_string($cabinId) || is_int($cabinId)) && ctype_digit((string) $cabinId)) {
        $form['cabin_id'] = (string) $cabinId;
    }

    if (is_string($startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) === 1) {
        $form['start_date'] = $startDate;
    }

    if (is_string($endDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) === 1) {
        $form['end_date'] = $endDate;
    }

    $form['source'] = 'MANUAL';
    $form['status'] = 'PENDING';
    $form['payment_status'] = 'PENDING';
    $form['check_in_time'] = '15:00';
    $form['check_out_time'] = '11:00';

    return $form;
}

function reservationFormFromInquiry(array $inquiry): array
{
    $form = defaultReservationForm();

    $adults = $inquiry['adults'] > 0 ? $inquiry['adults'] : max(1, $inquiry['guests']);
    $children = $inquiry['children'] >= 0 ? $inquiry['children'] : 0;

    $form['cabin_id'] = $inquiry['cabin_id'] !== null ? (string) $inquiry['cabin_id'] : '';
    $form['guest_name'] = $inquiry['full_name'];
    $form['email'] = $inquiry['email'] ?? '';
    $form['phone'] = $inquiry['phone'];
    $form['start_date'] = substr($inquiry['date_from'], 0, 10);
    $form['end_date'] = substr($inquiry['date_to'], 0, 10);
    $form['adults'] = (string) $adults;
    $form['children'] = (string) $children;
    $form['status'] = 'PENDING';
    $form['payment_status'] = 'PENDING';
    $form['paid_amount'] = '0';
    $form['source'] = $inquiry['source'] !== '' ? $inquiry['source'] : 'WWW';
    $form['notes'] = $inquiry['notes'] ?? '';

    return $form;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateReservationForm(array $form): array
{
    $errors = [];

    if ($form['guest_id'] !== '' && !ctype_digit($form['guest_id'])) {
        $errors['guest_id'] = 'Nieprawidłowy gość.';
    }

    if ($form['cabin_id'] === '' || !ctype_digit($form['cabin_id'])) {
        $errors['cabin_id'] = 'Wybierz domek.';
    }

    if ($form['guest_name'] === '') {
        $errors['guest_name'] = 'Podaj imię i nazwisko gościa.';
    }

    if ($form['email'] === '' || filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Podaj prawidłowy adres e-mail.';
    }

    if ($form['start_date'] === '') {
        $errors['start_date'] = 'Podaj datę rozpoczęcia pobytu.';
    }

    if ($form['end_date'] === '') {
        $errors['end_date'] = 'Podaj datę zakończenia pobytu.';
    }

    if (!isset($form['check_in_time']) || preg_match('/^\d{2}:\d{2}$/', $form['check_in_time']) !== 1) {
        $errors['check_in_time'] = 'Podaj godzinę przyjazdu.';
    }

    if (!isset($form['check_out_time']) || preg_match('/^\d{2}:\d{2}$/', $form['check_out_time']) !== 1) {
        $errors['check_out_time'] = 'Podaj godzinę wyjazdu.';
    }

    $form['check_in_time'] = reservationNormalizeTime($form['check_in_time'] ?? null, '15:00');
    $form['check_out_time'] = reservationNormalizeTime($form['check_out_time'] ?? null, '11:00');

    $nights = calculateReservationNights($form['start_date'], $form['end_date']);

    if ($nights === null) {
        $errors['end_date'] = 'Data zakończenia musi być późniejsza niż data rozpoczęcia.';
    }

    if (!ctype_digit($form['adults']) || (int) $form['adults'] < 1) {
        $errors['adults'] = 'Liczba dorosłych musi być większa od zera.';
    }

    if (!ctype_digit($form['children'])) {
        $errors['children'] = 'Liczba dzieci musi być liczbą całkowitą.';
    }

    if (!ctype_digit($form['paid_amount'])) {
        $errors['paid_amount'] = 'Wpłacona kwota musi być liczbą całkowitą.';
    }

    $allowedStatuses = ['PENDING', 'CONFIRMED', 'CHECKED_IN', 'CHECKED_OUT', 'CANCELLED'];

    if (!in_array($form['status'], $allowedStatuses, true)) {
        $errors['status'] = 'Nieprawidłowy status rezerwacji.';
    }

    $allowedPaymentStatuses = ['PENDING', 'PAID', 'PARTIAL', 'REFUNDED'];

    if (!in_array($form['payment_status'], $allowedPaymentStatuses, true)) {
        $errors['payment_status'] = 'Nieprawidłowy status płatności.';
    }

    $allowedSources = ['MANUAL', 'WWW', 'BOOKING', 'PHONE', 'AIRBNB'];

    if (!in_array($form['source'], $allowedSources, true)) {
        $errors['source'] = 'Nieprawidłowe źródło rezerwacji.';
    }

    return $errors;
}

function calculateReservationNights(string $startDate, string $endDate): ?int
{
    if ($startDate === '' || $endDate === '') {
        return null;
    }

    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);

    if (!$start instanceof DateTimeImmutable || !$end instanceof DateTimeImmutable) {
        return null;
    }

    if ($end <= $start) {
        return null;
    }

    return (int) $start->diff($end)->days;
}

/**
 * @param array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     description: string,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * } $cabin
 */
function getReservationNightPrice(int $nights, array $cabin): int
{
    if ($nights <= 1) {
        return $cabin['price_one_night'];
    }

    if ($nights === 2) {
        return $cabin['price_two_nights'];
    }

    if ($nights === 3) {
        return $cabin['price_three_nights'];
    }

    if ($nights === 4) {
        return $cabin['price_four_nights'];
    }

    if ($nights === 5) {
        return $cabin['price_five_nights'];
    }

    if ($nights === 6) {
        return $cabin['price_six_nights'];
    }

    return $cabin['price_seven_plus_nights'];
}

/**
 * @param array<string, string> $form
 * @return array{
 *     cabin_id: int,
 *     guest_id: int|null,
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
 *     payment_status: string,
 *     total_price: int,
 *     paid_amount: int,
 *     notes: string|null
 * }
 */
function reservationDataFromForm(array $form, int $nights, int $totalPrice, ?int $guestId): array
{
    $adults = (int) $form['adults'];
    $children = (int) $form['children'];

    return [
        'cabin_id' => (int) $form['cabin_id'],
        'guest_id' => $guestId,
        'guest_name' => $form['guest_name'],
        'email' => $form['email'],
        'phone' => $form['phone'] !== '' ? $form['phone'] : null,
        'start_date' => $form['start_date'],
        'end_date' => $form['end_date'],
        'check_in_at' => $form['start_date'] . ' ' . $form['check_in_time'] . ':00',
        'check_out_at' => $form['end_date'] . ' ' . $form['check_out_time'] . ':00',
        'nights' => $nights,
        'guests' => $adults + $children,
        'adults' => $adults,
        'children' => $children,
        'status' => $form['status'],
        'source' => $form['source'],
        'payment_status' => $form['payment_status'],
        'total_price' => $totalPrice,
        'paid_amount' => (int) $form['paid_amount'],
        'notes' => $form['notes'] !== '' ? $form['notes'] : null,
    ];
}

function reservationIdFromQuery(): ?int
{
    $value = $_GET['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function reservationIdFromPost(): ?int
{
    $value = $_POST['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function reservationStatusFromPost(): ?string
{
    $value = $_POST['status'] ?? null;

    if (!is_string($value)) {
        return null;
    }

    $allowedStatuses = ['PENDING', 'CONFIRMED', 'CHECKED_IN', 'CHECKED_OUT', 'CANCELLED'];

    if (!in_array($value, $allowedStatuses, true)) {
        return null;
    }

    return $value;
}

function paymentStatusFromPost(): ?string
{
    $value = $_POST['payment_status'] ?? null;

    if (!is_string($value)) {
        return null;
    }

    $allowedStatuses = ['PENDING', 'PAID', 'PARTIAL', 'REFUNDED'];

    if (!in_array($value, $allowedStatuses, true)) {
        return null;
    }

    return $value;
}


function paymentAmountFromPost(): ?int
{
    $value = $_POST['amount'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));

    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    $amount = (int) round((float) $normalized);

    if ($amount <= 0) {
        return null;
    }

    return $amount;
}

function reservationStatusBlocks(string $status): bool
{
    return in_array($status, ['PENDING', 'CONFIRMED', 'CHECKED_IN'], true);
}

function defaultGuestForm(): array
{
    return [
        'external_id' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'city' => '',
        'country' => '',
        'full_address' => '',
        'pesel' => '',
        'document_number' => '',
        'nationality' => '',
        'birth_date' => '',
        'is_vip' => '0',
        'source' => 'MANUAL',
        'notes' => '',
    ];
}

/**
 * @return array<string, string>
 */
function guestFormFromPost(): array
{
    $defaults = defaultGuestForm();
    $form = [];

    foreach ($defaults as $key => $value) {
        $postedValue = $_POST[$key] ?? $value;
        $form[$key] = is_string($postedValue) ? trim($postedValue) : $value;
    }

    return $form;
}

/**
 * @param array{
 *     id: int,
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     notes: string|null,
 *     created_at: string
 * } $guest
 * @return array<string, string>
 */
function guestFormFromGuest(array $guest): array
{
    return [
        'external_id' => isset($guest['external_id']) ? (string) $guest['external_id'] : '',
        'first_name' => (string) ($guest['first_name'] ?? ''),
        'last_name' => (string) ($guest['last_name'] ?? ''),
        'email' => (string) ($guest['email'] ?? ''),
        'phone' => isset($guest['phone']) ? (string) $guest['phone'] : '',
        'city' => isset($guest['city']) ? (string) $guest['city'] : '',
        'country' => isset($guest['country']) ? (string) $guest['country'] : '',
        'full_address' => isset($guest['full_address']) ? (string) $guest['full_address'] : '',
        'pesel' => isset($guest['pesel']) ? (string) $guest['pesel'] : '',
        'document_number' => isset($guest['document_number']) ? (string) $guest['document_number'] : '',
        'nationality' => isset($guest['nationality']) ? (string) $guest['nationality'] : '',
        'birth_date' => isset($guest['birth_date']) ? (string) $guest['birth_date'] : '',
        'is_vip' => (string) ((int) ($guest['is_vip'] ?? 0)),
        'source' => (string) ($guest['source'] ?? 'MANUAL'),
        'notes' => isset($guest['notes']) ? (string) $guest['notes'] : '',
    ];
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateGuestForm(array $form): array
{
    $errors = [];

    if (trim((string) $form['first_name']) === '') {
        $errors['first_name'] = 'Podaj imię gościa.';
    }

    if (trim((string) $form['last_name']) === '') {
        $errors['last_name'] = 'Podaj nazwisko gościa.';
    }

    if (trim((string) $form['email']) === '') {
        $errors['email'] = 'Podaj e-mail gościa.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Podaj poprawny adres e-mail.';
    }

    if (!in_array((string) $form['is_vip'], ['0', '1'], true)) {
        $errors['is_vip'] = 'Nieprawidłowa wartość VIP.';
    }

    $allowedSources = [
        'MANUAL',
        'WWW',
        'BOOKING',
        'PHONE',
        'AIRBNB',
        'BASE44',
    ];

    if (!in_array((string) $form['source'], $allowedSources, true)) {
        $errors['source'] = 'Nieprawidłowe źródło gościa.';
    }

    if ((string) $form['birth_date'] !== '') {
        try {
            new DateTimeImmutable((string) $form['birth_date']);
        } catch (Throwable $exception) {
            $errors['birth_date'] = 'Podaj poprawną datę urodzenia.';
        }
    }

    return $errors;
}

/**
 * @param array<string, string> $form
 * @return array{
 *     first_name: string,
 *     last_name: string,
 *     email: string,
 *     phone: string|null,
 *     country: string|null,
 *     city: string|null,
 *     is_vip: int,
 *     source: string,
 *     notes: string|null
 * }
 */
function guestDataFromForm(array $form): array
{
    return [
        'external_id' => trim((string) $form['external_id']) !== '' ? trim((string) $form['external_id']) : null,
        'first_name' => trim((string) $form['first_name']),
        'last_name' => trim((string) $form['last_name']),
        'email' => strtolower(trim((string) $form['email'])),
        'phone' => trim((string) $form['phone']) !== '' ? trim((string) $form['phone']) : null,
        'country' => trim((string) $form['country']) !== '' ? trim((string) $form['country']) : null,
        'city' => trim((string) $form['city']) !== '' ? trim((string) $form['city']) : null,
        'full_address' => trim((string) $form['full_address']) !== '' ? trim((string) $form['full_address']) : null,
        'pesel' => trim((string) $form['pesel']) !== '' ? trim((string) $form['pesel']) : null,
        'document_number' => trim((string) $form['document_number']) !== '' ? trim((string) $form['document_number']) : null,
        'nationality' => trim((string) $form['nationality']) !== '' ? trim((string) $form['nationality']) : null,
        'birth_date' => trim((string) $form['birth_date']) !== '' ? (new DateTimeImmutable((string) $form['birth_date']))->format('Y-m-d') : null,
        'is_vip' => (int) $form['is_vip'],
        'source' => (string) $form['source'],
        'notes' => trim((string) $form['notes']) !== '' ? trim((string) $form['notes']) : null,
    ];
}

function guestIdFromQuery(): ?int
{
    $value = $_GET['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function guestIdFromPost(): ?int
{
    $value = $_POST['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function guestVipStatusFromPost(): ?bool
{
    $value = $_POST['is_vip'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    if ((string) $value === '1') {
        return true;
    }

    if ((string) $value === '0') {
        return false;
    }

    return null;
}

function inquiryIdFromQuery(): ?int
{
    $value = $_GET['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function inquiryIdFromPost(): ?int
{
    $value = $_POST['id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function inquiryIdFromQueryForReservation(): ?int
{
    $value = $_GET['inquiry_id'] ?? null;

    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if (!is_int($id) || $id < 1) {
        return null;
    }

    return $id;
}

function inquiryStatusFromPost(): ?string
{
    $value = $_POST['status'] ?? null;

    if (!is_string($value)) {
        return null;
    }

    $allowedStatuses = ['NEW', 'IN_PROGRESS', 'RESOLVED', 'CANCELLED'];

    if (!in_array($value, $allowedStatuses, true)) {
        return null;
    }

    return $value;
}

function defaultSettingsForm(): array
{
    return [
        'property_name' => 'Domki Sztabinki',
        'contact_email' => 'kontakt@domkisztabinki.pl',
        'contact_phone' => '',
        'address_line' => 'Sztabinki',
        'postal_code' => '',
        'city' => 'Sejny',
        'country' => 'Polska',
        'check_in_time' => '15:00',
        'check_out_time' => '11:00',
        'minimum_nights' => '4',
        'currency' => 'PLN',
        'fishing_price' => '30',
        'hot_tub_price' => '200',
        'public_short_description' => 'Domki letniskowe nad jeziorem w spokojnej okolicy.',
        'booking_rules' => 'Obiekt przeznaczony jest do spokojnego wypoczynku. Nie organizujemy głośnych imprez.',
    ];
}

/**
 * @return array<string, string>
 */
function settingsFormFromPost(): array
{
    $defaults = defaultSettingsForm();
    $form = [];

    foreach ($defaults as $key => $defaultValue) {
        $value = $_POST[$key] ?? $defaultValue;
        $form[$key] = is_string($value) ? trim($value) : $defaultValue;
    }

    $form['currency'] = strtoupper($form['currency']);

    return $form;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateSettingsForm(array $form): array
{
    $errors = [];

    if ($form['property_name'] === '') {
        $errors['property_name'] = 'Podaj nazwę obiektu.';
    }

    if ($form['contact_email'] !== '' && filter_var($form['contact_email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['contact_email'] = 'Podaj prawidłowy adres e-mail.';
    }

    if ($form['check_in_time'] === '' || preg_match('/^\d{2}:\d{2}$/', $form['check_in_time']) !== 1) {
        $errors['check_in_time'] = 'Podaj godzinę zameldowania w formacie HH:MM.';
    }

    if ($form['check_out_time'] === '' || preg_match('/^\d{2}:\d{2}$/', $form['check_out_time']) !== 1) {
        $errors['check_out_time'] = 'Podaj godzinę wymeldowania w formacie HH:MM.';
    }

    if (!ctype_digit($form['minimum_nights']) || (int) $form['minimum_nights'] < 1) {
        $errors['minimum_nights'] = 'Minimalna liczba nocy musi być większa od zera.';
    }

    if ($form['currency'] === '' || preg_match('/^[A-Z]{3}$/', $form['currency']) !== 1) {
        $errors['currency'] = 'Waluta musi mieć format trzyliterowy, np. PLN.';
    }

    if (!ctype_digit($form['fishing_price'])) {
        $errors['fishing_price'] = 'Cena łowienia musi być liczbą całkowitą.';
    }

    if (!ctype_digit($form['hot_tub_price'])) {
        $errors['hot_tub_price'] = 'Cena balii/kubila musi być liczbą całkowitą.';
    }

    return $errors;
}

function defaultPublicInquiryForm(): array
{
    return [
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'email' => '',
        'cabin_id' => '',
        'date_from' => '',
        'date_to' => '',
        'adults' => '2',
        'children' => '0',
        'city' => '',
        'country' => 'Polska',
        'notes' => '',
    ];
}

/**
 * @return array<string, string>
 */
function publicInquiryFormFromPost(): array
{
    $defaults = defaultPublicInquiryForm();
    $form = [];

    foreach ($defaults as $key => $defaultValue) {
        $value = $_POST[$key] ?? $defaultValue;
        $form[$key] = is_string($value) ? trim($value) : $defaultValue;
    }

    return $form;
}

/**
 * @param array<string, string> $form
 * @param array<string, string> $settings
 * @return array<string, string>
 */
function validatePublicInquiryForm(array $form, array $settings): array
{
    $errors = [];

    if ($form['first_name'] === '') {
        $errors['first_name'] = 'Podaj imię.';
    }

    if ($form['last_name'] === '') {
        $errors['last_name'] = 'Podaj nazwisko.';
    }

    if ($form['phone'] === '') {
        $errors['phone'] = 'Podaj numer telefonu.';
    }

    if ($form['email'] !== '' && filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Podaj prawidłowy adres e-mail albo zostaw pole puste.';
    }

    if ($form['cabin_id'] !== '' && !ctype_digit($form['cabin_id'])) {
        $errors['cabin_id'] = 'Nieprawidłowy wybór domku.';
    }

    if ($form['date_from'] === '') {
        $errors['date_from'] = 'Wybierz datę przyjazdu.';
    }

    if ($form['date_to'] === '') {
        $errors['date_to'] = 'Wybierz datę wyjazdu.';
    }

    $nights = calculateReservationNights($form['date_from'], $form['date_to']);

    if ($nights === null) {
        $errors['date_to'] = 'Data wyjazdu musi być późniejsza niż data przyjazdu.';
    } else {
        $minimumNights = isset($settings['minimum_nights']) && ctype_digit($settings['minimum_nights'])
            ? (int) $settings['minimum_nights']
            : 1;

        if ($nights < $minimumNights) {
            $errors['date_to'] = 'Minimalna długość pobytu to ' . $minimumNights . ' noce.';
        }
    }

    if (!ctype_digit($form['adults']) || (int) $form['adults'] < 1) {
        $errors['adults'] = 'Liczba dorosłych musi być większa od zera.';
    }

    if (!ctype_digit($form['children'])) {
        $errors['children'] = 'Liczba dzieci musi być liczbą całkowitą.';
    }

    return $errors;
}

/**
 * @param array<string, string> $form
 * @param array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     description: string,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * }|null $cabin
 * @return array{
 *     full_name: string,
 *     first_name: string|null,
 *     last_name: string|null,
 *     phone: string,
 *     email: string|null,
 *     cabin_id: int|null,
 *     cabin_name: string|null,
 *     date_from: string,
 *     date_to: string,
 *     guests: int,
 *     adults: int,
 *     children: int,
 *     city: string|null,
 *     country: string|null,
 *     notes: string|null,
 *     status: string,
 *     source: string
 * }
 */
function publicInquiryDataFromForm(array $form, ?array $cabin): array
{
    $firstName = $form['first_name'];
    $lastName = $form['last_name'];
    $adults = (int) $form['adults'];
    $children = (int) $form['children'];

    return [
        'full_name' => trim($firstName . ' ' . $lastName),
        'first_name' => $firstName !== '' ? $firstName : null,
        'last_name' => $lastName !== '' ? $lastName : null,
        'phone' => $form['phone'],
        'email' => $form['email'] !== '' ? $form['email'] : null,
        'cabin_id' => $cabin !== null ? (int) $cabin['id'] : null,
        'cabin_name' => $cabin !== null ? $cabin['name'] : null,
        'date_from' => $form['date_from'],
        'date_to' => $form['date_to'],
        'guests' => $adults + $children,
        'adults' => $adults,
        'children' => $children,
        'city' => $form['city'] !== '' ? $form['city'] : null,
        'country' => $form['country'] !== '' ? $form['country'] : null,
        'notes' => $form['notes'] !== '' ? $form['notes'] : null,
        'status' => 'NEW',
        'source' => 'WWW',
    ];
}

function formatDateForDisplay(string $date): string
{
    if ($date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y', $timestamp);
}

function formatMoneyForDisplay(string|int|float|null $amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }

    return number_format((float) $amount, 0, ',', ' ') . ' zł';
}