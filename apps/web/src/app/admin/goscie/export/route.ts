import {
  getAdminRouteUnauthorizedResponse,
  isAdminRouteRequestAuthorized,
} from "@/lib/adminRouteAuth";
import { prisma } from "@/lib/prisma";

type GuestFilter =
  | "ALL"
  | "WITH_RESERVATIONS"
  | "WITHOUT_RESERVATIONS"
  | "MISSING_CONTACT"
  | "VIP"
  | "SOURCE_BASE44"
  | "SOURCE_CSV_IMPORT"
  | "SOURCE_MANUAL";

function getSearchQuery(value: string | null) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function getGuestFilter(value: string | null): GuestFilter {
  if (value === "WITH_RESERVATIONS") {
    return "WITH_RESERVATIONS";
  }

  if (value === "WITHOUT_RESERVATIONS") {
    return "WITHOUT_RESERVATIONS";
  }

  if (value === "MISSING_CONTACT") {
    return "MISSING_CONTACT";
  }

  if (value === "VIP") {
    return "VIP";
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
  }>,
) {
  if (reservations.length === 0) {
    return null;
  }

  return reservations
    .map((reservation) => reservation.checkInAt ?? reservation.startDate)
    .sort((a, b) => b.getTime() - a.getTime())[0];
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
    isVip: boolean;
    reservations: unknown[];
  },
  guestFilter: GuestFilter,
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

  if (guestFilter === "VIP") {
    return guest.isVip;
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
  if (!isAdminRouteRequestAuthorized(request)) {
    return getAdminRouteUnauthorizedResponse(request);
  }

  const url = new URL(request.url);
  const searchQuery = getSearchQuery(url.searchParams.get("q"));
  const guestFilter = getGuestFilter(url.searchParams.get("filter"));

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
              {
                pesel: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                documentNumber: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                nationality: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
              {
                externalGuestId: {
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
    guestMatchesFilter(guest, guestFilter),
  );

  const header = createCsvRow([
    "Gość",
    "Imię",
    "Nazwisko",
    "Email",
    "Telefon",
    "Kraj",
    "Ulica i numer",
    "Kod pocztowy",
    "Miasto",
    "Pełny adres",
    "PESEL",
    "Numer dokumentu",
    "Narodowość",
    "Data urodzenia",
    "VIP",
    "Zewnętrzne ID",
    "Źródło techniczne",
    "Notatki",
    "Liczba rezerwacji",
    "Liczba nocy",
    "Wartość pobytów",
    "Wpłacono",
    "Pozostało",
    "Ostatni pobyt",
    "Ostatni domek",
    "Dodano do bazy",
    "Ostatnia aktualizacja",
  ]);

  const rows = guests.map((guest) => {
    const reservationsCount = guest.reservations.length;

    const totalNights = guest.reservations.reduce(
      (sum, reservation) => sum + reservation.nights,
      0,
    );

    const totalValue = guest.reservations.reduce(
      (sum, reservation) => sum + decimalToNumber(reservation.totalPrice),
      0,
    );

    const totalPaid = guest.reservations.reduce(
      (sum, reservation) => sum + decimalToNumber(reservation.paidAmount),
      0,
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
      guest.street,
      guest.postalCode,
      guest.city,
      guest.fullAddress,
      guest.pesel,
      guest.documentNumber,
      guest.nationality,
      formatDate(guest.birthDate),
      guest.isVip ? "Tak" : "Nie",
      guest.externalGuestId,
      getSourceLabel(guest.source),
      guest.notes,
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
      formatDate(guest.updatedAt),
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