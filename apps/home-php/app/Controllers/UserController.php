<?php

declare(strict_types=1);

final class UserController
{
    /**
     * @var array<int, string>
     */
    private const ROLES = [
        'ADMIN',
        'PRACOWNIK',
    ];

    public static function index(): void
    {
        Auth::requireAdministrator();

        $users = [];
        $databaseMessage = null;
        $successMessage = null;

        if (isset($_GET['created'])) {
            $successMessage =
                'Użytkownik został dodany.';
        } elseif (isset($_GET['updated'])) {
            $successMessage =
                'Dane użytkownika zostały zaktualizowane.';
        } elseif (isset($_GET['status_changed'])) {
            $successMessage =
                'Status użytkownika został zmieniony.';
        }

        if (!Database::canAttemptConnection()) {
            $databaseMessage =
                'Brak połączenia z bazą danych.';
        } else {
            try {
                $users = UserRepository::all();
            } catch (Throwable $exception) {
                $databaseMessage =
                    'Nie udało się pobrać użytkowników: '
                    . AppErrorHandler::safeMessage(
                        $exception
                    );
            }
        }

        Response::html(
            View::render(
                'pages/admin_users',
                [
                    'title' => 'Użytkownicy',
                    'users' => $users,
                    'databaseMessage' =>
                        $databaseMessage,
                    'successMessage' =>
                        $successMessage,
                    'currentUserId' =>
                        Auth::currentUserId(),
                ]
            )
        );
    }

    public static function create(): void
    {
        Auth::requireAdministrator();

        self::renderForm(
            self::defaultForm(),
            [],
            null,
            null
        );
    }

    public static function store(): void
    {
        Auth::requireAdministrator();
        requireValidCsrf();

        $form = self::formFromPost();
        $errors = self::validateForm(
            $form,
            true,
            null
        );

        if ($errors !== []) {
            self::renderForm(
                $form,
                $errors,
                null,
                null,
                422
            );

            return;
        }

        try {
            UserRepository::create([
                'name' => $form['name'],
                'email' => $form['email'],
                'password_hash' =>
                    password_hash(
                        $form['password'],
                        PASSWORD_DEFAULT
                    ),
                'role' => $form['role'],
                'is_active' =>
                    (int) $form['is_active'],
            ]);

            Response::redirect(
                '/admin/uzytkownicy?created=1'
            );
        } catch (Throwable $exception) {
            self::renderForm(
                $form,
                [],
                'Nie udało się dodać użytkownika: '
                . AppErrorHandler::safeMessage(
                    $exception
                ),
                null,
                500
            );
        }
    }

    public static function edit(): void
    {
        Auth::requireAdministrator();

        $id = self::idFromQuery();

        if ($id === null) {
            self::renderInvalidId();

            return;
        }

        try {
            $user = UserRepository::find($id);

            if ($user === null) {
                self::renderNotFound();

                return;
            }

            self::renderForm(
                self::formFromUser($user),
                [],
                null,
                $id
            );
        } catch (Throwable $exception) {
            self::renderForm(
                self::defaultForm(),
                [],
                'Nie udało się pobrać użytkownika: '
                . AppErrorHandler::safeMessage(
                    $exception
                ),
                $id,
                500
            );
        }
    }

    public static function update(): void
    {
        Auth::requireAdministrator();
        requireValidCsrf();

        $id = self::idFromPost();

        if ($id === null) {
            self::renderInvalidId();

            return;
        }

        $form = self::formFromPost();

        try {
            $existing = UserRepository::find($id);

            if ($existing === null) {
                self::renderNotFound();

                return;
            }

            $errors = self::validateForm(
                $form,
                false,
                $id
            );

            $currentUserId =
                Auth::currentUserId();

            if ($currentUserId === $id) {
                if ($form['role'] !== 'ADMIN') {
                    $errors['role'] =
                        'Nie możesz odebrać sobie '
                        . 'roli Administrator.';
                }

                if ($form['is_active'] !== '1') {
                    $errors['is_active'] =
                        'Nie możesz zablokować '
                        . 'własnego konta.';
                }
            }

            $wouldRemoveActiveAdmin =
                (string) (
                    $existing['role']
                    ?? ''
                ) === 'ADMIN'
                && (int) (
                    $existing['is_active']
                    ?? 0
                ) === 1
                && (
                    $form['role'] !== 'ADMIN'
                    || $form['is_active'] !== '1'
                );

            if (
                $wouldRemoveActiveAdmin
                && UserRepository::countActiveAdmins()
                    <= 1
            ) {
                $errors['role'] =
                    'W systemie musi pozostać '
                    . 'co najmniej jeden aktywny '
                    . 'Administrator.';
            }

            if ($errors !== []) {
                self::renderForm(
                    $form,
                    $errors,
                    null,
                    $id,
                    422
                );

                return;
            }

            $passwordHash = null;

            if ($form['password'] !== '') {
                $passwordHash = password_hash(
                    $form['password'],
                    PASSWORD_DEFAULT
                );
            }

            UserRepository::update(
                $id,
                [
                    'name' => $form['name'],
                    'email' => $form['email'],
                    'role' => $form['role'],
                    'is_active' =>
                        (int) $form['is_active'],
                    'password_hash' =>
                        $passwordHash,
                ]
            );

            Response::redirect(
                '/admin/uzytkownicy?updated=1'
            );
        } catch (Throwable $exception) {
            self::renderForm(
                $form,
                [],
                'Nie udało się zapisać zmian: '
                . AppErrorHandler::safeMessage(
                    $exception
                ),
                $id,
                500
            );
        }
    }

    public static function changeStatus(): void
    {
        Auth::requireAdministrator();
        requireValidCsrf();

        $id = self::idFromPost();

        $isActive = filter_var(
            $_POST['is_active'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $id === null
            || !is_int($isActive)
            || !in_array(
                $isActive,
                [0, 1],
                true
            )
        ) {
            self::renderInvalidId();

            return;
        }

        if (Auth::currentUserId() === $id) {
            self::renderAccessError(
                'Nie możesz zablokować własnego konta.'
            );

            return;
        }

        try {
            $user = UserRepository::find($id);

            if ($user === null) {
                self::renderNotFound();

                return;
            }

            if (
                $isActive === 0
                && (string) ($user['role'] ?? '')
                    === 'ADMIN'
                && (int) ($user['is_active'] ?? 0)
                    === 1
                && UserRepository::countActiveAdmins()
                    <= 1
            ) {
                self::renderAccessError(
                    'W systemie musi pozostać '
                    . 'co najmniej jeden aktywny '
                    . 'Administrator.'
                );

                return;
            }

            UserRepository::setActive(
                $id,
                $isActive === 1
            );

            Response::redirect(
                '/admin/uzytkownicy'
                . '?status_changed=1'
            );
        } catch (Throwable $exception) {
            self::renderAccessError(
                'Nie udało się zmienić statusu: '
                . AppErrorHandler::safeMessage(
                    $exception
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
            'email' => '',
            'role' => 'PRACOWNIK',
            'is_active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function formFromPost(): array
    {
        $form = self::defaultForm();

        foreach ($form as $key => $default) {
            $value = $_POST[$key] ?? $default;

            $form[$key] = is_string($value)
                ? trim($value)
                : $default;
        }

        $form['email'] = strtolower(
            $form['email']
        );

        return $form;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, string>
     */
    private static function formFromUser(
        array $user
    ): array {
        return [
            'name' => (string) (
                $user['name']
                ?? ''
            ),
            'email' => (string) (
                $user['email']
                ?? ''
            ),
            'role' => (string) (
                $user['role']
                ?? 'PRACOWNIK'
            ),
            'is_active' => (string) (
                $user['is_active']
                ?? 1
            ),
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    /**
     * @param array<string, string> $form
     * @return array<string, string>
     */
    private static function validateForm(
        array $form,
        bool $passwordRequired,
        ?int $exceptId
    ): array {
        $errors = [];

        if (
            strlen($form['name'])
            < 2
        ) {
            $errors['name'] =
                'Podaj imię i nazwisko '
                . 'lub nazwę użytkownika.';
        } elseif (
            strlen($form['name'])
            > 120
        ) {
            $errors['name'] =
                'Nazwa może mieć maksymalnie '
                . '120 znaków.';
        }

        if (
            filter_var(
                $form['email'],
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errors['email'] =
                'Podaj prawidłowy adres e-mail.';
        } elseif (
            strlen($form['email'])
            > 190
        ) {
            $errors['email'] =
                'Adres e-mail jest za długi.';
        } elseif (
            UserRepository::emailExists(
                $form['email'],
                $exceptId
            )
        ) {
            $errors['email'] =
                'Użytkownik z tym adresem '
                . 'e-mail już istnieje.';
        }

        if (
            !in_array(
                $form['role'],
                self::ROLES,
                true
            )
        ) {
            $errors['role'] =
                'Wybierz prawidłową rolę.';
        }

        if (
            !in_array(
                $form['is_active'],
                ['0', '1'],
                true
            )
        ) {
            $errors['is_active'] =
                'Wybierz prawidłowy status konta.';
        }

        if (
            $passwordRequired
            && $form['password'] === ''
        ) {
            $errors['password'] =
                'Podaj hasło użytkownika.';
        }

        if (
            $form['password'] !== ''
            && strlen(
                $form['password']
            ) < 12
        ) {
            $errors['password'] =
                'Hasło musi mieć co najmniej '
                . '12 znaków.';
        }

        if (
            $form['password'] !== ''
            && $form['password']
                !== $form[
                    'password_confirmation'
                ]
        ) {
            $errors['password_confirmation'] =
                'Powtórzone hasło nie jest zgodne.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $form
     * @param array<string, string> $errors
     */
    private static function renderForm(
        array $form,
        array $errors,
        ?string $databaseMessage,
        ?int $userId,
        int $status = 200
    ): void {
        Response::html(
            View::render(
                'pages/admin_user_form',
                [
                    'title' => $userId === null
                        ? 'Nowy użytkownik'
                        : 'Edycja użytkownika',
                    'form' => $form,
                    'errors' => $errors,
                    'databaseMessage' =>
                        $databaseMessage,
                    'userId' => $userId,
                    'isCurrentUser' =>
                        $userId !== null
                        && Auth::currentUserId()
                            === $userId,
                ]
            ),
            $status
        );
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

    private static function renderInvalidId(): void
    {
        Response::html(
            View::render(
                'pages/error',
                [
                    'title' =>
                        'Nieprawidłowy użytkownik',
                    'message' =>
                        'Nie można rozpoznać '
                        . 'wybranego użytkownika.',
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
                        'Nie znaleziono użytkownika',
                    'message' =>
                        'Wybrane konto nie istnieje.',
                ]
            ),
            404
        );
    }

    private static function renderAccessError(
        string $message,
        int $status = 422
    ): void {
        Response::html(
            View::render(
                'pages/error',
                [
                    'title' =>
                        'Nie można zmienić konta',
                    'message' => $message,
                ]
            ),
            $status
        );
    }
}
