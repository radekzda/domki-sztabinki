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

function defaultCabinForm(): array
{
    return [
        'name' => '',
        'short_name' => '',
        'description' => '',
        'max_guests' => '6',
        'bedrooms' => '2',
        'bathrooms' => '1',
        'price_per_night' => '450',
        'price_one_night' => '800',
        'price_two_nights' => '450',
        'price_three_nights' => '440',
        'price_four_nights' => '430',
        'price_five_nights' => '420',
        'price_six_nights' => '410',
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
    $successMessage = isset($_GET['created']) ? 'Domek został zapisany.' : null;

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

$router->get('/admin/rezerwacje', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_reservations', [
        'title' => 'Rezerwacje',
    ]));
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