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
require dirname(__DIR__) . '/app/Repositories/CabinImageRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReservationRepository.php';
require dirname(__DIR__) . '/app/Repositories/GuestRepository.php';
require dirname(__DIR__) . '/app/Repositories/InquiryRepository.php';
require dirname(__DIR__) . '/app/Repositories/SettingsRepository.php';
require dirname(__DIR__) . '/app/Repositories/SiteImageRepository.php';
require dirname(__DIR__) . '/app/Controllers/MediaController.php';
require dirname(__DIR__) . '/app/Controllers/GuestImportController.php';
require dirname(__DIR__) . '/app/Controllers/CabinImportController.php';
require dirname(__DIR__) . '/app/Controllers/ReservationImportController.php';
require dirname(__DIR__) . '/app/Controllers/ImportAuditController.php';

$router = new Router();

$router->get('/', function (): void {
    Response::html(View::render('pages/home', [
        'title' => 'Strona główna',
        'inquiryForm' => null,
        'inquiryErrors' => null,
        'publicDatabaseMessage' => null,
    ]));
});

$router->post('/zapytanie', function (): void {
    $form = publicInquiryFormFromPost();
    $settings = defaultSettingsForm();
    $errors = [];
    $selectedCabin = null;
    $databaseMessage = null;

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/home', [
            'title' => 'Strona główna',
            'inquiryForm' => $form,
            'inquiryErrors' => [
                'date_from' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zapytania.',
            ],
            'publicDatabaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zapytania.',
        ]), 422);

        return;
    }

    try {
        $settings = SettingsRepository::all();
    } catch (Throwable $exception) {
        $databaseMessage = 'Nie udało się pobrać ustawień: ' . $exception->getMessage();
    }

    $errors = validatePublicInquiryForm($form, $settings);

    if ($form['cabin_id'] !== '' && ctype_digit($form['cabin_id'])) {
        try {
            $selectedCabin = CabinRepository::find((int) $form['cabin_id']);

            if ($selectedCabin === null || (int) $selectedCabin['is_active'] !== 1) {
                $errors['cabin_id'] = 'Wybrany domek nie jest dostępny w ofercie publicznej.';
                $selectedCabin = null;
            }
        } catch (Throwable $exception) {
            $errors['cabin_id'] = 'Nie udało się sprawdzić wybranego domku.';
        }
    }

    if ($databaseMessage !== null) {
        $errors['date_from'] = $databaseMessage;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/home', [
            'title' => 'Strona główna',
            'inquiryForm' => $form,
            'inquiryErrors' => $errors,
            'publicDatabaseMessage' => $databaseMessage,
        ]), 422);

        return;
    }

    try {
        InquiryRepository::create(publicInquiryDataFromForm($form, $selectedCabin));

        Response::redirect('/?inquiry_sent=1#zapytanie');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/home', [
            'title' => 'Strona główna',
            'inquiryForm' => $form,
            'inquiryErrors' => [
                'date_from' => 'Nie udało się zapisać zapytania.',
            ],
            'publicDatabaseMessage' => 'Nie udało się zapisać zapytania: ' . $exception->getMessage(),
        ]), 500);
    }
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

$router->get('/admin/domki/import', function (): void {
    CabinImportController::show();
});

$router->post('/admin/domki/import', function (): void {
    CabinImportController::store();
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

$router->get('/admin/domki/zdjecia', function (): void {
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
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zarządzać zdjęciami.',
        ]), 422);

        return;
    }

    $successMessage = null;

    if (isset($_GET['uploaded'])) {
        $successMessage = 'Zdjęcie zostało dodane.';
    }

    if (isset($_GET['main_changed'])) {
        $successMessage = 'Zdjęcie główne zostało zmienione.';
    }

    if (isset($_GET['deleted'])) {
        $successMessage = 'Zdjęcie zostało usunięte.';
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

        $images = CabinImageRepository::allForCabin($id);

        Response::html(View::render('pages/admin_cabins_photos', [
            'title' => 'Zdjęcia domku',
            'cabin' => $cabin,
            'images' => $images,
            'databaseMessage' => null,
            'successMessage' => $successMessage,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać zdjęć',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/domki/zdjecia/dodaj', function (): void {
    Auth::requireAdmin();

    $cabinIdValue = $_POST['cabin_id'] ?? null;
    $cabinId = filter_var($cabinIdValue, FILTER_VALIDATE_INT);

    if (!is_int($cabinId) || $cabinId < 1) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Brakuje prawidłowego identyfikatora domku.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można dodać zdjęcia.',
        ]), 422);

        return;
    }

    try {
        $cabin = CabinRepository::find($cabinId);

        if ($cabin === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono domku',
                'message' => 'Domek o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            Response::html(View::render('pages/error', [
                'title' => 'Brak pliku',
                'message' => 'Nie wybrano zdjęcia do wysłania.',
            ]), 422);

            return;
        }

        $uploadedFile = $_FILES['image'];
        $uploadError = (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            Response::html(View::render('pages/error', [
                'title' => 'Błąd uploadu',
                'message' => 'Nie udało się wysłać pliku. Kod błędu: ' . $uploadError . '.',
            ]), 422);

            return;
        }

        $tmpName = isset($uploadedFile['tmp_name']) && is_string($uploadedFile['tmp_name'])
            ? $uploadedFile['tmp_name']
            : '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::html(View::render('pages/error', [
                'title' => 'Nieprawidłowy plik',
                'message' => 'Wysłany plik jest nieprawidłowy.',
            ]), 422);

            return;
        }

        $size = (int) ($uploadedFile['size'] ?? 0);
        $maxSize = 5 * 1024 * 1024;

        if ($size < 1 || $size > $maxSize) {
            Response::html(View::render('pages/error', [
                'title' => 'Nieprawidłowy rozmiar',
                'message' => 'Zdjęcie musi mieć maksymalnie 5 MB.',
            ]), 422);

            return;
        }

        $imageInfo = getimagesize($tmpName);

        if ($imageInfo === false || !isset($imageInfo['mime'])) {
            Response::html(View::render('pages/error', [
                'title' => 'Nieprawidłowy format',
                'message' => 'Plik nie wygląda na prawidłowe zdjęcie.',
            ]), 422);

            return;
        }

        $mime = (string) $imageInfo['mime'];
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!array_key_exists($mime, $extensions)) {
            Response::html(View::render('pages/error', [
                'title' => 'Nieobsługiwany format',
                'message' => 'Dozwolone formaty: JPG, PNG, WEBP, GIF.',
            ]), 422);

            return;
        }

        $uploadDirectory = dirname(__DIR__) . '/public/uploads/cabins';

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            Response::html(View::render('pages/error', [
                'title' => 'Brak katalogu uploadu',
                'message' => 'Nie udało się utworzyć katalogu public/uploads/cabins.',
            ]), 500);

            return;
        }

        $extension = $extensions[$mime];
        $fileName = 'cabin-' . $cabinId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $targetPath = $uploadDirectory . '/' . $fileName;
        $publicPath = '/uploads/cabins/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            Response::html(View::render('pages/error', [
                'title' => 'Błąd zapisu pliku',
                'message' => 'Nie udało się zapisać zdjęcia na serwerze.',
            ]), 500);

            return;
        }

        $altText = isset($_POST['alt_text']) && is_string($_POST['alt_text'])
            ? trim($_POST['alt_text'])
            : '';

        $isMainValue = isset($_POST['is_main']) && is_string($_POST['is_main'])
            ? $_POST['is_main']
            : '0';

        $hasImages = CabinImageRepository::countForCabin($cabinId) > 0;
        $isMain = $isMainValue === '1' || !$hasImages ? 1 : 0;

        CabinImageRepository::create([
            'cabin_id' => $cabinId,
            'image_path' => $publicPath,
            'alt_text' => $altText !== '' ? $altText : $cabin['name'],
            'is_main' => $isMain,
        ]);

        Response::redirect('/admin/domki/zdjecia?id=' . $cabinId . '&uploaded=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się dodać zdjęcia',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/domki/zdjecia/glowne', function (): void {
    Auth::requireAdmin();

    $cabinId = filter_var($_POST['cabin_id'] ?? null, FILTER_VALIDATE_INT);
    $imageId = filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT);

    if (!is_int($cabinId) || $cabinId < 1 || !is_int($imageId) || $imageId < 1) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można ustawić zdjęcia głównego, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana.',
        ]), 422);

        return;
    }

    try {
        CabinImageRepository::setMain($imageId, $cabinId);

        Response::redirect('/admin/domki/zdjecia?id=' . $cabinId . '&main_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się ustawić zdjęcia głównego',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/domki/zdjecia/usun', function (): void {
    Auth::requireAdmin();

    $cabinId = filter_var($_POST['cabin_id'] ?? null, FILTER_VALIDATE_INT);
    $imageId = filter_var($_POST['image_id'] ?? null, FILTER_VALIDATE_INT);

    if (!is_int($cabinId) || $cabinId < 1 || !is_int($imageId) || $imageId < 1) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można usunąć zdjęcia, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana.',
        ]), 422);

        return;
    }

    try {
        $image = CabinImageRepository::find($imageId);

        if ($image !== null && $image['cabin_id'] === $cabinId && str_starts_with($image['image_path'], '/uploads/cabins/')) {
            $filePath = dirname(__DIR__) . '/public' . $image['image_path'];

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        CabinImageRepository::delete($imageId, $cabinId);

        Response::redirect('/admin/domki/zdjecia?id=' . $cabinId . '&deleted=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć zdjęcia',
            'message' => $exception->getMessage(),
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

$router->get('/admin/rezerwacje/import', function (): void {
    ReservationImportController::show();
});

$router->post('/admin/rezerwacje/import', function (): void {
    ReservationImportController::store();
});

$router->get('/admin/rezerwacje/nowa', function (): void {
    Auth::requireAdmin();

    $cabins = [];
    $guests = [];
    $databaseMessage = null;
    $form = defaultReservationForm();
    $form = reservationFormFromCalendarQuery($form);
    $inquiryId = inquiryIdFromQueryForReservation();

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Formularz jest widoczny, ale zapis zostanie odblokowany po ustawieniu MySQL w pliku .env.';
    } else {
        try {
            $cabins = CabinRepository::all();
            $guests = GuestRepository::all();

            if ($inquiryId !== null) {
                $inquiry = InquiryRepository::find($inquiryId);

                if ($inquiry === null) {
                    $databaseMessage = 'Nie znaleziono zapytania do wstępnego uzupełnienia formularza.';
                } else {
                    $form = reservationFormFromInquiry($inquiry);
                }
            }
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać danych do formularza: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_reservations_new', [
        'title' => 'Dodaj rezerwację',
        'form' => $form,
        'errors' => [],
        'cabins' => $cabins,
        'guests' => $guests,
        'databaseMessage' => $databaseMessage,
        'canSave' => Database::canAttemptConnection() && $cabins !== [],
        'calculatedNights' => null,
        'calculatedTotalPrice' => null,
    ]));
});



function reservationActionReturnUrlFromPost(int $id): string
{
    $returnUrl = $_POST['return_url'] ?? '';

    if (is_string($returnUrl)) {
        $returnUrl = trim($returnUrl);

        if (str_starts_with($returnUrl, '/admin/kalendarz')) {
            return $returnUrl;
        }

        if (str_starts_with($returnUrl, '/admin/rezerwacje/pokaz?id=')) {
            return $returnUrl;
        }
    }

    return '/admin/rezerwacje/pokaz?id=' . $id;
}

function reservationReturnUrlFromPost(): string
{
    $returnUrl = $_POST['return_url'] ?? '';

    if (!is_string($returnUrl)) {
        return '';
    }

    $returnUrl = trim($returnUrl);

    if (str_starts_with($returnUrl, '/admin/kalendarz')) {
        return $returnUrl;
    }

    return '';
}

$router->post('/admin/rezerwacje/nowa', function (): void {
    $returnUrl = reservationReturnUrlFromPost();

    Auth::requireAdmin();

    $form = reservationFormFromPost();
    $errors = validateReservationForm($form);
    $cabins = [];
    $guests = [];
    $databaseMessage = null;
    $calculatedNights = null;
    $calculatedTotalPrice = null;

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'guests' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać rezerwacji.',
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 422);

        return;
    }

    try {
        $cabins = CabinRepository::all();
        $guests = GuestRepository::all();
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'guests' => [],
            'databaseMessage' => 'Nie udało się pobrać danych do formularza: ' . $exception->getMessage(),
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
            'guests' => $guests,
            'databaseMessage' => $databaseMessage,
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 422);

        return;
    }

    try {
        $selectedGuestId = $form['guest_id'] !== '' ? (int) $form['guest_id'] : null;

        $guestId = GuestRepository::resolveForReservation(
            $selectedGuestId,
            $form['guest_name'],
            $form['email'],
            $form['phone'] !== '' ? $form['phone'] : null,
            $form['source'],
            $form['notes'] !== '' ? $form['notes'] : null
        );

        ReservationRepository::create(reservationDataFromForm($form, $calculatedNights, $calculatedTotalPrice, $guestId));

        if ($returnUrl !== '') {
        Response::redirect($returnUrl);
    }

    Response::redirect('/admin/rezerwacje?created=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => [],
            'cabins' => $cabins,
            'guests' => $guests,
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
            'guests' => [],
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
        $guests = GuestRepository::all();

        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => reservationFormFromReservation($reservation),
            'errors' => [],
            'cabins' => $cabins,
            'guests' => $guests,
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
            'guests' => [],
            'databaseMessage' => 'Nie udało się pobrać rezerwacji: ' . $exception->getMessage(),
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/edytuj', function (): void {
    $returnUrl = reservationReturnUrlFromPost();

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
    $guests = [];
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
            'guests' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zmian.',
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 422);

        return;
    }

    try {
        $cabins = CabinRepository::all();
        $guests = GuestRepository::all();
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'guests' => [],
            'databaseMessage' => 'Nie udało się pobrać danych do formularza: ' . $exception->getMessage(),
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
            'guests' => $guests,
            'databaseMessage' => $databaseMessage,
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 422);

        return;
    }

    try {
        $selectedGuestId = $form['guest_id'] !== '' ? (int) $form['guest_id'] : null;

        $guestId = GuestRepository::resolveForReservation(
            $selectedGuestId,
            $form['guest_name'],
            $form['email'],
            $form['phone'] !== '' ? $form['phone'] : null,
            $form['source'],
            $form['notes'] !== '' ? $form['notes'] : null
        );

        ReservationRepository::update($id, reservationDataFromForm($form, $calculatedNights, $calculatedTotalPrice, $guestId));

        // M13.63 return to calendar after edit
        if ($returnUrl !== '') {
            Response::redirect($returnUrl);
        }

        Response::redirect('/admin/rezerwacje?updated=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => $form,
            'errors' => [],
            'cabins' => $cabins,
            'guests' => $guests,
            'databaseMessage' => 'Nie udało się zapisać rezerwacji: ' . $exception->getMessage(),
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 500);
    }
});


$router->post('/admin/rezerwacje/szybki-status', function (): void {
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
        if (reservationStatusBlocks($status)) {
            $reservation = ReservationRepository::find($id);

            if ($reservation !== null) {
                $hasOverlap = ReservationRepository::hasBlockingOverlap(
                    (int) $reservation['cabin_id'],
                    substr((string) $reservation['start_date'], 0, 10),
                    substr((string) $reservation['end_date'], 0, 10),
                    $id
                );

                if ($hasOverlap) {
                    Response::html(View::render('pages/error', [
                        'title' => 'Kolizja rezerwacji',
                        'message' => 'Nie można ustawić statusu blokującego, ponieważ termin koliduje z inną rezerwacją.',
                    ]), 422);

                    return;
                }
            }
        }

        ReservationRepository::setStatus($id, $status);

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/szybka-platnosc', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();
    $paymentStatus = paymentStatusFromPost();

    if ($id === null || $paymentStatus === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić płatności rezerwacji, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić płatności rezerwacji.',
        ]), 422);

        return;
    }

    try {
        if ($paymentStatus === 'PAID') {
            ReservationRepository::markPaid($id);
        } else {
            ReservationRepository::setPaymentStatus($id, $paymentStatus);
        }

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić płatności',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/wplata', function (): void {
    Auth::requireAdmin();

    $id = reservationIdFromPost();
    $amount = paymentAmountFromPost();

    if ($id === null || $amount === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Podaj prawidłową kwotę wpłaty.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można dodać wpłaty.',
        ]), 422);

        return;
    }

    try {
        ReservationRepository::addPayment($id, $amount);

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się dodać wpłaty',
            'message' => $exception->getMessage(),
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

    if (isset($_GET['updated'])) {
        $successMessage = 'Gość został zaktualizowany.';
    }

    if (isset($_GET['vip_changed'])) {
        $successMessage = 'Status VIP został zmieniony.';
    }

    if (isset($_GET['deleted'])) {
        $successMessage = 'Gość został usunięty.';
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

$router->get('/admin/goscie/import', function (): void {
    GuestImportController::show();
});

$router->post('/admin/goscie/import', function (): void {
    GuestImportController::store();
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

$router->get('/admin/goscie/pokaz', function (): void {
    Auth::requireAdmin();

    $id = guestIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora gościa.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można pokazać szczegółów gościa.',
        ]), 422);

        return;
    }

    try {
        $guest = GuestRepository::find($id);

        if ($guest === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono gościa',
                'message' => 'Gość o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        $reservations = ReservationRepository::forGuest($id);

        Response::html(View::render('pages/admin_guests_show', [
            'title' => 'Szczegóły gościa',
            'guest' => $guest,
            'reservations' => $reservations,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać gościa',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/goscie/edytuj', function (): void {
    Auth::requireAdmin();

    $id = guestIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora gościa.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => defaultGuestForm(),
            'errors' => [],
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Edycja zostanie odblokowana po ustawieniu MySQL w pliku .env.',
            'canSave' => false,
        ]));

        return;
    }

    try {
        $guest = GuestRepository::find($id);

        if ($guest === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono gościa',
                'message' => 'Gość o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => guestFormFromGuest($guest),
            'errors' => [],
            'databaseMessage' => null,
            'canSave' => true,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => defaultGuestForm(),
            'errors' => [],
            'databaseMessage' => 'Nie udało się pobrać gościa: ' . $exception->getMessage(),
            'canSave' => false,
        ]), 500);
    }
});

$router->post('/admin/goscie/edytuj', function (): void {
    Auth::requireAdmin();

    $id = guestIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora gościa.',
        ]), 400);

        return;
    }

    $form = guestFormFromPost();
    $errors = validateGuestForm($form);

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać zmian.',
            'canSave' => false,
        ]), 422);

        return;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => null,
            'canSave' => true,
        ]), 422);

        return;
    }

    try {
        GuestRepository::update($id, guestDataFromForm($form));

        Response::redirect('/admin/goscie?updated=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_guests_edit', [
            'title' => 'Edytuj gościa',
            'id' => $id,
            'form' => $form,
            'errors' => [],
            'databaseMessage' => 'Nie udało się zapisać gościa: ' . $exception->getMessage(),
            'canSave' => true,
        ]), 500);
    }
});

$router->post('/admin/goscie/vip', function (): void {
    Auth::requireAdmin();

    $id = guestIdFromPost();
    $isVip = guestVipStatusFromPost();

    if ($id === null || $isVip === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić oznaczenia VIP, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić oznaczenia VIP.',
        ]), 422);

        return;
    }

    try {
        GuestRepository::setVip($id, $isVip);

        Response::redirect('/admin/goscie?vip_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić VIP',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/goscie/usun', function (): void {
    Auth::requireAdmin();

    $id = guestIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można usunąć gościa, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można usunąć gościa.',
        ]), 422);

        return;
    }

    try {
        GuestRepository::delete($id);

        Response::redirect('/admin/goscie?deleted=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć gościa',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/zapytania', function (): void {
    Auth::requireAdmin();

    $inquiries = [];
    $databaseMessage = null;
    $successMessage = null;

    if (isset($_GET['status_changed'])) {
        $successMessage = 'Status zapytania został zmieniony.';
    }

    if (isset($_GET['deleted'])) {
        $successMessage = 'Zapytanie zostało usunięte.';
    }

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Lista zapytań zostanie pokazana po ustawieniu danych MySQL w pliku .env.';
    } else {
        try {
            $inquiries = InquiryRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać listy zapytań z bazy: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_inquiries', [
        'title' => 'Zapytania',
        'inquiries' => $inquiries,
        'databaseMessage' => $databaseMessage,
        'successMessage' => $successMessage,
    ]));
});

$router->get('/admin/zapytania/pokaz', function (): void {
    Auth::requireAdmin();

    $id = inquiryIdFromQuery();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowy adres',
            'message' => 'Brakuje prawidłowego identyfikatora zapytania.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można pokazać szczegółów zapytania.',
        ]), 422);

        return;
    }

    try {
        $inquiry = InquiryRepository::find($id);

        if ($inquiry === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono zapytania',
                'message' => 'Zapytanie o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        Response::html(View::render('pages/admin_inquiries_show', [
            'title' => 'Szczegóły zapytania',
            'inquiry' => $inquiry,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać zapytania',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/zapytania/status', function (): void {
    Auth::requireAdmin();

    $id = inquiryIdFromPost();
    $status = inquiryStatusFromPost();

    if ($id === null || $status === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić statusu zapytania, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić statusu zapytania.',
        ]), 422);

        return;
    }

    try {
        InquiryRepository::setStatus($id, $status);

        Response::redirect('/admin/zapytania?status_changed=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu zapytania',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->post('/admin/zapytania/usun', function (): void {
    Auth::requireAdmin();

    $id = inquiryIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można usunąć zapytania, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można usunąć zapytania.',
        ]), 422);

        return;
    }

    try {
        InquiryRepository::delete($id);

        Response::redirect('/admin/zapytania?deleted=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć zapytania',
            'message' => $exception->getMessage(),
        ]), 500);
    }
});

$router->get('/admin/kalendarz', function (): void {
    Auth::requireAdmin();

    Response::html(View::render('pages/admin_calendar', [
        'title' => 'Kalendarz',
    ]));
});

$router->get('/admin/media', function (): void {
    MediaController::index();
});

$router->post('/admin/media', function (): void {
    MediaController::handle();
});

$router->get('/admin/ustawienia', function (): void {
    Auth::requireAdmin();

    $form = defaultSettingsForm();
    $databaseMessage = null;
    $successMessage = null;

    if (isset($_GET['saved'])) {
        $successMessage = 'Ustawienia zostały zapisane.';
    }

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Ustawienia zostaną odblokowane po ustawieniu MySQL w pliku .env.';
    } else {
        try {
            $form = SettingsRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać ustawień: ' . $exception->getMessage();
        }
    }

    Response::html(View::render('pages/admin_settings', [
        'title' => 'Ustawienia',
        'form' => $form,
        'errors' => [],
        'databaseMessage' => $databaseMessage,
        'successMessage' => $successMessage,
        'canSave' => Database::canAttemptConnection(),
    ]));
});

$router->post('/admin/ustawienia', function (): void {
    Auth::requireAdmin();

    $form = settingsFormFromPost();
    $errors = validateSettingsForm($form);

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/admin_settings', [
            'title' => 'Ustawienia',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zapisać ustawień.',
            'successMessage' => null,
            'canSave' => false,
        ]), 422);

        return;
    }

    if ($errors !== []) {
        Response::html(View::render('pages/admin_settings', [
            'title' => 'Ustawienia',
            'form' => $form,
            'errors' => $errors,
            'databaseMessage' => null,
            'successMessage' => null,
            'canSave' => true,
        ]), 422);

        return;
    }

    try {
        SettingsRepository::save($form);

        Response::redirect('/admin/ustawienia?saved=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_settings', [
            'title' => 'Ustawienia',
            'form' => $form,
            'errors' => [],
            'databaseMessage' => 'Nie udało się zapisać ustawień: ' . $exception->getMessage(),
            'successMessage' => null,
            'canSave' => true,
        ]), 500);
    }
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

$router->get('/admin/system/importy', function (): void {
    ImportAuditController::show();
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');