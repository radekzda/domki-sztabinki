<?php

declare(strict_types=1);

final class InvoiceSellerController
{
    /**
     * @var array<int, string>
     */
    private const TAX_ID_TYPES = [
        'NIP',
        'VAT_EU',
        'OTHER',
        'NONE',
    ];

    public static function index(): void
    {
        Auth::requireAdmin();

        $sellers = [];
        $databaseMessage = null;
        $successMessage = null;

        if (isset($_GET['created'])) {
            $successMessage =
                'Sprzedawca faktur został dodany.';
        } elseif (isset($_GET['updated'])) {
            $successMessage =
                'Dane sprzedawcy zostały zaktualizowane.';
        } elseif (isset($_GET['status_changed'])) {
            $successMessage =
                'Status sprzedawcy został zmieniony.';
        }

        if (!Database::canAttemptConnection()) {
            $databaseMessage =
                'Baza danych nie jest jeszcze skonfigurowana. '
                . 'Lista sprzedawców będzie dostępna po '
                . 'skonfigurowaniu MySQL.';
        } else {
            try {
                $sellers =
                    InvoiceSellerRepository::all();
            } catch (Throwable $exception) {
                $databaseMessage =
                    'Nie udało się pobrać sprzedawców: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    );
            }
        }

        Response::html(
            View::render(
                'pages/admin_invoice_sellers',
                [
                    'title' =>
                        'Sprzedawcy faktur',
                    'sellers' =>
                        $sellers,
                    'databaseMessage' =>
                        $databaseMessage,
                    'successMessage' =>
                        $successMessage,
                ]
            )
        );
    }

    public static function create(): void
    {
        Auth::requireAdmin();

        self::renderForm(
            self::defaultForm(),
            [],
            Database::canAttemptConnection()
                ? null
                : 'Baza danych nie jest jeszcze '
                    . 'skonfigurowana. Nie można zapisać '
                    . 'sprzedawcy.',
            Database::canAttemptConnection(),
            null
        );
    }

    public static function store(): void
    {
        Auth::requireAdmin();
        requireValidCsrf();

        $form = self::formFromPost();
        $errors = self::validateForm($form);

        if (!Database::canAttemptConnection()) {
            self::renderForm(
                $form,
                $errors,
                'Baza danych nie jest jeszcze '
                    . 'skonfigurowana. Nie można zapisać '
                    . 'sprzedawcy.',
                false,
                null,
                422
            );

            return;
        }

        if ($errors !== []) {
            self::renderForm(
                $form,
                $errors,
                null,
                true,
                null,
                422
            );

            return;
        }

        try {
            InvoiceSellerRepository::create(
                self::dataFromForm($form)
            );

            Response::redirect(
                '/admin/sprzedawcy-faktur?created=1'
            );
        } catch (Throwable $exception) {
            self::renderForm(
                $form,
                [],
                'Nie udało się zapisać sprzedawcy: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    ),
                true,
                null,
                500
            );
        }
    }

    public static function edit(): void
    {
        Auth::requireAdmin();

        $id = self::idFromQuery();

        if ($id === null) {
            self::renderInvalidId();

            return;
        }

        if (!Database::canAttemptConnection()) {
            self::renderForm(
                self::defaultForm(),
                [],
                'Baza danych nie jest jeszcze '
                    . 'skonfigurowana. Nie można pobrać '
                    . 'sprzedawcy.',
                false,
                $id,
                422
            );

            return;
        }

        try {
            $seller =
                InvoiceSellerRepository::find($id);

            if ($seller === null) {
                self::renderNotFound();

                return;
            }

            self::renderForm(
                self::formFromSeller($seller),
                [],
                null,
                true,
                $id
            );
        } catch (Throwable $exception) {
            self::renderForm(
                self::defaultForm(),
                [],
                'Nie udało się pobrać sprzedawcy: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    ),
                false,
                $id,
                500
            );
        }
    }

    public static function update(): void
    {
        Auth::requireAdmin();
        requireValidCsrf();

        $id = self::idFromQuery();

        if ($id === null) {
            self::renderInvalidId();

            return;
        }

        $form = self::formFromPost();
        $errors = self::validateForm($form);

        if (!Database::canAttemptConnection()) {
            self::renderForm(
                $form,
                $errors,
                'Baza danych nie jest jeszcze '
                    . 'skonfigurowana. Nie można zapisać '
                    . 'zmian.',
                false,
                $id,
                422
            );

            return;
        }

        if ($errors !== []) {
            self::renderForm(
                $form,
                $errors,
                null,
                true,
                $id,
                422
            );

            return;
        }

        try {
            $seller =
                InvoiceSellerRepository::find($id);

            if ($seller === null) {
                self::renderNotFound();

                return;
            }

            InvoiceSellerRepository::update(
                $id,
                self::dataFromForm($form)
            );

            Response::redirect(
                '/admin/sprzedawcy-faktur?updated=1'
            );
        } catch (Throwable $exception) {
            self::renderForm(
                $form,
                [],
                'Nie udało się zapisać zmian: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    ),
                true,
                $id,
                500
            );
        }
    }

    public static function changeStatus(): void
    {
        Auth::requireAdmin();
        requireValidCsrf();

        $id = self::idFromPost();

        $isActive = filter_var(
            $_POST['is_active'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $id === null
            || !is_int($isActive)
            || !in_array($isActive, [0, 1], true)
        ) {
            Response::html(
                View::render(
                    'pages/error',
                    [
                        'title' =>
                            'Nieprawidłowe dane',
                        'message' =>
                            'Nie można zmienić statusu '
                            . 'sprzedawcy, ponieważ przesłane '
                            . 'dane są nieprawidłowe.',
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
                            'Nie można zmienić statusu '
                            . 'sprzedawcy bez połączenia '
                            . 'z bazą danych.',
                    ]
                ),
                422
            );

            return;
        }

        try {
            if (
                InvoiceSellerRepository::find($id)
                === null
            ) {
                self::renderNotFound();

                return;
            }

            InvoiceSellerRepository::setActive(
                $id,
                $isActive === 1
            );

            Response::redirect(
                '/admin/sprzedawcy-faktur'
                . '?status_changed=1'
            );
        } catch (Throwable $exception) {
            Response::html(
                View::render(
                    'pages/error',
                    [
                        'title' =>
                            'Nie udało się zmienić statusu',
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

    /**
     * @return array<string, string>
     */
    private static function defaultForm(): array
    {
        return [
            'name' => '',
            'tax_id_type' => 'NIP',
            'tax_id' => '',
            'street' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Polska',
            'email' => '',
            'phone' => '',
            'bank_account_holder' => '',
            'bank_account_number' => '',
            'invoice_series' => 'FV',
            'is_active' => '1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function formFromPost(): array
    {
        $form = self::defaultForm();

        foreach ($form as $key => $defaultValue) {
            if ($key === 'is_active') {
                continue;
            }

            $value = $_POST[$key] ?? $defaultValue;

            $form[$key] = is_string($value)
                ? trim($value)
                : $defaultValue;
        }

        $form['tax_id_type'] = strtoupper(
            $form['tax_id_type']
        );

        $form['invoice_series'] = strtoupper(
            $form['invoice_series']
        );

        $form['is_active'] =
            isset($_POST['is_active'])
                ? '1'
                : '0';

        return $form;
    }

    /**
     * @param array<string, mixed> $seller
     *
     * @return array<string, string>
     */
    private static function formFromSeller(
        array $seller
    ): array {
        $form = self::defaultForm();

        foreach ($form as $key => $defaultValue) {
            if (!array_key_exists($key, $seller)) {
                continue;
            }

            $value = $seller[$key];

            if ($key === 'is_active') {
                $form[$key] =
                    (int) $value === 1
                        ? '1'
                        : '0';

                continue;
            }

            $form[$key] =
                $value !== null
                    ? trim((string) $value)
                    : '';
        }

        return $form;
    }

    /**
     * @param array<string, string> $form
     *
     * @return array<string, string>
     */
    private static function validateForm(
        array $form
    ): array {
        $errors = [];

        if ($form['name'] === '') {
            $errors['name'] =
                'Nazwa lub imię i nazwisko są wymagane.';
        } elseif (strlen($form['name']) > 190) {
            $errors['name'] =
                'Nazwa może mieć maksymalnie 190 znaków.';
        }

        if (
            !in_array(
                $form['tax_id_type'],
                self::TAX_ID_TYPES,
                true
            )
        ) {
            $errors['tax_id_type'] =
                'Wybierz prawidłowy typ identyfikatora.';
        }

        if (
            $form['tax_id_type'] !== 'NONE'
            && $form['tax_id'] === ''
        ) {
            $errors['tax_id'] =
                'Wpisz identyfikator podatkowy albo wybierz '
                . 'opcję „Brak”.';
        } elseif (strlen($form['tax_id']) > 40) {
            $errors['tax_id'] =
                'Identyfikator może mieć maksymalnie '
                . '40 znaków.';
        }

        if ($form['country'] === '') {
            $errors['country'] =
                'Kraj jest wymagany.';
        }

        if (
            $form['email'] !== ''
            && filter_var(
                $form['email'],
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errors['email'] =
                'Wpisz prawidłowy adres e-mail.';
        }

        if ($form['invoice_series'] === '') {
            $errors['invoice_series'] =
                'Seria faktur jest wymagana.';
        } elseif (
            preg_match(
                '/^[A-Z0-9_-]{1,40}$/',
                $form['invoice_series']
            ) !== 1
        ) {
            $errors['invoice_series'] =
                'Seria może zawierać wielkie litery, cyfry, '
                . 'myślnik i podkreślenie.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $form
     *
     * @return array<string, mixed>
     */
    private static function dataFromForm(
        array $form
    ): array {
        return [
            'name' => $form['name'],
            'tax_id_type' => $form['tax_id_type'],
            'tax_id' =>
                $form['tax_id_type'] === 'NONE'
                    ? null
                    : $form['tax_id'],
            'street' => $form['street'],
            'postal_code' => $form['postal_code'],
            'city' => $form['city'],
            'country' => $form['country'],
            'email' => $form['email'],
            'phone' => $form['phone'],
            'bank_account_holder' =>
                $form['bank_account_holder'],
            'bank_account_number' =>
                $form['bank_account_number'],
            'invoice_series' =>
                $form['invoice_series'],
            'is_active' =>
                $form['is_active'],
        ];
    }

    private static function idFromQuery(): ?int
    {
        $id = filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        return is_int($id) && $id > 0
            ? $id
            : null;
    }

    private static function idFromPost(): ?int
    {
        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        return is_int($id) && $id > 0
            ? $id
            : null;
    }

    /**
     * @param array<string, string> $form
     * @param array<string, string> $errors
     */
    private static function renderForm(
        array $form,
        array $errors,
        ?string $databaseMessage,
        bool $canSave,
        ?int $id,
        int $statusCode = 200
    ): void {
        $isEdit = $id !== null;

        Response::html(
            View::render(
                'pages/admin_invoice_seller_form',
                [
                    'title' =>
                        $isEdit
                            ? 'Edytuj sprzedawcę faktur'
                            : 'Dodaj sprzedawcę faktur',
                    'pageHeading' =>
                        $isEdit
                            ? 'Edytuj sprzedawcę'
                            : 'Dodaj sprzedawcę',
                    'formAction' =>
                        $isEdit
                            ? '/admin/sprzedawcy-faktur'
                                . '/edytuj?id='
                                . $id
                            : '/admin/sprzedawcy-faktur'
                                . '/nowy',
                    'submitLabel' =>
                        $isEdit
                            ? 'Zapisz zmiany'
                            : 'Dodaj sprzedawcę',
                    'form' => $form,
                    'errors' => $errors,
                    'databaseMessage' =>
                        $databaseMessage,
                    'canSave' => $canSave,
                    'isEdit' => $isEdit,
                ]
            ),
            $statusCode
        );
    }

    private static function renderInvalidId(): void
    {
        Response::html(
            View::render(
                'pages/error',
                [
                    'title' =>
                        'Nieprawidłowy adres',
                    'message' =>
                        'Brakuje prawidłowego identyfikatora '
                        . 'sprzedawcy faktur.',
                ]
            ),
            400
        );
    }

    private static function renderNotFound(): void
    {
        Response::html(
            View::render(
                'pages/error',
                [
                    'title' =>
                        'Nie znaleziono sprzedawcy',
                    'message' =>
                        'Sprzedawca faktur o podanym '
                        . 'identyfikatorze nie istnieje.',
                ]
            ),
            404
        );
    }
}