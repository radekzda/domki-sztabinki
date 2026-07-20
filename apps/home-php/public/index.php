<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Env.php';

Env::load(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/app/Config/config.php';

date_default_timezone_set($config['timezone'] ?? 'Europe/Warsaw');

require dirname(__DIR__) . '/app/Core/Response.php';
require dirname(__DIR__) . '/app/Core/View.php';
require dirname(__DIR__) . '/app/Core/AppErrorHandler.php';

AppErrorHandler::register($config, dirname(__DIR__));
require dirname(__DIR__) . '/app/Core/Router.php';
require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/Auth.php';
require dirname(__DIR__) . '/app/Core/Mailer.php';
require dirname(__DIR__) . '/app/Services/InquiryMailer.php';
require dirname(__DIR__) . '/app/Services/IcalParser.php';
require dirname(__DIR__) . '/app/Services/IcalCalendarClient.php';
require dirname(__DIR__) . '/app/Services/IcalSyncService.php';
require dirname(__DIR__) . '/app/Services/IcalExportService.php';
require dirname(__DIR__) . '/app/Support/helpers.php';
require dirname(__DIR__) . '/app/Services/MessageTemplateRenderer.php';
require dirname(__DIR__) . '/app/Support/PublicFormGuard.php';
require dirname(__DIR__) . '/app/Support/ImageUploader.php';
require dirname(__DIR__) . '/app/Repositories/CabinRepository.php';
require dirname(__DIR__) . '/app/Repositories/CabinImageRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReservationRepository.php';
require dirname(__DIR__) . '/app/Repositories/InvoiceSellerRepository.php';
require dirname(__DIR__) . '/app/Repositories/InvoiceRepository.php';
require dirname(__DIR__) . '/app/Controllers/InvoiceController.php';
require dirname(__DIR__) . '/app/Controllers/InvoiceSellerController.php';
require dirname(__DIR__) . '/app/Repositories/IcalEventRepository.php';
require dirname(__DIR__) . '/app/Repositories/IcalSyncLogRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReportRepository.php';
require dirname(__DIR__) . '/app/Repositories/ReservationHistoryRepository.php';
require dirname(__DIR__) . '/app/Repositories/GuestRepository.php';
require dirname(__DIR__) . '/app/Repositories/InquiryRepository.php';
require dirname(__DIR__) . '/app/Repositories/SettingsRepository.php';
require dirname(__DIR__) . '/app/Repositories/MessageTemplateRepository.php';
require dirname(__DIR__) . '/app/Repositories/SiteImageRepository.php';
require dirname(__DIR__) . '/app/Controllers/MediaController.php';
require dirname(__DIR__) . '/app/Controllers/GuestImportController.php';
require dirname(__DIR__) . '/app/Controllers/CabinImportController.php';
require dirname(__DIR__) . '/app/Controllers/ReservationImportController.php';
require dirname(__DIR__) . '/app/Controllers/ImportAuditController.php';

$router = new Router();

$router->get('/ical/domek', function (): void {
    $idValue = $_GET['id'] ?? null;
    $tokenValue = $_GET['token'] ?? null;

    $id = filter_var(
        $idValue,
        FILTER_VALIDATE_INT
    );

    $token = is_string($tokenValue)
        ? trim($tokenValue)
        : '';

    if (
        !is_int($id)
        || $id < 1
        || $token === ''
    ) {
        http_response_code(404);

        return;
    }

    try {
        $cabin = CabinRepository::find(
            $id
        );

        if ($cabin === null) {
            http_response_code(404);

            return;
        }

        $expectedToken = trim(
            (string) (
                $cabin['ical_export_token']
                ?? ''
            )
        );

        if (
            $expectedToken === ''
            || !hash_equals(
                $expectedToken,
                $token
            )
        ) {
            http_response_code(404);

            return;
        }

        $reservations =
            ReservationRepository::forIcalExport(
                $id
            );

        $content =
            IcalExportService::generate(
                $id,
                $reservations
            );

        header(
            'Content-Type: text/calendar; charset=utf-8'
        );

        header(
            'Content-Disposition: inline; filename="domek-'
            . $id
            . '.ics"'
        );

        header(
            'Cache-Control: no-cache, no-store, must-revalidate'
        );

        echo $content;
    } catch (Throwable $exception) {
        error_log(
            'Blad eksportu iCal domku #'
            . $id
            . ': '
            . $exception->getMessage()
        );

        http_response_code(500);
    }
});


$router->get('/', function (): void {
    Response::html(View::render('pages/home', [
        'title' => 'Strona główna',
        'inquiryForm' => null,
        'inquiryErrors' => null,
        'publicDatabaseMessage' => null,
    ]));
});

$router->get('/regulamin', function (): void {
    $settings = defaultSettingsForm();

    if (Database::canAttemptConnection()) {
        try {
            $settings = SettingsRepository::all();
        } catch (Throwable $exception) {
            error_log(
                'Nie udalo sie pobrac ustawien dla regulaminu: '
                . $exception->getMessage()
            );
        }
    }

    Response::html(
        View::render(
            'pages/regulations',
            [
                'title' =>
                    'Regulamin',
                'metaDescription' =>
                    'Regulamin rezerwacji i pobytu w Domkach Sztabinki.',
                'bookingRules' =>
                    (string) (
                        $settings['booking_rules']
                        ?? ''
                    ),
            ]
        )
    );
});

$router->get(
    '/polityka-prywatnosci',
    function (): void {
        Response::html(
            View::render(
                'pages/privacy_policy',
                [
                    'title' =>
                        'Polityka prywatności',
                    'metaDescription' =>
                        'Polityka prywatności i informacje o przetwarzaniu danych osobowych w Domkach Sztabinki.',
                ]
            )
        );
    }
);

$router->post('/zapytanie', function (): void {
    requireValidCsrf();
    PublicFormGuard::validate($_POST);
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
        $databaseMessage = 'Nie udało się pobrać ustawień: ' . AppErrorHandler::safeMessage($exception);
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
        $inquiryId = InquiryRepository::create(
            publicInquiryDataFromForm(
                $form,
                $selectedCabin
            )
        );

        InquiryMailer::sendAdminNotification(
            $inquiryId,
            $form,
            $selectedCabin,
            $settings
        );

        InquiryMailer::sendGuestConfirmation(
            $inquiryId,
            $form,
            $selectedCabin,
            $settings
        );

        Response::redirect('/?inquiry_sent=1#zapytanie');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/home', [
            'title' => 'Strona główna',
            'inquiryForm' => $form,
            'inquiryErrors' => [
                'date_from' => 'Nie udało się zapisać zapytania.',
            ],
            'publicDatabaseMessage' => 'Nie udało się zapisać zapytania: ' . AppErrorHandler::safeMessage($exception),
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
    Auth::startSession();
    requireValidCsrf();
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
        'error' => Auth::isLoginBlocked()
            ? 'Zbyt wiele nieudanych prób logowania. Logowanie zostało zablokowane na 15 minut.'
            : (
                Auth::isConfigured()
                    ? 'Nieprawidłowy adres e-mail lub hasło.'
                    : 'Logowanie nie jest skonfigurowane w pliku .env.'
            ),
        'isAuthConfigured' => Auth::isConfigured(),
    ]), 422);
});

$router->post('/wyloguj', function (): void {
    Auth::startSession();
    requireValidCsrf();
    Auth::logout();

    Response::redirect('/logowanie');
});

$router->get('/admin', function (): void {
    Auth::requireAdmin();

    $todayArrivals = [];
    $todayDepartures = [];
    $checkedInReservations = [];
    $newInquiries = [];
    $upcomingReservations = [];
    $cleaningCabins = [];
    $databaseMessage = null;

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Dane operacyjne dashboardu będą dostępne po skonfigurowaniu MySQL.';
    } else {
        try {
            $reservations = ReservationRepository::all();
            $inquiries = InquiryRepository::all();
            $cabins = CabinRepository::all();

            $today = new DateTimeImmutable('today');
            $todayKey = $today->format('Y-m-d');
            $upcomingEndKey = $today
                ->modify('+7 days')
                ->format('Y-m-d');

            foreach ($reservations as $reservation) {
                $status = (string) (
                    $reservation['status']
                    ?? ''
                );

                $startDate = substr(
                    (string) (
                        $reservation['start_date']
                        ?? ''
                    ),
                    0,
                    10
                );

                $endDate = substr(
                    (string) (
                        $reservation['end_date']
                        ?? ''
                    ),
                    0,
                    10
                );

                if (
                    $startDate === $todayKey
                    && in_array(
                        $status,
                        [
                            'PENDING',
                            'CONFIRMED',
                            'CHECKED_IN',
                        ],
                        true
                    )
                ) {
                    $todayArrivals[] = $reservation;
                }

                if (
                    $endDate === $todayKey
                    && $status !== 'CANCELLED'
                ) {
                    $todayDepartures[] = $reservation;
                }

                if ($status === 'CHECKED_IN') {
                    $checkedInReservations[] = $reservation;
                }

                if (
                    $startDate > $todayKey
                    && $startDate <= $upcomingEndKey
                    && in_array(
                        $status,
                        [
                            'PENDING',
                            'CONFIRMED',
                        ],
                        true
                    )
                ) {
                    $upcomingReservations[] = $reservation;
                }
            }

            foreach ($cabins as $cabin) {
                $cleaningStatus = (string) (
                    $cabin['cleaning_status']
                    ?? 'READY'
                );

                if (
                    in_array(
                        $cleaningStatus,
                        [
                            'DIRTY',
                            'CLEANING',
                        ],
                        true
                    )
                ) {
                    $cleaningCabins[] = $cabin;
                }
            }

            foreach ($inquiries as $inquiry) {
                if (
                    (string) (
                        $inquiry['status']
                        ?? ''
                    ) === 'NEW'
                ) {
                    $newInquiries[] = $inquiry;
                }
            }

            usort(
                $upcomingReservations,
                static function (
                    array $first,
                    array $second
                ): int {
                    return strcmp(
                        (string) (
                            $first['start_date']
                            ?? ''
                        ),
                        (string) (
                            $second['start_date']
                            ?? ''
                        )
                    );
                }
            );
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać danych dashboardu: '
                . AppErrorHandler::safeMessage(
                    $exception
                );
        }
    }

    Response::html(View::render('pages/admin', [
        'title' => 'Panel administratora',
        'todayArrivals' => $todayArrivals,
        'todayDepartures' => $todayDepartures,
        'checkedInReservations' => $checkedInReservations,
        'newInquiries' => $newInquiries,
        'upcomingReservations' => $upcomingReservations,
        'cleaningCabins' => $cleaningCabins,
        'databaseMessage' => $databaseMessage,
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
            $databaseMessage = 'Nie udało się pobrać listy domków z bazy: ' . AppErrorHandler::safeMessage($exception);
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
    Auth::requireAdmin();
    CabinImportController::show();
});

$router->post('/admin/domki/import', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();
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
    requireValidCsrf();

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
            'databaseMessage' => 'Nie udało się zapisać domku: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => true,
        ]), 500);
    }
});

$router->post('/admin/domki/status', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});


$router->post('/admin/domki/sprzatanie', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    $id = cabinIdFromPost();
    $cleaningStatus = cleaningStatusFromPost();

    if (
        $id === null
        || $cleaningStatus === null
    ) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można zmienić statusu sprzątania, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można zmienić statusu sprzątania.',
        ]), 422);

        return;
    }

    try {
        CabinRepository::setCleaningStatus(
            $id,
            $cleaningStatus
        );

        $returnUrl = $_POST['return_url']
            ?? '/admin/domki?cleaning_changed=1';

        if (
            !is_string($returnUrl)
            || !in_array(
                $returnUrl,
                [
                    '/admin',
                    '/admin/domki?cleaning_changed=1',
                ],
                true
            )
        ) {
            $returnUrl =
                '/admin/domki?cleaning_changed=1';
        }

        Response::redirect(
            $returnUrl
        );
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu sprzątania',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});


$router->post('/admin/domki/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    $id = cabinIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można usunąć domku, ponieważ przesłane dane są nieprawidłowe.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Baza danych nie jest jeszcze skonfigurowana. Nie można usunąć domku.',
        ]), 422);

        return;
    }

    try {
        if (CabinRepository::hasReservations($id)) {
            Response::redirect('/admin/domki?delete_blocked=1');
        }

        CabinRepository::delete($id);

        Response::redirect('/admin/domki?deleted=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć domku',
            'message' => AppErrorHandler::safeMessage($exception),
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

        $icalExportToken =
            CabinRepository::ensureIcalExportToken(
                $id
            );

        Response::html(View::render('pages/admin_cabins_edit', [
            'title' => 'Edytuj domek',
            'id' => $id,
            'form' => cabinFormFromCabin($cabin),
            'icalExportToken' => $icalExportToken,
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
            'databaseMessage' => 'Nie udało się pobrać danych domku: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => false,
        ]), 500);
    }
});

$router->post('/admin/domki/ical-synchronizuj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    $id = cabinIdFromPost();

    if ($id === null) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można uruchomić synchronizacji iCal, ponieważ identyfikator domku jest nieprawidłowy.',
        ]), 400);

        return;
    }

    if (!Database::canAttemptConnection()) {
        Response::html(View::render('pages/error', [
            'title' => 'Brak połączenia z bazą',
            'message' => 'Nie można uruchomić synchronizacji iCal bez połączenia z bazą danych.',
        ]), 422);

        return;
    }

    try {
        $cabin = CabinRepository::find(
            $id
        );

        if ($cabin === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono domku',
                'message' => 'Domek o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        $result = IcalSyncService::syncCabin(
            $cabin
        );

        $query = http_build_query([
            'id' => $id,
            'synced' => '1',
            'total' => $result['total'],
            'existing' =>
                $result['existing_ical'],
            'matched' =>
                $result['matched_reservations'],
            'conflicts' =>
                $result['conflicts'],
            'new_blocks' =>
                $result['new_blocks'],
            'deactivated' =>
                $result['deactivated'] ?? 0,
        ]);

        Response::redirect(
            '/admin/domki/ical-podglad?'
            . $query
        );
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Synchronizacja iCal nie powiodła się',
            'message' => AppErrorHandler::safeMessage(
                $exception
            ),
        ]), 500);
    }
});


$router->get('/admin/domki/ical-podglad', function (): void {
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
            'message' => 'Nie można pobrać konfiguracji kalendarza iCal.',
        ]), 422);

        return;
    }

    try {
        $cabin = CabinRepository::find(
            $id
        );

        if ($cabin === null) {
            Response::html(View::render('pages/error', [
                'title' => 'Nie znaleziono domku',
                'message' => 'Domek o podanym identyfikatorze nie istnieje.',
            ]), 404);

            return;
        }

        $rows = [];

        $counts = [
            'EXISTING_ICAL' => 0,
            'MATCH_RESERVATION' => 0,
            'CONFLICT' => 0,
            'NEW_BLOCK' => 0,
        ];

        $errorMessage = null;

        $icalUrl = trim(
            (string) (
                $cabin['ical_url']
                ?? ''
            )
        );

        if ($icalUrl === '') {
            $errorMessage =
                'Ten domek nie ma jeszcze ustawionego adresu URL kalendarza iCal.';
        } else {
            try {
                $content =
                    IcalCalendarClient::fetch(
                        $icalUrl
                    );

                $events =
                    IcalParser::parse(
                        $content
                    );

                foreach ($events as $event) {
                    $classification =
                        IcalEventRepository::classifyEvent(
                            $id,
                            $event
                        );

                    $action = (string) (
                        $classification['action']
                        ?? ''
                    );

                    if (
                        array_key_exists(
                            $action,
                            $counts
                        )
                    ) {
                        $counts[$action]++;
                    }

                    $rows[] = [
                        'event' => $event,
                        'action' => $action,
                        'matched_reservation' =>
                            $classification[
                                'matched_reservation'
                            ]
                            ?? null,
                        'conflicting_reservation' =>
                            $classification[
                                'conflicting_reservation'
                            ]
                            ?? null,
                    ];
                }
            } catch (Throwable $exception) {
                $errorMessage =
                    AppErrorHandler::safeMessage(
                        $exception
                    );
            }
        }

        Response::html(View::render(
            'pages/admin_ical_preview',
            [
                'title' =>
                    'Podgląd synchronizacji iCal',
                'cabin' => $cabin,
                'rows' => $rows,
                'counts' => $counts,
                'errorMessage' =>
                    $errorMessage,
            ]
        ));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się otworzyć podglądu iCal',
            'message' => AppErrorHandler::safeMessage(
                $exception
            ),
        ]), 500);
    }
});


$router->post('/admin/domki/edytuj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'databaseMessage' => 'Nie udało się zapisać zmian: ' . AppErrorHandler::safeMessage($exception),
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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/domki/zdjecia/dodaj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    $cabinId = filter_var($_POST['cabin_id'] ?? null, FILTER_VALIDATE_INT);

    if (!is_int($cabinId) || $cabinId < 1) {
        Response::html(View::render('pages/error', [
            'title' => 'Nieprawidłowe dane',
            'message' => 'Nie można dodać zdjęcia, ponieważ identyfikator domku jest nieprawidłowy.',
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
                'message' => 'Nie można dodać zdjęcia do nieistniejącego domku.',
            ]), 404);

            return;
        }

        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            throw new RuntimeException('Nie wybrano pliku zdjęcia.');
        }

        $uploadDirectory = dirname(__DIR__) . '/public/uploads/cabins';

        $uploadedImage = \App\Support\ImageUploader::upload(
            $_FILES['image'],
            $uploadDirectory,
            '/uploads/cabins',
            'cabin-' . $cabinId
        );

        $altText = trim((string) ($_POST['alt_text'] ?? ''));

        if ($altText === '') {
            $altText = (string) ($cabin['name'] ?? 'Zdjęcie domku');
        }

        $isMain = (int) ($_POST['is_main'] ?? 0) === 1;

        CabinImageRepository::create([
            'cabin_id' => $cabinId,
            'image_path' => $uploadedImage['public_path'],
            'alt_text' => $altText,
            'is_main' => $isMain ? 1 : 0,
        ]);

        Response::redirect('/admin/domki/zdjecia?id=' . $cabinId . '&uploaded=1');
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Błąd uploadu',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 422);
    }
});

$router->post('/admin/domki/zdjecia/glowne', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/domki/zdjecia/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
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
            $databaseMessage = 'Nie udało się pobrać listy rezerwacji z bazy: ' . AppErrorHandler::safeMessage($exception);
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
    Auth::requireAdmin();
    ReservationImportController::show();
});

$router->post('/admin/rezerwacje/import', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();
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
            $databaseMessage = 'Nie udało się pobrać danych do formularza: ' . AppErrorHandler::safeMessage($exception);
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


function reservationDeleteReturnUrlFromPost(): string
{
    $returnUrl = $_POST['return_url'] ?? '';

    if (is_string($returnUrl)) {
        $returnUrl = trim($returnUrl);

        if (str_starts_with($returnUrl, '/admin/kalendarz')) {
            return $returnUrl;
        }
    }

    return '/admin/rezerwacje?deleted=1';
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
    requireValidCsrf();

    $form = reservationFormFromPost();
    $errors = validateReservationForm($form);
    $cabins = [];
    $guests = [];
    $settings = defaultSettingsForm();
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
        $settings = SettingsRepository::all();
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_new', [
            'title' => 'Dodaj rezerwację',
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'guests' => [],
            'databaseMessage' => 'Nie udało się pobrać danych do formularza: ' . AppErrorHandler::safeMessage($exception),
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

            $calculatedTotalPrice = $calculatedNights
                * getReservationNightPriceFromSettings(
                    $calculatedNights,
                    $settings
                );
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
            'databaseMessage' => 'Nie udało się zapisać rezerwacji: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 500);
    }
});

$router->get('/admin/faktury', function (): void {
    Auth::requireAdmin();

    $invoices = [];
    $databaseMessage = null;

    if (!Database::canAttemptConnection()) {
        $databaseMessage =
            'Baza danych nie jest jeszcze '
            . 'skonfigurowana. Lista faktur '
            . 'będzie dostępna po ustawieniu '
            . 'połączenia z MySQL.';
    } else {
        try {
            $invoices =
                InvoiceRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage =
                'Nie udało się pobrać listy faktur: '
                . AppErrorHandler::safeMessage(
                    $exception
                );
        }
    }

    Response::html(
        View::render(
            'pages/admin_invoices',
            [
                'title' =>
                    'Faktury',

                'invoices' =>
                    $invoices,

                'databaseMessage' =>
                    $databaseMessage,
            ]
        )
    );
});

$router->get(
    '/admin/faktury/pokaz',
    function (): void {
        Auth::requireAdmin();

        $id = filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !is_int($id)
            || $id < 1
        ) {
            Response::html(
                View::render(
                    'pages/error',
                    [
                        'title' =>
                            'Nieprawidłowy adres',

                        'message' =>
                            'Brakuje prawidłowego '
                            . 'identyfikatora faktury.',
                    ]
                ),
                400
            );

            return;
        }

        if (!Database::canAttemptConnection()) {
            Response::html(
                View::render(
                    'pages/error',
                    [
                        'title' =>
                            'Brak połączenia z bazą',

                        'message' =>
                            'Baza danych nie jest '
                            . 'jeszcze skonfigurowana. '
                            . 'Nie można pokazać faktury.',
                    ]
                ),
                422
            );

            return;
        }

        try {
            $invoice =
                InvoiceRepository::find(
                    $id
                );

            if ($invoice === null) {
                Response::html(
                    View::render(
                        'pages/error',
                        [
                            'title' =>
                                'Nie znaleziono faktury',

                            'message' =>
                                'Faktura o podanym '
                                . 'identyfikatorze '
                                . 'nie istnieje.',
                        ]
                    ),
                    404
                );

                return;
            }

            Response::html(
                View::render(
                    'pages/admin_invoice_show',
                    [
                        'title' =>
                            'Faktura '
                            . (
                                $invoice[
                                    'invoice_number'
                                ]
                                ?? ''
                            ),

                        'invoice' =>
                            $invoice,
                    ]
                )
            );
        } catch (Throwable $exception) {
            Response::html(
                View::render(
                    'pages/error',
                    [
                        'title' =>
                            'Nie udało się '
                            . 'pobrać faktury',

                        'message' =>
                            AppErrorHandler::safeMessage(
                                $exception
                            ),
                    ]
                ),
                500
            );
        }
    }
);

$router->get(
    '/admin/faktury/nowa',
    function (): void {
        InvoiceController::createFromReservation();
    }
);

$router->post(
    '/admin/faktury/nowa',
    function (): void {
        InvoiceController::storeFromReservation();
    }
);

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

        $settings = SettingsRepository::all();

        $reservationHistory = ReservationHistoryRepository::forReservation(
            (int) $reservation['id']
        );

        $reservationMessageTemplates = [];

        foreach (
            MessageTemplateRepository::activeForContext(
                'RESERVATION'
            )
            as $messageTemplate
        ) {
            $messageTemplate[
                'rendered_content'
            ] = MessageTemplateRenderer::forReservation(
                (string) $messageTemplate['content'],
                $reservation,
                $settings
            );

            $reservationMessageTemplates[] = $messageTemplate;
        }

        Response::html(View::render('pages/admin_reservations_show', [
            'title' => 'Szczegóły rezerwacji',
            'reservation' => $reservation,
            'reservationHistory' => $reservationHistory,
            'reservationMessageTemplates' => $reservationMessageTemplates,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać rezerwacji',
            'message' => AppErrorHandler::safeMessage($exception),
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
            'databaseMessage' => 'Nie udało się pobrać rezerwacji: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => false,
            'calculatedNights' => null,
            'calculatedTotalPrice' => null,
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/edytuj', function (): void {
    $returnUrl = reservationReturnUrlFromPost();

    Auth::requireAdmin();
    requireValidCsrf();

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
    $settings = defaultSettingsForm();
    $existingReservation = null;
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
        $settings = SettingsRepository::all();
        $existingReservation = ReservationRepository::find($id);
    } catch (Throwable $exception) {
        Response::html(View::render('pages/admin_reservations_edit', [
            'title' => 'Edytuj rezerwację',
            'id' => $id,
            'form' => $form,
            'errors' => $errors,
            'cabins' => [],
            'guests' => [],
            'databaseMessage' => 'Nie udało się pobrać danych do formularza: ' . AppErrorHandler::safeMessage($exception),
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

            $existingCabinId = $existingReservation !== null
                ? (int) ($existingReservation['cabin_id'] ?? 0)
                : 0;

            $existingStartDate = $existingReservation !== null
                ? substr(
                    (string) ($existingReservation['start_date'] ?? ''),
                    0,
                    10
                )
                : '';

            $existingEndDate = $existingReservation !== null
                ? substr(
                    (string) ($existingReservation['end_date'] ?? ''),
                    0,
                    10
                )
                : '';

            $hasPricingChange =
                $existingCabinId !== (int) $form['cabin_id']
                || $existingStartDate !== $form['start_date']
                || $existingEndDate !== $form['end_date'];

            $existingTotalPrice =
                $existingReservation['total_price'] ?? null;

            if (
                !$hasPricingChange
                && $existingTotalPrice !== null
                && is_numeric($existingTotalPrice)
            ) {
                $calculatedTotalPrice =
                    (int) round((float) $existingTotalPrice);
            } else {
                $calculatedTotalPrice = $calculatedNights
                    * getReservationNightPriceFromSettings(
                        $calculatedNights,
                        $settings
                    );
            }
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
            'databaseMessage' => 'Nie udało się zapisać rezerwacji: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => true,
            'calculatedNights' => $calculatedNights,
            'calculatedTotalPrice' => $calculatedTotalPrice,
        ]), 500);
    }
});


$router->post('/admin/rezerwacje/szybki-status', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/szybka-platnosc', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/wplata', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/status', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić statusu',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/platnosc', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się zmienić płatności',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/anuluj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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

        Response::redirect(reservationActionReturnUrlFromPost($id));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się anulować rezerwacji',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/rezerwacje/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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

        Response::redirect(reservationDeleteReturnUrlFromPost());
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się usunąć rezerwacji',
            'message' => AppErrorHandler::safeMessage($exception),
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
            $databaseMessage = 'Nie udało się pobrać listy gości z bazy: ' . AppErrorHandler::safeMessage($exception);
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
    Auth::requireAdmin();
    GuestImportController::show();
});

$router->post('/admin/goscie/import', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();
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
    requireValidCsrf();

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
            'databaseMessage' => 'Nie udało się zapisać gościa: ' . AppErrorHandler::safeMessage($exception),
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

        $todayKey = (
            new DateTimeImmutable('today')
        )->format('Y-m-d');

        $guestStats = [
            'reservations_count' => 0,
            'completed_stays' => 0,
            'cancelled_count' => 0,
            'total_value' => 0.0,
            'total_paid' => 0.0,
            'last_stay' => null,
            'next_stay' => null,
        ];

        foreach ($reservations as $reservation) {
            $status = (string) (
                $reservation['status']
                ?? ''
            );

            $startDate = substr(
                (string) (
                    $reservation['start_date']
                    ?? ''
                ),
                0,
                10
            );

            $endDate = substr(
                (string) (
                    $reservation['end_date']
                    ?? ''
                ),
                0,
                10
            );

            if ($status === 'CANCELLED') {
                $guestStats[
                    'cancelled_count'
                ]++;

                continue;
            }

            $guestStats[
                'reservations_count'
            ]++;

            if ($status === 'CHECKED_OUT') {
                $guestStats[
                    'completed_stays'
                ]++;
            }

            $guestStats[
                'total_value'
            ] += is_numeric(
                $reservation['total_price']
                ?? null
            )
                ? (float) $reservation[
                    'total_price'
                ]
                : 0;

            $guestStats[
                'total_paid'
            ] += is_numeric(
                $reservation['paid_amount']
                ?? null
            )
                ? (float) $reservation[
                    'paid_amount'
                ]
                : 0;

            if (
                $endDate !== ''
                && (
                    $endDate < $todayKey
                    || $status === 'CHECKED_OUT'
                )
            ) {
                $lastStay = $guestStats[
                    'last_stay'
                ];

                if (
                    !is_array($lastStay)
                    || $endDate > (
                        $lastStay['end_date']
                        ?? ''
                    )
                ) {
                    $guestStats[
                        'last_stay'
                    ] = $reservation;
                }
            }

            if (
                $startDate >= $todayKey
                && in_array(
                    $status,
                    [
                        'PENDING',
                        'CONFIRMED',
                    ],
                    true
                )
            ) {
                $nextStay = $guestStats[
                    'next_stay'
                ];

                if (
                    !is_array($nextStay)
                    || $startDate < (
                        $nextStay['start_date']
                        ?? '9999-12-31'
                    )
                ) {
                    $guestStats[
                        'next_stay'
                    ] = $reservation;
                }
            }
        }

        Response::html(View::render('pages/admin_guests_show', [
            'title' => 'Szczegóły gościa',
            'guest' => $guest,
            'reservations' => $reservations,
            'guestStats' => $guestStats,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać gościa',
            'message' => AppErrorHandler::safeMessage($exception),
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
            'databaseMessage' => 'Nie udało się pobrać gościa: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => false,
        ]), 500);
    }
});

$router->post('/admin/goscie/edytuj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'databaseMessage' => 'Nie udało się zapisać gościa: ' . AppErrorHandler::safeMessage($exception),
            'canSave' => true,
        ]), 500);
    }
});

$router->post('/admin/goscie/vip', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/goscie/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
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
            $databaseMessage = 'Nie udało się pobrać listy zapytań z bazy: ' . AppErrorHandler::safeMessage($exception);
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

        $settings = SettingsRepository::all();

        $inquiryMessageTemplates = [];

        foreach (
            MessageTemplateRepository::activeForContext(
                'INQUIRY'
            )
            as $messageTemplate
        ) {
            $messageTemplate[
                'rendered_content'
            ] = MessageTemplateRenderer::forInquiry(
                (string) $messageTemplate['content'],
                $inquiry,
                $settings
            );

            $inquiryMessageTemplates[] = $messageTemplate;
        }

        Response::html(View::render('pages/admin_inquiries_show', [
            'title' => 'Szczegóły zapytania',
            'inquiry' => $inquiry,
            'inquiryMessageTemplates' => $inquiryMessageTemplates,
        ]));
    } catch (Throwable $exception) {
        Response::html(View::render('pages/error', [
            'title' => 'Nie udało się pobrać zapytania',
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/zapytania/status', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
        ]), 500);
    }
});

$router->post('/admin/zapytania/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

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
            'message' => AppErrorHandler::safeMessage($exception),
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
    Auth::requireAdmin();
    MediaController::index();
});

$router->post('/admin/media', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();
    MediaController::handle();
});

$router->get('/admin/szablony', function (): void {
    Auth::requireAdmin();

    $templates = [];
    $databaseMessage = null;
    $successMessage = null;
    $errorMessage = null;

    if (isset($_GET['added'])) {
        $successMessage = 'Szablon został dodany.';
    } elseif (isset($_GET['updated'])) {
        $successMessage = 'Szablon został zaktualizowany.';
    } elseif (isset($_GET['deleted'])) {
        $successMessage = 'Szablon został usunięty.';
    }

    $error = isset($_GET['error'])
        ? (string) $_GET['error']
        : '';

    if ($error === 'validation') {
        $errorMessage = 'Uzupełnij poprawnie nazwę, zastosowanie, kolejność i treść szablonu.';
    } elseif ($error === 'not_found') {
        $errorMessage = 'Nie znaleziono wskazanego szablonu.';
    } elseif ($error === 'database') {
        $errorMessage = 'Nie udało się wykonać operacji na szablonie.';
    }

    if (!Database::canAttemptConnection()) {
        $databaseMessage = 'Baza danych nie jest jeszcze skonfigurowana. Zarządzanie szablonami jest niedostępne.';
    } else {
        try {
            MessageTemplateRepository::ensureDefaultTemplates();
            $templates = MessageTemplateRepository::all();
        } catch (Throwable $exception) {
            $databaseMessage = 'Nie udało się pobrać szablonów: '
                . AppErrorHandler::safeMessage($exception);
        }
    }

    Response::html(View::render(
        'pages/admin_message_templates',
        [
            'title' => 'Szablony wiadomości',
            'templates' => $templates,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'databaseMessage' => $databaseMessage,
            'canSave' => Database::canAttemptConnection(),
        ]
    ));
});

$router->post('/admin/szablony/dodaj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    if (!Database::canAttemptConnection()) {
        Response::redirect(
            '/admin/szablony?error=database'
        );

        return;
    }

    $name = trim(
        (string) ($_POST['name'] ?? '')
    );

    $templateContext = strtoupper(
        trim(
            (string) (
                $_POST['template_context']
                ?? ''
            )
        )
    );

    $content = trim(
        (string) ($_POST['content'] ?? '')
    );

    $sortOrderRaw = trim(
        (string) ($_POST['sort_order'] ?? '0')
    );

    $allowedContexts = [
        'INQUIRY',
        'RESERVATION',
        'GENERAL',
    ];

    if (
        $name === ''
        || (
            function_exists('mb_strlen')
                ? mb_strlen($name)
                : strlen($name)
        ) > 150
        || !in_array(
            $templateContext,
            $allowedContexts,
            true
        )
        || $content === ''
        || preg_match(
            '/^\d{1,4}$/',
            $sortOrderRaw
        ) !== 1
    ) {
        Response::redirect(
            '/admin/szablony?error=validation'
        );

        return;
    }

    try {
        MessageTemplateRepository::create([
            'name' => $name,
            'template_key' => null,
            'template_context' => $templateContext,
            'content' => $content,
            'is_active' => isset(
                $_POST['is_active']
            ),
            'sort_order' => (int) $sortOrderRaw,
        ]);

        Response::redirect(
            '/admin/szablony?added=1'
        );
    } catch (Throwable $exception) {
        error_log(
            'Nie udało się dodać szablonu: '
            . $exception->getMessage()
        );

        Response::redirect(
            '/admin/szablony?error=database'
        );
    }
});

$router->post('/admin/szablony/edytuj', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    if (!Database::canAttemptConnection()) {
        Response::redirect(
            '/admin/szablony?error=database'
        );

        return;
    }

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!is_int($id) || $id < 1) {
        Response::redirect(
            '/admin/szablony?error=not_found'
        );

        return;
    }

    $name = trim(
        (string) ($_POST['name'] ?? '')
    );

    $templateContext = strtoupper(
        trim(
            (string) (
                $_POST['template_context']
                ?? ''
            )
        )
    );

    $content = trim(
        (string) ($_POST['content'] ?? '')
    );

    $sortOrderRaw = trim(
        (string) ($_POST['sort_order'] ?? '0')
    );

    $allowedContexts = [
        'INQUIRY',
        'RESERVATION',
        'GENERAL',
    ];

    if (
        $name === ''
        || (
            function_exists('mb_strlen')
                ? mb_strlen($name)
                : strlen($name)
        ) > 150
        || !in_array(
            $templateContext,
            $allowedContexts,
            true
        )
        || $content === ''
        || preg_match(
            '/^\d{1,4}$/',
            $sortOrderRaw
        ) !== 1
    ) {
        Response::redirect(
            '/admin/szablony?error=validation'
        );

        return;
    }

    try {
        $existingTemplate = MessageTemplateRepository::find(
            $id
        );

        if ($existingTemplate === null) {
            Response::redirect(
                '/admin/szablony?error=not_found'
            );

            return;
        }

        MessageTemplateRepository::update(
            $id,
            [
                'name' => $name,
                'template_key' => $existingTemplate[
                    'template_key'
                ],
                'template_context' => $templateContext,
                'content' => $content,
                'is_active' => isset(
                    $_POST['is_active']
                ),
                'sort_order' => (int) $sortOrderRaw,
            ]
        );

        Response::redirect(
            '/admin/szablony?updated=1'
        );
    } catch (Throwable $exception) {
        error_log(
            'Nie udało się zaktualizować szablonu: '
            . $exception->getMessage()
        );

        Response::redirect(
            '/admin/szablony?error=database'
        );
    }
});

$router->post('/admin/szablony/usun', function (): void {
    Auth::requireAdmin();
    requireValidCsrf();

    if (!Database::canAttemptConnection()) {
        Response::redirect(
            '/admin/szablony?error=database'
        );

        return;
    }

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!is_int($id) || $id < 1) {
        Response::redirect(
            '/admin/szablony?error=not_found'
        );

        return;
    }

    try {
        $template = MessageTemplateRepository::find(
            $id
        );

        if ($template === null) {
            Response::redirect(
                '/admin/szablony?error=not_found'
            );

            return;
        }

        MessageTemplateRepository::delete(
            $id
        );

        Response::redirect(
            '/admin/szablony?deleted=1'
        );
    } catch (Throwable $exception) {
        error_log(
            'Nie udało się usunąć szablonu: '
            . $exception->getMessage()
        );

        Response::redirect(
            '/admin/szablony?error=database'
        );
    }
});

$router->get(
    '/admin/sprzedawcy-faktur',
    function (): void {
        InvoiceSellerController::index();
    }
);

$router->get(
    '/admin/sprzedawcy-faktur/nowy',
    function (): void {
        InvoiceSellerController::create();
    }
);

$router->post(
    '/admin/sprzedawcy-faktur/nowy',
    function (): void {
        InvoiceSellerController::store();
    }
);

$router->get(
    '/admin/sprzedawcy-faktur/edytuj',
    function (): void {
        InvoiceSellerController::edit();
    }
);

$router->post(
    '/admin/sprzedawcy-faktur/edytuj',
    function (): void {
        InvoiceSellerController::update();
    }
);

$router->post(
    '/admin/sprzedawcy-faktur/status',
    function (): void {
        InvoiceSellerController::changeStatus();
    }
);

$router->get('/admin/ustawienia', function (): void {
    Auth::requireAdmin();

    if (Database::canAttemptConnection()) {
        try {
            MessageTemplateRepository::ensureDefaultTemplates();
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się przygotować domyślnych szablonów wiadomości: '
                . $exception->getMessage()
            );
        }
    }

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
            $databaseMessage = 'Nie udało się pobrać ustawień: ' . AppErrorHandler::safeMessage($exception);
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
    requireValidCsrf();

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
            'databaseMessage' => 'Nie udało się zapisać ustawień: ' . AppErrorHandler::safeMessage($exception),
            'successMessage' => null,
            'canSave' => true,
        ]), 500);
    }
});

$router->get('/admin/raporty', function (): void {
    Auth::requireAdmin();

    $currentYear = date('Y');

    $dateFrom = isset($_GET['date_from'])
        && is_string($_GET['date_from'])
        ? trim($_GET['date_from'])
        : $currentYear . '-01-01';

    $dateTo = isset($_GET['date_to'])
        && is_string($_GET['date_to'])
        ? trim($_GET['date_to'])
        : $currentYear . '-12-31';

    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

    if (
        preg_match(
            $datePattern,
            $dateFrom
        ) !== 1
    ) {
        $dateFrom = $currentYear
            . '-01-01';
    }

    if (
        preg_match(
            $datePattern,
            $dateTo
        ) !== 1
    ) {
        $dateTo = $currentYear
            . '-12-31';
    }

    if ($dateFrom > $dateTo) {
        [
            $dateFrom,
            $dateTo,
        ] = [
            $dateTo,
            $dateFrom,
        ];
    }

    $summary = ReportRepository::emptySummary();
    $statusRows = [];
    $sourceRows = [];
    $monthRows = [];
    $cabinRows = [];
    $databaseMessage = null;

    if (!Database::canAttemptConnection()) {
        $databaseMessage =
            'Baza danych nie jest jeszcze skonfigurowana. '
            . 'Raporty będą dostępne po skonfigurowaniu MySQL.';
    } else {
        try {
            $summary = ReportRepository::summary(
                $dateFrom,
                $dateTo
            );

            $statusRows = ReportRepository::byStatus(
                $dateFrom,
                $dateTo
            );

            $sourceRows = ReportRepository::bySource(
                $dateFrom,
                $dateTo
            );

            $monthRows = ReportRepository::byMonth(
                $dateFrom,
                $dateTo
            );

            $cabinRows = ReportRepository::byCabin(
                $dateFrom,
                $dateTo
            );
        } catch (Throwable $exception) {
            $databaseMessage =
                'Nie udało się przygotować raportu: '
                . AppErrorHandler::safeMessage(
                    $exception
                );
        }
    }

    Response::html(
        View::render(
            'pages/admin_reports',
            [
                'title' => 'Raporty',
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'summary' => $summary,
                'statusRows' => $statusRows,
                'sourceRows' => $sourceRows,
                'monthRows' => $monthRows,
                'cabinRows' => $cabinRows,
                'databaseMessage' =>
                    $databaseMessage,
            ]
        )
    );
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
    requireValidCsrf();

    $message = '';
    $messageType = 'warning';

    try {
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        $executedStatements = Database::installSchema($schemaPath);

        $message = 'Instalator zakończył pracę poprawnie. Wykonano poleceń SQL: ' . $executedStatements . '.';
        $messageType = 'success';
    } catch (Throwable $exception) {
        $message = 'Nie udało się uruchomić instalatora: ' . AppErrorHandler::safeMessage($exception);
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
    requireValidCsrf();

    $message = '';
    $messageType = 'warning';

    try {
        $seedPath = dirname(__DIR__) . '/database/seed.sql';
        $executedStatements = Database::seedDefaultData($seedPath);

        $message = 'Dane startowe zostały wgrane poprawnie. Wykonano poleceń SQL: ' . $executedStatements . '.';
        $messageType = 'success';
    } catch (Throwable $exception) {
        $message = 'Nie udało się wgrać danych startowych: ' . AppErrorHandler::safeMessage($exception);
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
    Auth::requireAdmin();
    ImportAuditController::show();
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
