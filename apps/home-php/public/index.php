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
    ]));
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

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');