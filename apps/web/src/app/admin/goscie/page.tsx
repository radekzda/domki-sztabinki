import Link from "next/link";
import { syncGuestsFromReservations } from "@/actions/guests";
import { prisma } from "@/lib/prisma";

type GuestFilter =
  | "ALL"
  | "WITH_RESERVATIONS"
  | "WITHOUT_RESERVATIONS"
  | "MISSING_CONTACT"
  | "SOURCE_BASE44"
  | "SOURCE_CSV_IMPORT"
  | "SOURCE_MANUAL";

type Props = {
  searchParams?: Promise<{
    q?: string;
    filter?: string;
    sync?: string;
    reservations?: string;
    created?: string;
    updated?: string;
  }>;
};

const guestFilters: {
  label: string;
  value: GuestFilter;
}[] = [
  {
    label: "Wszyscy goście",
    value: "ALL",
  },
  {
    label: "Z rezerwacjami",
    value: "WITH_RESERVATIONS",
  },
  {
    label: "Bez rezerwacji",
    value: "WITHOUT_RESERVATIONS",
  },
  {
    label: "Braki kontaktu",
    value: "MISSING_CONTACT",
  },
  {
    label: "Base44",
    value: "SOURCE_BASE44",
  },
  {
    label: "Import CSV",
    value: "SOURCE_CSV_IMPORT",
  },
  {
    label: "Ręcznie dodani",
    value: "SOURCE_MANUAL",
  },
];

function getSearchQuery(value: string | undefined) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function getGuestFilter(value: string | undefined): GuestFilter {
  if (value === "WITH_RESERVATIONS") {
    return "WITH_RESERVATIONS";
  }

  if (value === "WITHOUT_RESERVATIONS") {
    return "WITHOUT_RESERVATIONS";
  }

  if (value === "MISSING_CONTACT") {
    return "MISSING_CONTACT";
  }

  if (value === "SOURCE_BASE44") {
    return "SOURCE_BASE44";
  }

  if (value === "SOURCE_CSV_IMPORT") {
    return "SOURCE_CSV_IMPORT";
  }

  if (value === "SOURCE_MANUAL") {
    return "SOURCE_MANUAL";
  }

  return "ALL";
}

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return 0;
  }

  return Number(value.toString());
}

function formatDate(date: Date | null) {
  if (!date) {
    return "—";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function formatMoney(value: number) {
  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
}

function getGuestFullName(firstName: string, lastName: string) {
  return `${firstName} ${lastName}`.trim();
}

function getLastReservationDate(
  reservations: Array<{
    startDate: Date;
    checkInAt: Date | null;
  }>
) {
  if (reservations.length === 0) {
    return null;
  }

  return reservations
    .map((reservation) => reservation.checkInAt ?? reservation.startDate)
    .sort((a, b) => b.getTime() - a.getTime())[0];
}

function getGuestFilterLabel(filter: GuestFilter) {
  if (filter === "WITH_RESERVATIONS") {
    return "Z rezerwacjami";
  }

  if (filter === "WITHOUT_RESERVATIONS") {
    return "Bez rezerwacji";
  }

  if (filter === "MISSING_CONTACT") {
    return "Braki kontaktu";
  }

  if (filter === "SOURCE_BASE44") {
    return "Base44";
  }

  if (filter === "SOURCE_CSV_IMPORT") {
    return "Import CSV";
  }

  if (filter === "SOURCE_MANUAL") {
    return "Ręcznie dodani";
  }

  return "Wszyscy goście";
}

function getSourceLabel(source: string) {
  switch (source) {
    case "MANUAL":
      return "Ręcznie";
    case "PHONE":
      return "Telefon";
    case "WEBSITE":
      return "WWW";
    case "BOOKING":
      return "Booking";
    case "AIRBNB":
      return "Airbnb";
    case "BASE44":
      return "Base44";
    case "CSV_IMPORT":
      return "Import CSV";
    case "RESERVATION_SYNC":
      return "Synchronizacja rezerwacji";
    default:
      return source;
  }
}

function getSourceClassName(source: string) {
  switch (source) {
    case "BOOKING":
      return "bg-green-100 text-green-700";
    case "AIRBNB":
      return "bg-red-100 text-red-700";
    case "WEBSITE":
      return "bg-blue-100 text-blue-700";
    case "PHONE":
      return "bg-yellow-100 text-yellow-800";
    case "BASE44":
      return "bg-purple-100 text-purple-700";
    case "CSV_IMPORT":
      return "bg-purple-100 text-purple-700";
    case "RESERVATION_SYNC":
      return "bg-indigo-100 text-indigo-700";
    case "MANUAL":
      return "bg-zinc-100 text-zinc-700";
    default:
      return "bg-zinc-100 text-zinc-700";
  }
}

function getQuickFilterClassName(isActive: boolean) {
  if (isActive) {
    return "rounded-full bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800";
  }

  return "rounded-full border bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900";
}

function buildGuestsUrl(searchQuery: string, guestFilter: GuestFilter) {
  const params = new URLSearchParams();

  if (searchQuery) {
    params.set("q", searchQuery);
  }

  if (guestFilter !== "ALL") {
    params.set("filter", guestFilter);
  }

  const queryString = params.toString();

  return queryString ? `/admin/goscie?${queryString}` : "/admin/goscie";
}

function buildExportUrl(searchQuery: string, guestFilter: GuestFilter) {
  const params = new URLSearchParams();

  if (searchQuery) {
    params.set("q", searchQuery);
  }

  if (guestFilter !== "ALL") {
    params.set("filter", guestFilter);
  }

  const queryString = params.toString();

  return queryString
    ? `/admin/goscie/export?${queryString}`
    : "/admin/goscie/export";
}

function getNumberFromSearchParam(value: string | undefined) {
  if (!value) {
    return 0;
  }

  const parsedValue = Number(value);

  if (!Number.isFinite(parsedValue)) {
    return 0;
  }

  return parsedValue;
}

function hasMissingContact({
  email,
  phone,
}: {
  email: string;
  phone: string | null;
}) {
  return !email.trim() || !phone?.trim();
}

function guestMatchesFilter(
  guest: {
    email: string;
    phone: string | null;
    source: string;
    reservations: unknown[];
  },
  guestFilter: GuestFilter
) {
  if (guestFilter === "WITH_RESERVATIONS") {
    return guest.reservations.length > 0;
  }

  if (guestFilter === "WITHOUT_RESERVATIONS") {
    return guest.reservations.length === 0;
  }

  if (guestFilter === "MISSING_CONTACT") {
    return hasMissingContact({
      email: guest.email,
      phone: guest.phone,
    });
  }

  if (guestFilter === "SOURCE_BASE44") {
    return guest.source === "BASE44";
  }

  if (guestFilter === "SOURCE_CSV_IMPORT") {
    return guest.source === "CSV_IMPORT";
  }

  if (guestFilter === "SOURCE_MANUAL") {
    return guest.source === "MANUAL";
  }

  return true;
}

export default async function GuestsPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;
  const searchQuery = getSearchQuery(resolvedSearchParams?.q);
  const guestFilter = getGuestFilter(resolvedSearchParams?.filter);

  const allGuests = await prisma.guest.findMany({
    where: {
      ...(searchQuery
        ? {
            OR: [
              {
                firstName: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                lastName: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                email: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                phone: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                country: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                street: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                postalCode: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                city: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                fullAddress: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                source: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
            ],
          }
        : {}),
    },
    orderBy: [
      {
        lastName: "asc",
      },
      {
        firstName: "asc",
      },
      {
        createdAt: "desc",
      },
    ],
    include: {
      reservations: {
        orderBy: {
          startDate: "desc",
        },
        include: {
          cabin: true,
        },
      },
    },
  });

  const guests = allGuests.filter((guest) =>
    guestMatchesFilter(guest, guestFilter)
  );

  const reservationsWithoutGuestCount = await prisma.reservation.count({
    where: {
      guestId: null,
    },
  });

  const totalGuests = guests.length;

  const guestsWithReservations = guests.filter(
    (guest) => guest.reservations.length > 0
  ).length;

  const guestsWithoutReservations = guests.filter(
    (guest) => guest.reservations.length === 0
  ).length;

  const guestsWithMissingContact = guests.filter((guest) =>
    hasMissingContact({
      email: guest.email,
      phone: guest.phone,
    })
  ).length;

  const totalReservations = guests.reduce(
    (sum, guest) => sum + guest.reservations.length,
    0
  );

  const totalNights = guests.reduce(
    (sum, guest) =>
      sum +
      guest.reservations.reduce(
        (reservationSum, reservation) => reservationSum + reservation.nights,
        0
      ),
    0
  );

  const totalValue = guests.reduce(
    (sum, guest) =>
      sum +
      guest.reservations.reduce(
        (reservationSum, reservation) =>
          reservationSum + decimalToNumber(reservation.totalPrice),
        0
      ),
    0
  );

  const exportUrl = buildExportUrl(searchQuery, guestFilter);

  const syncReservations = getNumberFromSearchParam(
    resolvedSearchParams?.reservations
  );

  const syncCreatedGuests = getNumberFromSearchParam(
    resolvedSearchParams?.created
  );

  const syncUpdatedGuests = getNumberFromSearchParam(
    resolvedSearchParams?.updated
  );

  const showSyncSuccess = resolvedSearchParams?.sync === "ok";
  const activeFilters = searchQuery !== "" || guestFilter !== "ALL";

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Goście</h1>

          <p className="mt-2 text-zinc-500">
            Baza gości tworzona automatycznie z rezerwacji, importu CSV i
            ręcznego dodawania.
          </p>
        </div>

        <div className="flex flex-wrap gap-3">
          <Link
            href="/admin/goscie/nowy"
            className="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
          >
            + Dodaj gościa
          </Link>

          <Link
            href="/admin/goscie/import"
            className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
          >
            Import CSV
          </Link>

          <Link
            href={exportUrl}
            className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
          >
            Eksport CSV
          </Link>

          <Link
            href="/admin/rezerwacje/nowa"
            className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
          >
            + Dodaj rezerwację
          </Link>
        </div>
      </div>

      {showSyncSuccess ? (
        <section className="rounded-xl border border-green-200 bg-green-50 p-5 text-green-800">
          <div className="font-semibold">
            Synchronizacja zakończona poprawnie.
          </div>

          <div className="mt-2 text-sm">
            Połączono rezerwacje: {syncReservations}. Utworzono gości:{" "}
            {syncCreatedGuests}. Zaktualizowano gości: {syncUpdatedGuests}.
          </div>
        </section>
      ) : null}

      {reservationsWithoutGuestCount > 0 ? (
        <section className="rounded-xl border border-yellow-200 bg-yellow-50 p-5">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-yellow-900">
                Są stare rezerwacje bez przypisanego gościa
              </h2>

              <p className="mt-1 text-sm text-yellow-800">
                Liczba rezerwacji bez `guestId`: {reservationsWithoutGuestCount}
                . Kliknij synchronizację, aby połączyć je z bazą gości.
              </p>
            </div>

            <form action={syncGuestsFromReservations}>
              <button className="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700">
                Synchronizuj stare rezerwacje
              </button>
            </form>
          </div>
        </section>
      ) : (
        <section className="rounded-xl border border-green-200 bg-green-50 p-5 text-green-800">
          <div className="font-semibold">
            Wszystkie rezerwacje są połączone z bazą gości.
          </div>
        </section>
      )}

      <section className="grid gap-4 md:grid-cols-6">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Goście</div>
          <div className="mt-1 text-3xl font-bold">{totalGuests}</div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Z rezerwacjami</div>
          <div className="mt-1 text-3xl font-bold text-blue-700">
            {guestsWithReservations}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Bez rezerwacji</div>
          <div className="mt-1 text-3xl font-bold text-zinc-700">
            {guestsWithoutReservations}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Braki kontaktu</div>
          <div className="mt-1 text-3xl font-bold text-red-700">
            {guestsWithMissingContact}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Rezerwacje</div>
          <div className="mt-1 text-3xl font-bold">{totalReservations}</div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Noce</div>
          <div className="mt-1 text-3xl font-bold">{totalNights}</div>
        </div>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-semibold">Wartość pobytów</h2>

            <p className="mt-1 text-sm text-zinc-500">
              Suma wartości rezerwacji przypisanych do widocznych gości.
            </p>
          </div>

          <div className="text-3xl font-bold text-green-700">
            {formatMoney(totalValue)}
          </div>
        </div>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="mb-5">
          <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Szybki filtr gości
          </div>

          <div className="flex flex-wrap gap-2">
            {guestFilters.map((filter) => {
              const isActive = filter.value === guestFilter;

              return (
                <Link
                  key={filter.value}
                  href={buildGuestsUrl(searchQuery, filter.value)}
                  className={getQuickFilterClassName(isActive)}
                >
                  {filter.label}
                </Link>
              );
            })}
          </div>
        </div>

        <form className="flex flex-wrap items-end gap-4">
          {guestFilter !== "ALL" ? (
            <input type="hidden" name="filter" value={guestFilter} />
          ) : null}

          <div className="min-w-[280px] flex-1 space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szukaj gościa
            </label>

            <input
              name="q"
              defaultValue={searchQuery}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
              placeholder="Imię, nazwisko, email, telefon, kraj, miasto albo adres"
            />
          </div>

          <button className="h-10 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white hover:bg-zinc-800">
            Szukaj
          </button>

          <Link
            href="/admin/goscie"
            className="flex h-10 items-center rounded-lg border px-4 text-sm font-semibold hover:bg-zinc-50"
          >
            Wyczyść
          </Link>
        </form>
      </section>

      <section className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
          <h2 className="text-xl font-semibold">Lista gości</h2>

          {activeFilters ? (
            <div className="flex flex-wrap gap-2 text-sm">
              {searchQuery ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Wyniki dla: {searchQuery}
                </span>
              ) : null}

              {guestFilter !== "ALL" ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Filtr: {getGuestFilterLabel(guestFilter)}
                </span>
              ) : null}
            </div>
          ) : null}
        </div>

        {guests.length === 0 ? (
          <div className="p-8 text-center text-zinc-500">
            Brak gości dla wybranych filtrów.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[1250px] border-collapse text-sm">
              <thead className="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                  <th className="border-b p-4">Gość</th>
                  <th className="border-b p-4">Kontakt</th>
                  <th className="border-b p-4">Kraj</th>
                  <th className="border-b p-4">Źródło</th>
                  <th className="border-b p-4 text-center">Rezerwacje</th>
                  <th className="border-b p-4 text-center">Noce</th>
                  <th className="border-b p-4 text-right">Wartość</th>
                  <th className="border-b p-4">Ostatni pobyt</th>
                  <th className="border-b p-4">Ostatni domek</th>
                  <th className="border-b p-4 text-right">Akcje</th>
                </tr>
              </thead>

              <tbody>
                {guests.map((guest) => {
                  const reservationsCount = guest.reservations.length;

                  const guestNights = guest.reservations.reduce(
                    (sum, reservation) => sum + reservation.nights,
                    0
                  );

                  const guestValue = guest.reservations.reduce(
                    (sum, reservation) =>
                      sum + decimalToNumber(reservation.totalPrice),
                    0
                  );

                  const lastReservation = guest.reservations[0] ?? null;

                  const lastReservationDate = getLastReservationDate(
                    guest.reservations
                  );

                  const missingContact = hasMissingContact({
                    email: guest.email,
                    phone: guest.phone,
                  });

                  return (
                    <tr key={guest.id} className="align-top hover:bg-zinc-50">
                      <td className="border-b p-4">
                        <div className="font-semibold text-zinc-900">
                          {getGuestFullName(guest.firstName, guest.lastName)}
                        </div>

                        <div className="mt-1 text-xs text-zinc-500">
                          dodano: {formatDate(guest.createdAt)}
                        </div>

                        {missingContact ? (
                          <div className="mt-2 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                            braki kontaktu
                          </div>
                        ) : null}
                      </td>

                      <td className="border-b p-4">
                        <div className="font-medium">
                          {guest.phone || "brak telefonu"}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          {guest.email || "brak emaila"}
                        </div>
                      </td>

                      <td className="border-b p-4">
                        {guest.country || "—"}
                      </td>

                      <td className="border-b p-4">
                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getSourceClassName(
                            guest.source
                          )}`}
                        >
                          {getSourceLabel(guest.source)}
                        </span>
                      </td>

                      <td className="border-b p-4 text-center font-semibold">
                        {reservationsCount}
                      </td>

                      <td className="border-b p-4 text-center font-semibold">
                        {guestNights}
                      </td>

                      <td className="border-b p-4 text-right font-semibold">
                        {formatMoney(guestValue)}
                      </td>

                      <td className="border-b p-4">
                        {formatDate(lastReservationDate)}
                      </td>

                      <td className="border-b p-4">
                        {lastReservation
                          ? lastReservation.cabin.shortName ||
                            lastReservation.cabin.name
                          : "—"}
                      </td>

                      <td className="border-b p-4 text-right">
                        <div className="flex justify-end gap-2">
                          <Link
                            href={`/admin/goscie/${guest.id}`}
                            className="rounded-lg bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-800"
                          >
                            Szczegóły
                          </Link>

                          {lastReservation ? (
                            <Link
                              href={`/admin/rezerwacje/${lastReservation.id}`}
                              className="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-zinc-50"
                            >
                              Ostatnia
                            </Link>
                          ) : null}

                          <Link
                            href={`/admin/goscie/${guest.id}/usun`}
                            className="rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800"
                          >
                            Usuń
                          </Link>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}