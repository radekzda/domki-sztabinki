<?php

declare(strict_types=1);

final class AvailabilityRepository
{
    /**
     * Zwraca okresy, które rzeczywiście blokują dostępność domku.
     *
     * Źródłem są jednocześnie rezerwacje PMS oraz aktywne wydarzenia iCal.
     * Wydarzenie iCal powiązane z rezerwacją o tym samym terminie nie jest
     * zwracane drugi raz.
     *
     * @return array<int, array{
     *     kind: string,
     *     cabin_id: int,
     *     start_date: string,
     *     end_date: string,
     *     status: string,
     *     source: string,
     *     reservation_id: int|null,
     *     ical_event_id: int|null
     * }>
     */
    public static function blockingPeriods(
        string $startDate,
        string $endDate
    ): array {
        return self::periods(
            $startDate,
            $endDate,
            true
        );
    }

    /**
     * Zwraca okresy widoczne w kalendarzu administratora.
     *
     * Rezerwacje anulowane są pomijane. Aktywne wydarzenia iCal są dodawane
     * jako zewnętrzne blokady. Powiązany iCal o dokładnie tym samym terminie
     * co rezerwacja nie jest dublowany.
     *
     * @return array<int, array{
     *     kind: string,
     *     cabin_id: int,
     *     start_date: string,
     *     end_date: string,
     *     status: string,
     *     source: string,
     *     reservation_id: int|null,
     *     ical_event_id: int|null
     * }>
     */
    public static function calendarPeriods(
        string $startDate,
        string $endDate
    ): array {
        return self::periods(
            $startDate,
            $endDate,
            false
        );
    }

    /**
     * @return array<int, array{
     *     kind: string,
     *     cabin_id: int,
     *     start_date: string,
     *     end_date: string,
     *     status: string,
     *     source: string,
     *     reservation_id: int|null,
     *     ical_event_id: int|null
     * }>
     */
    private static function periods(
        string $startDate,
        string $endDate,
        bool $blockingOnly
    ): array {
        self::validateRange(
            $startDate,
            $endDate
        );

        IcalEventRepository::ensureTable();

        $connection = Database::connection();

        $reservationStatusSql = $blockingOnly
            ? 'status IN ("PENDING", "CONFIRMED", "CHECKED_IN")'
            : 'status <> "CANCELLED"';

        $reservationStatement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE ' . $reservationStatusSql . '
            AND start_date < :end_date
            AND end_date > :start_date
            ORDER BY cabin_id ASC, start_date ASC, end_date ASC, id ASC'
        );

        $reservationStatement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $periods = [];
        $reservationRows = $reservationStatement->fetchAll();

        if (is_array($reservationRows)) {
            foreach ($reservationRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $periods[] = [
                    'kind' => 'RESERVATION',
                    'cabin_id' => (int) ($row['cabin_id'] ?? 0),
                    'start_date' => (string) ($row['start_date'] ?? ''),
                    'end_date' => (string) ($row['end_date'] ?? ''),
                    'status' => (string) ($row['status'] ?? ''),
                    'source' => (string) ($row['source'] ?? 'MANUAL'),
                    'reservation_id' => (int) ($row['id'] ?? 0),
                    'ical_event_id' => null,
                ];
            }
        }

        $matchedReservationCondition = $blockingOnly
            ? 'mr.status IN ("PENDING", "CONFIRMED", "CHECKED_IN")'
            : 'mr.status <> "CANCELLED"';

        $icalStatement = $connection->prepare(
            'SELECT
                ie.id,
                ie.cabin_id,
                ie.start_date,
                ie.end_date,
                ie.source
            FROM ical_events ie
            WHERE ie.is_active = 1
            AND (
                ie.event_status IS NULL
                OR UPPER(ie.event_status) NOT IN (
                    "CANCELLED",
                    "CANCELED"
                )
            )
            AND ie.start_date < :end_date
            AND ie.end_date > :start_date
            AND NOT EXISTS (
                SELECT 1
                FROM reservations mr
                WHERE mr.id = ie.matched_reservation_id
                AND mr.cabin_id = ie.cabin_id
                AND mr.start_date = ie.start_date
                AND mr.end_date = ie.end_date
                AND ' . $matchedReservationCondition . '
            )
            ORDER BY ie.cabin_id ASC, ie.start_date ASC, ie.end_date ASC, ie.id ASC'
        );

        $icalStatement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $icalRows = $icalStatement->fetchAll();

        if (is_array($icalRows)) {
            foreach ($icalRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $periods[] = [
                    'kind' => 'ICAL',
                    'cabin_id' => (int) ($row['cabin_id'] ?? 0),
                    'start_date' => (string) ($row['start_date'] ?? ''),
                    'end_date' => (string) ($row['end_date'] ?? ''),
                    'status' => 'ICAL',
                    'source' => (string) ($row['source'] ?? 'ICAL'),
                    'reservation_id' => null,
                    'ical_event_id' => (int) ($row['id'] ?? 0),
                ];
            }
        }

        usort(
            $periods,
            static function (array $first, array $second): int {
                $cabinCompare = ((int) ($first['cabin_id'] ?? 0))
                    <=> ((int) ($second['cabin_id'] ?? 0));

                if ($cabinCompare !== 0) {
                    return $cabinCompare;
                }

                $startCompare = strcmp(
                    (string) ($first['start_date'] ?? ''),
                    (string) ($second['start_date'] ?? '')
                );

                if ($startCompare !== 0) {
                    return $startCompare;
                }

                $endCompare = strcmp(
                    (string) ($first['end_date'] ?? ''),
                    (string) ($second['end_date'] ?? '')
                );

                if ($endCompare !== 0) {
                    return $endCompare;
                }

                return strcmp(
                    (string) ($first['kind'] ?? ''),
                    (string) ($second['kind'] ?? '')
                );
            }
        );

        return $periods;
    }

    private static function validateRange(
        string $startDate,
        string $endDate
    ): void {
        if (
            !self::isValidDate($startDate)
            || !self::isValidDate($endDate)
            || $endDate <= $startDate
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowy zakres dat dostępności.'
            );
        }
    }

    private static function isValidDate(
        string $date
    ): bool {
        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if ($parsed === false) {
            return false;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if (
            is_array($errors)
            && (
                ($errors['warning_count'] ?? 0) > 0
                || ($errors['error_count'] ?? 0) > 0
            )
        ) {
            return false;
        }

        return $parsed->format('Y-m-d') === $date;
    }
}
