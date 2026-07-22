# Backup i odtwarzanie — Domki Sztabinki PMS

## Co zawiera backup

Każdy backup zawiera:

- pełny zrzut bazy MySQL/MariaDB w `database.sql`,
- pliki z `public/uploads`, jeżeli katalog istnieje,
- pliki z `storage/uploads`, jeżeli katalog istnieje,
- `manifest.json` z listą plików, rozmiarami i sumami SHA-256.

Backupy są zapisywane w:

```text
storage/backups/backup-RRRRMMDD-GGMMSS-xxxxxx
```

Katalog `storage/backups` nie powinien być publicznie dostępny.

## Ręczne utworzenie backupu

Z katalogu `apps/home-php` lub przez pełną ścieżkę:

```bash
php bin/create-backup.php
```

W projekcie uruchamianym z katalogu repozytorium:

```bash
php.exe apps/home-php/bin/create-backup.php
```

## Weryfikacja najnowszego backupu

```bash
php.exe apps/home-php/bin/verify-backup.php
```

Weryfikacja konkretnego backupu:

```bash
php.exe apps/home-php/bin/verify-backup.php \
  --path="apps/home-php/storage/backups/backup-RRRRMMDD-GGMMSS-xxxxxx"
```

## Retencja

Domyślnie backupy starsze niż 30 dni są automatycznie usuwane.

Można zmienić okres retencji w `.env`:

```text
BACKUP_RETENTION_DAYS=30
```

## Kopia poza serwerem

Można ustawić dodatkowy katalog kopii:

```text
BACKUP_COPY_DIRECTORY=/mnt/backup/domki-sztabinki
```

Na Windows może to być na przykład:

```text
BACKUP_COPY_DIRECTORY=D:/Backups/DomkiSztabinki
```

Dla prawdziwego zabezpieczenia produkcyjnego ten katalog powinien znajdować się na innym nośniku, serwerze albo zamontowanym zdalnym zasobie.

Po utworzeniu backupu system:

1. tworzy lokalną kopię,
2. weryfikuje sumy SHA-256,
3. kopiuje backup do katalogu zewnętrznego,
4. ponownie weryfikuje zewnętrzną kopię,
5. dopiero wtedy uznaje operację za zakończoną.

## CRON produkcyjny

Przykład codziennego backupu o 02:15:

```cron
15 2 * * * /usr/bin/php /pełna/ścieżka/apps/home-php/bin/create-backup.php >> /pełna/ścieżka/apps/home-php/storage/logs/backup.log 2>&1
```

Należy użyć rzeczywistej ścieżki PHP i aplikacji na serwerze.

## Odtwarzanie

Odtwarzanie jest celowo zabezpieczone wymaganym parametrem `--confirm=RESTORE`.

Przed właściwym odtworzeniem skrypt automatycznie tworzy dodatkowy backup aktualnego stanu systemu.

Przykład:

```bash
php.exe apps/home-php/bin/restore-backup.php \
  --path="apps/home-php/storage/backups/backup-RRRRMMDD-GGMMSS-xxxxxx" \
  --confirm=RESTORE
```

Proces:

1. weryfikuje manifest i sumy SHA-256,
2. tworzy backup bezpieczeństwa aktualnego stanu,
3. odtwarza strukturę i dane MySQL/MariaDB,
4. odtwarza katalogi uploadów.

## Test odtwarzania

Na produkcji nie należy testować odtwarzania bezpośrednio na działającej bazie.

Prawidłowy test:

1. utworzyć osobną testową bazę MySQL/MariaDB,
2. skonfigurować kopię aplikacji z osobnym `.env`,
3. uruchomić `restore-backup.php` na tej kopii,
4. sprawdzić logowanie, domki, rezerwacje, gości, faktury i zdjęcia,
5. powtarzać test odtwarzania okresowo.

Backup bez regularnego testu odtwarzania nie powinien być uznawany za w pełni sprawdzony.
