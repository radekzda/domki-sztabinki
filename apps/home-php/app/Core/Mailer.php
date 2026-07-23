<?php

declare(strict_types=1);

final class Mailer
{
    public static function isEnabled(): bool
    {
        return Env::bool(
            'MAIL_ENABLED',
            false
        );
    }

    public static function send(
        string $to,
        string $subject,
        string $body,
        ?string $replyTo = null
    ): bool {
        if (!self::isEnabled()) {
            return false;
        }

        $to = trim(
            $to
        );

        if (
            filter_var(
                $to,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return false;
        }

        $fromEmail = trim(
            (string) Env::get(
                'MAIL_FROM_EMAIL',
                ''
            )
        );

        $fromName = trim(
            (string) Env::get(
                'MAIL_FROM_NAME',
                'Domki Sztabinki'
            )
        );

        if (
            filter_var(
                $fromEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return false;
        }

        if (
            $replyTo !== null
            && filter_var(
                trim(
                    $replyTo
                ),
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $replyTo = null;
        }

        $smtpHost = trim(
            (string) Env::get(
                'SMTP_HOST',
                ''
            )
        );

        if ($smtpHost !== '') {
            return self::sendViaSmtp(
                $to,
                $subject,
                $body,
                $fromEmail,
                $fromName,
                $replyTo
            );
        }

        return self::sendViaPhpMail(
            $to,
            $subject,
            $body,
            $fromEmail,
            $fromName,
            $replyTo
        );
    }

    public static function sendSafely(
        string $to,
        string $subject,
        string $body,
        ?string $replyTo = null
    ): bool {
        try {
            return self::send(
                $to,
                $subject,
                $body,
                $replyTo
            );
        } catch (Throwable $exception) {
            error_log(
                'Mailer error: '
                . $exception::class
                . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    private static function sendViaPhpMail(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        ?string $replyTo
    ): bool {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: '
                . self::formatAddress(
                    $fromEmail,
                    $fromName
                ),
        ];

        if ($replyTo !== null) {
            $headers[] =
                'Reply-To: '
                . trim(
                    $replyTo
                );
        }

        $safeSubject =
            self::encodeMimeHeader(
                $subject
            );

        $normalizedBody =
            self::normalizeBody(
                $body,
                "\n"
            );

        return mail(
            $to,
            $safeSubject,
            $normalizedBody,
            implode(
                "\r\n",
                $headers
            )
        );
    }

    private static function sendViaSmtp(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        ?string $replyTo
    ): bool {
        $host = trim(
            (string) Env::get(
                'SMTP_HOST',
                ''
            )
        );

        $port = (int) (
            Env::get(
                'SMTP_PORT',
                '465'
            )
            ?? '465'
        );

        $encryption = strtolower(
            trim(
                (string) Env::get(
                    'SMTP_ENCRYPTION',
                    'ssl'
                )
            )
        );

        $username = trim(
            (string) Env::get(
                'SMTP_USERNAME',
                ''
            )
        );

        $password = str_replace(
            ' ',
            '',
            trim(
                (string) Env::get(
                    'SMTP_PASSWORD',
                    ''
                )
            )
        );

        if ($host === '') {
            throw new RuntimeException(
                'Brak SMTP_HOST.'
            );
        }

        if (
            $port < 1
            || $port > 65535
        ) {
            throw new RuntimeException(
                'Nieprawidłowy SMTP_PORT.'
            );
        }

        if ($username === '') {
            throw new RuntimeException(
                'Brak SMTP_USERNAME.'
            );
        }

        if ($password === '') {
            throw new RuntimeException(
                'Brak SMTP_PASSWORD. '
                . 'Dla konta Gmail użyj hasła aplikacji Google, '
                . 'nie zwykłego hasła do konta.'
            );
        }

        if (
            !in_array(
                $encryption,
                [
                    'ssl',
                    'tls',
                    'none',
                    '',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Nieprawidłowe SMTP_ENCRYPTION.'
            );
        }

        if (
            $encryption !== 'none'
            && $encryption !== ''
            && !extension_loaded(
                'openssl'
            )
        ) {
            throw new RuntimeException(
                'Rozszerzenie PHP OpenSSL jest wymagane do SMTP SSL/TLS.'
            );
        }

        $remote = match ($encryption) {
            'ssl' =>
                'ssl://'
                . $host
                . ':'
                . $port,

            default =>
                'tcp://'
                . $host
                . ':'
                . $port,
        };

        $errorNumber = 0;
        $errorMessage = '';

        $stream = @stream_socket_client(
            $remote,
            $errorNumber,
            $errorMessage,
            20,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($stream)) {
            throw new RuntimeException(
                'Nie udało się połączyć z serwerem SMTP '
                . $host
                . ':'
                . $port
                . '. '
                . $errorMessage
                . ' ('
                . $errorNumber
                . ').'
            );
        }

        stream_set_timeout(
            $stream,
            20
        );

        try {
            self::smtpExpect(
                $stream,
                [
                    220,
                ],
                'połączenie'
            );

            $clientName =
                gethostname();

            if (
                !is_string(
                    $clientName
                )
                || trim(
                    $clientName
                ) === ''
            ) {
                $clientName =
                    'localhost';
            }

            self::smtpCommand(
                $stream,
                'EHLO '
                    . $clientName,
                [
                    250,
                ],
                'EHLO'
            );

            if ($encryption === 'tls') {
                self::smtpCommand(
                    $stream,
                    'STARTTLS',
                    [
                        220,
                    ],
                    'STARTTLS'
                );

                $cryptoEnabled =
                    @stream_socket_enable_crypto(
                        $stream,
                        true,
                        STREAM_CRYPTO_METHOD_TLS_CLIENT
                    );

                if ($cryptoEnabled !== true) {
                    throw new RuntimeException(
                        'Nie udało się uruchomić szyfrowania TLS dla SMTP.'
                    );
                }

                self::smtpCommand(
                    $stream,
                    'EHLO '
                        . $clientName,
                    [
                        250,
                    ],
                    'EHLO po STARTTLS'
                );
            }

            self::smtpCommand(
                $stream,
                'AUTH LOGIN',
                [
                    334,
                ],
                'AUTH LOGIN'
            );

            self::smtpCommand(
                $stream,
                base64_encode(
                    $username
                ),
                [
                    334,
                ],
                'SMTP_USERNAME'
            );

            self::smtpCommand(
                $stream,
                base64_encode(
                    $password
                ),
                [
                    235,
                ],
                'SMTP_PASSWORD'
            );

            self::smtpCommand(
                $stream,
                'MAIL FROM:<'
                    . $fromEmail
                    . '>',
                [
                    250,
                ],
                'MAIL FROM'
            );

            self::smtpCommand(
                $stream,
                'RCPT TO:<'
                    . $to
                    . '>',
                [
                    250,
                    251,
                ],
                'RCPT TO'
            );

            self::smtpCommand(
                $stream,
                'DATA',
                [
                    354,
                ],
                'DATA'
            );

            $message =
                self::buildSmtpMessage(
                    $to,
                    $subject,
                    $body,
                    $fromEmail,
                    $fromName,
                    $replyTo
                );

            if (
                fwrite(
                    $stream,
                    $message
                    . "\r\n.\r\n"
                ) === false
            ) {
                throw new RuntimeException(
                    'Nie udało się wysłać treści wiadomości do serwera SMTP.'
                );
            }

            self::smtpExpect(
                $stream,
                [
                    250,
                ],
                'wysyłka wiadomości'
            );

            self::smtpCommand(
                $stream,
                'QUIT',
                [
                    221,
                ],
                'QUIT'
            );

            return true;
        } finally {
            fclose(
                $stream
            );
        }
    }

    /**
     * @param resource $stream
     * @param array<int, int> $expectedCodes
     */
    private static function smtpCommand(
        $stream,
        string $command,
        array $expectedCodes,
        string $context
    ): string {
        if (
            fwrite(
                $stream,
                $command
                . "\r\n"
            ) === false
        ) {
            throw new RuntimeException(
                'Nie udało się wysłać komendy SMTP: '
                . $context
                . '.'
            );
        }

        return self::smtpExpect(
            $stream,
            $expectedCodes,
            $context
        );
    }

    /**
     * @param resource $stream
     * @param array<int, int> $expectedCodes
     */
    private static function smtpExpect(
        $stream,
        array $expectedCodes,
        string $context
    ): string {
        $response = '';

        while (
            (
                $line = fgets(
                    $stream,
                    515
                )
            ) !== false
        ) {
            $response .=
                $line;

            if (
                strlen(
                    $line
                ) >= 4
                && $line[3] === '-'
            ) {
                continue;
            }

            break;
        }

        if ($response === '') {
            throw new RuntimeException(
                'Brak odpowiedzi serwera SMTP podczas: '
                . $context
                . '.'
            );
        }

        $code = (int) substr(
            $response,
            0,
            3
        );

        if (
            !in_array(
                $code,
                $expectedCodes,
                true
            )
        ) {
            throw new RuntimeException(
                'Błąd SMTP podczas '
                . $context
                . ': '
                . trim(
                    $response
                )
            );
        }

        return $response;
    }

    private static function buildSmtpMessage(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        ?string $replyTo
    ): string {
        $headers = [
            'Date: '
                . date(
                    DATE_RFC2822
                ),
            'From: '
                . self::formatAddress(
                    $fromEmail,
                    $fromName
                ),
            'To: '
                . $to,
            'Subject: '
                . self::encodeMimeHeader(
                    $subject
                ),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        if ($replyTo !== null) {
            $headers[] =
                'Reply-To: '
                . trim(
                    $replyTo
                );
        }

        $normalizedBody =
            self::normalizeBody(
                $body,
                "\r\n"
            );

        $normalizedBody =
            preg_replace(
                '/^\./m',
                '..',
                $normalizedBody
            )
            ?? $normalizedBody;

        return implode(
            "\r\n",
            $headers
        )
            . "\r\n\r\n"
            . $normalizedBody;
    }

    private static function encodeMimeHeader(
        string $value
    ): string {
        $safeValue = str_replace(
            [
                "\r",
                "\n",
            ],
            ' ',
            trim(
                $value
            )
        );

        if (
            function_exists(
                'mb_encode_mimeheader'
            )
        ) {
            return mb_encode_mimeheader(
                $safeValue,
                'UTF-8',
                'B',
                "\r\n"
            );
        }

        return $safeValue;
    }

    private static function normalizeBody(
        string $body,
        string $lineEnding
    ): string {
        $normalized = str_replace(
            [
                "\r\n",
                "\r",
            ],
            "\n",
            $body
        );

        return str_replace(
            "\n",
            $lineEnding,
            $normalized
        );
    }

    private static function formatAddress(
        string $email,
        string $name
    ): string {
        $safeName = str_replace(
            [
                "\r",
                "\n",
                '"',
            ],
            [
                '',
                '',
                "'",
            ],
            trim(
                $name
            )
        );

        if ($safeName === '') {
            return $email;
        }

        if (
            function_exists(
                'mb_encode_mimeheader'
            )
        ) {
            $safeName =
                mb_encode_mimeheader(
                    $safeName,
                    'UTF-8',
                    'B',
                    "\r\n"
                );
        }

        return $safeName
            . ' <'
            . $email
            . '>';
    }
}
