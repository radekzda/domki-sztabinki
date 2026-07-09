import { prisma } from "@/lib/prisma";

type ReservationPaymentStatus = "PENDING" | "PAID" | "PARTIAL" | "REFUNDED";

const allowedStatuses = [
  "ALL",
  "PENDING",
  "CONFIRMED",
  "CHECKED_IN",
  "CHECKED_OUT",
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

const allowedPaymentFilters = [
  "ALL",
  "PENDING",
  "PAID",
  "PARTIAL",
  "REFUNDED",
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

function getPaymentFilter(value: string | null) {
  if (!value) {
    return "ALL";
  }

  if (!allowedPaymentFilters.includes(value)) {
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
  paidAmount: number | null,
) {
  if (totalPrice === null) {
    return null;
  }

  return Math.max(0, totalPrice - (paidAmount ?? 0));
}

function getReservationPaymentStatus({
  paymentStatus,
  totalPrice,
  paidAmount,
}: {
  paymentStatus: string | null;
  totalPrice: number | null;
  paidAmount: number | null;
}): ReservationPaymentStatus {
  if (paymentStatus === "REFUNDED") {
    return "REFUNDED";
  }

  if (paymentStatus === "PAID") {
    return "PAID";
  }

  if (paymentStatus === "PARTIAL") {
    return "PARTIAL";
  }

  if (paymentStatus === "PENDING") {
    return "PENDING";
  }

  if (totalPrice === null) {
    return "PENDING";
  }

  if (paidAmount === null || paidAmount <= 0) {
    return "PENDING";
  }

  if (paidAmount >= totalPrice) {
    return "PAID";
  }

  return "PARTIAL";
}

function reservationMatchesPaymentFilter({
  paymentFilter,
  paymentStatus,
  totalPrice,
  paidAmount,
}: {
  paymentFilter: string;
  paymentStatus: string | null;
  totalPrice: number | null;
  paidAmount: number | null;
}) {
  if (paymentFilter === "ALL") {
    return true;
  }

  const reservationPaymentStatus = getReservationPaymentStatus({
    paymentStatus,
    totalPrice,
    paidAmount,
  });

  return reservationPaymentStatus === paymentFilter;
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

function normalizeReservationStatus(status: string) {
  if (status === "COMPLETED") {
    return "CHECKED_OUT";
  }

  if (
    status === "PENDING" ||
    status === "CONFIRMED" ||
    status === "CHECKED_IN" ||
    status === "CHECKED_OUT" ||
    status === "CANCELLED"
  ) {
    return status;
  }

  return "PENDING";
}

function getStatusLabel(status: string) {
  const normalizedStatus = normalizeReservationStatus(status);

  switch (normalizedStatus) {
    case "PENDING":
      return "Oczekuje na potwierdzenie";
    case "CONFIRMED":
      return "Potwierdzona";
    case "CHECKED_IN":
      return "Zameldowany";
    case "CHECKED_OUT":
      return "Wymeldowany";
    case "CANCELLED":
      return "Anulowany";
    default:
      return normalizedStatus;
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

function getPaymentStatusLabel(status: ReservationPaymentStatus) {
  switch (status) {
    case "PENDING":
      return "Oczekuje";
    case "PAID":
      return "Opłacona";
    case "PARTIAL":
      return "Częściowa";
    case "REFUNDED":
      return "Zwrócona";
    default:
      return status;
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
  const paymentFilter = getPaymentFilter(url.searchParams.get("payment"));
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

  const allReservations = await prisma.reservation.findMany({
    where: {
      ...(statusFilter !== "ALL"
        ? {
            status:
              statusFilter === "CHECKED_OUT"
                ? {
                    in: ["CHECKED_OUT", "COMPLETED"],
                  }
                : statusFilter,
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

  const reservations = allReservations.filter((reservation) => {
    const totalPrice = decimalToNumber(reservation.totalPrice);
    const paidAmount = decimalToNumber(reservation.paidAmount);

    return reservationMatchesPaymentFilter({
      paymentFilter,
      paymentStatus: reservation.paymentStatus,
      totalPrice,
      paidAmount,
    });
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
    "Status rezerwacji",
    "Źródło",
    "Status płatności",
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

    const paymentStatus = getReservationPaymentStatus({
      paymentStatus: reservation.paymentStatus,
      totalPrice,
      paidAmount,
    });

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
      getPaymentStatusLabel(paymentStatus),
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