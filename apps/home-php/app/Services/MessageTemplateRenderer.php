<?php

declare(strict_types=1);

final class MessageTemplateRenderer
{
    /**
     * @param array<string, mixed> $reservation
     * @param array<string, string> $settings
     */
    public static function forReservation(
        string $template,
        array $reservation,
        array $settings
    ): string {
        $guestName = trim(
            (string) ($reservation['guest_name'] ?? '')
        );

        $cabinName = trim(
            (string) ($reservation['cabin_name'] ?? '')
        );

        $startDate = self::formatDate(
            (string) ($reservation['start_date'] ?? '')
        );

        $endDate = self::formatDate(
            (string) ($reservation['end_date'] ?? '')
        );

        $nights = max(
            0,
            (int) ($reservation['nights'] ?? 0)
        );

        $guests = max(
            1,
            (int) ($reservation['guests'] ?? 1)
        );

        $totalPrice = is_numeric(
            $reservation['total_price'] ?? null
        )
            ? (float) $reservation['total_price']
            : 0;

        $depositAmount = is_numeric(
            $settings['deposit_amount'] ?? null
        )
            ? (float) $settings['deposit_amount']
            : 0;

        $reservationId = (int) (
            $reservation['id'] ?? 0
        );

        $paymentTitle = 'Zadatek za rezerwację #'
            . $reservationId;

        if ($guestName !== '') {
            $paymentTitle .= ' - ' . $guestName;
        }

        $location = self::buildLocation(
            $settings
        );

        return self::replaceVariables(
            $template,
            [
                'guest_name' => $guestName,
                'first_name' => self::firstName(
                    $guestName
                ),
                'cabin_name' => $cabinName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'nights' => (string) $nights,
                'night_label' => self::nightLabel(
                    $nights
                ),
                'guests' => (string) $guests,
                'person_label' => self::personLabel(
                    $guests
                ),
                'total_price' => self::formatMoney(
                    $totalPrice
                ),
                'deposit_amount' => self::formatMoney(
                    $depositAmount
                ),
                'bank_account_holder' => trim(
                    (string) (
                        $settings[
                            'bank_account_holder'
                        ]
                        ?? ''
                    )
                ),
                'bank_account_number' => trim(
                    (string) (
                        $settings[
                            'bank_account_number'
                        ]
                        ?? ''
                    )
                ),
                'payment_title' => $paymentTitle,
                'check_in_time' => trim(
                    (string) (
                        $settings[
                            'check_in_time'
                        ]
                        ?? '15:00'
                    )
                ),
                'check_out_time' => trim(
                    (string) (
                        $settings[
                            'check_out_time'
                        ]
                        ?? '11:00'
                    )
                ),
                'contact_phone' => trim(
                    (string) (
                        $settings[
                            'contact_phone'
                        ]
                        ?? ''
                    )
                ),
                'location' => $location,
                'property_name' => self::propertyName(
                    $settings
                ),
            ]
        );
    }

    /**
     * @param array<string, mixed> $inquiry
     * @param array<string, string> $settings
     */
    public static function forInquiry(
        string $template,
        array $inquiry,
        array $settings
    ): string {
        $firstName = trim(
            (string) ($inquiry['first_name'] ?? '')
        );

        $lastName = trim(
            (string) ($inquiry['last_name'] ?? '')
        );

        $guestName = trim(
            $firstName . ' ' . $lastName
        );

        $dateFrom = (string) (
            $inquiry['date_from'] ?? ''
        );

        $dateTo = (string) (
            $inquiry['date_to'] ?? ''
        );

        $nights = calculateReservationNights(
            $dateFrom,
            $dateTo
        );

        $nights = $nights ?? 0;

        $nightPrice = $nights > 0
            ? getReservationNightPriceFromSettings(
                $nights,
                $settings
            )
            : 0;

        $totalPrice = $nights * $nightPrice;

        $guests = max(
            1,
            (int) ($inquiry['guests'] ?? 1)
        );

        return self::replaceVariables(
            $template,
            [
                'guest_name' => $guestName,
                'first_name' => $firstName,
                'cabin_name' => trim(
                    (string) (
                        $inquiry['cabin_name']
                        ?? ''
                    )
                ),
                'start_date' => self::formatDate(
                    $dateFrom
                ),
                'end_date' => self::formatDate(
                    $dateTo
                ),
                'nights' => (string) $nights,
                'night_label' => self::nightLabel(
                    $nights
                ),
                'guests' => (string) $guests,
                'person_label' => self::personLabel(
                    $guests
                ),
                'total_price' => self::formatMoney(
                    $totalPrice
                ),
                'deposit_amount' => self::formatMoney(
                    is_numeric(
                        $settings[
                            'deposit_amount'
                        ]
                        ?? null
                    )
                        ? (float) $settings[
                            'deposit_amount'
                        ]
                        : 0
                ),
                'bank_account_holder' => trim(
                    (string) (
                        $settings[
                            'bank_account_holder'
                        ]
                        ?? ''
                    )
                ),
                'bank_account_number' => trim(
                    (string) (
                        $settings[
                            'bank_account_number'
                        ]
                        ?? ''
                    )
                ),
                'payment_title' => '',
                'check_in_time' => trim(
                    (string) (
                        $settings[
                            'check_in_time'
                        ]
                        ?? '15:00'
                    )
                ),
                'check_out_time' => trim(
                    (string) (
                        $settings[
                            'check_out_time'
                        ]
                        ?? '11:00'
                    )
                ),
                'contact_phone' => trim(
                    (string) (
                        $settings[
                            'contact_phone'
                        ]
                        ?? ''
                    )
                ),
                'location' => self::buildLocation(
                    $settings
                ),
                'property_name' => self::propertyName(
                    $settings
                ),
            ]
        );
    }

    /**
     * @param array<string, string> $variables
     */
    private static function replaceVariables(
        string $template,
        array $variables
    ): string {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements[
                '{{' . $key . '}}'
            ] = $value;
        }

        return strtr(
            $template,
            $replacements
        );
    }

    /**
     * @param array<string, string> $settings
     */
    private static function buildLocation(
        array $settings
    ): string {
        $parts = array_filter(
            [
                trim(
                    (string) (
                        $settings['address_line']
                        ?? ''
                    )
                ),
                trim(
                    (string) (
                        $settings['postal_code']
                        ?? ''
                    )
                ),
                trim(
                    (string) (
                        $settings['city']
                        ?? ''
                    )
                ),
            ],
            static fn (
                string $value
            ): bool => $value !== ''
        );

        return implode(
            ', ',
            $parts
        );
    }

    /**
     * @param array<string, string> $settings
     */
    private static function propertyName(
        array $settings
    ): string {
        $propertyName = trim(
            (string) (
                $settings['property_name']
                ?? 'Domki Sztabinki'
            )
        );

        return $propertyName !== ''
            ? $propertyName
            : 'Domki Sztabinki';
    }

    private static function firstName(
        string $guestName
    ): string {
        $guestName = trim(
            $guestName
        );

        if ($guestName === '') {
            return '';
        }

        $parts = preg_split(
            '/\s+/',
            $guestName
        );

        if (
            !is_array($parts)
            || $parts === []
        ) {
            return $guestName;
        }

        return (string) $parts[0];
    }

    private static function formatDate(
        string $date
    ): string {
        if ($date === '') {
            return '—';
        }

        try {
            return (
                new DateTimeImmutable($date)
            )->format('d.m.Y');
        } catch (Throwable $exception) {
            return $date;
        }
    }

    private static function formatMoney(
        float $amount
    ): string {
        return number_format(
            $amount,
            0,
            ',',
            ' '
        );
    }

    private static function nightLabel(
        int $nights
    ): string {
        if ($nights === 1) {
            return 'nocleg';
        }

        $lastTwoDigits = $nights % 100;
        $lastDigit = $nights % 10;

        if (
            $lastDigit >= 2
            && $lastDigit <= 4
            && !(
                $lastTwoDigits >= 12
                && $lastTwoDigits <= 14
            )
        ) {
            return 'noclegi';
        }

        return 'noclegów';
    }

    private static function personLabel(
        int $guests
    ): string {
        return $guests === 1
            ? 'osoby'
            : 'osób';
    }
}
