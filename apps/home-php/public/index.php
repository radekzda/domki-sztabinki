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
require dirname(__DIR__) . '/app/Support/helpers.php';
require dirname(__DIR__) . '/app/Repositories/CabinRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReservationRepository.php';
require dirname(__DIR__) . '/app/Repositories/GuestRepository.php';

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
    $successMessage = null;

    if (isset($_GET['created'])) {
        $successMessage = 'Rezerwacja została zapisana.';
    }

    if (isset($_GET['updated'])) {
        $successMessage = 'Rezerwacja została zaktualizowana.';
    }

    if (isset($_GET['status_changed'])) {
        $successMessage = 'Status rezerwacji został zmieniony.';
    }

    if (isset($_GET['payment_changed'])) {
        $successMessage = 'Status płatności został zmieniony.';
    }

    if (isset($_GET['cancelled'])) {
        $successMessage = 'Rezerwacja została anulowana.';
    }

    if (isset($_GET['deleted'])) {
        $successMessage = 'Rezerwacja została trwale usunięta.';
    }

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
            if (reservationStatusBlocks($form['status'])) {
                $hasOverlap = ReservationRepository::hasBlockingOverlap(
                    (int) $form['cabin_id'],
                    $form['start_date'],
                    $form['end_date']
                );

                if ($hasOverlap) {
                    $errors['start_date'] = 'Ten domek ma już rezerwację blokującą w wybranym terminie.';
                }
            }

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

$router->get('/admin/rezerwacje/pokaz', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora rezerwacji.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można pokazać szczegółów rezerwacji.',
        ]), 422);

        return;
    }

    try {
        $reservation = ReservationRepository::find($id);

        if ($reservation === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono rezerwacji',
                'message' => 'Rezerwacja o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        Response::html(View::render('pages/admin_reservations_show', [
            'title' => 'Szczegóły rezerwacji',
            'reservation' => $reservation,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać rezerwacji',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/rezerwacje/edytuj', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora rezerwacji.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => defaultReservationForm(),
            'errors' => [],
            'cabins' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Edycja zostanie odblokowana po ustawieniu MySQL w pliku .env.',
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]));

        return;
    }

    try {
        $reservation = ReservationRepository::find($id);

        if ($reservation === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono rezerwacji',
                'message' => 'Rezerwacja o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        $cabins = CabinRepository::all();

        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => reservationFormFromReservation($reservation),
            'errors' => [],
            'cabins' => $cabins,
            'databaseMessage' => null,
            'canSave' => $cabins !== [],
            'calculatedNights' => $reservation['nights'],
            'calculatedTotalPrice' => $reservation['total_price'] !== null ? (int) $reservation['total_price'] : null,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => defaultReservationForm(),
            'errors' => [],
            'cabins' => [],
            'databaseMessage' => 'Nie udało się pobrać rezerwacji: ' . $exception->getMessage(),
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/edytuj', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora rezerwacji.',
        ]), 400);

        return;
    }

    $form = reservationFormFromPost();
    $errors = validateReservationForm($form);
    $cabins = [];
    $databaseMessage = null;
    $calculatedNights = null;
    $calculatedTotalPrice = null;

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zmian.',
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 422);

        return;
    }

    try {
        $cabins = CabinRepository::all();
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
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
            if (reservationStatusBlocks($form['status'])) {
                $hasOverlap = ReservationRepository::hasBlockingOverlap(
                    (int) $form['cabin_id'],
                    $form['start_date'],
                    $form['end_date'],
                    $id
                );

                if ($hasOverlap) {
                    $errors['start_date'] = 'Ten domek ma już rezerwację blokującą w wybranym terminie.';
                }
            }

            $calculatedTotalPrice = $calculatedNights * getReservationNightPrice($calculatedNights, $selectedCabin);
        }
    }

    if ($errors !== [] || $calculatedNights === null || $calculatedTotalPrice === null) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
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
        ReservationRepository::update($id, reservationDataFromForm($form, $calculatedNights, $calculatedTotalPrice));

        Response::redirect('/admin/rezerwacje?updated=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
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

$router->post('/admin/rezerwacje/status', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();
    $status = reservationStatusFromPost();

    if ($id === null || $status === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić statusu rezerwacji, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić statusu rezerwacji.',
        ]), 422);

        return;
    }

    try {
        $reservation = ReservationRepository::find($id);

        if ($reservation === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono rezerwacji',
                'message' => 'Rezerwacja o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        if (reservationStatusBlocks($status)) {
            $hasOverlap = ReservationRepository::hasBlockingOverlap(
                $reservation['cabin_id'],
                substr($reservation['start_date'], 0, 10),
                substr($reservation['end_date'], 0, 10),
                $id
            );

            if ($hasOverlap) {
                Response::html(View::render('pages/error', [
                    'title' => 'Kolizja terminu',
                    'message' => 'Nie można ustawić statusu blokującego, ponieważ ten domek ma już inną rezerwację w tym terminie.',
                ]), 422);

                return;
            }
        }

        ReservationRepository::setStatus($id, $status);

        Response::redirect('/admin/rezerwacje?status_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/platnosc', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();
    $paymentStatus = paymentStatusFromPost();

    if ($id === null || $paymentStatus === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić statusu płatności, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić statusu płatności.',
        ]), 422);

        return;
    }

    try {
        ReservationRepository::setPaymentStatus($id, $paymentStatus);

        Response::redirect('/admin/rezerwacje?payment_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić płatności',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/anuluj', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można anulować rezerwacji, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można anulować rezerwacji.',
        ]), 422);

        return;
    }

    try {
        ReservationRepository::cancel($id);

        Response::redirect('/admin/rezerwacje?cancelled=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się anulować rezerwacji',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/usun', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można usunąć rezerwacji, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można usunąć rezerwacji.',
        ]), 422);

        return;
    }

    try {
        ReservationRepository::delete($id);

        Response::redirect('/admin/rezerwacje?deleted=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć rezerwacji',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/goscie', function (): void {
    Auth::requireAdmin();

    $guests = [];
    $databaseMessage = null;
    $successMessage = null;

    if (isset($_GET['created'])) {
        $successMessage = 'Gość został zapisany.';
    }

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Lista gości zostanie pokazana po ustawieniu danych MySQL w pliku .env.';
    } else {
        try {
            $guests = GuestRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać listy gości z bazy: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_guests', [
        'title' => 'Goście',
        'guests' => $guests,
        'databaseMessage' => $databaseMessage,
        'successMessage' => $successMessage,
    ]));
});

$router->get('/admin/goscie/nowy', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_guests_new', [
        'title' => 'Dodaj gościa',
        'form' => defaultGuestForm(),
        'errors' => [],
        'databaseMessage' => Database::canAttemptConnection()
            ? null
            : 'Baza danych nie jest jeszcze skonfigurowana. Formularz jest widoczny, ale zapis zostanie odblokowany po ustawieniu MySQL w pliku .env.',
        'canSave' => Database::canAttemptConnection(),
    ]));
});

$router->post('/admin/goscie/nowy', function (): void {
    Auth::requireAdmin();

    $form = guestFormFromPost();
    $errors = validateGuestForm($form);

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_guests_new', [
            'title' => 'Dodaj gościa',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać gościa.',
            'canSave' => false,
        ]), 422);

        return;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/admin_guests_new', [
            'title' => 'Dodaj gościa',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => null,
            'canSave' => true,
        ]), 422);

        return;
    }

    try {
        GuestRepository::create(guestDataFromForm($form));

        Response::redirect('/admin/goscie?created=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_guests_new', [
            'title' => 'Dodaj gościa',
            'form' => $form,
            'errors' => [],
            'databaseMessage' => 'Nie udało się zapisać gościa: ' . $exception->getMessage(),
            'canSave' => true,
        ]), 500);
    }
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