<?php

declare(strict_types=1);

final class IcalEventRepository
{
    private static bool $tableEnsured = false;

    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        $connection = Database::connection();

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS ical_events (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                cabin_id INT UNSIGNED NOT NULL,
                matched_reservation_id INT UNSIGNED NULL,
                ical_uid VARCHAR(191) NOT NULL,
                source VARCHAR(40) NOT NULL DEFAULT "BOOKING",
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                summary VARCHAR(255) NULL,
                description TEXT NULL,
                event_status VARCHAR(40) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_seen_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY ical_events_cabin_uid_unique (
                    cabin_id,
                    ical_uid
                ),
                INDEX ical_events_cabin_index (
                    cabin_id
                ),
                INDEX ical_events_reservation_index (
                    matched_reservation_id
                ),
                INDEX ical_events_source_index (
                    source
                ),
                INDEX ical_events_start_date_index (
                    start_date
                ),
                INDEX ical_events_end_date_index (
                    end_date
                ),
                CONSTRAINT ical_events_cabin_foreign
                    FOREIGN KEY (cabin_id)
                    REFERENCES cabins(id)
                    ON DELETE CASCADE,
                CONSTRAINT ical_events_reservation_foreign
                    FOREIGN KEY (matched_reservation_id)
                    REFERENCES reservations(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci'
        );

        self::$tableEnsured = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        self::ensureTable();

        if ($id < 1) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                matched_reservation_id,
                ical_uid,
                source,
                start_date,
                end_date,
                summary,
                description,
                event_status,
                is_active,
                last_seen_at,
                created_at,
                updated_at
            FROM ical_events
            WHERE id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByUid(
        int $cabinId,
        string $uid
    ): ?array {
        self::ensureTable();

        $uid = trim(
            $uid
        );

        if (
            $cabinId < 1
            || $uid === ''
        ) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                matched_reservation_id,
                ical_uid,
                source,
                start_date,
                end_date,
                summary,
                description,
                event_status,
                is_active,
                last_seen_at,
                created_at,
                updated_at
            FROM ical_events
            WHERE cabin_id = :cabin_id
            AND ical_uid = :ical_uid
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'ical_uid' => $uid,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findExactReservation(
        int $cabinId,
        string $startDate,
        string $endDate,
        ?string $source = null
    ): ?array {
        $connection = Database::connection();

        $source = strtoupper(
            trim(
                (string) ($source ?? '')
            )
        );

        $sql = 'SELECT
                id,
                cabin_id,
                guest_name,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND start_date = :start_date
            AND end_date = :end_date
            AND status <> "CANCELLED"';

        $params = [
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($source !== '') {
            $sql .= ' AND UPPER(source) = :source';
            $params['source'] = $source;
        }

        $sql .= ' ORDER BY id ASC
            LIMIT 1';

        $statement = $connection->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function findReservationById(
        int $reservationId
    ): ?array {
        if ($reservationId < 1) {
            return null;
        }

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                guest_name,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $reservationId,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBlockingConflict(
        int $cabinId,
        string $startDate,
        string $endDate
    ): ?array {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                cabin_id,
                guest_name,
                start_date,
                end_date,
                status,
                source
            FROM reservations
            WHERE cabin_id = :cabin_id
            AND status IN (
                "PENDING",
                "CONFIRMED",
                "CHECKED_IN"
            )
            AND start_date < :end_date
            AND end_date > :start_date
            ORDER BY start_date ASC, id ASC
            LIMIT 1'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $row;
    }

    /**
     * Sprawdza, jak należy potraktować wydarzenie iCal.
     *
     * Zwracane typy:
     *
     * EXISTING_ICAL
     * - wydarzenie o tym UID jest już znane.
     *
     * MATCH_RESERVATION
     * - istnieje rezerwacja tego samego domku
     *   z dokładnie takim samym terminem.
     *
     * CONFLICT
     * - istnieje inna rezerwacja nachodząca
     *   na termin wydarzenia.
     *
     * NEW_BLOCK
     * - wydarzenie nie jest jeszcze znane
     *   i nie odpowiada istniejącej rezerwacji.
     *
     * @param array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string
     * } $event
     *
     * @return array{
     *     action: string,
     *     existing_ical_event: array<string, mixed>|null,
     *     matched_reservation: array<string, mixed>|null,
     *     conflicting_reservation: array<string, mixed>|null
     * }
     */
    public static function classifyEvent(
        int $cabinId,
        array $event,
        ?string $source = null
    ): array {
        $uid = trim(
            (string) (
                $event['uid']
                ?? ''
            )
        );

        $startDate = trim(
            (string) (
                $event['start_date']
                ?? ''
            )
        );

        $endDate = trim(
            (string) (
                $event['end_date']
                ?? ''
            )
        );

        self::validateEventData(
            $cabinId,
            $uid,
            $startDate,
            $endDate
        );

        $normalizedSource = strtoupper(
            trim(
                (string) ($source ?? '')
            )
        );

        $reservationSource =
            self::reservationSourceFromIcalSource(
                $normalizedSource
            );

        $existingIcalEvent = self::findByUid(
            $cabinId,
            $uid
        );

        if ($existingIcalEvent !== null) {
            $existingMatchedReservationId =
                isset(
                    $existingIcalEvent[
                        'matched_reservation_id'
                    ]
                )
                && $existingIcalEvent[
                    'matched_reservation_id'
                ] !== null
                    ? (int) $existingIcalEvent[
                        'matched_reservation_id'
                    ]
                    : null;

            if (
                $existingMatchedReservationId !== null
                && $existingMatchedReservationId > 0
            ) {
                $linkedReservation =
                    self::findReservationById(
                        $existingMatchedReservationId
                    );

                if (
                    $linkedReservation !== null
                    && strtoupper(
                        trim(
                            (string) (
                                $linkedReservation[
                                    'status'
                                ]
                                ?? ''
                            )
                        )
                    ) !== 'CANCELLED'
                    && (int) (
                        $linkedReservation[
                            'cabin_id'
                        ]
                        ?? 0
                    ) === $cabinId
                    && (string) (
                        $linkedReservation[
                            'start_date'
                        ]
                        ?? ''
                    ) === $startDate
                    && (string) (
                        $linkedReservation[
                            'end_date'
                        ]
                        ?? ''
                    ) === $endDate
                ) {
                    return [
                        'action' => 'EXISTING_ICAL',
                        'existing_ical_event' =>
                            $existingIcalEvent,
                        'matched_reservation' =>
                            $linkedReservation,
                        'conflicting_reservation' =>
                            null,
                    ];
                }
            }
        }

        $matchedReservation =
            self::findExactReservation(
                $cabinId,
                $startDate,
                $endDate,
                $reservationSource !== ''
                    ? $reservationSource
                    : null
            );

        if ($matchedReservation !== null) {
            $matchedReservationId = (int) (
                $matchedReservation['id']
                ?? 0
            );

            $existingMatchedReservationId =
                $existingIcalEvent !== null
                && isset(
                    $existingIcalEvent[
                        'matched_reservation_id'
                    ]
                )
                && $existingIcalEvent[
                    'matched_reservation_id'
                ] !== null
                    ? (int) $existingIcalEvent[
                        'matched_reservation_id'
                    ]
                    : null;

            $existingStartDate = trim(
                (string) (
                    $existingIcalEvent[
                        'start_date'
                    ]
                    ?? ''
                )
            );

            $existingEndDate = trim(
                (string) (
                    $existingIcalEvent[
                        'end_date'
                    ]
                    ?? ''
                )
            );

            if (
                $existingIcalEvent !== null
                && $existingMatchedReservationId
                    === $matchedReservationId
                && $existingStartDate === $startDate
                && $existingEndDate === $endDate
            ) {
                return [
                    'action' => 'EXISTING_ICAL',
                    'existing_ical_event' =>
                        $existingIcalEvent,
                    'matched_reservation' =>
                        $matchedReservation,
                    'conflicting_reservation' =>
                        null,
                ];
            }

            return [
                'action' => 'MATCH_RESERVATION',
                'existing_ical_event' =>
                    $existingIcalEvent,
                'matched_reservation' =>
                    $matchedReservation,
                'conflicting_reservation' =>
                    null,
            ];
        }

        $linkCandidateReservation =
            self::findExactReservation(
                $cabinId,
                $startDate,
                $endDate
            );

        if ($linkCandidateReservation !== null) {
            return [
                'action' => 'CONFLICT',
                'existing_ical_event' =>
                    $existingIcalEvent,
                'matched_reservation' =>
                    null,
                'conflicting_reservation' =>
                    $linkCandidateReservation,
                'link_candidate_reservation' =>
                    $linkCandidateReservation,
            ];
        }

        $conflictingReservation =
            self::findBlockingConflict(
                $cabinId,
                $startDate,
                $endDate
            );

        if ($conflictingReservation !== null) {
            return [
                'action' => 'CONFLICT',
                'existing_ical_event' =>
                    $existingIcalEvent,
                'matched_reservation' =>
                    null,
                'conflicting_reservation' =>
                    $conflictingReservation,
                'link_candidate_reservation' =>
                    null,
            ];
        }

        if ($existingIcalEvent !== null) {
            return [
                'action' => 'EXISTING_ICAL',
                'existing_ical_event' =>
                    $existingIcalEvent,
                'matched_reservation' =>
                    null,
                'conflicting_reservation' =>
                    null,
            ];
        }

        return [
            'action' => 'NEW_BLOCK',
            'existing_ical_event' =>
                null,
            'matched_reservation' =>
                null,
            'conflicting_reservation' =>
                null,
        ];
    }

    /**
     * Zapisuje lub aktualizuje wydarzenie iCal.
     *
     * Nie tworzy rezerwacji i nie zmienia danych
     * istniejącej rezerwacji.
     *
     * @param array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string,
     *     summary?: string,
     *     description?: string,
     *     status?: string
     * } $event
     */
    public static function saveObservedEvent(
        int $cabinId,
        string $source,
        array $event,
        ?int $matchedReservationId = null
    ): int {
        self::ensureTable();

        $uid = trim(
            (string) (
                $event['uid']
                ?? ''
            )
        );

        $startDate = trim(
            (string) (
                $event['start_date']
                ?? ''
            )
        );

        $endDate = trim(
            (string) (
                $event['end_date']
                ?? ''
            )
        );

        self::validateEventData(
            $cabinId,
            $uid,
            $startDate,
            $endDate
        );

        $source = strtoupper(
            trim(
                $source
            )
        );

        if ($source === '') {
            $source = 'BOOKING';
        }

        $source = substr(
            $source,
            0,
            40
        );

        $summary = trim(
            (string) (
                $event['summary']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $event['description']
                ?? ''
            )
        );

        $eventStatus = strtoupper(
            trim(
                (string) (
                    $event['status']
                    ?? ''
                )
            )
        );

        $summary = $summary !== ''
            ? substr(
                $summary,
                0,
                255
            )
            : null;

        $description = $description !== ''
            ? $description
            : null;

        $eventStatus = $eventStatus !== ''
            ? substr(
                $eventStatus,
                0,
                40
            )
            : null;

        $isActive = (
            $eventStatus === 'CANCELLED'
        )
            ? 0
            : 1;

        $existing = self::findByUid(
            $cabinId,
            $uid
        );

        $connection = Database::connection();

        if ($existing !== null) {
            $finalMatchedReservationId =
                $matchedReservationId;

            if ($finalMatchedReservationId === null) {
                $existingMatchedReservationId =
                    isset(
                        $existing[
                            'matched_reservation_id'
                        ]
                    )
                    && $existing[
                        'matched_reservation_id'
                    ] !== null
                        ? (int) $existing[
                            'matched_reservation_id'
                        ]
                        : null;

                if (
                    $existingMatchedReservationId !== null
                    && $existingMatchedReservationId > 0
                ) {
                    $linkedReservation =
                        self::findReservationById(
                            $existingMatchedReservationId
                        );

                    if (
                        $linkedReservation !== null
                        && strtoupper(
                            trim(
                                (string) (
                                    $linkedReservation[
                                        'status'
                                    ]
                                    ?? ''
                                )
                            )
                        ) !== 'CANCELLED'
                        && (int) (
                            $linkedReservation[
                                'cabin_id'
                            ]
                            ?? 0
                        ) === $cabinId
                        && (string) (
                            $linkedReservation[
                                'start_date'
                            ]
                            ?? ''
                        ) === $startDate
                        && (string) (
                            $linkedReservation[
                                'end_date'
                            ]
                            ?? ''
                        ) === $endDate
                    ) {
                        $finalMatchedReservationId =
                            $existingMatchedReservationId;
                    }
                }
            }

            $statement = $connection->prepare(
                'UPDATE ical_events
                SET
                    matched_reservation_id =
                        :matched_reservation_id,
                    source = :source,
                    start_date = :start_date,
                    end_date = :end_date,
                    summary = :summary,
                    description = :description,
                    event_status = :event_status,
                    is_active = :is_active,
                    last_seen_at = NOW()
                WHERE id = :id'
            );

            $statement->execute([
                'id' => (int) $existing['id'],
                'matched_reservation_id' =>
                    $finalMatchedReservationId,
                'source' => $source,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'summary' => $summary,
                'description' => $description,
                'event_status' => $eventStatus,
                'is_active' => $isActive,
            ]);

            return (int) $existing['id'];
        }

        $statement = $connection->prepare(
            'INSERT INTO ical_events (
                cabin_id,
                matched_reservation_id,
                ical_uid,
                source,
                start_date,
                end_date,
                summary,
                description,
                event_status,
                is_active,
                last_seen_at
            ) VALUES (
                :cabin_id,
                :matched_reservation_id,
                :ical_uid,
                :source,
                :start_date,
                :end_date,
                :summary,
                :description,
                :event_status,
                :is_active,
                NOW()
            )'
        );

        $statement->execute([
            'cabin_id' => $cabinId,
            'matched_reservation_id' =>
                $matchedReservationId,
            'ical_uid' => $uid,
            'source' => $source,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $summary,
            'description' => $description,
            'event_status' => $eventStatus,
            'is_active' => $isActive,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * Klasyfikuje wydarzenie iCal i zapisuje je
     * w tabeli ical_events.
     *
     * Nie tworzy automatycznie rezerwacji.
     *
     * @param array{
     *     uid: string,
     *     start_date: string,
     *     end_date: string,
     *     summary?: string,
     *     description?: string,
     *     status?: string
     * } $event
     *
     * @return array{
     *     action: string,
     *     ical_event_id: int,
     *     existing_ical_event: array<string, mixed>|null,
     *     matched_reservation: array<string, mixed>|null,
     *     conflicting_reservation: array<string, mixed>|null
     * }
     */
    public static function classifyAndStore(
        int $cabinId,
        string $source,
        array $event
    ): array {
        $classification = self::classifyEvent(
            $cabinId,
            $event,
            $source
        );

        $matchedReservationId = null;

        if (
            is_array(
                $classification['matched_reservation']
            )
        ) {
            $matchedReservationId = (int) (
                $classification[
                    'matched_reservation'
                ]['id']
                ?? 0
            );

            if ($matchedReservationId < 1) {
                $matchedReservationId = null;
            }
        }

        $icalEventId = self::saveObservedEvent(
            $cabinId,
            $source,
            $event,
            $matchedReservationId
        );

        return [
            'action' =>
                $classification['action'],
            'ical_event_id' =>
                $icalEventId,
            'existing_ical_event' =>
                $classification[
                    'existing_ical_event'
                ],
            'matched_reservation' =>
                $classification[
                    'matched_reservation'
                ],
            'conflicting_reservation' =>
                $classification[
                    'conflicting_reservation'
                ],
            'link_candidate_reservation' =>
                $classification[
                    'link_candidate_reservation'
                ]
                ?? null,
        ];
    }

    public static function linkReservation(
        int $icalEventId,
        int $reservationId
    ): void {
        self::ensureTable();

        if (
            $icalEventId < 1
            || $reservationId < 1
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowe powiązanie wydarzenia iCal z rezerwacją.'
            );
        }

        $connection = Database::connection();
        $transactionStarted = false;

        try {
            if (!$connection->inTransaction()) {
                $connection->beginTransaction();
                $transactionStarted = true;
            }

            $statement = $connection->prepare(
                'SELECT
                    ie.id AS ical_event_id,
                    ie.cabin_id AS ical_cabin_id,
                    ie.matched_reservation_id,
                    ie.source AS ical_source,
                    ie.start_date AS ical_start_date,
                    ie.end_date AS ical_end_date,
                    ie.is_active,
                    r.id AS reservation_id,
                    r.cabin_id AS reservation_cabin_id,
                    r.start_date AS reservation_start_date,
                    r.end_date AS reservation_end_date
                FROM ical_events ie
                INNER JOIN reservations r
                    ON r.id = :reservation_id
                WHERE ie.id = :ical_event_id
                LIMIT 1'
            );

            $statement->execute([
                'ical_event_id' => $icalEventId,
                'reservation_id' => $reservationId,
            ]);

            $row = $statement->fetch();

            if (!is_array($row)) {
                throw new RuntimeException(
                    'Nie znaleziono wydarzenia iCal albo rezerwacji do powiązania.'
                );
            }

            if ((int) ($row['is_active'] ?? 0) !== 1) {
                throw new RuntimeException(
                    'Nie można powiązać nieaktywnej blokady iCal z rezerwacją.'
                );
            }

            $existingReservationId = isset(
                $row['matched_reservation_id']
            ) && $row['matched_reservation_id'] !== null
                ? (int) $row['matched_reservation_id']
                : null;

            if (
                $existingReservationId !== null
                && $existingReservationId !== $reservationId
            ) {
                throw new RuntimeException(
                    'Ta blokada iCal jest już powiązana z inną rezerwacją.'
                );
            }

            if (
                (int) ($row['ical_cabin_id'] ?? 0)
                    !== (int) ($row['reservation_cabin_id'] ?? 0)
                || (string) ($row['ical_start_date'] ?? '')
                    !== (string) ($row['reservation_start_date'] ?? '')
                || (string) ($row['ical_end_date'] ?? '')
                    !== (string) ($row['reservation_end_date'] ?? '')
            ) {
                throw new RuntimeException(
                    'Powiązać można tylko rezerwację tego samego domku z dokładnie takim samym terminem jak blokada iCal.'
                );
            }

            $icalSource = strtoupper(
                trim(
                    (string) (
                        $row['ical_source']
                        ?? 'OTHER'
                    )
                )
            );

            $reservationSource = match ($icalSource) {
                'BOOKING' => 'BOOKING',
                'AIRBNB' => 'AIRBNB',
                default => 'ICAL_OTHER',
            };

            $updateEvent = $connection->prepare(
                'UPDATE ical_events
                SET matched_reservation_id = :reservation_id
                WHERE id = :ical_event_id'
            );

            $updateEvent->execute([
                'reservation_id' => $reservationId,
                'ical_event_id' => $icalEventId,
            ]);

            $updateReservation = $connection->prepare(
                'UPDATE reservations
                SET source = :source
                WHERE id = :reservation_id'
            );

            $updateReservation->execute([
                'source' => $reservationSource,
                'reservation_id' => $reservationId,
            ]);

            if (
                $transactionStarted
                && $connection->inTransaction()
            ) {
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if (
                $transactionStarted
                && $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function hasBlockingOverlap(
        int $cabinId,
        string $startDate,
        string $endDate,
        ?int $ignoreReservationId = null,
        ?int $ignoreIcalEventId = null
    ): bool {
        self::ensureTable();

        $connection = Database::connection();

        $sql = 'SELECT COUNT(*)
            FROM ical_events
            WHERE cabin_id = :cabin_id
            AND is_active = 1
            AND (
                event_status IS NULL
                OR event_status <> "CANCELLED"
            )
            AND start_date < :end_date
            AND end_date > :start_date';

        $params = [
            'cabin_id' => $cabinId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($ignoreReservationId !== null) {
            $sql .= ' AND (
                matched_reservation_id IS NULL
                OR matched_reservation_id <> :ignore_reservation_id
            )';

            $params['ignore_reservation_id'] =
                $ignoreReservationId;
        }

        if ($ignoreIcalEventId !== null) {
            $sql .= ' AND id <> :ignore_ical_event_id';
            $params['ignore_ical_event_id'] =
                $ignoreIcalEventId;
        }

        $statement = $connection->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Dezaktywuje wydarzenia, które były zapisane wcześniej,
     * ale nie występują już w aktualnie pobranym kalendarzu.
     *
     * @param array<int, string> $seenUids
     */
    public static function deactivateMissingForCabin(
        int $cabinId,
        string $source,
        array $seenUids
    ): int {
        self::ensureTable();

        if ($cabinId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator domku.'
            );
        }

        $source = strtoupper(
            trim(
                $source
            )
        );

        if ($source === '') {
            $source = 'BOOKING';
        }

        $seenUids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $uid): string =>
                            trim((string) $uid),
                        $seenUids
                    ),
                    static fn (string $uid): bool =>
                        $uid !== ''
                )
            )
        );

        $connection = Database::connection();

        $params = [
            'cabin_id' => $cabinId,
            'source' => $source,
        ];

        $sql = 'UPDATE ical_events
            SET is_active = 0
            WHERE cabin_id = :cabin_id
            AND source = :source
            AND is_active = 1';

        if ($seenUids !== []) {
            $placeholders = [];

            foreach (
                $seenUids
                as $index => $uid
            ) {
                $key = 'uid_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $uid;
            }

            $sql .= ' AND ical_uid NOT IN ('
                . implode(
                    ', ',
                    $placeholders
                )
                . ')';
        }

        $statement = $connection->prepare(
            $sql
        );

        $statement->execute(
            $params
        );

        return $statement->rowCount();
    }

    private static function reservationSourceFromIcalSource(
        string $source
    ): string {
        $source = strtoupper(
            trim(
                $source
            )
        );

        if ($source === '') {
            return '';
        }

        return match ($source) {
            'BOOKING' => 'BOOKING',
            'AIRBNB' => 'AIRBNB',
            default => 'ICAL_OTHER',
        };
    }

    private static function validateEventData(
        int $cabinId,
        string $uid,
        string $startDate,
        string $endDate
    ): void {
        if ($cabinId < 1) {
            throw new InvalidArgumentException(
                'Nieprawidłowy identyfikator domku.'
            );
        }

        if ($uid === '') {
            throw new InvalidArgumentException(
                'Wydarzenie iCal nie ma identyfikatora UID.'
            );
        }

        if (
            !self::isValidDate(
                $startDate
            )
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data rozpoczęcia wydarzenia iCal.'
            );
        }

        if (
            !self::isValidDate(
                $endDate
            )
        ) {
            throw new InvalidArgumentException(
                'Nieprawidłowa data zakończenia wydarzenia iCal.'
            );
        }

        if ($endDate <= $startDate) {
            throw new InvalidArgumentException(
                'Data zakończenia wydarzenia iCal musi być późniejsza od daty rozpoczęcia.'
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

        return $parsed->format(
            'Y-m-d'
        ) === $date;
    }
}
