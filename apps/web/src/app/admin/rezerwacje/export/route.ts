import { prisma } from "@/lib/prisma";

const allowedStatuses = [
  "ALL",
  "PENDING",
  "CONFIRMED",
  "CANCELLED",
  "COMPLETED",
];

const allowedSources = [
  "ALL",
  "MANUAL",
  "PHONE",
  "WEBSITE",
  "BOOKING",
  "AIRBNB",
];

function getStatusFilter(value: string | null) {
  if (!value) {
    return "ALL";
  }

  if (!allowedStatuses.includes(value)) {
    return "ALL";
  }

  return value;
}

function getSourceFilter(value: string | null) {
  if (!value) {
    return "ALL";
  }

  if (!allowedSources.includes(value)) {
    return "ALL";
  }

  return value;
}

function getSearchQuery(value: string | null) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function getDateInputValue(value: string | null) {
  if (!value) {
    return "";
  }

  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return "";
  }

  return value;
}

function createDateAtStart(dateValue: string) {
  return new Date(`${dateValue}T00:00:00.000`);
}

function createDateAtEnd(dateValue: string) {
  return new Date(`${dateValue}T23:59:59.999`);
}

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

function getRemainingAmount(
  totalPrice: number | null,
  paidAmount: number | null
) {
  if (totalPrice === null) {
    return null;
  }

  return Math.max(0, totalPrice - (paidAmount ?? 0));
}

function formatDateTime(date: Date | null) {
  if (!date) {
    return "";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
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

function createExportFileName() {
  const now = new Date();

  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const hour = String(now.getHours()).padStart(2, "0");
  const minute = String(now.getMinutes()).padStart(2, "0");

  return `rezerwacje-${year}-${month}-${day}-${hour}${minute}.csv`;
}

export async function GET(request: Request) {
  const url = new URL(request.url);

  const statusFilter = getStatusFilter(url.searchParams.get("status"));
  const sourceFilter = getSourceFilter(url.searchParams.get("source"));
  const searchQuery = getSearchQuery(url.searchParams.get("q"));
  const dateFrom = getDateInputValue(url.searchParams.get("dateFrom"));
  const dateTo = getDateInputValue(url.searchParams.get("dateTo"));

  const dateConditions: Array<
    { endDate: { gte: Date } } | { startDate: { lte: Date } }
  > = [];

  if (dateFrom) {
    dateConditions.push({
      endDate: {
        gte: createDateAtStart(dateFrom),
      },
    });
  }

  if (dateTo) {
    dateConditions.push({
      startDate: {
        lte: createDateAtEnd(dateTo),
      },
    });
  }

  const reservations = await prisma.reservation.findMany({
    where: {
      ...(statusFilter !== "ALL"
        ? {
            status: statusFilter,
          }
        : {}),

      ...(sourceFilter !== "ALL"
        ? {
            source: sourceFilter,
          }
        : {}),

      ...(dateConditions.length > 0
        ? {
            AND: dateConditions,
          }
        : {}),

      ...(searchQuery
        ? {
            OR: [
              {
                guestName: {
                  contains: searchQuery,
                  mode: "insensitive",
                },
              },
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
                cabin: {
                  name: {
                    contains: searchQuery,
                    mode: "insensitive",
                  },
                },
              },
              {
                cabin: {
                  shortName: {
                    contains: searchQuery,
                    mode: "insensitive",
                  },
                },
              },
            ],
          }
        : {}),
    },
    orderBy: [
      {
        startDate: "asc",
      },
      {
        createdAt: "desc",
      },
    ],
    include: {
      cabin: true,
    },
  });

  const header = createCsvRow([
    "Gość",
    "Imię",
    "Nazwisko",
    "Email",
    "Telefon",
    "Domek",
    "Przyjazd",
    "Wyjazd",
    "Noce",
    "Goście",
    "Dorośli",
    "Dzieci",
    "Status",
    "Źródło",
    "Cena pobytu",
    "Cena za noc",
    "Wpłacono",
    "Pozostało",
    "Adres",
    "Uwagi",
  ]);

  const rows = reservations.map((reservation) => {
    const totalPrice = decimalToNumber(reservation.totalPrice);
    const pricePerNight = decimalToNumber(reservation.pricePerNight);
    const paidAmount = decimalToNumber(reservation.paidAmount);
    const remainingAmount = getRemainingAmount(totalPrice, paidAmount);

    const address = [
      reservation.street,
      [reservation.postalCode, reservation.city].filter(Boolean).join(" "),
      reservation.country,
    ]
      .filter(Boolean)
      .join(", ");

    return createCsvRow([
      reservation.guestName,
      reservation.firstName,
      reservation.lastName,
      reservation.email,
      reservation.phone,
      reservation.cabin.shortName || reservation.cabin.name,
      formatDateTime(reservation.checkInAt ?? reservation.startDate),
      formatDateTime(reservation.checkOutAt ?? reservation.endDate),
      reservation.nights,
      reservation.guests,
      reservation.adults,
      reservation.children,
      getStatusLabel(reservation.status),
      getSourceLabel(reservation.source),
      totalPrice,
      pricePerNight,
      paidAmount,
      remainingAmount,
      address,
      reservation.notes,
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