import Link from "next/link";
import { prisma } from "@/lib/prisma";

type Props = {
  searchParams?: Promise<{
    q?: string;
  }>;
};

function getSearchQuery(value: string | undefined) {
  if (!value) {
    return "";
  }

  return value.trim();
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

export default async function GuestsPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;
  const searchQuery = getSearchQuery(resolvedSearchParams?.q);

  const guests = await prisma.guest.findMany({
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

  const totalGuests = guests.length;

  const guestsWithReservations = guests.filter(
    (guest) => guest.reservations.length > 0
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

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Goście</h1>

          <p className="mt-2 text-zinc-500">
            Baza gości tworzona automatycznie z rezerwacji.
          </p>
        </div>

        <Link
          href="/admin/rezerwacje/nowa"
          className="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
        >
          + Dodaj rezerwację
        </Link>
      </div>

      <section className="grid gap-4 md:grid-cols-4">
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
        <form className="flex flex-wrap items-end gap-4">
          <div className="min-w-[280px] flex-1 space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szukaj gościa
            </label>

            <input
              name="q"
              defaultValue={searchQuery}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
              placeholder="Imię, nazwisko, email, telefon albo kraj"
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

          {searchQuery ? (
            <div className="rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-700">
              Wyniki dla: {searchQuery}
            </div>
          ) : null}
        </div>

        {guests.length === 0 ? (
          <div className="p-8 text-center text-zinc-500">
            Brak gości dla wybranej frazy.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[1100px] border-collapse text-sm">
              <thead className="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                  <th className="border-b p-4">Gość</th>
                  <th className="border-b p-4">Kontakt</th>
                  <th className="border-b p-4">Kraj</th>
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

                  return (
                    <tr key={guest.id} className="align-top hover:bg-zinc-50">
                      <td className="border-b p-4">
                        <div className="font-semibold text-zinc-900">
                          {getGuestFullName(guest.firstName, guest.lastName)}
                        </div>

                        <div className="mt-1 text-xs text-zinc-500">
                          dodano: {formatDate(guest.createdAt)}
                        </div>
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
                        {lastReservation ? (
                          <Link
                            href={`/admin/rezerwacje/${lastReservation.id}`}
                            className="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-zinc-50"
                          >
                            Ostatnia rezerwacja
                          </Link>
                        ) : (
                          <span className="text-xs text-zinc-400">
                            Brak akcji
                          </span>
                        )}
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