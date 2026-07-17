<?php

declare(strict_types=1);

final class InquiryMailer
{
    /**
     * @param array<string, string> $form
     * @param array<string, mixed>|null $selectedCabin
     * @param array<string, string> $settings
     */
    public static function sendAdminNotification(
        int $inquiryId,
        array $form,
        ?array $selectedCabin,
        array $settings
    ): bool {
        $recipient = self::adminRecipient($settings);

        if ($recipient === null) {
            return false;
        }

        $propertyName = trim(
            (string) ($settings['property_name'] ?? 'Domki Sztabinki')
        );

        if ($propertyName === '') {
            $propertyName = 'Domki Sztabinki';
        }

        $guestName = trim(
            ($form['first_name'] ?? '')
            . ' '
            . ($form['last_name'] ?? '')
        );

        if ($guestName === '') {
            $guestName = 'Gość';
        }

        $dateFrom = trim($form['date_from'] ?? '');
        $dateTo = trim($form['date_to'] ?? '');

        $subject = sprintf(
            'Nowe zapytanie WWW #%d - %s',
            $inquiryId,
            $guestName
        );

        $cabinName = self::cabinName($selectedCabin);

        $bodyLines = [
            'Nowe zapytanie ze strony internetowej.',
            '',
            'Numer zapytania: #' . $inquiryId,
            'Obiekt: ' . $propertyName,
            '',
            'DANE GOŚCIA',
            'Imię i nazwisko: ' . $guestName,
            'Telefon: ' . self::valueOrDash($form['phone'] ?? ''),
            'E-mail: ' . self::valueOrDash($form['email'] ?? ''),
            'Miejscowość: ' . self::valueOrDash($form['city'] ?? ''),
            'Kraj: ' . self::valueOrDash($form['country'] ?? ''),
            '',
            'POBYT',
            'Domek: ' . $cabinName,
            'Przyjazd: ' . self::valueOrDash($dateFrom),
            'Wyjazd: ' . self::valueOrDash($dateTo),
            'Dorośli: ' . self::valueOrDash($form['adults'] ?? ''),
            'Dzieci: ' . self::valueOrDash($form['children'] ?? ''),
            '',
            'WIADOMOŚĆ',
            self::valueOrDash($form['notes'] ?? ''),
            '',
            'Zapytanie zostało zapisane w panelu administratora.',
        ];

        $guestEmail = trim($form['email'] ?? '');

        $replyTo = filter_var(
            $guestEmail,
            FILTER_VALIDATE_EMAIL
        ) !== false
            ? $guestEmail
            : null;

        return Mailer::sendSafely(
            $recipient,
            $subject,
            implode("\n", $bodyLines),
            $replyTo
        );
    }

    /**
     * @param array<string, string> $settings
     */
    private static function adminRecipient(array $settings): ?string
    {
        $contactEmail = trim(
            (string) ($settings['contact_email'] ?? '')
        );

        if (
            filter_var(
                $contactEmail,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $contactEmail;
        }

        $adminEmail = trim(
            (string) Env::get('ADMIN_EMAIL', '')
        );

        if (
            filter_var(
                $adminEmail,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $adminEmail;
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $selectedCabin
     */
    private static function cabinName(?array $selectedCabin): string
    {
        if ($selectedCabin === null) {
            return 'Bez wskazania konkretnego domku';
        }

        $name = trim(
            (string) ($selectedCabin['name'] ?? '')
        );

        if ($name === '') {
            return 'Bez wskazania konkretnego domku';
        }

        return $name;
    }

    private static function valueOrDash(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? $value : '-';
    }
}
