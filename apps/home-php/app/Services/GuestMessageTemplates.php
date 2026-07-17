<?php

declare(strict_types=1);

final class GuestMessageTemplates
{
    /**
     * @param array<string, mixed> $inquiry
     * @param array<string, string> $settings
     */
    public static function availabilityReply(
        array $inquiry,
        array $settings
    ): string {
        $firstName = trim(
            (string) ($inquiry['first_name'] ?? '')
        );

        $greeting = $firstName !== ''
            ? 'Dzień dobry ' . $firstName . ','
            : 'Dzień dobry,';

        $dateFrom = (string) ($inquiry['date_from'] ?? '');
        $dateTo = (string) ($inquiry['date_to'] ?? '');

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

        $propertyName = trim(
            (string) (
                $settings['property_name']
                ?? 'Domki Sztabinki'
            )
        );

        if ($propertyName === '') {
            $propertyName = 'Domki Sztabinki';
        }

        $lines = [
            $greeting,
            '',
            'dziękujemy za zapytanie. Wybrany termin jest dostępny.',
            '',
            sprintf(
                'Cena pobytu wynosi %s zł za %d %s. Cena obejmuje pobyt do %d %s oraz korzystanie z wyposażenia domku, grilla, łódki, kajaka i rowerków wodnych.',
                number_format(
                    $totalPrice,
                    0,
                    ',',
                    ' '
                ),
                $nights,
                self::nightLabel($nights),
                $guests,
                self::personLabel($guests)
            ),
            '',
            'W celu potwierdzenia rezerwacji prosimy o informację zwrotną. Następnie prześlemy dane do wpłaty zadatku.',
            '',
            'Pozdrawiamy serdecznie',
            $propertyName,
        ];

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $reservation
     * @param array<string, string> $settings
     */
    public static function reservationConfirmation(
        array $reservation,
        array $settings
    ): string {
        $guestName = trim(
            (string) ($reservation['guest_name'] ?? '')
        );

        $greeting = $guestName !== ''
            ? 'Dzień dobry ' . $guestName . ','
            : 'Dzień dobry,';

        $propertyName = trim(
            (string) (
                $settings['property_name']
                ?? 'Domki Sztabinki'
            )
        );

        if ($propertyName === '') {
            $propertyName = 'Domki Sztabinki';
        }

        $cabinName = trim(
            (string) ($reservation['cabin_name'] ?? '')
        );

        if ($cabinName === '') {
            $cabinName = 'Domek';
        }

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

        $checkInTime = trim(
            (string) (
                $settings['check_in_time']
                ?? '15:00'
            )
        );

        $checkOutTime = trim(
            (string) (
                $settings['check_out_time']
                ?? '11:00'
            )
        );

        $lines = [
            $greeting,
            '',
            'dziękujemy. Potwierdzamy rezerwację.',
            '',
            'Szczegóły rezerwacji:',
            'Domek: ' . $cabinName,
            'Termin: ' . $startDate . ' — ' . $endDate,
            'Liczba nocy: ' . $nights,
            'Liczba osób: ' . $guests,
            'Cena pobytu: '
                . number_format(
                    $totalPrice,
                    0,
                    ',',
                    ' '
                )
                . ' zł',
            '',
            'Zameldowanie od godz. ' . $checkInTime . '.',
            'Wymeldowanie do godz. ' . $checkOutTime . '.',
            '',
            'Cena obejmuje korzystanie z wyposażenia domku, grilla, łódki, kajaka i rowerków wodnych.',
            '',
            'W razie pytań prosimy o kontakt.',
            '',
            'Pozdrawiamy serdecznie',
            $propertyName,
        ];

        return implode("\n", $lines);
    }

    private static function formatDate(string $date): string
    {
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

    private static function nightLabel(int $nights): string
    {
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

    private static function personLabel(int $guests): string
    {
        return $guests === 1
            ? 'osoby'
            : 'osób';
    }
}
