import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

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

function formatDateTime(date: Date | null) {
  if (!date) {
    return "—";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
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

function getRemainingAmount(totalPrice: number, paidAmount: number) {
  return Math.max(0, totalPrice - paidAmount);
}

function getStatusLabel(status: string) {
  switch (status) {
    case "PENDING":
      return "Oczekująca";
    case "CONFIRMED":
      return "Potwierdzona";
    case "CANCELLED":
      return "Anulowana";
    case "COMPLETED":
      return "Zakończona";
    default:
      return status;
  }
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
    default:
      return source;
  }
}

function getStatusClassName(status: string) {
  switch (status) {
    case "CONFIRMED":
      return "bg-blue-100 text-blue-700";
    case "PENDING":
      return "bg-yellow-100 text-yellow-800";
    case "CANCELLED":
      return "bg-red-100 text-red-700";
    case "COMPLETED":
      return "bg-zinc-100 text-zinc-700";
    default:
      return "bg-zinc-100 text-zinc-700";
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
    case "MANUAL":
      return "bg-zinc-100 text-zinc-700";
    default:
      return "bg-zinc-100 text-zinc-700";
  }
}

function getPaymentClassName(remainingAmount: number) {
  if (remainingAmount === 0) {
    return "text-green-700";
  }

  return "text-red-700";
}

function getPaymentLabel(remainingAmount: number) {
  if (remainingAmount === 0) {
    return "Opłacono";
  }

  return `Do zapłaty ${formatMoney(remainingAmount)}`;
}

function getAddress({
  street,
  postalCode,
  city,
  country,
}: {
  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;
}) {
  const parts = [
    street,
    [postalCode, city].filter(Boolean).join(" "),
    country,
  ].filter(Boolean);

  if (parts.length === 0) {
    return "—";
  }

  return parts.join(", ");
}

export default async function GuestDetailsPage({ params }: Props) {
  const resolvedParams = await params;

  const guest = await prisma.guest.findUnique({
    where: {
      id: resolvedParams.id,
    },
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

  if (!guest) {
    notFound();
  }

  const reservationsCount = guest.reservations.length;

  const totalNights = guest.reservations.reduce(
    (sum, reservation) => sum + reservation.nights,
    0
  );

  const totalValue = guest.reservations.reduce(
    (sum, reservation) => sum + decimalToNumber(reservation.totalPrice),
    0
  );

  const totalPaid = guest.reservations.reduce(
    (sum, reservation) => sum + decimalToNumber(reservation.paidAmount),
    0
  );

  const totalRemaining = Math.max(0, totalValue - totalPaid);

  const lastReservation = guest.reservations[0] ?? null;

  const guestName = getGuestFullName(guest.firstName, guest.lastName);

  return (
    <div className="space-y-8">
      <div>
        <Link
          href="/admin/goscie"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do gości
        </Link>

        <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold">{guestName || "Gość"}</h1>

            <p className="mt-2 text-zinc-500">
              Szczegóły gościa i pełna historia rezerwacji.
            </p>
          </div>

          <div className="flex flex-wrap gap-3">
            <Link
              href={`/admin/goscie/${guest.id}/edytuj`}
              className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
            >
              Edytuj gościa
            </Link>

            <Link
              href="/admin/rezerwacje/nowa"
              className="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
            >
              + Dodaj rezerwację
            </Link>
          </div>
        </div>
      </div>

      <section className="grid gap-4 lg:grid-cols-[1.2fr_2fr]">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Dane gościa</h2>

          <div className="mt-5 space-y-4">
            <div>
              <div className="text-sm text-zinc-500">Imię i nazwisko</div>
              <div className="mt-1 font-semibold">{guestName || "—"}</div>
            </div>

            <div>
              <div className="text-sm text-zinc-500">Telefon</div>
              <div className="mt-1 font-semibold">
                {guest.phone || "brak telefonu"}
              </div>
            </div>

            <div>
              <div className="text-sm text-zinc-500">Email</div>
              <div className="mt-1 font-semibold">
                {guest.email || "brak emaila"}
              </div>
            </div>

            <div>
              <div className="text-sm text-zinc-500">Kraj</div>
              <div className="mt-1 font-semibold">{guest.country || "—"}</div>
            </div>

            <div>
              <div className="text-sm text-zinc-500">Dodano do bazy</div>
              <div className="mt-1 font-semibold">
                {formatDate(guest.createdAt)}
              </div>
            </div>

            <div>
              <div className="text-sm text-zinc-500">Ostatni pobyt</div>
              <div className="mt-1 font-semibold">
                {lastReservation
                  ? formatDate(
                      lastReservation.checkInAt ?? lastReservation.startDate
                    )
                  : "—"}
              </div>
            </div>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Rezerwacje</div>
            <div className="mt-1 text-3xl font-bold">{reservationsCount}</div>
          </div>

          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Noce razem</div>
            <div className="mt-1 text-3xl font-bold">{totalNights}</div>
          </div>

          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Wartość pobytów</div>
            <div className="mt-1 text-3xl font-bold text-green-700">
              {formatMoney(totalValue)}
            </div>
          </div>

          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Wpłacono</div>
            <div className="mt-1 text-3xl font-bold text-green-700">
              {formatMoney(totalPaid)}
            </div>
          </div>

          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Pozostało</div>
            <div className="mt-1 text-3xl font-bold text-red-700">
              {formatMoney(totalRemaining)}
            </div>
          </div>

          <div className="rounded-xl border bg-white p-5 shadow-sm">
            <div className="text-sm text-zinc-500">Średnia rezerwacja</div>
            <div className="mt-1 text-3xl font-bold">
              {reservationsCount > 0
                ? formatMoney(Math.round(totalValue / reservationsCount))
                : formatMoney(0)}
            </div>
          </div>
        </div>
      </section>

      <section className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div className="border-b px-5 py-4">
          <h2 className="text-xl font-semibold">Historia rezerwacji</h2>
        </div>

        {guest.reservations.length === 0 ? (
          <div className="p-8 text-center text-zinc-500">
            Ten gość nie ma jeszcze przypisanych rezerwacji.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[1150px] border-collapse text-sm">
              <thead className="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                  <th className="border-b p-4">Domek</th>
                  <th className="border-b p-4">Termin</th>
                  <th className="border-b p-4 text-center">Noce</th>
                  <th className="border-b p-4 text-center">Goście</th>
                  <th className="border-b p-4 text-right">Cena</th>
                  <th className="border-b p-4 text-right">Wpłacono</th>
                  <th className="border-b p-4 text-right">Pozostało</th>
                  <th className="border-b p-4">Status</th>
                  <th className="border-b p-4">Źródło</th>
                  <th className="border-b p-4">Adres</th>
                  <th className="border-b p-4 text-right">Akcje</th>
                </tr>
              </thead>

              <tbody>
                {guest.reservations.map((reservation) => {
                  const totalPrice = decimalToNumber(reservation.totalPrice);
                  const paidAmount = decimalToNumber(reservation.paidAmount);
                  const remainingAmount = getRemainingAmount(
                    totalPrice,
                    paidAmount
                  );

                  return (
                    <tr
                      key={reservation.id}
                      className="align-top hover:bg-zinc-50"
                    >
                      <td className="border-b p-4">
                        <div className="font-semibold">
                          {reservation.cabin.shortName ||
                            reservation.cabin.name}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          max {reservation.cabin.maxGuests} osób
                        </div>
                      </td>

                      <td className="border-b p-4">
                        <div className="font-semibold">
                          {formatDate(
                            reservation.checkInAt ?? reservation.startDate
                          )}
                          {" – "}
                          {formatDate(
                            reservation.checkOutAt ?? reservation.endDate
                          )}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          Przyjazd:{" "}
                          {formatDateTime(
                            reservation.checkInAt ?? reservation.startDate
                          )}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          Wyjazd:{" "}
                          {formatDateTime(
                            reservation.checkOutAt ?? reservation.endDate
                          )}
                        </div>
                      </td>

                      <td className="border-b p-4 text-center font-semibold">
                        {reservation.nights}
                      </td>

                      <td className="border-b p-4 text-center">
                        <div className="font-semibold">
                          {reservation.guests}
                        </div>

                        <div className="mt-1 text-xs text-zinc-500">
                          D: {reservation.adults}, Dz: {reservation.children}
                        </div>
                      </td>

                      <td className="border-b p-4 text-right font-semibold">
                        {formatMoney(totalPrice)}
                      </td>

                      <td className="border-b p-4 text-right">
                        {formatMoney(paidAmount)}
                      </td>

                      <td
                        className={`border-b p-4 text-right font-bold ${getPaymentClassName(
                          remainingAmount
                        )}`}
                      >
                        {getPaymentLabel(remainingAmount)}
                      </td>

                      <td className="border-b p-4">
                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getStatusClassName(
                            reservation.status
                          )}`}
                        >
                          {getStatusLabel(reservation.status)}
                        </span>
                      </td>

                      <td className="border-b p-4">
                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getSourceClassName(
                            reservation.source
                          )}`}
                        >
                          {getSourceLabel(reservation.source)}
                        </span>
                      </td>

                      <td className="border-b p-4">
                        {getAddress({
                          street: reservation.street,
                          postalCode: reservation.postalCode,
                          city: reservation.city,
                          country: reservation.country,
                        })}
                      </td>

                      <td className="border-b p-4 text-right">
                        <div className="flex justify-end gap-2">
                          <Link
                            href={`/admin/rezerwacje/${reservation.id}`}
                            className="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-zinc-50"
                          >
                            Szczegóły
                          </Link>

                          <Link
                            href={`/admin/rezerwacje/${reservation.id}/edytuj`}
                            className="rounded-lg bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-800"
                          >
                            Edytuj
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