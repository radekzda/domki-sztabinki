<?php

declare(strict_types=1);

final class Auth
{
    private const SESSION_KEY = 'domki_sztabinki_admin_logged_in';
    private const LOGIN_ATTEMPTS_KEY = 'domki_sztabinki_login_attempts';
    private const LOGIN_BLOCKED_UNTIL_KEY = 'domki_sztabinki_login_blocked_until';

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

        return isset($_SESSION[self::SESSION_KEY])
            && $_SESSION[self::SESSION_KEY] === true;
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            Response::redirect('/logowanie');
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        self::startSession();

        if (self::isLoginBlocked()) {
            return false;
        }

        if (!self::isConfigured()) {
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
            strtolower(trim($email))
        );

        $passwordMatches = self::passwordMatches(
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

        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION['admin_email'] = $configuredEmail;

        return true;
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
            $_SESSION['admin_email']
            ?? '';

        if (is_string($email)) {
            return $email;
        }

        return '';
    }

    public static function isConfigured(): bool
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

    public static function passwordMode(): string
    {
        if (self::hasPasswordHash()) {
            return 'hash';
        }

        if (self::hasPlainPassword()) {
            return 'plain';
        }

        return 'missing';
    }

    private static function passwordMatches(
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
