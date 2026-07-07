import Link from "next/link";
import { notFound } from "next/navigation";

import { deleteReservation } from "@/actions/delete-reservation";
import { prisma } from "@/lib/prisma";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

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

function formatMoney(value: { toString: () => string } | null) {
  if (!value) {
    return "—";
  }

  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(Number(value.toString()));
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

export default async function DeleteReservationPage({ params }: Props) {
  const resolvedParams = await params;

  const reservation = await prisma.reservation.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      cabin: true,
      guest: true,
    },
  });

  if (!reservation) {
    notFound();
  }

  return (
    <div className="max-w-3xl space-y-8">
      <div>
        <Link
          href={`/admin/rezerwacje/${reservation.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów rezerwacji
        </Link>

        <h1 className="mt-3 text-3xl font-bold text-red-700">
          Usuń rezerwację
        </h1>

        <p className="mt-2 text-zinc-500">
          Potwierdź usunięcie rezerwacji. Tej operacji nie można cofnąć.
        </p>
      </div>

      <section className="rounded-xl border border-red-200 bg-red-50 p-5">
        <h2 className="text-xl font-semibold text-red-900">
          Czy na pewno chcesz usunąć tę rezerwację?
        </h2>

        <p className="mt-2 text-sm leading-6 text-red-800">
          Rezerwacja zostanie trwale usunięta z listy rezerwacji, kalendarza i
          historii gościa. Sam gość nie zostanie usunięty.
        </p>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Dane rezerwacji</h2>

        <div className="mt-5 space-y-4">
          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Gość</span>
            <span className="text-right font-semibold">
              {reservation.guestName}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Domek</span>
            <span className="text-right font-semibold">
              {reservation.cabin.name}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Zameldowanie</span>
            <span className="text-right font-semibold">
              {formatDateTime(reservation.checkInAt ?? reservation.startDate)}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Wymeldowanie</span>
            <span className="text-right font-semibold">
              {formatDateTime(reservation.checkOutAt ?? reservation.endDate)}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Status</span>
            <span className="text-right font-semibold">
              {getStatusLabel(reservation.status)}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Cena pobytu</span>
            <span className="text-right font-semibold">
              {formatMoney(reservation.totalPrice)}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Przypisany gość</span>
            <span className="text-right font-semibold">
              {reservation.guest
                ? `${reservation.guest.firstName} ${reservation.guest.lastName}`.trim()
                : "Brak przypisanego gościa"}
            </span>
          </div>
        </div>
      </section>

      <div className="flex flex-wrap gap-3">
        <form action={deleteReservation}>
          <input type="hidden" name="reservationId" value={reservation.id} />

          <button
            type="submit"
            className="rounded-lg bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800"
          >
            Tak, usuń rezerwację
          </button>
        </form>

        <Link
          href={`/admin/rezerwacje/${reservation.id}`}
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Anuluj
        </Link>
      </div>
    </div>
  );
}