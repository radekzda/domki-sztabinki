# Domki Sztabinki PMS — instrukcja wdrożenia produkcyjnego

## 1. Cel

Ten dokument opisuje podstawowe kroki wdrożenia aplikacji Domki Sztabinki PMS na środowisko produkcyjne.

Projekt działa jako monorepo pnpm workspace.

Główny katalog repozytorium:

```txt
domki-sztabinki
```

Aplikacja web znajduje się w:

```txt
domki-sztabinki/apps/web
```

Lokalnie w naszym środowisku wygląda to tak:

```txt
D:/StronaDomkiSztabinki/domki-sztabinki
D:/StronaDomkiSztabinki/domki-sztabinki/apps/web
```

## 2. Wymagania

Na serwerze produkcyjnym powinny być dostępne:

```txt
Node.js
pnpm 9.0.0
PostgreSQL / Neon
Git
```

Projekt używa:

```txt
Next.js 16
React 19
Prisma 5
PostgreSQL
Tailwind CSS 4
pnpm workspace
Turbo
```

## 3. Zmienne środowiskowe

W środowisku produkcyjnym trzeba ustawić zmienne z pliku:

```txt
apps/web/.env.example
```

Wymagane zmienne:

```env
DATABASE_URL="postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require"
NEXT_PUBLIC_APP_URL="https://twoja-domena.pl"
ADMIN_PASSWORD="mocne_haslo_administratora"
ADMIN_SESSION_SECRET="dlugi_losowy_ciag_64_hex"
```

Sekret administratora można wygenerować lokalnie komendą:

```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

Nigdy nie należy commitować pliku `.env`.

## 4. Instalacja zależności

Zależności instalujemy z głównego katalogu repozytorium, a nie z katalogu `apps/web`.

Główny katalog repozytorium to:

```txt
domki-sztabinki
```

Czyli lokalnie u nas:

```txt
D:/StronaDomkiSztabinki/domki-sztabinki
```

Aplikacja web znajduje się niżej:

```txt
domki-sztabinki/apps/web
```

### Wariant A — terminal jest w głównym katalogu repozytorium

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki
```

uruchom:

```bash
pnpm install --frozen-lockfile
```

### Wariant B — terminal jest w katalogu aplikacji web

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki/apps/web
```

uruchom:

```bash
pnpm -C ../.. install --frozen-lockfile
```

Ta komenda mówi `pnpm`, żeby wykonał instalację dwa katalogi wyżej, czyli w głównym katalogu repozytorium.

Nie uruchamiaj zwykłego:

```bash
pnpm install
```

z katalogu `apps/web`, jeżeli chcesz instalować zależności całego workspace.

Jeżeli lokalnie na Windows wystąpi błąd symlinków `EPERM`, najczęściej jest to problem lokalnych uprawnień, blokady plików przez system, antywirusa albo otwartego procesu. Na serwerze Linux zwykle nie powinno to występować.

## 5. Migracje bazy danych

Przed startem aplikacji na produkcji należy wykonać migracje Prisma.

### Jeżeli terminal jest w katalogu aplikacji web

```txt
domki-sztabinki/apps/web
```

uruchom:

```bash
pnpm run db:migrate:deploy
```

Sprawdzenie statusu migracji:

```bash
pnpm run db:migrate:status
```

### Jeżeli terminal jest w głównym katalogu repozytorium

```txt
domki-sztabinki
```

uruchom:

```bash
pnpm -C apps/web run db:migrate:deploy
```

Sprawdzenie statusu migracji:

```bash
pnpm -C apps/web run db:migrate:status
```

Oczekiwany wynik:

```txt
Database schema is up to date!
```

## 6. Build produkcyjny

### Build z głównego katalogu repozytorium

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki
```

uruchom:

```bash
pnpm build
```

### Build z katalogu aplikacji web

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki/apps/web
```

uruchom:

```bash
pnpm build
```

Build aplikacji web automatycznie wykonuje:

```bash
prisma generate --schema=./prisma/schema.prisma
```

## 7. Start aplikacji

Start aplikacji wykonujemy z katalogu aplikacji web.

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki/apps/web
```

uruchom:

```bash
pnpm start
```

Jeżeli terminal jest w głównym katalogu repozytorium:

```txt
domki-sztabinki
```

uruchom:

```bash
pnpm -C apps/web start
```

Aplikacja domyślnie działa jako aplikacja Next.js.

## 8. Panel administratora

Panel administratora znajduje się pod adresem:

```txt
/admin
```

Logowanie znajduje się pod adresem:

```txt
/logowanie
```

Panel admina i eksporty CSV są zabezpieczone sesją administratora.

Wymagane zmienne:

```env
ADMIN_PASSWORD
ADMIN_SESSION_SECRET
```

Po zmianie `ADMIN_SESSION_SECRET` wszystkie stare sesje administratora przestają działać i trzeba zalogować się ponownie.

## 9. Zdjęcia i uploady

Zdjęcia domków są zapisywane lokalnie do katalogu:

```txt
apps/web/public/uploads
```

Ten katalog jest ignorowany przez Git.

Przy wdrożeniu na nowy serwer trzeba osobno przenieść katalog:

```txt
apps/web/public/uploads
```

do takiej samej lokalizacji na serwerze produkcyjnym:

```txt
domki-sztabinki/apps/web/public/uploads
```

Jeżeli katalog nie zostanie przeniesiony, aplikacja będzie działać, ale zdjęcia zapisane wcześniej w bazie mogą się nie wyświetlać.

Docelowo warto rozważyć zewnętrzny storage na zdjęcia, na przykład S3, Cloudflare R2 albo inny kompatybilny storage.

## 10. Ważne ścieżki

```txt
package.json
pnpm-lock.yaml
pnpm-workspace.yaml
apps/web/package.json
apps/web/.env.example
apps/web/prisma/schema.prisma
apps/web/prisma/migrations
apps/web/public/uploads
```

## 11. Komendy kontrolne przed wdrożeniem

### Z katalogu aplikacji web

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki/apps/web
```

uruchom:

```bash
git status --short
pnpm run db:generate
pnpm run db:migrate:status
pnpm build
```

### Z głównego katalogu repozytorium

Jeżeli terminal jest tutaj:

```txt
domki-sztabinki
```

uruchom:

```bash
git status --short
pnpm -C apps/web run db:generate
pnpm -C apps/web run db:migrate:status
pnpm build
```

## 12. Checklist przed produkcją

Przed uruchomieniem produkcji sprawdzić:

```txt
[ ] DATABASE_URL ustawiony na produkcyjną bazę
[ ] ADMIN_PASSWORD ustawiony na mocne hasło
[ ] ADMIN_SESSION_SECRET wygenerowany jako nowy losowy sekret
[ ] NEXT_PUBLIC_APP_URL ustawiony na produkcyjny adres strony
[ ] zależności zainstalowane z głównego katalogu repo
[ ] migracje Prisma wykonane
[ ] pnpm build przechodzi
[ ] /admin wymaga logowania
[ ] eksporty CSV bez logowania nie pobierają plików
[ ] katalog public/uploads przeniesiony na serwer, jeśli używane są lokalne zdjęcia
[ ] .env nie jest commitowany do Git
```