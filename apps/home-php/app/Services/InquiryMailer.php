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
     * @param array<string, string> $form
     * @param array<string, mixed>|null $selectedCabin
     * @param array<string, string> $settings
     */
    public static function sendGuestConfirmation(
        int $inquiryId,
        array $form,
        ?array $selectedCabin,
        array $settings
    ): bool {
        $guestEmail = trim($form['email'] ?? '');

        if (
            filter_var(
                $guestEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return false;
        }

        $propertyName = trim(
            (string) ($settings['property_name'] ?? 'Domki Sztabinki')
        );

        if ($propertyName === '') {
            $propertyName = 'Domki Sztabinki';
        }

        $firstName = trim($form['first_name'] ?? '');
        $lastName = trim($form['last_name'] ?? '');

        $adults = max(
            0,
            (int) ($form['adults'] ?? 0)
        );

        $children = max(
            0,
            (int) ($form['children'] ?? 0)
        );

        $cabinName = self::cabinName($selectedCabin);

        $subject = 'Potwierdzenie otrzymania zapytania - ' . $propertyName;

        $templateContent =
            self::defaultGuestConfirmationTemplate();

        try {
            MessageTemplateRepository::ensureDefaultTemplates();

            $messageTemplate =
                MessageTemplateRepository::findByKey(
                    'INQUIRY_RECEIVED_CONFIRMATION'
                );

            if (
                is_array($messageTemplate)
                && !(
                    $messageTemplate['is_active']
                    ?? false
                )
            ) {
                return false;
            }

            $storedContent = is_array($messageTemplate)
                ? trim(
                    (string) (
                        $messageTemplate['content']
                        ?? ''
                    )
                )
                : '';

            if ($storedContent !== '') {
                $templateContent = $storedContent;
            }
        } catch (Throwable $exception) {
            error_log(
                'Nie udało się pobrać szablonu '
                . 'potwierdzenia zapytania: '
                . $exception::class
                . ': '
                . $exception->getMessage()
            );
        }

        $body = MessageTemplateRenderer::forInquiry(
            $templateContent,
            [
                'id' => $inquiryId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim(
                    $firstName . ' ' . $lastName
                ),
                'email' => $guestEmail,
                'phone' => trim(
                    $form['phone'] ?? ''
                ),
                'date_from' => trim(
                    $form['date_from'] ?? ''
                ),
                'date_to' => trim(
                    $form['date_to'] ?? ''
                ),
                'adults' => $adults,
                'children' => $children,
                'guests' => max(
                    1,
                    $adults + $children
                ),
                'cabin_name' => $cabinName,
            ],
            $settings
        );

        $contactEmail = trim(
            (string) ($settings['contact_email'] ?? '')
        );

        $replyTo = filter_var(
            $contactEmail,
            FILTER_VALIDATE_EMAIL
        ) !== false
            ? $contactEmail
            : null;

        return Mailer::sendSafely(
            $guestEmail,
            $subject,
            $body,
            $replyTo
        );
    }

    private static function defaultGuestConfirmationTemplate(): string
    {
        return <<<'TEXT'
{{greeting}}

dziękujemy za przesłanie zapytania.
Otrzymaliśmy je poprawnie i odpowiemy po sprawdzeniu dostępności oraz ceny pobytu.

SZCZEGÓŁY ZAPYTANIA
Numer zapytania: #{{inquiry_id}}
Domek: {{cabin_name}}
Przyjazd: {{start_date}}
Wyjazd: {{end_date}}
Dorośli: {{adults}}
Dzieci: {{children}}

To jest automatyczne potwierdzenie otrzymania zapytania.
Samo wysłanie zapytania nie oznacza jeszcze potwierdzenia rezerwacji.

Pozdrawiamy serdecznie
{{property_name}}
TEXT;
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
