<?php

declare(strict_types=1);

final class IcalSyncService
{
    /**
     * @param array<string, mixed> $cabin
     *
     * @return array{
     *     total: int,
     *     existing_ical: int,
     *     matched_reservations: int,
     *     conflicts: int,
     *     new_blocks: int
     * }
     */
    public static function syncCabin(
        array $cabin
    ): array {
        $cabinId = (int) (
            $cabin['id']
            ?? 0
        );

        if ($cabinId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator domku.'
            );
        }

        $icalUrl = trim(
            (string) (
                $cabin['ical_url']
                ?? ''
            )
        );

        if ($icalUrl === '') {
            throw new InvalidArgumentException(
                'Ten domek nie ma ustawionego adresu URL kalendarza iCal.'
            );
        }

        $source = strtoupper(
            trim(
                (string) (
                    $cabin['ical_source']
                    ?? 'BOOKING'
                )
            )
        );

        if ($source === '') {
            $source = 'BOOKING';
        }

        try {
            $content = IcalCalendarClient::fetch(
                $icalUrl
            );

            $events = IcalParser::parse(
                $content
            );

            $counts = [
                'EXISTING_ICAL' => 0,
                'MATCH_RESERVATION' => 0,
                'CONFLICT' => 0,
                'NEW_BLOCK' => 0,
            ];

            foreach ($events as $event) {
                $result =
                    IcalEventRepository::classifyAndStore(
                        $cabinId,
                        $source,
                        $event
                    );

                $action = (string) (
                    $result['action']
                    ?? ''
                );

                if (
                    array_key_exists(
                        $action,
                        $counts
                    )
                ) {
                    $counts[$action]++;
                }
            }

            CabinRepository::recordIcalSyncResult(
                $cabinId,
                'SUCCESS'
            );

            return [
                'total' => count($events),
                'existing_ical' =>
                    $counts['EXISTING_ICAL'],
                'matched_reservations' =>
                    $counts['MATCH_RESERVATION'],
                'conflicts' =>
                    $counts['CONFLICT'],
                'new_blocks' =>
                    $counts['NEW_BLOCK'],
            ];
        } catch (Throwable $exception) {
            try {
                CabinRepository::recordIcalSyncResult(
                    $cabinId,
                    'ERROR'
                );
            } catch (Throwable $statusException) {
                error_log(
                    'Nie udało się zapisać statusu błędu synchronizacji iCal domku #'
                    . $cabinId
                    . ': '
                    . $statusException->getMessage()
                );
            }

            throw $exception;
        }
    }
}