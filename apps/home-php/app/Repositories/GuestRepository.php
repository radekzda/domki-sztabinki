<?php

declare(strict_types=1);

final class GuestRepository
{
    private static bool $profileColumnsEnsured = false;

    public static function all(): array
    {
        self::ensureProfileColumns();

        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                external_id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                full_address,
                pesel,
                document_number,
                nationality,
                birth_date,
                is_vip,
                source,
                created_at
            FROM guests
            ORDER BY last_name ASC, first_name ASC, id DESC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map([self::class, 'mapGuestRow'], $rows);
    }

    public static function find(int $id): ?array
    {
        self::ensureProfileColumns();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                external_id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                full_address,
                pesel,
                document_number,
                nationality,
                birth_date,
                is_vip,
                source,
                notes,
                preferred_contact,
                preferences,
                important_notes,
                created_at
            FROM guests
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

        return self::mapGuestRow($row);
    }

    public static function findByEmail(string $email): ?array
    {
        self::ensureProfileColumns();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                external_id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                full_address,
                pesel,
                document_number,
                nationality,
                birth_date,
                is_vip,
                source,
                notes,
                preferred_contact,
                preferences,
                important_notes,
                created_at
            FROM guests
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return self::mapGuestRow($row);
    }

    public static function create(array $data): int
    {
        self::ensureProfileColumns();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO guests (
                external_id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                full_address,
                pesel,
                document_number,
                nationality,
                birth_date,
                is_vip,
                source,
                notes,
                preferred_contact,
                preferences,
                important_notes
            ) VALUES (
                :external_id,
                :first_name,
                :last_name,
                :email,
                :phone,
                :country,
                :city,
                :full_address,
                :pesel,
                :document_number,
                :nationality,
                :birth_date,
                :is_vip,
                :source,
                :notes,
                :preferred_contact,
                :preferences,
                :important_notes
            )'
        );

        $statement->execute([
            'external_id' => $data['external_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'full_address' => $data['full_address'] ?? null,
            'pesel' => $data['pesel'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'is_vip' => $data['is_vip'],
            'source' => $data['source'],
            'notes' => $data['notes'],
            'preferred_contact' => $data['preferred_contact'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'important_notes' => $data['important_notes'] ?? null,
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureProfileColumns();

        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE guests
            SET
                external_id = :external_id,
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                country = :country,
                city = :city,
                full_address = :full_address,
                pesel = :pesel,
                document_number = :document_number,
                nationality = :nationality,
                birth_date = :birth_date,
                is_vip = :is_vip,
                source = :source,
                notes = :notes,
                preferred_contact = :preferred_contact,
                preferences = :preferences,
                important_notes = :important_notes
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'external_id' => $data['external_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'full_address' => $data['full_address'] ?? null,
            'pesel' => $data['pesel'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'is_vip' => $data['is_vip'],
            'source' => $data['source'],
            'notes' => $data['notes'],
            'preferred_contact' => $data['preferred_contact'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'important_notes' => $data['important_notes'] ?? null,
        ]);
    }

    public static function setVip(int $id, bool $isVip): void
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE guests
            SET is_vip = :is_vip
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'is_vip' => $isVip ? 1 : 0,
        ]);
    }

    public static function delete(int $id): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                'UPDATE reservations
                SET guest_id = NULL
                WHERE guest_id = :guest_id'
            );

            $statement->execute([
                'guest_id' => $id,
            ]);

            $deleteStatement = $connection->prepare(
                'DELETE FROM guests
                WHERE id = :id'
            );

            $deleteStatement->execute([
                'id' => $id,
            ]);

            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    public static function resolveForReservation(
        ?int $guestId,
        string $guestName,
        string $email,
        ?string $phone,
        string $source,
        ?string $notes
    ): ?int {
        if ($guestId !== null && $guestId > 0) {
            $guest = self::find($guestId);

            if ($guest !== null) {
                return $guestId;
            }
        }

        $email = strtolower(trim($email));

        if ($email !== '') {
            $existingGuest = self::findByEmail($email);

            if ($existingGuest !== null) {
                return (int) $existingGuest['id'];
            }
        }

        $guestName = trim($guestName);

        if ($guestName === '' && $email === '') {
            return null;
        }

        $nameParts = preg_split('/\s+/', $guestName) ?: [];
        $firstName = $nameParts[0] ?? 'Gość';
        $lastNameParts = array_slice($nameParts, 1);
        $lastName = implode(' ', $lastNameParts);

        if ($lastName === '') {
            $lastName = '—';
        }

        if ($email === '') {
            $email = 'brak-email-' . uniqid('', true) . '@manual.local';
        }

        return self::create([
            'external_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'country' => null,
            'city' => null,
            'full_address' => null,
            'pesel' => null,
            'document_number' => null,
            'nationality' => null,
            'birth_date' => null,
            'is_vip' => 0,
            'source' => $source,
            'notes' => $notes,
        ]);
    }

    private static function ensureProfileColumns(): void
    {
        if (self::$profileColumnsEnsured) {
            return;
        }

        $connection = Database::connection();

        $statement = $connection->query(
            'SHOW COLUMNS FROM guests'
        );

        $columns = [];

        if ($statement !== false) {
            $rows = $statement->fetchAll();

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (
                        is_array($row)
                        && isset($row['Field'])
                    ) {
                        $columns[] = (string) $row['Field'];
                    }
                }
            }
        }

        if (
            !in_array(
                'preferred_contact',
                $columns,
                true
            )
        ) {
            $connection->exec(
                'ALTER TABLE guests
                ADD COLUMN preferred_contact VARCHAR(30) NULL
                AFTER notes'
            );

            $columns[] = 'preferred_contact';
        }

        if (
            !in_array(
                'preferences',
                $columns,
                true
            )
        ) {
            $connection->exec(
                'ALTER TABLE guests
                ADD COLUMN preferences TEXT NULL
                AFTER preferred_contact'
            );

            $columns[] = 'preferences';
        }

        if (
            !in_array(
                'important_notes',
                $columns,
                true
            )
        ) {
            $connection->exec(
                'ALTER TABLE guests
                ADD COLUMN important_notes TEXT NULL
                AFTER preferences'
            );
        }

        self::$profileColumnsEnsured = true;
    }

    private static function mapGuestRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'external_id' => isset($row['external_id']) ? (string) $row['external_id'] : null,
            'first_name' => (string) ($row['first_name'] ?? ''),
            'last_name' => (string) ($row['last_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
            'country' => isset($row['country']) ? (string) $row['country'] : null,
            'city' => isset($row['city']) ? (string) $row['city'] : null,
            'full_address' => isset($row['full_address']) ? (string) $row['full_address'] : null,
            'pesel' => isset($row['pesel']) ? (string) $row['pesel'] : null,
            'document_number' => isset($row['document_number']) ? (string) $row['document_number'] : null,
            'nationality' => isset($row['nationality']) ? (string) $row['nationality'] : null,
            'birth_date' => isset($row['birth_date']) ? (string) $row['birth_date'] : null,
            'is_vip' => (int) ($row['is_vip'] ?? 0),
            'source' => (string) ($row['source'] ?? 'MANUAL'),
            'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            'preferred_contact' => isset($row['preferred_contact']) ? (string) $row['preferred_contact'] : null,
            'preferences' => isset($row['preferences']) ? (string) $row['preferences'] : null,
            'important_notes' => isset($row['important_notes']) ? (string) $row['important_notes'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}
