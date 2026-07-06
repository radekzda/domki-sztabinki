# Domki Sztabinki PMS — PROJECT_STATE

## Data stanu projektu

Aktualny etap: po module **M4.10.7 — aktualizacja dokumentacji PROJECT_STATE.md**.

Projekt jest rozwijany jako profesjonalny system PMS do zarządzania wynajmem domków letniskowych Domki Sztabinki.

Źródłem prawdy jest aktualny lokalny projekt Git oraz GitHub.

Nie używać ZIP jako źródła prawdy projektu.

---

# Zasady pracy w projekcie

1. Zawsze podawaj całe pliki.
2. Nigdy nie podawaj fragmentów kodu.
3. Nie używaj `...` ani nie pomijaj kodu.
4. Jeżeli zmieniasz kilka plików, zawsze podawaj komplet wszystkich zmienionych plików.
5. Jedna odpowiedź = jedno logiczne zadanie.
6. Nie przechodź do kolejnego zadania, dopóki użytkownik nie potwierdzi, że poprzednie działa.
7. Każda odpowiedź techniczna kończy się sekcjami:
   - TEST
   - GIT
8. Nie przebudowuj działających modułów bez potrzeby.
9. Projekt rozwijamy krok po kroku do wdrożenia produkcyjnego.
10. Interfejs ma być prosty, jasny, biały i profesjonalny.
11. Nie generuj obrazów, chyba że użytkownik wyraźnie o to poprosi.
12. Nie używaj `git add .`.
13. Nie dodawaj eksportu historii pojedynczego gościa.
14. Nie powtarzaj bez potrzeby komendy `cd /d/StronaDomkiSztabinki/domki-sztabinki/apps/web`, bo użytkownik pracuje już z katalogu `apps/web`.
15. Przy nowych plikach podawaj jasną ścieżkę i pełną zawartość pliku.
16. Nie używaj domyślnie Bash heredoc do tworzenia plików, chyba że użytkownik wyraźnie o to poprosi albo jest to najbezpieczniejsze rozwiązanie.

---

# Stack technologiczny

- Next.js 16
- React 19
- TypeScript
- Prisma
- PostgreSQL / Neon
- Tailwind CSS 4
- pnpm
- App Router
- Server Actions
- Turborepo / monorepo

---

# Struktura projektu

Repozytorium główne:

```txt
D:/StronaDomkiSztabinki/domki-sztabinki
```

Główna aplikacja web:

```txt
D:/StronaDomkiSztabinki/domki-sztabinki/apps/web
```

Praca odbywa się głównie z katalogu:

```txt
D:/StronaDomkiSztabinki/domki-sztabinki/apps/web
```

Uruchamianie projektu lokalnie:

```bash
pnpm dev
```

Adres aplikacji:

```txt
http://localhost:3000
```

Prisma Studio:

```bash
npx prisma studio
```

Adres Prisma Studio:

```txt
http://localhost:5555
```

W razie problemów z Prisma Studio:

```bash
Ctrl + C
npx prisma generate
npx prisma studio
```

W przeglądarce:

```txt
Ctrl + F5
```

---

# Monorepo

Projekt ma konfigurację monorepo na poziomie głównego katalogu repozytorium.

Ważne pliki główne:

```txt
package.json
pnpm-workspace.yaml
pnpm-lock.yaml
tsconfig.base.json
turbo.json
.gitignore
```

Wspólne paczki:

```txt
packages/utils/cn.ts
```

Helper `cn`:

```ts
import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: any[]) {
  return twMerge(clsx(inputs));
}
```

Root `package.json` zawiera skrypty:

```json
{
  "name": "domki-sztabinki",
  "private": true,
  "packageManager": "pnpm@9.0.0",
  "scripts": {
    "dev": "turbo dev",
    "build": "turbo build",
    "lint": "turbo lint"
  },
  "devDependencies": {
    "turbo": "^2.0.0"
  }
}
```

Workspace:

```yaml
packages:
  - "apps/*"
  - "packages/*"
```

---

# Git i pliki lokalne

Nie używać:

```bash
git add .
```

Dodawać tylko konkretne pliki, na przykład:

```bash
git add src/app/admin/rezerwacje/page.tsx
```

Lokalne uploady zdjęć są ignorowane przez `.gitignore`.

Ignorowane są między innymi:

```txt
apps/web/public/uploads/
public/uploads/
node_modules/
.next/
.env
*.log
*.tsbuildinfo
.turbo/
```

Nie commitować lokalnych backupów Prisma, na przykład:

```txt
schema.prisma.backup
*.backup
```

---

# Aktualny schema Prisma

Plik:

```txt
apps/web/prisma/schema.prisma
```

Aktualne modele projektu:

```prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

model Cabin {
  id          String @id @default(cuid())
  name        String
  description String

  maxGuests Int
  bedrooms  Int
  bathrooms Int

  pricePerNight        Int @default(450)
  priceOneNight        Int @default(800)
  priceTwoNights       Int @default(450)
  priceThreeNights     Int @default(440)
  priceFourNights      Int @default(430)
  priceFiveNights      Int @default(420)
  priceSixNights       Int @default(410)
  priceSevenPlusNights Int @default(350)

  isActive     Boolean @default(true)
  mainImageUrl String?
  shortName    String?
  sortOrder    Int     @default(0)

  createdAt DateTime @default(now())
  updatedAt DateTime @default(now()) @updatedAt

  images       CabinImage[]
  reservations Reservation[]
}

model CabinImage {
  id        String   @id @default(cuid())
  cabinId   String
  url       String
  alt       String?
  isMain    Boolean  @default(false)
  sortOrder Int      @default(0)
  createdAt DateTime @default(now())

  cabin Cabin @relation(fields: [cabinId], references: [id], onDelete: Cascade)
}

model Reservation {
  id String @id @default(cuid())

  cabinId String
  guestId String?

  guestName String
  email     String
  phone     String?

  firstName String?
  lastName  String?

  startDate DateTime
  endDate   DateTime

  checkInAt  DateTime?
  checkOutAt DateTime?

  nights        Int      @default(1)
  pricePerNight Decimal? @db.Decimal(10, 2)

  guests   Int
  adults   Int @default(1)
  children Int @default(0)

  status String @default("PENDING")
  source String @default("MANUAL")

  totalPrice Decimal? @db.Decimal(10, 2)
  paidAmount Decimal? @db.Decimal(10, 2)

  street     String?
  postalCode String?
  city       String?
  country    String?

  notes String?

  createdAt DateTime @default(now())
  updatedAt DateTime @default(now()) @updatedAt

  cabin Cabin @relation(fields: [cabinId], references: [id])
  guest Guest? @relation(fields: [guestId], references: [id])
}

model Guest {
  id String @id @default(cuid())

  firstName String
  lastName  String

  email   String
  phone   String?
  country String?

  createdAt DateTime @default(now())

  reservations Reservation[]
}

model SystemSettings {
  id String @id @default("main")

  propertyName String @default("Domki Sztabinki")

  ownerName  String?
  ownerEmail String?
  ownerPhone String?

  street     String?
  postalCode String?
  city       String?
  country    String @default("Polska")

  checkInTime  String @default("16:00")
  checkOutTime String @default("11:00")

  minimumNights Int @default(1)

  seasonStartMonth Int @default(5)
  seasonEndMonth   Int @default(9)

  websiteUrl String?

  createdAt DateTime @default(now())
  updatedAt DateTime @updatedAt
}

model User {
  id       String @id @default(cuid())
  email    String @unique
  password String
  role     String @default("ADMIN")
}
```

Po zmianach w Prisma zwykle uruchamiać:

```bash
npx prisma format
npx prisma migrate dev --name NAZWA_MIGRACJI
npx prisma generate
```

---

# Migracje Prisma

Istotne migracje dodane do repo:

```txt
20260705140426_extend_reservation_details
20260706081558_add_reservation_pricing_rules
20260706173628_add_system_settings
```

---

# Aktualne moduły w panelu admina

## Dashboard

Status: działa.

Adres:

```txt
/admin
```

Funkcje:

- kafle statystyk,
- liczba rezerwacji,
- liczba domków,
- przychody,
- szybkie akcje,
- alerty operacyjne,
- nadchodzące rezerwacje,
- prosty biały układ panelu.

Etapy:

```txt
M4.8.2 — uporządkowany dashboard
M4.8.3 — alerty operacyjne dashboardu
```

---

## Nawigacja admina

Status: działa.

Ważne pliki:

```txt
src/app/admin/layout.tsx
src/components/admin/AdminSidebar.tsx
```

Menu zawiera:

```txt
Dashboard
Rezerwacje
Goście
Domki
Kalendarz
Ustawienia
```

Aktywny link w sidebarze działa.

Etap:

```txt
M4.8.1 — uporządkowana nawigacja admina
```

---

## Domki

Status: działa.

Adresy:

```txt
/admin/domki
/admin/domki/nowy
/admin/domki/[id]/edytuj
/admin/domki/[id]/zdjecia
```

Funkcje:

- lista domków,
- dodawanie domków,
- edycja domków,
- aktywacja / ukrywanie,
- usuwanie,
- upload zdjęć,
- ustawianie zdjęcia głównego,
- sortowanie zdjęć,
- cennik domku według długości pobytu,
- pola sortowania i nazwy skróconej.

Cennik domyślny:

```txt
1 noc = 800 zł / noc
2 noce = 450 zł / noc
3 noce = 440 zł / noc
4 noce = 430 zł / noc
5 nocy = 420 zł / noc
6 nocy = 410 zł / noc
7+ nocy = 350 zł / noc
```

Ważne pliki:

```txt
src/actions/cabins.ts
src/app/admin/domki/page.tsx
src/app/admin/domki/nowy/page.tsx
src/app/admin/domki/[id]/edytuj/page.tsx
src/app/admin/domki/[id]/zdjecia/page.tsx
```

Etap porządkujący:

```txt
M4.10.2 — uporządkowane zmiany modułu domków
```

Test po M4.10.2:

```txt
/admin/domki 200
/admin/domki/nowy 200
/admin/domki/[id]/edytuj 200
```

---

## Rezerwacje

Status: działa.

Adresy:

```txt
/admin/rezerwacje
/admin/rezerwacje/nowa
/admin/rezerwacje/[id]
/admin/rezerwacje/[id]/edytuj
/admin/rezerwacje/export
```

Funkcje:

- lista rezerwacji,
- wyszukiwanie,
- filtrowanie po statusie,
- filtrowanie po źródle,
- filtrowanie po datach,
- dodawanie rezerwacji,
- edycja rezerwacji,
- szczegóły rezerwacji,
- szybka zmiana statusu,
- szybka zmiana płatności,
- automatyczne wyliczanie nocy,
- automatyczne wyliczanie ceny domyślnej,
- sprawdzanie dostępności domku,
- blokada kolizji terminów dla statusów `PENDING` i `CONFIRMED`,
- eksport CSV rezerwacji,
- powiązanie rezerwacji z gościem przez `guestId`,
- walidacja minimalnej liczby nocy z ustawień systemu.

Statusy:

```txt
PENDING
CONFIRMED
CANCELLED
COMPLETED
```

Źródła:

```txt
MANUAL
PHONE
WEBSITE
BOOKING
AIRBNB
```

Ważne pliki:

```txt
src/actions/reservations.ts
src/lib/reservations.ts
src/app/admin/rezerwacje/page.tsx
src/app/admin/rezerwacje/nowa/page.tsx
src/app/admin/rezerwacje/[id]/page.tsx
src/app/admin/rezerwacje/[id]/edytuj/page.tsx
src/app/admin/rezerwacje/export/route.ts
src/components/reservations/ReservationForm.tsx
src/components/reservations/ReservationEditForm.tsx
src/modules/pricing/pricing.utils.ts
```

Etapy:

```txt
M4.7.7 — pełne powiązanie rezerwacji z gościem przez guestId
M4.9.3 — użycie ustawień w nowej rezerwacji
M4.9.4 — serwerowa walidacja minimalnej liczby nocy
M4.9.5 — użycie ustawień w edycji rezerwacji
M4.9.6 — naprawa kolizji terminów rezerwacji
M4.10.1 — dodanie brakujących plików rezerwacji i migracji
M4.10.3 — uporządkowanie listy rezerwacji
```

Ważne zachowanie kolizji:

- kolizja sprawdzana jest dla statusów `PENDING` i `CONFIRMED`,
- edycja rezerwacji ignoruje własną rezerwację przez `ignoreReservationId`,
- sprawdzanie dostępności uwzględnia `checkInAt` i `checkOutAt`,
- jeśli `checkInAt` albo `checkOutAt` nie istnieją, fallbackiem są `startDate` i `endDate`.

Test po M4.9.6:

```txt
Edycja bez kolizji zapisuje się poprawnie.
Edycja z kolizją nie zapisuje się.
System pokazuje komunikat:
Wybrany domek jest już zarezerwowany w podanym terminie.
```

Test po M4.10.3:

```txt
/admin/rezerwacje 200
wyszukiwanie działa
szczegóły rezerwacji działają
edycja rezerwacji działa
eksport CSV działa
```

---

## Kalendarz

Status: działa.

Adres:

```txt
/admin/kalendarz
```

Funkcje:

- miesięczna oś czasu,
- rezerwacje jako belki,
- tooltip rezerwacji,
- kolory weekendów,
- wyróżnienie dzisiejszego dnia,
- filtrowanie,
- kliknięcie rezerwacji otwiera szczegóły,
- podwójne kliknięcie wolnego dnia otwiera formularz nowej rezerwacji z uzupełnionym domkiem i datami.

Ważne pliki:

```txt
src/app/admin/kalendarz/page.tsx
src/components/calendar/Calendar.tsx
src/components/calendar/CalendarLegend.tsx
src/components/calendar/CalendarToolbar.tsx
src/components/calendar/MonthNavigation.tsx
src/components/calendar/ReservationBar.tsx
src/components/calendar/ReservationLayer.tsx
src/components/calendar/ReservationTooltip.tsx
src/modules/calendar/calendar.types.ts
src/modules/calendar/calendar.utils.ts
```

Etap:

```txt
M4.10.4 — uporządkowane zmiany kalendarza
```

Test po M4.10.4:

```txt
Kalendarz działa bez błędów.
```

---

## Goście

Status: działa.

Adresy:

```txt
/admin/goscie
/admin/goscie/[id]
/admin/goscie/[id]/edytuj
/admin/goscie/export
```

Funkcje:

- automatyczne tworzenie gości z rezerwacji,
- lista gości,
- wyszukiwanie po imieniu, nazwisku, emailu, telefonie i kraju,
- szczegóły gościa,
- historia rezerwacji gościa,
- edycja danych gościa,
- aktualizacja powiązanych rezerwacji po edycji gościa,
- synchronizacja starych rezerwacji bez `guestId`,
- eksport CSV całej lub wyszukanej listy gości,
- dodawanie nowej rezerwacji bezpośrednio z karty gościa,
- przekazywanie `guestId` do formularza nowej rezerwacji.

Ważne:

Eksport historii pojedynczego gościa był zaproponowany jako M4.7.8, ale użytkownik uznał, że nie jest potrzebny.

Nie został wdrożony i nie należy go dodawać.

Ważne pliki:

```txt
src/actions/guests.ts
src/app/admin/goscie/page.tsx
src/app/admin/goscie/[id]/page.tsx
src/app/admin/goscie/[id]/edytuj/page.tsx
src/app/admin/goscie/export/route.ts
```

---

## Ustawienia systemu

Status: działa.

Adres:

```txt
/admin/ustawienia
```

Funkcje:

- dane obiektu,
- dane właściciela,
- adres obiektu,
- domyślny kraj,
- godzina zameldowania,
- godzina wymeldowania,
- minimalna liczba nocy,
- sezon od miesiąca,
- sezon do miesiąca,
- adres strony internetowej.

Ważne pliki:

```txt
src/actions/settings.ts
src/app/admin/ustawienia/page.tsx
```

Etapy:

```txt
M4.9.1 — model SystemSettings w Prisma
M4.9.2 — formularz ustawień systemu
M4.9.3 — ustawienia używane w nowej rezerwacji
M4.9.5 — ustawienia używane w edycji rezerwacji
```

Test po M4.9.2:

```txt
GET /admin/ustawienia 200
POST /admin/ustawienia 303
GET /admin/ustawienia?saved=1 200
```

---

# Aktualne etapy zakończone

## M4.8

```txt
M4.8.1 — uporządkowanie nawigacji admina
M4.8.2 — uporządkowanie dashboardu admina
M4.8.3 — alerty operacyjne dashboardu
```

## M4.9

```txt
M4.9.1 — dodanie modelu SystemSettings
M4.9.2 — formularz ustawień systemu
M4.9.3 — użycie ustawień w nowej rezerwacji
M4.9.4 — serwerowa walidacja minimalnej liczby nocy
M4.9.5 — użycie ustawień w edycji rezerwacji
M4.9.6 — naprawa kolizji terminów rezerwacji
```

## M4.10

```txt
M4.10.1 — dodanie brakujących plików rezerwacji i migracji
M4.10.2 — uporządkowanie zmian modułu domków
M4.10.3 — uporządkowanie listy rezerwacji
M4.10.4 — uporządkowanie zmian kalendarza
M4.10.5 — dodanie .gitignore dla plików lokalnych
M4.10.6 — dodanie konfiguracji monorepo
M4.10.7 — aktualizacja PROJECT_STATE.md
```

---

# Ważne ostatnie commity

Znane commity z ostatniego porządkowania:

```txt
0d39df0 M4.10.1 dodaj brakujące pliki rezerwacji i migracji
524978c M4.10.2 uporządkuj zmiany modułu domków
ef39fc4 M4.10.3 uporządkuj listę rezerwacji
```

Commity M4.10.4, M4.10.5 i M4.10.6 zostały wykonane, ale ich hashe nie są zapisane w tym pliku.

---

# Ostatnie testy pozytywne

Po M4.10.1:

```txt
/admin 200
/admin/rezerwacje 200
/admin/goscie 200
/admin/domki 200
/admin/kalendarz 200
/admin/ustawienia 200
/admin/rezerwacje/export 200
```

Po M4.10.2:

```txt
/admin/domki 200
/admin/domki/nowy 200
/admin/domki/[id]/edytuj 200
```

Po M4.10.3:

```txt
/admin/rezerwacje 200
wyszukiwanie działa
szczegóły rezerwacji działają
edycja rezerwacji działa
eksport CSV działa
```

Po M4.10.4:

```txt
Kalendarz działa bez błędów.
```

---

# Aktualny stan Git po M4.10.6

Po M4.10.6 w `git status --short` powinno zostać tylko:

```txt
?? ../../docs/
```

Po zapisaniu i commicie tego pliku `docs/PROJECT_STATE.md` status powinien być czysty.

---

# Ostatnie problemy i rozwiązania

## Prisma Studio

Pojawił się błąd:

```txt
Prisma client error Message: Unable to communicate with Prisma Client. Is Studio still running?
```

Diagnoza:

To był problem Prisma Studio, nie aplikacji PMS.

Naprawa:

```bash
Ctrl + C
npx prisma generate
npx prisma studio
```

W przeglądarce:

```txt
Ctrl + F5
```

---

## Prisma Client po dodaniu SystemSettings

Po dodaniu modelu `SystemSettings` pojawił się błąd TypeScript, że `systemSettings` nie istnieje na Prisma Client.

Naprawa:

```bash
npx prisma generate
```

Następnie restart TypeScript server albo restart `pnpm dev`.

---

## Git diff otwiera pager

Jeżeli `git diff` wygląda jakby zawiesił Bash i na dole widać:

```txt
:
```

to działa pager `less`.

Wyjście:

```txt
q
```

Bez pagera:

```bash
git --no-pager diff --name-status
git --no-pager diff --stat
```

---

## Ostrzeżenie LF / CRLF

Na Windows może pojawić się ostrzeżenie:

```txt
LF will be replaced by CRLF the next time Git touches it
```

To nie jest błąd aplikacji.

---

# Co NIE robić teraz

Nie robić:

```bash
git add .
```

Nie dodawać eksportu historii pojedynczego gościa.

Nie przebudowywać działających modułów rezerwacji, gości, domków ani kalendarza bez potrzeby.

Nie zmieniać schema Prisma bez wyraźnej potrzeby.

Nie zaczynać płatności online, dopóki nie będzie decyzji o etapie publicznej rezerwacji.

Nie wdrażać produkcji, dopóki nie będzie:

```txt
autoryzacji admina,
bezpieczeństwa panelu,
backupów,
walidacji środowiska produkcyjnego,
konfiguracji domeny,
zmiennych środowiskowych produkcji,
decyzji o hostingu,
testu build,
testu migracji produkcyjnych.
```

Nie używać ZIP jako źródła prawdy.

Nie przywracać starych plików z ZIP.

---

# Najbardziej sensowny następny krok

Po zapisaniu i commicie `PROJECT_STATE.md` najbardziej sensowny następny krok to:

```txt
M4.11 — audyt techniczny przed przejściem do strony publicznej
```

Cel M4.11:

```txt
1. Sprawdzić git status.
2. Uruchomić aplikację.
3. Sprawdzić główne trasy admina.
4. Uruchomić build, jeżeli projekt jest gotowy.
5. Sprawdzić potencjalne błędy TypeScript.
6. Sprawdzić, czy nie ma brakujących plików w repo.
7. Upewnić się, że można bezpiecznie przejść do M5.
```

Po M4.11 można przejść do:

```txt
M5 — Strona publiczna
```

---

# Proponowana kolejność dalszych prac

## M4.11 — Audyt techniczny

```txt
M4.11.1 — git status i test tras admina
M4.11.2 — build aplikacji
M4.11.3 — poprawki techniczne po buildzie
M4.11.4 — decyzja o przejściu do M5
```

## M5 — Strona publiczna

```txt
M5.1 — landing page
M5.2 — prezentacja domków
M5.3 — galeria
M5.4 — cennik
M5.5 — formularz zapytania
M5.6 — SEO podstawowe
```

## M6 — Rezerwacje online

```txt
M6.1 — publiczny formularz rezerwacji
M6.2 — sprawdzanie dostępności
M6.3 — zapis jako PENDING
M6.4 — powiadomienie dla właściciela
M6.5 — potwierdzanie rezerwacji w adminie
```

## M7 — Synchronizacja z Booking / iCal

```txt
M7.1 — import ICS
M7.2 — eksport ICS
M7.3 — blokowanie terminów
M7.4 — obsługa konfliktów
M7.5 — log synchronizacji
```

## M8 — Produkcja

```txt
M8.1 — logowanie admina
M8.2 — bezpieczeństwo panelu
M8.3 — backup bazy
M8.4 — konfiguracja środowiska produkcyjnego
M8.5 — deployment
M8.6 — domena
M8.7 — test produkcyjny
```