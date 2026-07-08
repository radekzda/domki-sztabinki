import { prisma } from "@/lib/prisma";

type ReservationFilter = "ALL" | "WITH_RESERVATIONS" | "WITHOUT_RESERVATIONS";

function getSearchQuery(value: string | null) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function getReservationFilter(value: string | null): ReservationFilter {
  if (value === "WITH_RESERVATIONS") {
    return "WITH_RESERVATIONS";
  }

  if (value === "WITHOUT_RESERVATIONS") {
    return "WITHOUT_RESERVATIONS";
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
    return "";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function formatCsvValue(value: string | number | null) {
  if (value === null) {
    return "";
  }

  const stringValue = String(value);

  if (
    stringValue.includes(";") ||
    stringValue.includes('"') ||
    stringValue.includes("\n") ||
    stringValue.includes("\r")
  ) {
    return `"${stringValue.replace(/"/g, '""')}"`;
  }

  return stringValue;
}

function createCsvRow(values: Array<string | number | null>) {
  return values.map(formatCsvValue).join(";");
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

function guestMatchesReservationFilter(
  reservationsCount: number,
  reservationFilter: ReservationFilter
) {
  if (reservationFilter === "WITH_RESERVATIONS") {
    return reservationsCount > 0;
  }

  if (reservationFilter === "WITHOUT_RESERVATIONS") {
    return reservationsCount === 0;
  }

  return true;
}

function createExportFileName() {
  const now = new Date();

  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const hour = String(now.getHours()).padStart(2, "0");
  const minute = String(now.getMinutes()).padStart(2, "0");

  return `goscie-${year}-${month}-${day}-${hour}${minute}.csv`;
}

export async function GET(request: Request) {
  const url = new URL(request.url);
  const searchQuery = getSearchQuery(url.searchParams.get("q"));
  const reservationFilter = getReservationFilter(url.searchParams.get("filter"));

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
    guestMatchesReservationFilter(guest.reservations.length, reservationFilter)
  );

  const header = createCsvRow([
    "Gość",
    "Imię",
    "Nazwisko",
    "Email",
    "Telefon",
    "Kraj",
    "Liczba rezerwacji",
    "Liczba nocy",
    "Wartość pobytów",
    "Wpłacono",
    "Pozostało",
    "Ostatni pobyt",
    "Ostatni domek",
    "Dodano do bazy",
  ]);

  const rows = guests.map((guest) => {
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
    const lastReservationDate = getLastReservationDate(guest.reservations);

    return createCsvRow([
      getGuestFullName(guest.firstName, guest.lastName),
      guest.firstName,
      guest.lastName,
      guest.email,
      guest.phone,
      guest.country,
      reservationsCount,
      totalNights,
      totalValue,
      totalPaid,
      totalRemaining,
      formatDate(lastReservationDate),
      lastReservation
        ? lastReservation.cabin.shortName || lastReservation.cabin.name
        : "",
      formatDate(guest.createdAt),
    ]);
  });

  const csvContent = ["\uFEFF" + header, ...rows].join("\r\n");

  return new Response(csvContent, {
    headers: {
      "Content-Type": "text/csv; charset=utf-8",
      "Content-Disposition": `attachment; filename="${createExportFileName()}"`,
      "Cache-Control": "no-store",
    },
  });
}