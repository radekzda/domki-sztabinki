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

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT "PRACOWNIK",
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            password_changed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY users_email_unique (email),
            INDEX users_role_index (role),
            INDEX users_active_index (is_active)
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci'
    );

    $countStatement = $pdo->query(
        'SELECT COUNT(*) FROM users'
    );

    $userCount = $countStatement !== false
        ? (int) $countStatement->fetchColumn()
        : 0;

    if ($userCount > 0) {
        echo "OK: tabela users istnieje."
            . PHP_EOL;
        echo "OK: istniejący użytkownicy: "
            . $userCount
            . PHP_EOL;
        echo "Migracja nie zmieniła istniejących kont."
            . PHP_EOL;

        exit(0);
    }

    $email = strtolower(
        trim(
            (string) (
                Env::get(
                    'ADMIN_EMAIL',
                    ''
                )
                ?? ''
            )
        )
    );

    if (
        $email === ''
        || $email === 'admin@example.com'
        || filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) === false
    ) {
        throw new RuntimeException(
            'Nie można utworzyć pierwszego Administratora. '
            . 'Ustaw prawidłowy ADMIN_EMAIL w lokalnym .env.'
        );
    }

    $passwordHash = trim(
        (string) (
            Env::get(
                'ADMIN_PASSWORD_HASH',
                ''
            )
            ?? ''
        )
    );

    $hashInfo = $passwordHash !== ''
        ? password_get_info($passwordHash)
        : [];

    $hasValidHash =
        isset($hashInfo['algoName'])
        && is_string($hashInfo['algoName'])
        && $hashInfo['algoName'] !== 'unknown';

    if (!$hasValidHash) {
        $plainPassword = (string) (
            Env::get(
                'ADMIN_PASSWORD',
                ''
            )
            ?? ''
        );

        if (
            trim($plainPassword) === ''
            || $plainPassword
                === 'CHANGE_ME_STRONG_PASSWORD'
        ) {
            throw new RuntimeException(
                'Nie można utworzyć pierwszego Administratora. '
                . 'Ustaw ADMIN_PASSWORD_HASH albo ADMIN_PASSWORD '
                . 'w lokalnym .env.'
            );
        }

        $passwordHash = password_hash(
            $plainPassword,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Nie udało się bezpiecznie zahashować hasła '
                . 'pierwszego Administratora.'
            );
        }
    }

    $insert = $pdo->prepare(
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
            "ADMIN",
            1,
            NOW()
        )'
    );

    $insert->execute([
        'name' => 'Administrator',
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);

    echo "OK: utworzono tabelę users."
        . PHP_EOL;
    echo "OK: przeniesiono dotychczasowe konto do bazy."
        . PHP_EOL;
    echo "Administrator: "
        . $email
        . PHP_EOL;
    echo "Hasło nie zostało wyświetlone ani zapisane jawnie."
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
