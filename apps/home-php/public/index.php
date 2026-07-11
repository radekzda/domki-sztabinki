<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';

Env::load(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/app/Config/config.php';

date_default_timezone_set($config['timezone'] ?? 'Europe/Warsaw');

require dirname(__DIR__) . '/app/Core/Response.php';
require dirname(__DIR__) . '/app/Core/View.php';
require dirname(__DIR__) . '/app/Core/Router.php';
require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/Auth.php';
require dirname(__DIR__) . '/app/Repositories/CabinRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReservationRepository.php';

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
        'cabin_id' => '',
        'guest_name' => '',
        'email' => '',
        'phone' => '',
        'start_date' => '',
        'end_date' => '',
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
function reservationFormFromPost(): array
{
    $defaults = defaultReservationForm();
    $form = [];

    foreach ($defaults as $key => $defaultValue) {
        $value = $_POST[$key] ?? $defaultValue;
        $form[$key] = is_string($value) ? trim($value) : $defaultValue;
    }

    return $form;
}

/**
 * @param array<string, string> $form
 * @return array<string, string>
 */
function validateReservationForm(array $form): array
{
    $errors = [];

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
function reservationDataFromForm(array $form, int $nights, int $totalPrice): array
{
    $adults = (int) $form['adults'];
    $children = (int) $form['children'];

    return [
        'cabin_id' => (int) $form['cabin_id'],
        'guest_name' => $form['guest_name'],
        'email' => $form['email'],
        'phone' => $form['phone'] !== '' ? $form['phone'] : null,
        'start_date' => $form['start_date'],
        'end_date' => $form['end_date'],
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

$router = new Router();

$router->get('/', function (): void {
    Response::html(View::render('pages/home', [
        'title' => 'Strona główna',
    ]));
});

$router->get('/logowanie', function (): void {
    if (Auth::check()) {
        Response::redirect('/admin');
    }

    Response::html(View::render('pages/login', [
        'title' => 'Logowanie',
        'email' => Env::get('ADMIN_EMAIL', ''),
        'error' => null,
        'isAuthConfigured' => Auth::isConfigured(),
    ]));
});

$router->post('/logowanie', function (): void {
    $email = isset($_POST['email']) && is_string($_POST['email'])
        ? trim($_POST['email'])
        : '';

    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    if (Auth::attempt($email, $password)) {
        Response::redirect('/admin');
    }

    Response::html(View::render('pages/login', [
        'title' => 'Logowanie',
        'email' => $email,
        'error' => Auth::isConfigured()
            ? 'Nieprawidłowy adres e-mail lub hasło.'
            : 'Logowanie nie jest skonfigurowane w pliku .env.',
        'isAuthConfigured' => Auth::isConfigured(),
    ]), 422);
});

$router->post('/wyloguj', function (): void {
    Auth::logout();

    Response::redirect('/logowanie');
});

$router->get('/admin', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin', [
        'title' => 'Panel administratora',
    ]));
});

$router->get('/admin/domki', function (): void {
    Auth::requireAdmin();

    $cabins = [];
    $databaseMessage = null;
    $successMessage = null;

    if (isset($_GET['created'])) {
        $successMessage = 'Domek został zapisany.';
    }

    if (isset($_GET['updated'])) {
        $successMessage = 'Domek został zaktualizowany.';
    }

    if (isset($_GET['status_changed'])) {
        $successMessage = 'Status domku został zmieniony.';
    }

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Lista domków zostanie pokazana po ustawieniu danych MySQL w pliku .env.';
    } else {
        try {
            $cabins = CabinRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać listy domków z bazy: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_cabins', [
        'title' => 'Domki',
        'cabins' => $cabins,
        'databaseMessage' => $databaseMessage,
        'successMessage' => $successMessage,
    ]));
});

$router->get('/admin/domki/nowy', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_cabins_new', [
        'title' => 'Dodaj domek',
        'form' => defaultCabinForm(),
        'errors' => [],
        'databaseMessage' => Database::canAttemptConnection()
            ? null
            : 'Baza danych nie jest jeszcze skonfigurowana. Formularz jest widoczny, ale zapis zostanie odblokowany po ustawieniu MySQL w pliku .env.',
        'canSave' => Database::canAttemptConnection(),
    ]));
});

$router->post('/admin/domki/nowy', function (): void {
    Auth::requireAdmin();

    $form = cabinFormFromPost();
    $errors = validateCabinForm($form);

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_cabins_new', [
            'title' => 'Dodaj domek',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać domku.',
            'canSave' => false,
        ]), 422);

        return;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/admin_cabins_new', [
            'title' => 'Dodaj domek',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => null,
            'canSave' => true,
        ]), 422);

        return;
    }

    try {
        CabinRepository::create(cabinDataFromForm($form));

        Response::redirect('/admin/domki?created=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_cabins_new', [
            'title' => 'Dodaj domek',
            'form' => $form,
            'errors' => [],
            'databaseMessage' => 'Nie udało się zapisać domku: ' . $exception->getMessage(),
            'canSave' => true,
        ]), 500);
    }
});

$router->post('/admin/domki/status', function (): void {
    Auth::requireAdmin();

    $id = cabinIdFromPost();
    $isActive = activeStatusFromPost();

    if ($id === null || $isActive === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić statusu domku, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić statusu domku.',
        ]), 422);

        return;
    }

    try {
        CabinRepository::setActive($id, $isActive);

        Response::redirect('/admin/domki?status_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/domki/edytuj', function (): void {
    Auth::requireAdmin();

    $id = cabinIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora domku.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => defaultCabinForm(),
            'errors' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Edycja zostanie odblokowana po ustawieniu MySQL w pliku .env.',
            'canSave' => false,
        ]));

        return;
    }

    try {
        $cabin = CabinRepository::find($id);

        if ($cabin === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono domku',
                'message' => 'Domek o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => cabinFormFromCabin($cabin),
            'errors' => [],
            'databaseMessage' => null,
            'canSave' => true,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => defaultCabinForm(),
            'errors' => [],
            'databaseMessage' => 'Nie udało się pobrać danych domku: ' . $exception->getMessage(),
            'canSave' => false,
        ]), 500);
    }
});

$router->post('/admin/domki/edytuj', function (): void {
    Auth::requireAdmin();

    $id = cabinIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora domku.',
        ]), 400);

        return;
    }

    $form = cabinFormFromPost();
    $errors = validateCabinForm($form);

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zmian.',
            'canSave' => false,
        ]), 422);

        return;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => null,
            'canSave' => true,
        ]), 422);

        return;
    }

    try {
        CabinRepository::update($id, cabinDataFromForm($form));

        Response::redirect('/admin/domki?updated=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => $form,
            'errors' => [],
            'databaseMessage' => 'Nie udało się zapisać zmian: ' . $exception->getMessage(),
            'canSave' => true,
        ]), 500);
    }
});

$router->get('/admin/rezerwacje', function (): void {
    Auth::requireAdmin();

    $reservations = [];
    $databaseMessage = null;
    $successMessage = isset($_GET['created']) ? 'Rezerwacja została zapisana.' : null;

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Lista rezerwacji zostanie pokazana po ustawieniu danych MySQL w pliku .env.';
    } else {
        try {
            $reservations = ReservationRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać listy rezerwacji z bazy: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_reservations', [
        'title' => 'Rezerwacje',
        'reservations' => $reservations,
        'databaseMessage' => $databaseMessage,
        'successMessage' => $successMessage,
    ]));
});

$router->get('/admin/rezerwacje/nowa', function (): void {
    Auth::requireAdmin();

    $cabins = [];
    $databaseMessage = null;

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Formularz jest widoczny, ale zapis zostanie odblokowany po ustawieniu MySQL w pliku .env.';
    } else {
        try {
            $cabins = CabinRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać listy domków: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_reservations_new', [
        'title' => 'Dodaj rezerwację',
        'form' => defaultReservationForm(),
        'errors' => [],
        'cabins' => $cabins,
        'databaseMessage' => $databaseMessage,
        'canSave' => Database::canAttemptConnection() && $cabins !== [],
        'calculatedNights' => null,
        'calculatedTotalPrice' => null,
    ]));
});

$router->post('/admin/rezerwacje/nowa', function (): void {
    Auth::requireAdmin();

    $form = reservationFormFromPost();
    $errors = validateReservationForm($form);
    $cabins = [];
    $databaseMessage = null;
    $calculatedNights = null;
    $calculatedTotalPrice = null;

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać rezerwacji.',
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 422);

        return;
    }

    try {
        $cabins = CabinRepository::all();
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'databaseMessage' => 'Nie udało się pobrać listy domków: ' . $exception->getMessage(),
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 500);

        return;
    }

    if ($errors === []) {
        $selectedCabin = CabinRepository::find((int) $form['cabin_id']);
        $calculatedNights = calculateReservationNights($form['start_date'], $form['end_date']);

        if ($selectedCabin === null) {
            $errors['cabin_id'] = 'Wybrany domek nie istnieje.';
        }

        if ($selectedCabin !== null && $calculatedNights !== null) {
            $calculatedTotalPrice = $calculatedNights * getReservationNightPrice($calculatedNights, $selectedCabin);
        }
    }

    if ($errors !== [] || $calculatedNights === null || $calculatedTotalPrice === null) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => $cabins,
            'databaseMessage' => $databaseMessage,
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 422);

        return;
    }

    try {
        ReservationRepository::create(reservationDataFromForm($form, $calculatedNights, $calculatedTotalPrice));

        Response::redirect('/admin/rezerwacje?created=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => [],
            'cabins' => $cabins,
            'databaseMessage' => 'Nie udało się zapisać rezerwacji: ' . $exception->getMessage(),
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 500);
    }
});

$router->get('/admin/goscie', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_guests', [
        'title' => 'Goście',
    ]));
});

$router->get('/admin/zapytania', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_inquiries', [
        'title' => 'Zapytania',
    ]));
});

$router->get('/admin/kalendarz', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_calendar', [
        'title' => 'Kalendarz',
    ]));
});

$router->get('/admin/ustawienia', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_settings', [
        'title' => 'Ustawienia',
    ]));
});

$router->get('/admin/system', function () use ($config): void {
    Auth::requireAdmin();

    $uploadsPath = dirname(__DIR__) . '/storage/uploads';

    $dbDatabase = Env::get('DB_DATABASE', '');
    $dbUsername = Env::get('DB_USERNAME', '');

    $checks = [
        'PHP' => PHP_VERSION,
        'Aplikacja' => (string) ($config['app_name'] ?? 'Domki Sztabinki PMS'),
        'Środowisko' => (string) ($config['environment'] ?? 'production'),
        'Debug' => !empty($config['debug']) ? 'włączony' : 'wyłączony',
        'Strefa czasowa' => date_default_timezone_get(),
        'APP_URL' => Env::get('APP_URL', 'brak'),
        'PDO' => extension_loaded('pdo') ? 'dostępne' : 'brak',
        'PDO MySQL' => extension_loaded('pdo_mysql') ? 'dostępne' : 'brak',
        'DB_DATABASE' => $dbDatabase !== '' && $dbDatabase !== 'CHANGE_ME_DATABASE' ? 'ustawione' : 'brak',
        'DB_USERNAME' => $dbUsername !== '' && $dbUsername !== 'CHANGE_ME_USERNAME' ? 'ustawione' : 'brak',
        'storage/uploads istnieje' => is_dir($uploadsPath) ? 'tak' : 'nie',
        'storage/uploads zapisywalny' => is_writable($uploadsPath) ? 'tak' : 'nie',
    ];

    Response::html(View::render('pages/system', [
        'title' => 'Status środowiska',
        'checks' => $checks,
    ]));
});

$router->get('/admin/system/database', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/database', [
        'title' => 'Połączenie z bazą MySQL',
        'checks' => Database::diagnostics(),
    ]));
});

$router->get('/admin/system/database/install', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/database_install', [
        'title' => 'Instalator bazy MySQL',
        'canInstall' => Database::canAttemptConnection(),
        'message' => null,
        'messageType' => 'warning',
        'checks' => Database::diagnostics(),
    ]));
});

$router->post('/admin/system/database/install', function (): void {
    Auth::requireAdmin();

    $message = '';
    $messageType = 'warning';

    try {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        $executedStatements = Database::installSchema($schemaPath);

        $message = 'Instalator zakończył pracę poprawnie. Wykonano poleceń SQL: ' . $executedStatements . '.';
        $messageType = 'success';
    } catch (Throwable $exception) {
        $message = 'Nie udało się uruchomić instalatora: ' . $exception->getMessage();
        $messageType = 'danger';
    }

    Response::html(View::render('pages/database_install', [
        'title' => 'Instalator bazy MySQL',
        'canInstall' => Database::canAttemptConnection(),
        'message' => $message,
        'messageType' => $messageType,
        'checks' => Database::diagnostics(),
    ]));
});

$router->get('/admin/system/database/seed', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/database_seed', [
        'title' => 'Dane startowe',
        'canSeed' => Database::canAttemptConnection(),
        'message' => null,
        'messageType' => 'warning',
        'checks' => Database::diagnostics(),
    ]));
});

$router->post('/admin/system/database/seed', function (): void {
    Auth::requireAdmin();

    $message = '';
    $messageType = 'warning';

    try {
        $seedPath = dirname(__DIR__) . '/database/seed.sql';
        $executedStatements = Database::seedDefaultData($seedPath);

        $message = 'Dane startowe zostały wgrane poprawnie. Wykonano poleceń SQL: ' . $executedStatements . '.';
        $messageType = 'success';
    } catch (Throwable $exception) {
        $message = 'Nie udało się wgrać danych startowych: ' . $exception->getMessage();
        $messageType = 'danger';
    }

    Response::html(View::render('pages/database_seed', [
        'title' => 'Dane startowe',
        'canSeed' => Database::canAttemptConnection(),
        'message' => $message,
        'messageType' => $messageType,
        'checks' => Database::diagnostics(),
    ]));
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');