<?php

declare(strict_types=1);

final class IcalExportService
{
    /**
     * @param array<int, array<string, mixed>> $reservations
     */
    public static function generate(
        int $cabinId,
        array $reservations
    ): string {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Domki Sztabinki//PMS iCal//PL',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Domki Sztabinki',
        ];

        $dtstamp = (
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            )
        )->format(
            'Ymd\THis\Z'
        );

        foreach ($reservations as $reservation) {
            $id = (int) (
                $reservation['id']
                ?? 0
            );

            $startDate = self::formatDate(
                (string) (
                    $reservation['start_date']
                    ?? ''
                )
            );

            $endDate = self::formatDate(
                (string) (
                    $reservation['end_date']
                    ?? ''
                )
            );

            if (
                $id < 1
                || $startDate === null
                || $endDate === null
            ) {
                continue;
            }

            $lines[] = 'BEGIN:VEVENT';

            $lines[] =
                'UID:pms-reservation-'
                . $id
                . '-cabin-'
                . $cabinId
                . '@domkisztabinki.pl';

            $lines[] =
                'DTSTAMP:'
                . $dtstamp;

            $lines[] =
                'DTSTART;VALUE=DATE:'
                . $startDate;

            $lines[] =
                'DTEND;VALUE=DATE:'
                . $endDate;

            $lines[] =
                'SUMMARY:Zajete';

            $lines[] =
                'STATUS:CONFIRMED';

            $lines[] =
                'TRANSP:OPAQUE';

            $lines[] =
                'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode(
            "\r\n",
            $lines
        ) . "\r\n";
    }

    private static function formatDate(
        string $date
    ): ?string {
        $date = trim(
            $date
        );

        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if ($parsed === false) {
            return null;
        }

        return $parsed->format(
            'Ymd'
        );
    }
}