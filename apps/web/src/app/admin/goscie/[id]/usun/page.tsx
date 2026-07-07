import Link from "next/link";
import { notFound } from "next/navigation";

import { deleteGuest } from "@/actions/delete-guest";
import { prisma } from "@/lib/prisma";

type Props = {
  params: Promise<{
    id: string;
  }>;
  searchParams?: Promise<{
    error?: string;
  }>;
};

function getGuestFullName(firstName: string, lastName: string) {
  return `${firstName} ${lastName}`.trim();
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

export default async function DeleteGuestPage({
  params,
  searchParams,
}: Props) {
  const resolvedParams = await params;
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const guest = await prisma.guest.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      _count: {
        select: {
          reservations: true,
        },
      },
      reservations: {
        orderBy: {
          startDate: "desc",
        },
        take: 5,
        include: {
          cabin: true,
        },
      },
    },
  });

  if (!guest) {
    notFound();
  }

  const guestName = getGuestFullName(guest.firstName, guest.lastName);
  const hasReservations = guest._count.reservations > 0;

  return (
    <div className="max-w-3xl space-y-8">
      <div>
        <Link
          href={`/admin/goscie/${guest.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów gościa
        </Link>

        <h1 className="mt-3 text-3xl font-bold text-red-700">
          Usuń gościa
        </h1>

        <p className="mt-2 text-zinc-500">
          Potwierdź usunięcie gościa z bazy. Tej operacji nie można cofnąć.
        </p>
      </div>

      {resolvedSearchParams?.error ? (
        <section className="rounded-xl border border-red-200 bg-red-50 p-5 text-sm font-medium text-red-800">
          {resolvedSearchParams.error}
        </section>
      ) : null}

      {hasReservations ? (
        <section className="rounded-xl border border-yellow-200 bg-yellow-50 p-5">
          <h2 className="text-xl font-semibold text-yellow-900">
            Tego gościa nie można teraz usunąć
          </h2>

          <p className="mt-2 text-sm leading-6 text-yellow-800">
            Gość ma przypisane rezerwacje. Dla bezpieczeństwa system nie usuwa
            gości, którzy mają historię pobytów. Najpierw usuń jego rezerwacje,
            a potem wróć do tej strony.
          </p>
        </section>
      ) : (
        <section className="rounded-xl border border-red-200 bg-red-50 p-5">
          <h2 className="text-xl font-semibold text-red-900">
            Czy na pewno chcesz usunąć tego gościa?
          </h2>

          <p className="mt-2 text-sm leading-6 text-red-800">
            Gość zostanie trwale usunięty z bazy gości. Ta operacja nie usuwa
            żadnych rezerwacji, ponieważ ten gość nie ma przypisanych
            rezerwacji.
          </p>
        </section>
      )}

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Dane gościa</h2>

        <div className="mt-5 space-y-4">
          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Imię i nazwisko</span>
            <span className="text-right font-semibold">
              {guestName || "Gość"}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Email</span>
            <span className="text-right font-semibold">
              {guest.email || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Telefon</span>
            <span className="text-right font-semibold">
              {guest.phone || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Kraj</span>
            <span className="text-right font-semibold">
              {guest.country || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Dodano</span>
            <span className="text-right font-semibold">
              {formatDate(guest.createdAt)}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Liczba rezerwacji</span>
            <span className="text-right font-semibold">
              {guest._count.reservations}
            </span>
          </div>
        </div>
      </section>

      {hasReservations ? (
        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Ostatnie rezerwacje gościa</h2>

          <div className="mt-5 space-y-3">
            {guest.reservations.map((reservation) => (
              <div
                key={reservation.id}
                className="rounded-lg border bg-zinc-50 p-4 text-sm"
              >
                <div className="font-semibold">
                  {reservation.cabin.shortName || reservation.cabin.name}
                </div>

                <div className="mt-1 text-zinc-500">
                  {formatDate(reservation.checkInAt ?? reservation.startDate)}
                  {" – "}
                  {formatDate(reservation.checkOutAt ?? reservation.endDate)}
                </div>

                <div className="mt-3">
                  <Link
                    href={`/admin/rezerwacje/${reservation.id}/usun`}
                    className="rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800"
                  >
                    Usuń tę rezerwację
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </section>
      ) : null}

      <div className="flex flex-wrap gap-3">
        {hasReservations ? (
          <Link
            href={`/admin/goscie/${guest.id}`}
            className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
          >
            Wróć do gościa
          </Link>
        ) : (
          <form action={deleteGuest}>
            <input type="hidden" name="guestId" value={guest.id} />

            <button
              type="submit"
              className="rounded-lg bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800"
            >
              Tak, usuń gościa
            </button>
          </form>
        )}

        <Link
          href="/admin/goscie"
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Lista gości
        </Link>
      </div>
    </div>
  );
}