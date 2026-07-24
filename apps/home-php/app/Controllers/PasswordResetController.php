<?php

declare(strict_types=1);

final class PasswordResetController
{
    public static function requestForm(): void
    {
        Response::html(
            View::render(
                'pages/forgot_password',
                [
                    'title' => 'Odzyskiwanie hasła',
                    'email' => '',
                    'errors' => [],
                    'requestSent' => false,
                ]
            )
        );
    }

    public static function requestLink(): void
    {
        Auth::startSession();
        requireValidCsrf();

        $email = strtolower(
            trim(
                is_string(
                    $_POST['email']
                    ?? null
                )
                    ? $_POST['email']
                    : ''
            )
        );

        $errors = [];

        if (
            $email !== ''
            && filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errors['email'] =
                'Podaj prawidłowy adres e-mail.';
        }

        if ($errors !== []) {
            Response::html(
                View::render(
                    'pages/forgot_password',
                    [
                        'title' =>
                            'Odzyskiwanie hasła',
                        'email' => $email,
                        'errors' => $errors,
                        'requestSent' => false,
                    ]
                ),
                422
            );

            return;
        }

        /*
         * Dla pustego, nieistniejącego i zablokowanego konta
         * pokazujemy dokładnie ten sam komunikat.
         */
        PasswordResetService::request(
            $email
        );

        Response::html(
            View::render(
                'pages/forgot_password',
                [
                    'title' => 'Odzyskiwanie hasła',
                    'email' => '',
                    'errors' => [],
                    'requestSent' => true,
                ]
            )
        );
    }

    public static function resetForm(): void
    {
        $token = self::tokenFromQuery();
        $tokenData = $token !== ''
            ? PasswordResetService::validToken(
                $token
            )
            : null;

        Response::html(
            View::render(
                'pages/reset_password',
                [
                    'title' => 'Ustaw nowe hasło',
                    'token' => $token,
                    'errors' => [],
                    'invalidToken' =>
                        $tokenData === null,
                ]
            ),
            $tokenData === null
                ? 422
                : 200
        );
    }

    public static function resetPassword(): void
    {
        Auth::startSession();
        requireValidCsrf();

        $token = trim(
            is_string(
                $_POST['token']
                ?? null
            )
                ? $_POST['token']
                : ''
        );

        $password = is_string(
            $_POST['password']
            ?? null
        )
            ? $_POST['password']
            : '';

        $confirmation = is_string(
            $_POST['password_confirmation']
            ?? null
        )
            ? $_POST['password_confirmation']
            : '';

        $errors = [];

        if (
            PasswordResetService::validToken(
                $token
            ) === null
        ) {
            Response::html(
                View::render(
                    'pages/reset_password',
                    [
                        'title' =>
                            'Ustaw nowe hasło',
                        'token' => '',
                        'errors' => [],
                        'invalidToken' => true,
                    ]
                ),
                422
            );

            return;
        }

        if (strlen($password) < 12) {
            $errors['password'] =
                'Hasło musi mieć co najmniej 12 znaków.';
        }

        if ($password !== $confirmation) {
            $errors['password_confirmation'] =
                'Powtórzone hasło nie jest zgodne.';
        }

        if ($errors !== []) {
            Response::html(
                View::render(
                    'pages/reset_password',
                    [
                        'title' =>
                            'Ustaw nowe hasło',
                        'token' => $token,
                        'errors' => $errors,
                        'invalidToken' => false,
                    ]
                ),
                422
            );

            return;
        }

        try {
            if (
                !PasswordResetService::resetPassword(
                    $token,
                    $password
                )
            ) {
                Response::html(
                    View::render(
                        'pages/reset_password',
                        [
                            'title' =>
                                'Ustaw nowe hasło',
                            'token' => '',
                            'errors' => [],
                            'invalidToken' => true,
                        ]
                    ),
                    422
                );

                return;
            }

            /*
             * Bieżąca sesja również jest zamykana.
             * Pozostałe sesje unieważnia session_version.
             */
            Auth::logout();

            Response::redirect(
                '/logowanie?password_reset=1'
            );
        } catch (Throwable $exception) {
            error_log(
                'Password reset error: '
                . $exception::class
                . ': '
                . $exception->getMessage()
            );

            Response::html(
                View::render(
                    'pages/reset_password',
                    [
                        'title' =>
                            'Ustaw nowe hasło',
                        'token' => $token,
                        'errors' => [
                            'password' =>
                                'Nie udało się ustawić nowego hasła. '
                                . 'Spróbuj ponownie.',
                        ],
                        'invalidToken' => false,
                    ]
                ),
                500
            );
        }
    }

    private static function tokenFromQuery(): string
    {
        $token = $_GET['token']
            ?? '';

        return is_string($token)
            ? trim($token)
            : '';
    }
}
