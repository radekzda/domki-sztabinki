<?php

declare(strict_types=1);

final class UserRepository
{
    public static function tableExists(): bool
    {
        if (!Database::canAttemptConnection()) {
            return false;
        }

        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = "users"'
        );

        $statement->execute();

        return (int) $statement->fetchColumn() > 0;
    }

    public static function countAll(): int
    {
        if (!self::tableExists()) {
            return 0;
        }

        $statement = Database::connection()->query(
            'SELECT COUNT(*) FROM users'
        );

        if ($statement === false) {
            return 0;
        }

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $statement = Database::connection()->query(
            'SELECT
                id,
                name,
                email,
                role,
                is_active,
                last_login_at,
                password_changed_at,
                created_at,
                updated_at
            FROM users
            ORDER BY
                CASE
                    WHEN role = "ADMIN" THEN 0
                    ELSE 1
                END,
                name ASC,
                email ASC,
                id ASC'
        );

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return is_array($rows)
            ? $rows
            : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT
                id,
                name,
                email,
                password_hash,
                role,
                is_active,
                last_login_at,
                password_changed_at,
                created_at,
                updated_at
            FROM users
            WHERE id = :id
            LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $user = $statement->fetch();

        return is_array($user)
            ? $user
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(
        string $email
    ): ?array {
        $statement = Database::connection()->prepare(
            'SELECT
                id,
                name,
                email,
                password_hash,
                role,
                is_active,
                last_login_at,
                password_changed_at,
                created_at,
                updated_at
            FROM users
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1'
        );

        $statement->execute([
            'email' => trim($email),
        ]);

        $user = $statement->fetch();

        return is_array($user)
            ? $user
            : null;
    }

    public static function emailExists(
        string $email,
        ?int $exceptId = null
    ): bool {
        $sql =
            'SELECT COUNT(*)
            FROM users
            WHERE LOWER(email) = LOWER(:email)';

        $params = [
            'email' => trim($email),
        ];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }

        $statement = Database::connection()->prepare(
            $sql
        );

        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password_hash: string,
     *     role: string,
     *     is_active: int
     * } $data
     */
    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (
                name,
                email,
                password_hash,
                role,
                is_active,
                password_changed_at
            ) VALUES (
                :name,
                :email,
                :password_hash,
                :role,
                :is_active,
                NOW()
            )'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password_hash' =>
                $data['password_hash'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);

        return (int) Database::connection()
            ->lastInsertId();
    }

    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     role: string,
     *     is_active: int,
     *     password_hash?: string|null
     * } $data
     */
    public static function update(
        int $id,
        array $data
    ): void {
        $set = [
            'name = :name',
            'email = :email',
            'role = :role',
            'is_active = :is_active',
        ];

        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ];

        $passwordHash = $data['password_hash']
            ?? null;

        if (
            is_string($passwordHash)
            && $passwordHash !== ''
        ) {
            $set[] = 'password_hash = :password_hash';
            $set[] = 'password_changed_at = NOW()';
            $params['password_hash'] = $passwordHash;
        }

        $statement = Database::connection()->prepare(
            'UPDATE users
            SET '
            . implode(', ', $set)
            . '
            WHERE id = :id'
        );

        $statement->execute($params);
    }

    public static function setActive(
        int $id,
        bool $isActive
    ): void {
        $statement = Database::connection()->prepare(
            'UPDATE users
            SET is_active = :is_active
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'is_active' => $isActive ? 1 : 0,
        ]);
    }

    public static function updateLastLogin(
        int $id
    ): void {
        $statement = Database::connection()->prepare(
            'UPDATE users
            SET last_login_at = NOW()
            WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
        ]);
    }

    public static function countActiveAdmins(): int
    {
        $statement = Database::connection()->query(
            'SELECT COUNT(*)
            FROM users
            WHERE role = "ADMIN"
            AND is_active = 1'
        );

        if ($statement === false) {
            return 0;
        }

        return (int) $statement->fetchColumn();
    }
}
