<?php

declare(strict_types=1);

final class PublicFormGuard
{
    private const SESSION_KEY = 'public_inquiry_submission_times';
    private const WINDOW_SECONDS = 300;
    private const MAX_SUBMISSIONS = 3;

    public static function validate(array $post): void
    {
        self::validateHoneypot($post);
        self::validateRateLimit();
    }

    private static function validateHoneypot(array $post): void
    {
        $website = trim((string) ($post['website'] ?? ''));

        if ($website !== '') {
            self::reject(
                422,
                'Nie udało się wysłać formularza',
                'Formularz został odrzucony. Odśwież stronę i spróbuj ponownie.'
            );
        }
    }

    private static function validateRateLimit(): void
    {
        $now = time();
        $timestamps = $_SESSION[self::SESSION_KEY] ?? [];

        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $timestamps = array_values(
            array_filter(
                $timestamps,
                static fn (mixed $timestamp): bool =>
                    is_int($timestamp)
                    && $timestamp > ($now - self::WINDOW_SECONDS)
            )
        );

        if (count($timestamps) >= self::MAX_SUBMISSIONS) {
            self::reject(
                429,
                'Zbyt wiele zapytań',
                'Wysłano kilka zapytań w krótkim czasie. Odczekaj kilka minut i spróbuj ponownie.'
            );
        }

        $timestamps[] = $now;
        $_SESSION[self::SESSION_KEY] = $timestamps;
    }

    private static function reject(
        int $statusCode,
        string $title,
        string $message
    ): never {
        http_response_code($statusCode);

        echo '<!DOCTYPE html>';
        echo '<html lang="pl">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '</head>';
        echo '<body>';
        echo '<main style="max-width:720px;margin:80px auto;padding:24px;font-family:Arial,sans-serif;">';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><a href="/">Wróć na stronę główną</a></p>';
        echo '</main>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}
