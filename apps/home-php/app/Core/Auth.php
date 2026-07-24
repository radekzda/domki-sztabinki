<?php

declare(strict_types=1);

final class Auth
{
    private const LEGACY_SESSION_KEY =
        'domki_sztabinki_admin_logged_in';

    private const USER_ID_KEY =
        'domki_sztabinki_user_id';

    private const USER_EMAIL_KEY =
        'domki_sztabinki_user_email';

    private const USER_NAME_KEY =
        'domki_sztabinki_user_name';

    private const USER_ROLE_KEY =
        'domki_sztabinki_user_role';

    private const USER_SESSION_VERSION_KEY =
        'domki_sztabinki_user_session_version';

    private const LOGIN_ATTEMPTS_KEY =
        'domki_sztabinki_login_attempts';

    private const LOGIN_BLOCKED_UNTIL_KEY =
        'domki_sztabinki_login_blocked_until';

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_BLOCK_SECONDS = 900;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('domki_sztabinki_admin');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => self::isHttps(),
        ]);

        session_start();
    }

    public static function check(): bool
    {
        self::startSession();

        $userId = $_SESSION[self::USER_ID_KEY] ?? null;

        if (is_int($userId) && $userId > 0) {
            return self::refreshDatabaseUser($userId);
        }

        return self::restoreLegacySession();
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            Response::redirect('/logowanie');
        }
    }

    public static function requireAdministrator(): void
    {
        self::requireAdmin();

        if (self::isAdministrator()) {
            return;
        }

        Response::html(
            View::render(
                'pages/error',
                [
                    'title' => 'Brak uprawnień',
                    'message' =>
                        'Ta funkcja jest dostępna wyłącznie '
                        . 'dla użytkownika z rolą Administrator.',
                ]
            ),
            403
        );

        exit;
    }

    public static function attempt(
        string $email,
        string $password
    ): bool {
        self::startSession();

        if (self::isLoginBlocked()) {
            return false;
        }

        $normalizedEmail = strtolower(
            trim($email)
        );

        if (
            $normalizedEmail === ''
            || $password === ''
        ) {
            self::recordFailedLogin();

            return false;
        }

        if (self::databaseUsersEnabled()) {
            try {
                $user = UserRepository::findByEmail(
                    $normalizedEmail
                );
            } catch (Throwable $exception) {
                error_log(
                    'Nie udało się sprawdzić użytkownika: '
                    . $exception->getMessage()
                );

                return false;
            }

            if (
                $user === null
                || (int) ($user['is_active'] ?? 0) !== 1
                || !password_verify(
                    $password,
                    (string) (
                        $user['password_hash']
                        ?? ''
                    )
                )
            ) {
                self::recordFailedLogin();

                return false;
            }

            self::clearLoginAttempts();
            session_regenerate_id(true);
            self::storeDatabaseUser($user);

            UserRepository::updateLastLogin(
                (int) $user['id']
            );

            return true;
        }

        return self::attemptLegacy(
            $normalizedEmail,
            $password
        );
    }

    public static function isLoginBlocked(): bool
    {
        self::startSession();

        $sessionBlockedUntil =
            $_SESSION[
                self::LOGIN_BLOCKED_UNTIL_KEY
            ]
            ?? null;

        $sessionBlocked = is_int(
            $sessionBlockedUntil
        )
            && $sessionBlockedUntil > time();

        if (
            is_int($sessionBlockedUntil)
            && $sessionBlockedUntil <= time()
        ) {
            self::clearSessionLoginAttempts();
            $sessionBlocked = false;
        }

        return $sessionBlocked
            || self::ipBlockedUntil() > time();
    }

    public static function loginBlockedSecondsRemaining(): int
    {
        self::startSession();

        $sessionBlockedUntil =
            $_SESSION[
                self::LOGIN_BLOCKED_UNTIL_KEY
            ]
            ?? 0;

        $sessionTimestamp = is_int(
            $sessionBlockedUntil
        )
            ? $sessionBlockedUntil
            : 0;

        $blockedUntil = max(
            $sessionTimestamp,
            self::ipBlockedUntil()
        );

        return max(
            0,
            $blockedUntil - time()
        );
    }

    public static function logout(): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $cookieParams =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }

        session_destroy();
    }

    public static function adminEmail(): string
    {
        self::startSession();

        $email =
            $_SESSION[self::USER_EMAIL_KEY]
            ?? $_SESSION['admin_email']
            ?? '';

        return is_string($email)
            ? $email
            : '';
    }

    public static function currentUserId(): ?int
    {
        if (!self::check()) {
            return null;
        }

        $userId =
            $_SESSION[self::USER_ID_KEY]
            ?? null;

        return is_int($userId)
            ? $userId
            : null;
    }

    public static function currentUserName(): string
    {
        if (!self::check()) {
            return '';
        }

        $name =
            $_SESSION[self::USER_NAME_KEY]
            ?? '';

        return is_string($name)
            ? $name
            : '';
    }

    public static function currentRole(): string
    {
        if (!self::check()) {
            return '';
        }

        $role =
            $_SESSION[self::USER_ROLE_KEY]
            ?? '';

        return is_string($role)
            ? $role
            : '';
    }

    public static function isAdministrator(): bool
    {
        return self::currentRole() === 'ADMIN';
    }

    public static function isConfigured(): bool
    {
        if (self::databaseUsersEnabled()) {
            return true;
        }

        return self::legacyConfigured();
    }

    public static function passwordMode(): string
    {
        if (self::databaseUsersEnabled()) {
            return 'database';
        }

        if (self::hasPasswordHash()) {
            return 'hash';
        }

        if (self::hasPlainPassword()) {
            return 'plain';
        }

        return 'missing';
    }

    private static function databaseUsersEnabled(): bool
    {
        if (!Database::canAttemptConnection()) {
            return false;
        }

        try {
            return UserRepository::tableExists()
                && UserRepository::countAll() > 0;
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się sprawdzić tabeli użytkowników: '
                . $exception->getMessage()
            );

            /*
             * Przy skonfigurowanej bazie błąd połączenia nie może
             * uruchomić awaryjnego logowania z .env.
             */
            return true;
        }
    }

    private static function refreshDatabaseUser(
        int $userId
    ): bool {
        if (!self::databaseUsersEnabled()) {
            self::clearUserSession();

            return false;
        }

        try {
            $user = UserRepository::find($userId);
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się odświeżyć sesji użytkownika: '
                . $exception->getMessage()
            );

            self::clearUserSession();

            return false;
        }

        if (
            $user === null
            || (int) ($user['is_active'] ?? 0) !== 1
        ) {
            self::clearUserSession();

            return false;
        }

        $sessionVersion =
            $_SESSION[
                self::USER_SESSION_VERSION_KEY
            ]
            ?? null;

        $databaseVersion = (int) (
            $user['session_version']
            ?? 1
        );

        /*
         * Brak wersji oznacza starą sesję sprzed wdrożenia
         * mechanizmu unieważniania. Taką sesję zamykamy.
         */
        if (
            !is_int($sessionVersion)
            || $sessionVersion !== $databaseVersion
        ) {
            self::clearUserSession();

            return false;
        }

        self::storeDatabaseUser($user);

        return true;
    }

    private static function restoreLegacySession(): bool
    {
        $legacyLoggedIn =
            $_SESSION[self::LEGACY_SESSION_KEY]
            ?? false;

        if ($legacyLoggedIn !== true) {
            return false;
        }

        if (self::databaseUsersEnabled()) {
            $email = trim(
                (string) (
                    $_SESSION['admin_email']
                    ?? Env::get(
                        'ADMIN_EMAIL',
                        ''
                    )
                    ?? ''
                )
            );

            if ($email === '') {
                self::clearUserSession();

                return false;
            }

            try {
                $user = UserRepository::findByEmail(
                    $email
                );
            } catch (Throwable $exception) {
                error_log(
                    'Nie udało się przenieść starej sesji: '
                    . $exception->getMessage()
                );

                self::clearUserSession();

                return false;
            }

            if (
                $user === null
                || (int) ($user['is_active'] ?? 0) !== 1
            ) {
                self::clearUserSession();

                return false;
            }

            self::storeDatabaseUser($user);

            return true;
        }

        if (!self::legacyConfigured()) {
            self::clearUserSession();

            return false;
        }

        $_SESSION[self::USER_EMAIL_KEY] = trim(
            (string) (
                $_SESSION['admin_email']
                ?? Env::get(
                    'ADMIN_EMAIL',
                    ''
                )
                ?? ''
            )
        );

        $_SESSION[self::USER_NAME_KEY] =
            'Administrator';

        $_SESSION[self::USER_ROLE_KEY] =
            'ADMIN';

        return true;
    }

    /**
     * @param array<string, mixed> $user
     */
    private static function storeDatabaseUser(
        array $user
    ): void {
        $_SESSION[self::LEGACY_SESSION_KEY] = true;
        $_SESSION[self::USER_ID_KEY] =
            (int) ($user['id'] ?? 0);
        $_SESSION[self::USER_EMAIL_KEY] =
            (string) ($user['email'] ?? '');
        $_SESSION[self::USER_NAME_KEY] =
            (string) ($user['name'] ?? '');
        $_SESSION[self::USER_ROLE_KEY] =
            (string) ($user['role'] ?? '');
        $_SESSION[self::USER_SESSION_VERSION_KEY] =
            (int) ($user['session_version'] ?? 1);
        $_SESSION['admin_email'] =
            (string) ($user['email'] ?? '');
    }

    private static function clearUserSession(): void
    {
        unset(
            $_SESSION[self::LEGACY_SESSION_KEY],
            $_SESSION[self::USER_ID_KEY],
            $_SESSION[self::USER_EMAIL_KEY],
            $_SESSION[self::USER_NAME_KEY],
            $_SESSION[self::USER_ROLE_KEY],
            $_SESSION[
                self::USER_SESSION_VERSION_KEY
            ],
            $_SESSION['admin_email']
        );
    }

    private static function attemptLegacy(
        string $email,
        string $password
    ): bool {
        if (!self::legacyConfigured()) {
            return false;
        }

        $configuredEmail = trim(
            (string) (
                Env::get(
                    'ADMIN_EMAIL',
                    ''
                )
                ?? ''
            )
        );

        $emailMatches = hash_equals(
            strtolower($configuredEmail),
            $email
        );

        $passwordMatches =
            self::legacyPasswordMatches(
                $password
            );

        if (
            !$emailMatches
            || !$passwordMatches
        ) {
            self::recordFailedLogin();

            return false;
        }

        self::clearLoginAttempts();
        session_regenerate_id(true);

        $_SESSION[self::LEGACY_SESSION_KEY] = true;
        $_SESSION[self::USER_EMAIL_KEY] =
            $configuredEmail;
        $_SESSION[self::USER_NAME_KEY] =
            'Administrator';
        $_SESSION[self::USER_ROLE_KEY] =
            'ADMIN';
        $_SESSION['admin_email'] =
            $configuredEmail;

        return true;
    }

    private static function legacyConfigured(): bool
    {
        $email = trim(
            (string) (
                Env::get(
                    'ADMIN_EMAIL',
                    ''
                )
                ?? ''
            )
        );

        if (
            $email === ''
            || $email === 'admin@example.com'
        ) {
            return false;
        }

        return self::hasPasswordHash()
            || self::hasPlainPassword();
    }

    private static function legacyPasswordMatches(
        string $password
    ): bool {
        $passwordHash = trim(
            (string) (
                Env::get(
                    'ADMIN_PASSWORD_HASH',
                    ''
                )
                ?? ''
            )
        );

        if ($passwordHash !== '') {
            return password_verify(
                $password,
                $passwordHash
            );
        }

        $configuredPassword = (string) (
            Env::get(
                'ADMIN_PASSWORD',
                ''
            )
            ?? ''
        );

        return $configuredPassword !== ''
            && hash_equals(
                $configuredPassword,
                $password
            );
    }

    private static function hasPasswordHash(): bool
    {
        $passwordHash = trim(
            (string) (
                Env::get(
                    'ADMIN_PASSWORD_HASH',
                    ''
                )
                ?? ''
            )
        );

        if ($passwordHash === '') {
            return false;
        }

        $info = password_get_info(
            $passwordHash
        );

        return isset($info['algoName'])
            && is_string($info['algoName'])
            && $info['algoName'] !== 'unknown';
    }

    private static function hasPlainPassword(): bool
    {
        $password = trim(
            (string) (
                Env::get(
                    'ADMIN_PASSWORD',
                    ''
                )
                ?? ''
            )
        );

        return $password !== ''
            && $password
                !== 'CHANGE_ME_STRONG_PASSWORD';
    }

    private static function recordFailedLogin(): void
    {
        self::startSession();

        $attempts =
            $_SESSION[
                self::LOGIN_ATTEMPTS_KEY
            ]
            ?? 0;

        if (!is_int($attempts)) {
            $attempts = 0;
        }

        $attempts++;

        $_SESSION[
            self::LOGIN_ATTEMPTS_KEY
        ] = $attempts;

        if (
            $attempts
            >= self::MAX_LOGIN_ATTEMPTS
        ) {
            $_SESSION[
                self::LOGIN_BLOCKED_UNTIL_KEY
            ] = time()
                + self::LOGIN_BLOCK_SECONDS;
        }

        self::recordIpFailedLogin();
    }

    private static function clearLoginAttempts(): void
    {
        self::clearSessionLoginAttempts();
        self::clearIpLoginAttempts();
    }

    private static function clearSessionLoginAttempts(): void
    {
        unset(
            $_SESSION[
                self::LOGIN_ATTEMPTS_KEY
            ],
            $_SESSION[
                self::LOGIN_BLOCKED_UNTIL_KEY
            ]
        );
    }

    private static function recordIpFailedLogin(): void
    {
        $path = self::rateLimitFilePath();

        if ($path === null) {
            return;
        }

        self::withLockedRateLimitFile(
            $path,
            static function (
                array $state
            ): array {
                $now = time();
                $blockedUntil = (int) (
                    $state['blocked_until']
                    ?? 0
                );

                if ($blockedUntil <= $now) {
                    $attempts = (int) (
                        $state['attempts']
                        ?? 0
                    );

                    $attempts++;

                    $state = [
                        'attempts' => $attempts,
                        'blocked_until' => 0,
                        'updated_at' => $now,
                    ];

                    if (
                        $attempts
                        >= self::MAX_LOGIN_ATTEMPTS
                    ) {
                        $state[
                            'blocked_until'
                        ] = $now
                            + self::LOGIN_BLOCK_SECONDS;
                    }
                }

                return $state;
            }
        );
    }

    private static function ipBlockedUntil(): int
    {
        $path = self::rateLimitFilePath();

        if (
            $path === null
            || !is_file($path)
        ) {
            return 0;
        }

        $state = self::readRateLimitState(
            $path
        );

        $blockedUntil = (int) (
            $state['blocked_until']
            ?? 0
        );

        if ($blockedUntil <= time()) {
            if (
                $blockedUntil > 0
                || (int) (
                    $state['attempts']
                    ?? 0
                ) > 0
            ) {
                @unlink($path);
            }

            return 0;
        }

        return $blockedUntil;
    }

    private static function clearIpLoginAttempts(): void
    {
        $path = self::rateLimitFilePath();

        if (
            $path !== null
            && is_file($path)
        ) {
            @unlink($path);
        }
    }

    private static function rateLimitFilePath(): ?string
    {
        $directory = dirname(
            __DIR__,
            2
        )
            . '/storage/security/login-rate-limit';

        if (
            !is_dir($directory)
            && !@mkdir(
                $directory,
                0770,
                true
            )
            && !is_dir($directory)
        ) {
            return null;
        }

        $ip = self::clientIp();

        return $directory
            . '/'
            . hash(
                'sha256',
                $ip
            )
            . '.json';
    }

    private static function readRateLimitState(
        string $path
    ): array {
        $content = @file_get_contents(
            $path
        );

        if (
            !is_string($content)
            || trim($content) === ''
        ) {
            return [];
        }

        $decoded = json_decode(
            $content,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private static function withLockedRateLimitFile(
        string $path,
        callable $callback
    ): void {
        $handle = @fopen(
            $path,
            'c+'
        );

        if ($handle === false) {
            return;
        }

        try {
            if (
                !flock(
                    $handle,
                    LOCK_EX
                )
            ) {
                return;
            }

            rewind($handle);
            $content = stream_get_contents(
                $handle
            );

            $state = [];

            if (
                is_string($content)
                && trim($content) !== ''
            ) {
                $decoded = json_decode(
                    $content,
                    true
                );

                if (is_array($decoded)) {
                    $state = $decoded;
                }
            }

            $newState = $callback(
                $state
            );

            if (!is_array($newState)) {
                $newState = [];
            }

            $encoded = json_encode(
                $newState,
                JSON_UNESCAPED_SLASHES
            );

            if ($encoded === false) {
                return;
            }

            rewind($handle);
            ftruncate(
                $handle,
                0
            );
            fwrite(
                $handle,
                $encoded
            );
            fflush($handle);
        } finally {
            @flock(
                $handle,
                LOCK_UN
            );
            fclose($handle);
        }
    }

    private static function clientIp(): string
    {
        $remoteAddress =
            $_SERVER[
                'REMOTE_ADDR'
            ]
            ?? '';

        if (
            is_string($remoteAddress)
            && trim($remoteAddress) !== ''
        ) {
            return trim(
                $remoteAddress
            );
        }

        return 'unknown';
    }

    private static function isHttps(): bool
    {
        $https =
            $_SERVER['HTTPS']
            ?? '';

        if (
            is_string($https)
            && strtolower($https)
                === 'on'
        ) {
            return true;
        }

        $forwardedProto =
            $_SERVER[
                'HTTP_X_FORWARDED_PROTO'
            ]
            ?? '';

        return is_string(
            $forwardedProto
        )
            && strtolower(
                $forwardedProto
            ) === 'https';
    }
}
