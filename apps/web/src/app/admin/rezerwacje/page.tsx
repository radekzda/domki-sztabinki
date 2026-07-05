import Link from "next/link";
import { Prisma } from "@prisma/client";
import { prisma } from "@/lib/prisma";

type Props = {
  searchParams?: Promise<{
    status?: string;
    cabinId?: string;
  }>;
};

const reservationStatuses = [
  { value: "ALL", label: "Wszystkie" },
  { value: "PENDING", label: "Oczekujące" },
  { value: "CONFIRMED", label: "Potwierdzone" },
  { value: "CANCELLED", label: "Anulowane" },
  { value: "COMPLETED", label: "Zakończone" },
];

function formatDate(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function getStatusLabel(status: string) {
  return (
    reservationStatuses.find((item) => item.value === status)?.label ?? status
  );
}

function getStatusClassName(status: string) {
  if (status === "CONFIRMED") return "bg-green-100 text-green-700";
  if (status === "PENDING") return "bg-yellow-100 text-yellow-700";
  if (status === "CANCELLED") return "bg-red-100 text-red-700";
  if (status === "COMPLETED") return "bg-blue-100 text-blue-700";

  return "bg-zinc-100 text-zinc-700";
}

function buildFilterUrl(status: string, cabinId: string) {
  const params = new URLSearchParams();

  if (status !== "ALL") {
    params.set("status", status);
  }

  if (cabinId !== "ALL") {
    params.set("cabinId", cabinId);
  }

  const queryString = params.toString();

  return queryString ? `/admin/rezerwacje?${queryString}` : "/admin/rezerwacje";
}

export default async function RezerwacjePage({ searchParams }: Props) {
  const resolvedSearchParams = await searchParams;

  const selectedStatus = resolvedSearchParams?.status ?? "ALL";
  const selectedCabinId = resolvedSearchParams?.cabinId ?? "ALL";

  const where: Prisma.ReservationWhereInput = {};

  if (selectedStatus !== "ALL") {
    where.status = selectedStatus;
  }

  if (selectedCabinId !== "ALL") {
    where.cabinId = selectedCabinId;
  }

  const [reservations, cabins, reservationsCount] = await Promise.all([
    prisma.reservation.findMany({
      where,
      orderBy: {
        startDate: "desc",
      },
      include: {
        cabin: true,
      },
    }),

    prisma.cabin.findMany({
      orderBy: {
        sortOrder: "asc",
      },
    }),

    prisma.reservation.count({
      where,
    }),
  ]);

  return (
    <div className="space-y-8">
      <div className="flex items-start justify-between gap-6">
        <div>
          <h1 className="text-3xl font-bold">Rezerwacje</h1>
          <p className="mt-2 text-zinc-500">
            Lista rezerwacji w panelu administratora.
          </p>
        </div>

        <button
          disabled
          className="rounded-lg bg-zinc-300 px-4 py-2 text-sm font-medium text-zinc-500"
        >
          + Dodaj rezerwację
        </button>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <p className="text-sm text-zinc-500">Widoczne rezerwacje</p>
          <p className="mt-2 text-3xl font-bold">{reservationsCount}</p>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <p className="text-sm text-zinc-500">Filtr statusu</p>
          <p className="mt-2 text-xl font-semibold">
            {getStatusLabel(selectedStatus)}
          </p>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <p className="text-sm text-zinc-500">Filtr domku</p>
          <p className="mt-2 text-xl font-semibold">
            {selectedCabinId === "ALL"
              ? "Wszystkie domki"
              : cabins.find((cabin) => cabin.id === selectedCabinId)?.name ??
                "Nieznany domek"}
          </p>
        </div>
      </div>

      <div className="space-y-4 rounded-xl border bg-white p-6 shadow-sm">
        <div>
          <h2 className="text-xl font-semibold">Filtry</h2>
          <p className="mt-1 text-sm text-zinc-500">
            Zawężaj listę rezerwacji po statusie oraz domku.
          </p>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <div className="space-y-2">
            <p className="text-sm font-medium">Status</p>

            <div className="flex flex-wrap gap-2">
              {reservationStatuses.map((status) => (
                <Link
                  key={status.value}
                  href={buildFilterUrl(status.value, selectedCabinId)}
                  className={`rounded-lg border px-4 py-2 text-sm transition ${
                    selectedStatus === status.value
                      ? "border-green-700 bg-green-700 text-white"
                      : "bg-white text-zinc-700 hover:bg-zinc-50"
                  }`}
                >
                  {status.label}
                </Link>
              ))}
            </div>
          </div>

          <div className="space-y-2">
            <p className="text-sm font-medium">Domek</p>

            <div className="flex flex-wrap gap-2">
              <Link
                href={buildFilterUrl(selectedStatus, "ALL")}
                className={`rounded-lg border px-4 py-2 text-sm transition ${
                  selectedCabinId === "ALL"
                    ? "border-green-700 bg-green-700 text-white"
                    : "bg-white text-zinc-700 hover:bg-zinc-50"
                }`}
              >
                Wszystkie
              </Link>

              {cabins.map((cabin) => (
                <Link
                  key={cabin.id}
                  href={buildFilterUrl(selectedStatus, cabin.id)}
                  className={`rounded-lg border px-4 py-2 text-sm transition ${
                    selectedCabinId === cabin.id
                      ? "border-green-700 bg-green-700 text-white"
                      : "bg-white text-zinc-700 hover:bg-zinc-50"
                  }`}
                >
                  {cabin.name}
                </Link>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table className="w-full">
          <thead className="bg-zinc-100 text-left text-sm">
            <tr>
              <th className="p-4">Gość</th>
              <th className="p-4">Domek</th>
              <th className="p-4">Termin</th>
              <th className="p-4">Osoby</th>
              <th className="p-4">Status</th>
            </tr>
          </thead>

          <tbody>
            {reservations.length === 0 ? (
              <tr>
                <td colSpan={5} className="p-8 text-center text-zinc-500">
                  Brak rezerwacji dla wybranych filtrów.
                </td>
              </tr>
            ) : (
              reservations.map((reservation) => (
                <tr key={reservation.id} className="border-t hover:bg-zinc-50">
                  <td className="p-4">
                    <div className="font-semibold">
                      {reservation.guestName}
                    </div>

                    <div className="text-sm text-zinc-500">
                      {reservation.email}
                    </div>

                    {reservation.phone ? (
                      <div className="text-sm text-zinc-500">
                        {reservation.phone}
                      </div>
                    ) : null}
                  </td>

                  <td className="p-4">{reservation.cabin.name}</td>

                  <td className="p-4">
                    <div className="font-medium">
                      {formatDate(reservation.startDate)}
                    </div>

                    <div className="text-sm text-zinc-500">
                      do {formatDate(reservation.endDate)}
                    </div>
                  </td>

                  <td className="p-4">{reservation.guests}</td>

                  <td className="p-4">
                    <span
                      className={`inline-block rounded-full px-3 py-1 text-sm font-medium ${getStatusClassName(
                        reservation.status
                      )}`}
                    >
                      {getStatusLabel(reservation.status)}
                    </span>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}