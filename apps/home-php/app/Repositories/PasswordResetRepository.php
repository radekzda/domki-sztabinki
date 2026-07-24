<?php

declare(strict_types=1);

final class PasswordResetRepository
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
            AND table_name = "password_reset_tokens"'
        );

        $statement->execute();

        return (int) $statement->fetchColumn() > 0;
    }

    public static function invalidateForUser(
        int $userId
    ): void {
        $statement = Database::connection()->prepare(
            'UPDATE password_reset_tokens
            SET used_at = NOW()
            WHERE user_id = :user_id
            AND used_at IS NULL'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);
    }

    public static function create(
        int $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        string $requestIpHash
    ): int {
        $statement = Database::connection()->prepare(
            'INSERT INTO password_reset_tokens (
                user_id,
                token_hash,
                expires_at,
                request_ip_hash
            ) VALUES (
                :user_id,
                :token_hash,
                :expires_at,
                :request_ip_hash
            )'
        );

        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' =>
                $expiresAt->format(
                    'Y-m-d H:i:s'
                ),
            'request_ip_hash' =>
                $requestIpHash,
        ]);

        return (int) Database::connection()
            ->lastInsertId();
    }

    public static function countRecentForUser(
        int $userId,
        DateTimeImmutable $since
    ): int {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
            FROM password_reset_tokens
            WHERE user_id = :user_id
            AND created_at >= :created_since'
        );

        $statement->execute([
            'user_id' => $userId,
            'created_since' =>
                $since->format(
                    'Y-m-d H:i:s'
                ),
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function latestForUser(
        int $userId
    ): ?array {
        $statement = Database::connection()->prepare(
            'SELECT
                id,
                user_id,
                created_at,
                expires_at,
                used_at
            FROM password_reset_tokens
            WHERE user_id = :user_id
            ORDER BY id DESC
            LIMIT 1'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findValidByHash(
        string $tokenHash
    ): ?array {
        $statement = Database::connection()->prepare(
            'SELECT
                password_reset_tokens.id,
                password_reset_tokens.user_id,
                password_reset_tokens.expires_at,
                password_reset_tokens.used_at,
                users.email,
                users.name,
                users.is_active
            FROM password_reset_tokens
            INNER JOIN users
                ON users.id = password_reset_tokens.user_id
            WHERE password_reset_tokens.token_hash = :token_hash
            AND password_reset_tokens.used_at IS NULL
            AND password_reset_tokens.expires_at > NOW()
            AND users.is_active = 1
            LIMIT 1'
        );

        $statement->execute([
            'token_hash' => $tokenHash,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $row
            : null;
    }

    public static function consumeAndChangePassword(
        string $tokenHash,
        string $passwordHash
    ): bool {
        $pdo = Database::connection();

        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'SELECT
                    password_reset_tokens.id,
                    password_reset_tokens.user_id,
                    password_reset_tokens.expires_at,
                    password_reset_tokens.used_at,
                    users.is_active
                FROM password_reset_tokens
                INNER JOIN users
                    ON users.id = password_reset_tokens.user_id
                WHERE password_reset_tokens.token_hash = :token_hash
                LIMIT 1
                FOR UPDATE'
            );

            $statement->execute([
                'token_hash' => $tokenHash,
            ]);

            $token = $statement->fetch();

            if (
                !is_array($token)
                || $token['used_at'] !== null
                || (int) ($token['is_active'] ?? 0) !== 1
            ) {
                $pdo->rollBack();

                return false;
            }

            $expiresAt = new DateTimeImmutable(
                (string) $token['expires_at']
            );

            if (
                $expiresAt
                <= new DateTimeImmutable('now')
            ) {
                $pdo->rollBack();

                return false;
            }

            $userId = (int) $token['user_id'];
            $tokenId = (int) $token['id'];

            $userUpdate = $pdo->prepare(
                'UPDATE users
                SET
                    password_hash = :password_hash,
                    password_changed_at = NOW(),
                    session_version = session_version + 1
                WHERE id = :user_id'
            );

            $userUpdate->execute([
                'password_hash' => $passwordHash,
                'user_id' => $userId,
            ]);

            $tokenUpdate = $pdo->prepare(
                'UPDATE password_reset_tokens
                SET used_at = NOW()
                WHERE user_id = :user_id
                AND used_at IS NULL'
            );

            $tokenUpdate->execute([
                'user_id' => $userId,
            ]);

            $pdo->commit();

            return $tokenId > 0;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public static function deleteExpired(): int
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM password_reset_tokens
            WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            OR (
                used_at IS NOT NULL
                AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            )'
        );

        $statement->execute();

        return $statement->rowCount();
    }
}
