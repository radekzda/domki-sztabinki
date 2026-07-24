<?php

declare(strict_types=1);

final class PasswordResetService
{
    private const MAX_REQUESTS_PER_HOUR = 5;
    private const MIN_SECONDS_BETWEEN_REQUESTS = 60;

    public static function request(
        string $email
    ): void {
        $normalizedEmail = strtolower(
            trim($email)
        );

        /*
         * Odpowiedź HTTP pozostaje zawsze taka sama.
         * Nie ujawniamy, czy konto istnieje.
         */
        if (
            filter_var(
                $normalizedEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
            || !Database::canAttemptConnection()
            || !PasswordResetRepository::tableExists()
            || self::isIpRateLimited()
        ) {
            return;
        }

        try {
            PasswordResetRepository::deleteExpired();

            $user = UserRepository::findByEmail(
                $normalizedEmail
            );

            if (
                $user === null
                || (int) ($user['is_active'] ?? 0) !== 1
            ) {
                return;
            }

            $userId = (int) ($user['id'] ?? 0);

            if ($userId < 1) {
                return;
            }

            if (!self::canCreateForUser($userId)) {
                return;
            }

            $plainToken = bin2hex(
                random_bytes(32)
            );

            $tokenHash = hash(
                'sha256',
                $plainToken
            );

            $expiresAt = new DateTimeImmutable(
                '+'
                . self::ttlMinutes()
                . ' minutes'
            );

            PasswordResetRepository::invalidateForUser(
                $userId
            );

            PasswordResetRepository::create(
                $userId,
                $tokenHash,
                $expiresAt,
                self::requestIpHash()
            );

            $resetUrl = self::baseUrl()
                . '/odzyskaj-haslo?token='
                . rawurlencode($plainToken);

            $subject =
                'Odzyskiwanie hasła — Domki Sztabinki';

            $body = self::messageBody(
                (string) ($user['name'] ?? ''),
                $resetUrl,
                $expiresAt
            );

            if (
                !Mailer::sendSafely(
                    (string) ($user['email'] ?? ''),
                    $subject,
                    $body
                )
            ) {
                PasswordResetRepository::invalidateForUser(
                    $userId
                );

                error_log(
                    'Nie udało się wysłać wiadomości '
                    . 'odzyskiwania hasła dla użytkownika #'
                    . $userId
                    . '.'
                );
            }
        } catch (Throwable $exception) {
            error_log(
                'Password reset request error: '
                . $exception::class
                . ': '
                . $exception->getMessage()
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function validToken(
        string $plainToken
    ): ?array {
        $plainToken = trim(
            $plainToken
        );

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $plainToken
            ) !== 1
            || !Database::canAttemptConnection()
            || !PasswordResetRepository::tableExists()
        ) {
            return null;
        }

        return PasswordResetRepository::findValidByHash(
            hash(
                'sha256',
                $plainToken
            )
        );
    }

    public static function resetPassword(
        string $plainToken,
        string $password
    ): bool {
        if (self::validToken($plainToken) === null) {
            return false;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Nie udało się bezpiecznie zapisać nowego hasła.'
            );
        }

        return PasswordResetRepository::consumeAndChangePassword(
            hash(
                'sha256',
                $plainToken
            ),
            $passwordHash
        );
    }

    public static function ttlMinutes(): int
    {
        $configured = (int) (
            Env::get(
                'PASSWORD_RESET_TTL_MINUTES',
                '30'
            )
            ?? '30'
        );

        return max(
            10,
            min(
                120,
                $configured
            )
        );
    }

    private static function canCreateForUser(
        int $userId
    ): bool {
        $now = new DateTimeImmutable('now');

        $latest =
            PasswordResetRepository::latestForUser(
                $userId
            );

        if (is_array($latest)) {
            try {
                $latestAt = new DateTimeImmutable(
                    (string) (
                        $latest['created_at']
                        ?? ''
                    )
                );

                if (
                    $latestAt
                    > $now->modify(
                        '-'
                        . self::MIN_SECONDS_BETWEEN_REQUESTS
                        . ' seconds'
                    )
                ) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        return PasswordResetRepository::countRecentForUser(
            $userId,
            $now->modify('-1 hour')
        ) < self::MAX_REQUESTS_PER_HOUR;
    }

    private static function baseUrl(): string
    {
        $configured = rtrim(
            trim(
                (string) (
                    Env::get(
                        'APP_URL',
                        ''
                    )
                    ?? ''
                )
            ),
            '/'
        );

        if (
            $configured !== ''
            && filter_var(
                $configured,
                FILTER_VALIDATE_URL
            ) !== false
            && in_array(
                strtolower(
                    (string) parse_url(
                        $configured,
                        PHP_URL_SCHEME
                    )
                ),
                [
                    'http',
                    'https',
                ],
                true
            )
        ) {
            return $configured;
        }

        $isHttps =
            isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== ''
            && $_SERVER['HTTPS'] !== 'off';

        $host = trim(
            (string) (
                $_SERVER['HTTP_HOST']
                ?? 'localhost'
            )
        );

        $host = preg_replace(
            '/[^a-zA-Z0-9.\-:\[\]]/',
            '',
            $host
        ) ?: 'localhost';

        return (
            $isHttps
                ? 'https://'
                : 'http://'
        )
            . $host;
    }

    private static function messageBody(
        string $name,
        string $resetUrl,
        DateTimeImmutable $expiresAt
    ): string {
        $greeting = trim($name) !== ''
            ? 'Dzień dobry '
                . trim($name)
                . ','
            : 'Dzień dobry,';

        return $greeting
            . PHP_EOL
            . PHP_EOL
            . 'Otrzymaliśmy prośbę o ustawienie nowego hasła '
            . 'do panelu Domki Sztabinki.'
            . PHP_EOL
            . PHP_EOL
            . 'Aby ustawić nowe hasło, otwórz poniższy link:'
            . PHP_EOL
            . $resetUrl
            . PHP_EOL
            . PHP_EOL
            . 'Link jest jednorazowy i wygasa '
            . $expiresAt->format('d.m.Y o H:i')
            . '.'
            . PHP_EOL
            . PHP_EOL
            . 'Jeżeli nie proszono o zmianę hasła, '
            . 'zignoruj tę wiadomość. Dotychczasowe hasło '
            . 'pozostanie aktywne.'
            . PHP_EOL
            . PHP_EOL
            . 'Domki Sztabinki';
    }

    private static function isIpRateLimited(): bool
    {
        $path = self::rateLimitFilePath();

        if ($path === null) {
            return false;
        }

        $handle = @fopen(
            $path,
            'c+'
        );

        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
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

            $now = time();
            $windowStartedAt = (int) (
                $state['window_started_at']
                ?? 0
            );
            $attempts = (int) (
                $state['attempts']
                ?? 0
            );

            if (
                $windowStartedAt <= 0
                || $windowStartedAt
                    < $now - 3600
            ) {
                $windowStartedAt = $now;
                $attempts = 0;
            }

            $limited =
                $attempts
                >= self::MAX_REQUESTS_PER_HOUR;

            if (!$limited) {
                $attempts++;
            }

            $newState = json_encode(
                [
                    'window_started_at' =>
                        $windowStartedAt,
                    'attempts' => $attempts,
                    'updated_at' => $now,
                ],
                JSON_UNESCAPED_SLASHES
            );

            if (is_string($newState)) {
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, $newState);
                fflush($handle);
            }

            return $limited;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function rateLimitFilePath(): ?string
    {
        $directory = dirname(
            __DIR__,
            2
        )
            . '/storage/security/password-reset-rate-limit';

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

        return $directory
            . '/'
            . self::requestIpHash()
            . '.json';
    }

    private static function requestIpHash(): string
    {
        $ip = trim(
            (string) (
                $_SERVER['REMOTE_ADDR']
                ?? 'unknown'
            )
        );

        return hash(
            'sha256',
            $ip
        );
    }
}
