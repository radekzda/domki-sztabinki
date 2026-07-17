<?php

declare(strict_types=1);

final class Mailer
{
    public static function isEnabled(): bool
    {
        return Env::bool('MAIL_ENABLED', false);
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

        $to = trim($to);

        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $fromEmail = trim((string) Env::get('MAIL_FROM_EMAIL', ''));
        $fromName = trim((string) Env::get('MAIL_FROM_NAME', 'Domki Sztabinki'));

        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        if (
            $replyTo !== null
            && filter_var(trim($replyTo), FILTER_VALIDATE_EMAIL) === false
        ) {
            $replyTo = null;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . self::formatAddress($fromEmail, $fromName),
        ];

        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . trim($replyTo);
        }

        $safeSubject = str_replace(["\r", "\n"], ' ', trim($subject));
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);

        if (function_exists('mb_encode_mimeheader')) {
            $safeSubject = mb_encode_mimeheader(
                $safeSubject,
                'UTF-8',
                'B',
                "\r\n"
            );
        }

        return mail(
            $to,
            $safeSubject,
            $normalizedBody,
            implode("\r\n", $headers)
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

    private static function formatAddress(
        string $email,
        string $name
    ): string {
        $safeName = str_replace(
            ["\r", "\n", '"'],
            ['', '', "'"],
            trim($name)
        );

        if ($safeName === '') {
            return $email;
        }

        if (function_exists('mb_encode_mimeheader')) {
            $safeName = mb_encode_mimeheader(
                $safeName,
                'UTF-8',
                'B',
                "\r\n"
            );
        }

        return $safeName . ' <' . $email . '>';
    }
}
