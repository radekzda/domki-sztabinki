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

        $configuredEmail = Env::get('ADMIN_EMAIL', '');
        $configuredPassword = Env::get('ADMIN_PASSWORD', '');

        if ($configuredEmail === null || $configuredPassword === null) {
            return false;
        }

        $emailMatches = hash_equals($configuredEmail, $email);
        $passwordMatches = hash_equals($configuredPassword, $password);

        if (!$emailMatches || !$passwordMatches) {
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

        $blockedUntil = $_SESSION[self::LOGIN_BLOCKED_UNTIL_KEY] ?? null;

        if (!is_int($blockedUntil)) {
            return false;
        }

        if ($blockedUntil <= time()) {
            self::clearLoginAttempts();

            return false;
        }

        return true;
    }

    public static function loginBlockedSecondsRemaining(): int
    {
        self::startSession();

        $blockedUntil = $_SESSION[self::LOGIN_BLOCKED_UNTIL_KEY] ?? null;

        if (!is_int($blockedUntil)) {
            return 0;
        }

        return max(0, $blockedUntil - time());
    }

    public static function logout(): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();

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

        $email = $_SESSION['admin_email'] ?? '';

        if (is_string($email)) {
            return $email;
        }

        return '';
    }

    public static function isConfigured(): bool
    {
        $email = Env::get('ADMIN_EMAIL', '');
        $password = Env::get('ADMIN_PASSWORD', '');

        if ($email === null || $password === null) {
            return false;
        }

        $email = trim($email);
        $password = trim($password);

        if ($email === '' || $password === '') {
            return false;
        }

        if (
            $email === 'admin@example.com'
            || $password === 'CHANGE_ME_STRONG_PASSWORD'
        ) {
            return false;
        }

        return true;
    }

    private static function recordFailedLogin(): void
    {
        self::startSession();

        $attempts = $_SESSION[self::LOGIN_ATTEMPTS_KEY] ?? 0;

        if (!is_int($attempts)) {
            $attempts = 0;
        }

        $attempts++;

        $_SESSION[self::LOGIN_ATTEMPTS_KEY] = $attempts;

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $_SESSION[self::LOGIN_BLOCKED_UNTIL_KEY] =
                time() + self::LOGIN_BLOCK_SECONDS;
        }
    }

    private static function clearLoginAttempts(): void
    {
        unset(
            $_SESSION[self::LOGIN_ATTEMPTS_KEY],
            $_SESSION[self::LOGIN_BLOCKED_UNTIL_KEY]
        );
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        if (is_string($https) && strtolower($https) === 'on') {
            return true;
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        return is_string($forwardedProto)
            && strtolower($forwardedProto) === 'https';
    }
}
