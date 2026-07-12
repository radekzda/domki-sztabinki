<?php

declare(strict_types=1);

final class GuestRepository
{
    /**
     * @return array<int, array{
     *     id: int,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     created_at: string
     * }>
     */
    public static function all(): array
    {
        $connection = Database::connection();

        $statement = $connection->query(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
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

        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
                'country' => isset($row['country']) ? (string) $row['country'] : null,
                'city' => isset($row['city']) ? (string) $row['city'] : null,
                'is_vip' => (int) ($row['is_vip'] ?? 0),
                'source' => (string) ($row['source'] ?? 'MANUAL'),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * @return array{
     *     id: int,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     notes: string|null,
     *     created_at: string
     * }|null
     */
    public static function find(int $id): ?array
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                is_vip,
                source,
                notes,
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

        return [
            'id' => (int) ($row['id'] ?? 0),
            'first_name' => (string) ($row['first_name'] ?? ''),
            'last_name' => (string) ($row['last_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
            'country' => isset($row['country']) ? (string) $row['country'] : null,
            'city' => isset($row['city']) ? (string) $row['city'] : null,
            'is_vip' => (int) ($row['is_vip'] ?? 0),
            'source' => (string) ($row['source'] ?? 'MANUAL'),
            'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     notes: string|null,
     *     created_at: string
     * }|null
     */
    public static function findByEmail(string $email): ?array
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'SELECT
                id,
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                is_vip,
                source,
                notes,
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

        return [
            'id' => (int) ($row['id'] ?? 0),
            'first_name' => (string) ($row['first_name'] ?? ''),
            'last_name' => (string) ($row['last_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => isset($row['phone']) ? (string) $row['phone'] : null,
            'country' => isset($row['country']) ? (string) $row['country'] : null,
            'city' => isset($row['city']) ? (string) $row['city'] : null,
            'is_vip' => (int) ($row['is_vip'] ?? 0),
            'source' => (string) ($row['source'] ?? 'MANUAL'),
            'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    /**
     * @param array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     notes: string|null
     * } $data
     */
    public static function create(array $data): int
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'INSERT INTO guests (
                first_name,
                last_name,
                email,
                phone,
                country,
                city,
                is_vip,
                source,
                notes
            ) VALUES (
                :first_name,
                :last_name,
                :email,
                :phone,
                :country,
                :city,
                :is_vip,
                :source,
                :notes
            )'
        );

        $statement->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'is_vip' => $data['is_vip'],
            'source' => $data['source'],
            'notes' => $data['notes'],
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @param array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     country: string|null,
     *     city: string|null,
     *     is_vip: int,
     *     source: string,
     *     notes: string|null
     * } $data
     */
    public static function update(int $id, array $data): void
    {
        $connection = Database::connection();

        $statement = $connection->prepare(
            'UPDATE guests
            SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                country = :country,
                city = :city,
                is_vip = :is_vip,
                source = :source,
                notes = :notes
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'city' => $data['city'],
            'is_vip' => $data['is_vip'],
            'source' => $data['source'],
            'notes' => $data['notes'],
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
            $unlinkStatement = $connection->prepare(
                'UPDATE reservations
                SET guest_id = NULL
                WHERE guest_id = :guest_id'
            );

            $unlinkStatement->execute([
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
            return $guestId;
        }

        $existingGuest = self::findByEmail($email);

        if ($existingGuest !== null) {
            return $existingGuest['id'];
        }

        $nameParts = preg_split('/\s+/', trim($guestName));

        if (!is_array($nameParts) || $nameParts === []) {
            $firstName = $guestName;
            $lastName = '—';
        } else {
            $firstName = (string) ($nameParts[0] ?? $guestName);
            $lastNameParts = array_slice($nameParts, 1);
            $lastName = $lastNameParts !== [] ? implode(' ', $lastNameParts) : '—';
        }

        return self::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'country' => 'Polska',
            'city' => null,
            'is_vip' => 0,
            'source' => $source,
            'notes' => $notes,
        ]);
    }
}