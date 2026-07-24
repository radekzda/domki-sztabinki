<?php

declare(strict_types=1);

$appRoot = dirname(__DIR__);

require_once $appRoot . '/app/Core/Env.php';

Env::load(
    $appRoot . '/.env'
);

$config = require $appRoot . '/app/Config/config.php';

date_default_timezone_set(
    (string) (
        $config['timezone']
        ?? 'Europe/Warsaw'
    )
);

require_once $appRoot . '/app/Core/Database.php';

if (!Database::canAttemptConnection()) {
    fwrite(
        STDERR,
        "ERROR: Brak konfiguracji połączenia z bazą."
        . PHP_EOL
    );

    exit(1);
}

try {
    $pdo = Database::connection();

    $usersTableStatement = $pdo->prepare(
        'SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = "users"'
    );

    $usersTableStatement->execute();

    if (
        (int) $usersTableStatement->fetchColumn()
        === 0
    ) {
        throw new RuntimeException(
            'Brakuje tabeli users. '
            . 'Najpierw uruchom bin/users-migrate.php.'
        );
    }

    $sessionVersionStatement = $pdo->prepare(
        'SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = "users"
        AND column_name = "session_version"'
    );

    $sessionVersionStatement->execute();

    if (
        (int) $sessionVersionStatement->fetchColumn()
        === 0
    ) {
        $pdo->exec(
            'ALTER TABLE users
            ADD COLUMN session_version
                INT UNSIGNED NOT NULL DEFAULT 1
                AFTER is_active'
        );

        echo "OK: dodano users.session_version."
            . PHP_EOL;
    } else {
        echo "OK: users.session_version istnieje."
            . PHP_EOL;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            request_ip_hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY password_reset_tokens_hash_unique (
                token_hash
            ),
            INDEX password_reset_tokens_user_index (
                user_id
            ),
            INDEX password_reset_tokens_expires_index (
                expires_at
            ),
            CONSTRAINT password_reset_tokens_user_foreign
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci'
    );

    echo "OK: tabela password_reset_tokens istnieje."
        . PHP_EOL;
    echo "OK: mechanizm odzyskiwania hasła jest gotowy."
        . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'ERROR: '
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}
