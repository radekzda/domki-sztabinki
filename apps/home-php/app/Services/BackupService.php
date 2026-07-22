<?php

declare(strict_types=1);

final class BackupService
{
    private const BACKUP_PREFIX = 'backup-';

    public static function create(): array
    {
        $root = self::rootPath();
        $backupRoot = self::backupRoot();
        $logsPath = $root . '/storage/logs';

        self::ensureDirectory($backupRoot);
        self::ensureDirectory($logsPath);

        $lockPath = $logsPath . '/backup.lock';
        $lockHandle = fopen($lockPath, 'c');

        if ($lockHandle === false) {
            throw new RuntimeException(
                'Nie udało się otworzyć pliku blokady backupu.'
            );
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);

            throw new RuntimeException(
                'Backup jest już wykonywany przez inny proces.'
            );
        }

        $timestamp = date('Ymd-His');
        $suffix = bin2hex(random_bytes(3));
        $backupName = self::BACKUP_PREFIX
            . $timestamp
            . '-'
            . $suffix;

        $temporaryPath = $backupRoot
            . '/.tmp-'
            . $backupName;

        $finalPath = $backupRoot
            . '/'
            . $backupName;

        try {
            self::ensureDirectory($temporaryPath);

            $databasePath = $temporaryPath
                . '/database.sql';

            $databaseStats = self::dumpDatabase(
                $databasePath
            );

            $uploadRoots = [
                'public_uploads' => $root
                    . '/public/uploads',
                'storage_uploads' => $root
                    . '/storage/uploads',
            ];

            $copiedUploadRoots = [];

            foreach ($uploadRoots as $key => $sourcePath) {
                if (!is_dir($sourcePath)) {
                    continue;
                }

                $targetPath = $temporaryPath
                    . '/uploads/'
                    . $key;

                self::copyDirectory(
                    $sourcePath,
                    $targetPath
                );

                $copiedUploadRoots[$key] = [
                    'source' => $sourcePath,
                    'backup_relative_path' =>
                        'uploads/' . $key,
                ];
            }

            $files = self::collectFileMetadata(
                $temporaryPath,
                [
                    'manifest.json',
                ]
            );

            $manifest = [
                'version' => 1,
                'created_at' => date(DATE_ATOM),
                'backup_name' => $backupName,
                'database' => [
                    'name' => (string) (
                        Env::get(
                            'DB_DATABASE',
                            ''
                        )
                        ?? ''
                    ),
                    'tables' =>
                        $databaseStats['tables'],
                    'rows' =>
                        $databaseStats['rows'],
                    'file' => 'database.sql',
                ],
                'uploads' => $copiedUploadRoots,
                'files_count' => count($files),
                'files_total_bytes' =>
                    array_sum(
                        array_column(
                            $files,
                            'size'
                        )
                    ),
                'files' => $files,
            ];

            $manifestPath = $temporaryPath
                . '/manifest.json';

            $encodedManifest = json_encode(
                $manifest,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

            if (!is_string($encodedManifest)) {
                throw new RuntimeException(
                    'Nie udało się utworzyć manifestu backupu.'
                );
            }

            if (
                file_put_contents(
                    $manifestPath,
                    $encodedManifest
                    . PHP_EOL
                ) === false
            ) {
                throw new RuntimeException(
                    'Nie udało się zapisać manifestu backupu.'
                );
            }

            $verification = self::verify(
                $temporaryPath
            );

            if (!$verification['valid']) {
                throw new RuntimeException(
                    'Weryfikacja backupu nie powiodła się: '
                    . implode(
                        '; ',
                        $verification['errors']
                    )
                );
            }

            if (
                !rename(
                    $temporaryPath,
                    $finalPath
                )
            ) {
                throw new RuntimeException(
                    'Nie udało się sfinalizować katalogu backupu.'
                );
            }

            $copyPath = self::copyToExternalLocation(
                $finalPath,
                $backupName
            );

            self::cleanupOldBackups(
                $backupRoot
            );

            if ($copyPath !== null) {
                self::cleanupOldBackups(
                    dirname($copyPath)
                );
            }

            return [
                'path' => $finalPath,
                'name' => $backupName,
                'tables' =>
                    $databaseStats['tables'],
                'rows' =>
                    $databaseStats['rows'],
                'files_count' =>
                    $manifest['files_count'],
                'files_total_bytes' =>
                    $manifest['files_total_bytes'],
                'external_copy_path' =>
                    $copyPath,
            ];
        } catch (Throwable $exception) {
            if (is_dir($temporaryPath)) {
                self::removeDirectory(
                    $temporaryPath
                );
            }

            throw $exception;
        } finally {
            flock(
                $lockHandle,
                LOCK_UN
            );

            fclose($lockHandle);
        }
    }

    public static function verify(
        string $backupPath
    ): array {
        $backupPath = rtrim(
            $backupPath,
            '/\\'
        );

        $errors = [];

        if (!is_dir($backupPath)) {
            return [
                'valid' => false,
                'errors' => [
                    'Katalog backupu nie istnieje.',
                ],
                'files_checked' => 0,
            ];
        }

        $manifestPath = $backupPath
            . '/manifest.json';

        $databasePath = $backupPath
            . '/database.sql';

        if (!is_file($manifestPath)) {
            $errors[] =
                'Brakuje pliku manifest.json.';
        }

        if (!is_file($databasePath)) {
            $errors[] =
                'Brakuje pliku database.sql.';
        }

        if ($errors !== []) {
            return [
                'valid' => false,
                'errors' => $errors,
                'files_checked' => 0,
            ];
        }

        $manifestContents = file_get_contents(
            $manifestPath
        );

        if ($manifestContents === false) {
            return [
                'valid' => false,
                'errors' => [
                    'Nie udało się odczytać manifestu.',
                ],
                'files_checked' => 0,
            ];
        }

        $manifest = json_decode(
            $manifestContents,
            true
        );

        if (!is_array($manifest)) {
            return [
                'valid' => false,
                'errors' => [
                    'Manifest nie jest poprawnym JSON.',
                ],
                'files_checked' => 0,
            ];
        }

        $files = $manifest['files']
            ?? null;

        if (!is_array($files)) {
            return [
                'valid' => false,
                'errors' => [
                    'Manifest nie zawiera listy plików.',
                ],
                'files_checked' => 0,
            ];
        }

        $checked = 0;

        foreach ($files as $file) {
            if (!is_array($file)) {
                $errors[] =
                    'Nieprawidłowy wpis pliku w manifeście.';

                continue;
            }

            $relativePath = (string) (
                $file['path']
                ?? ''
            );

            $expectedSize = (int) (
                $file['size']
                ?? -1
            );

            $expectedHash = (string) (
                $file['sha256']
                ?? ''
            );

            if (
                $relativePath === ''
                || str_contains(
                    $relativePath,
                    '..'
                )
            ) {
                $errors[] =
                    'Nieprawidłowa ścieżka pliku w manifeście.';

                continue;
            }

            $path = $backupPath
                . '/'
                . str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );

            if (!is_file($path)) {
                $errors[] =
                    'Brakuje pliku: '
                    . $relativePath;

                continue;
            }

            $actualSize = filesize($path);

            if (
                $actualSize === false
                || $actualSize !== $expectedSize
            ) {
                $errors[] =
                    'Nieprawidłowy rozmiar pliku: '
                    . $relativePath;

                continue;
            }

            $actualHash = hash_file(
                'sha256',
                $path
            );

            if (
                !is_string($actualHash)
                || !hash_equals(
                    $expectedHash,
                    $actualHash
                )
            ) {
                $errors[] =
                    'Nieprawidłowa suma SHA-256: '
                    . $relativePath;

                continue;
            }

            $checked++;
        }

        if (
            is_file($databasePath)
            && filesize($databasePath) === 0
        ) {
            $errors[] =
                'Plik database.sql jest pusty.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'files_checked' => $checked,
        ];
    }

    public static function latestBackupPath(
        ?string $basePath = null
    ): ?string {
        $basePath = $basePath !== null
            ? rtrim(
                $basePath,
                '/\\'
            )
            : self::backupRoot();

        if (!is_dir($basePath)) {
            return null;
        }

        $candidates = [];

        $iterator = new DirectoryIterator(
            $basePath
        );

        foreach ($iterator as $item) {
            if (
                $item->isDot()
                || !$item->isDir()
            ) {
                continue;
            }

            $name = $item->getFilename();

            if (
                !str_starts_with(
                    $name,
                    self::BACKUP_PREFIX
                )
            ) {
                continue;
            }

            $candidates[
                $item->getMTime()
            ] = $item->getPathname();
        }

        if ($candidates === []) {
            return null;
        }

        krsort(
            $candidates,
            SORT_NUMERIC
        );

        return reset($candidates)
            ?: null;
    }

    public static function restore(
        string $backupPath
    ): array {
        $verification = self::verify(
            $backupPath
        );

        if (!$verification['valid']) {
            throw new RuntimeException(
                'Backup nie przeszedł weryfikacji: '
                . implode(
                    '; ',
                    $verification['errors']
                )
            );
        }

        $safetyBackup = self::create();

        $databasePath = rtrim(
            $backupPath,
            '/\\'
        )
            . '/database.sql';

        $statements = self::restoreDatabase(
            $databasePath
        );

        self::restoreUploads(
            $backupPath
        );

        return [
            'statements' => $statements,
            'safety_backup_path' =>
                $safetyBackup['path'],
        ];
    }

    private static function dumpDatabase(
        string $path
    ): array {
        $connection = Database::connection();

        $handle = fopen(
            $path,
            'wb'
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Nie udało się utworzyć database.sql.'
            );
        }

        $tablesCount = 0;
        $rowsCount = 0;

        try {
            fwrite(
                $handle,
                '-- Domki Sztabinki PMS backup'
                . PHP_EOL
            );

            fwrite(
                $handle,
                'SET NAMES utf8mb4;'
                . PHP_EOL
            );

            fwrite(
                $handle,
                "SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'NO_BACKSLASH_ESCAPES', '');"
                . PHP_EOL
            );

            fwrite(
                $handle,
                'SET FOREIGN_KEY_CHECKS=0;'
                . PHP_EOL
            );

            $connection->exec(
                'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ'
            );

            $connection->exec(
                'START TRANSACTION WITH CONSISTENT SNAPSHOT'
            );

            $tableStatement = $connection->query(
                "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"
            );

            $tables = [];

            while (
                $tableStatement !== false
                && (
                    $row = $tableStatement->fetch(
                        PDO::FETCH_NUM
                    )
                ) !== false
            ) {
                if (
                    isset($row[0])
                    && is_string($row[0])
                ) {
                    $tables[] = $row[0];
                }
            }

            sort(
                $tables,
                SORT_STRING
            );

            foreach ($tables as $table) {
                $quotedTable = self::quoteIdentifier(
                    $table
                );

                $createStatement = $connection->query(
                    'SHOW CREATE TABLE '
                    . $quotedTable
                );

                $createRow = $createStatement !== false
                    ? $createStatement->fetch(
                        PDO::FETCH_NUM
                    )
                    : false;

                if (
                    !is_array($createRow)
                    || !isset($createRow[1])
                ) {
                    throw new RuntimeException(
                        'Nie udało się pobrać definicji tabeli: '
                        . $table
                    );
                }

                $createSql = preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $createRow[1]
                );

                fwrite(
                    $handle,
                    'DROP TABLE IF EXISTS '
                    . $quotedTable
                    . ';'
                    . PHP_EOL
                );

                fwrite(
                    $handle,
                    $createSql
                    . ';'
                    . PHP_EOL
                );

                $dataStatement = $connection->query(
                    'SELECT * FROM '
                    . $quotedTable
                );

                if ($dataStatement === false) {
                    throw new RuntimeException(
                        'Nie udało się odczytać danych tabeli: '
                        . $table
                    );
                }

                $batch = [];
                $columns = [];

                while (
                    (
                        $dataRow =
                            $dataStatement->fetch(
                                PDO::FETCH_ASSOC
                            )
                    ) !== false
                ) {
                    if ($columns === []) {
                        $columns = array_keys(
                            $dataRow
                        );
                    }

                    $values = [];

                    foreach (
                        $columns
                        as $column
                    ) {
                        $value = $dataRow[
                            $column
                        ] ?? null;

                        $values[] =
                            self::sqlValue(
                                $value
                            );
                    }

                    $batch[] =
                        '('
                        . implode(
                            ',',
                            $values
                        )
                        . ')';

                    $rowsCount++;

                    if (
                        count($batch)
                        >= 100
                    ) {
                        self::writeInsertBatch(
                            $handle,
                            $quotedTable,
                            $columns,
                            $batch
                        );

                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    self::writeInsertBatch(
                        $handle,
                        $quotedTable,
                        $columns,
                        $batch
                    );
                }

                $tablesCount++;
            }

            $connection->commit();

            fwrite(
                $handle,
                'SET FOREIGN_KEY_CHECKS=1;'
                . PHP_EOL
            );
        } catch (Throwable $exception) {
            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            throw $exception;
        } finally {
            fclose($handle);
        }

        return [
            'tables' => $tablesCount,
            'rows' => $rowsCount,
        ];
    }

    private static function restoreDatabase(
        string $databasePath
    ): int {
        $handle = fopen(
            $databasePath,
            'rb'
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Nie udało się otworzyć database.sql.'
            );
        }

        $connection = Database::connection();
        $executed = 0;

        try {
            while (
                (
                    $line = fgets($handle)
                ) !== false
            ) {
                $statement = trim(
                    $line
                );

                if (
                    $statement === ''
                    || str_starts_with(
                        $statement,
                        '--'
                    )
                ) {
                    continue;
                }

                $connection->exec(
                    $statement
                );

                $executed++;
            }
        } finally {
            fclose($handle);
        }

        return $executed;
    }

    private static function restoreUploads(
        string $backupPath
    ): void {
        $root = self::rootPath();

        $mappings = [
            'uploads/public_uploads' =>
                $root . '/public/uploads',
            'uploads/storage_uploads' =>
                $root . '/storage/uploads',
        ];

        foreach (
            $mappings
            as $relativeSource => $target
        ) {
            $source = rtrim(
                $backupPath,
                '/\\'
            )
                . '/'
                . $relativeSource;

            if (!is_dir($source)) {
                continue;
            }

            if (is_dir($target)) {
                self::removeDirectory(
                    $target
                );
            }

            self::copyDirectory(
                $source,
                $target
            );
        }
    }

    private static function writeInsertBatch(
        $handle,
        string $quotedTable,
        array $columns,
        array $batch
    ): void {
        if (
            $columns === []
            || $batch === []
        ) {
            return;
        }

        $quotedColumns = array_map(
            static fn (
                string $column
            ): string =>
                self::quoteIdentifier(
                    $column
                ),
            $columns
        );

        fwrite(
            $handle,
            'INSERT INTO '
            . $quotedTable
            . ' ('
            . implode(
                ',',
                $quotedColumns
            )
            . ') VALUES '
            . implode(
                ',',
                $batch
            )
            . ';'
            . PHP_EOL
        );
    }

    private static function sqlValue(
        mixed $value
    ): string {
        if ($value === null) {
            return 'NULL';
        }

        $value = (string) $value;

        $value = str_replace(
            [
                '\\',
                "\0",
                "\n",
                "\r",
                "\x1a",
                "'",
            ],
            [
                '\\\\',
                '\\0',
                '\\n',
                '\\r',
                '\\Z',
                "\\'",
            ],
            $value
        );

        return "'"
            . $value
            . "'";
    }

    private static function quoteIdentifier(
        string $identifier
    ): string {
        return '`'
            . str_replace(
                '`',
                '``',
                $identifier
            )
            . '`';
    }

    private static function collectFileMetadata(
        string $directory,
        array $excludedRelativePaths
    ): array {
        $directory = rtrim(
            $directory,
            '/\\'
        );

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $path = $item->getPathname();

            $relativePath = str_replace(
                '\\',
                '/',
                substr(
                    $path,
                    strlen($directory) + 1
                )
            );

            if (
                in_array(
                    $relativePath,
                    $excludedRelativePaths,
                    true
                )
            ) {
                continue;
            }

            $hash = hash_file(
                'sha256',
                $path
            );

            if (!is_string($hash)) {
                throw new RuntimeException(
                    'Nie udało się obliczyć SHA-256: '
                    . $relativePath
                );
            }

            $size = filesize($path);

            if ($size === false) {
                throw new RuntimeException(
                    'Nie udało się odczytać rozmiaru pliku: '
                    . $relativePath
                );
            }

            $files[] = [
                'path' => $relativePath,
                'size' => $size,
                'sha256' => $hash,
            ];
        }

        usort(
            $files,
            static fn (
                array $left,
                array $right
            ): int =>
                strcmp(
                    (string) $left['path'],
                    (string) $right['path']
                )
        );

        return $files;
    }

    private static function copyToExternalLocation(
        string $finalPath,
        string $backupName
    ): ?string {
        $externalRoot = trim(
            (string) (
                Env::get(
                    'BACKUP_COPY_DIRECTORY',
                    ''
                )
                ?? ''
            )
        );

        if ($externalRoot === '') {
            return null;
        }

        $externalRoot = rtrim(
            $externalRoot,
            '/\\'
        );

        self::ensureDirectory(
            $externalRoot
        );

        $temporaryPath = $externalRoot
            . '/.tmp-'
            . $backupName;

        $finalCopyPath = $externalRoot
            . '/'
            . $backupName;

        if (is_dir($temporaryPath)) {
            self::removeDirectory(
                $temporaryPath
            );
        }

        self::copyDirectory(
            $finalPath,
            $temporaryPath
        );

        $verification = self::verify(
            $temporaryPath
        );

        if (!$verification['valid']) {
            self::removeDirectory(
                $temporaryPath
            );

            throw new RuntimeException(
                'Zewnętrzna kopia backupu nie przeszła weryfikacji.'
            );
        }

        if (
            !rename(
                $temporaryPath,
                $finalCopyPath
            )
        ) {
            self::removeDirectory(
                $temporaryPath
            );

            throw new RuntimeException(
                'Nie udało się sfinalizować zewnętrznej kopii backupu.'
            );
        }

        return $finalCopyPath;
    }

    private static function cleanupOldBackups(
        string $directory
    ): void {
        $retentionDays = (int) (
            Env::get(
                'BACKUP_RETENTION_DAYS',
                '30'
            )
            ?? '30'
        );

        if ($retentionDays < 1) {
            $retentionDays = 30;
        }

        $threshold = time()
            - (
                $retentionDays
                * 86400
            );

        if (!is_dir($directory)) {
            return;
        }

        $iterator = new DirectoryIterator(
            $directory
        );

        foreach ($iterator as $item) {
            if (
                $item->isDot()
                || !$item->isDir()
            ) {
                continue;
            }

            $name = $item->getFilename();

            if (
                !str_starts_with(
                    $name,
                    self::BACKUP_PREFIX
                )
            ) {
                continue;
            }

            if (
                $item->getMTime()
                >= $threshold
            ) {
                continue;
            }

            self::removeDirectory(
                $item->getPathname()
            );
        }
    }

    private static function copyDirectory(
        string $source,
        string $target
    ): void {
        if (!is_dir($source)) {
            return;
        }

        self::ensureDirectory(
            $target
        );

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr(
                $item->getPathname(),
                strlen(
                    rtrim(
                        $source,
                        '/\\'
                    )
                ) + 1
            );

            $destination = rtrim(
                $target,
                '/\\'
            )
                . DIRECTORY_SEPARATOR
                . $relativePath;

            if ($item->isLink()) {
                continue;
            }

            if ($item->isDir()) {
                self::ensureDirectory(
                    $destination
                );

                continue;
            }

            self::ensureDirectory(
                dirname(
                    $destination
                )
            );

            if (
                !copy(
                    $item->getPathname(),
                    $destination
                )
            ) {
                throw new RuntimeException(
                    'Nie udało się skopiować pliku: '
                    . $item->getPathname()
                );
            }
        }
    }

    private static function removeDirectory(
        string $directory
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (
                $item->isDir()
                && !$item->isLink()
            ) {
                rmdir(
                    $item->getPathname()
                );

                continue;
            }

            unlink(
                $item->getPathname()
            );
        }

        rmdir(
            $directory
        );
    }

    private static function ensureDirectory(
        string $directory
    ): void {
        if (is_dir($directory)) {
            return;
        }

        if (
            !mkdir(
                $directory,
                0775,
                true
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Nie udało się utworzyć katalogu: '
                . $directory
            );
        }
    }

    private static function backupRoot(): string
    {
        return self::rootPath()
            . '/storage/backups';
    }

    private static function rootPath(): string
    {
        return dirname(
            __DIR__,
            2
        );
    }
}
