import {
  getAdminRouteUnauthorizedResponse,
  isAdminRouteRequestAuthorized,
} from "@/lib/adminRouteAuth";
import { prisma } from "@/lib/prisma";

type InquiryStatusFilter = "NEW" | "APPROVED" | "ARCHIVED";

const allowedStatuses: InquiryStatusFilter[] = [
  "NEW",
  "APPROVED",
  "ARCHIVED",
];

function getStatusFilter(value: string | null) {
  if (!value) {
    return null;
  }

  if (!allowedStatuses.includes(value as InquiryStatusFilter)) {
    return null;
  }

  return value as InquiryStatusFilter;
}

function getSearchQuery(value: string | null) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function formatDate(date: Date | null) {
  if (!date) {
    return "";
  }

  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: "Europe/Warsaw",
  }).format(date);
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
    timeZone: "Europe/Warsaw",
  }).format(date);
}

function getStatusLabel(status: string) {
  if (status === "NEW") {
    return "Nowe";
  }

  if (status === "APPROVED" || status === "CONTACTED") {
    return "Zatwierdzone";
  }

  if (status === "ARCHIVED") {
    return "Archiwalne";
  }

  return status;
}

function getSourceLabel(source: string) {
  if (source === "WWW") {
    return "WWW";
  }

  return source;
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

  return `zapytania-www-${year}-${month}-${day}-${hour}${minute}.csv`;
}

export async function GET(request: Request) {
  if (!isAdminRouteRequestAuthorized(request)) {
    return getAdminRouteUnauthorizedResponse(request);
  }

  const url = new URL(request.url);

  const activeStatus = getStatusFilter(url.searchParams.get("status"));
  const searchQuery = getSearchQuery(url.searchParams.get("q"));

  const inquiryWhere = {
    ...(activeStatus === "APPROVED"
      ? {
          status: {
            in: ["APPROVED", "CONTACTED"],
          },
        }
      : activeStatus
        ? {
            status: activeStatus,
          }
        : {}),
    ...(searchQuery
      ? {
          OR: [
            {
              fullName: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              phone: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              email: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              cabinName: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              notes: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              cabin: {
                is: {
                  name: {
                    contains: searchQuery,
                    mode: "insensitive" as const,
                  },
                },
              },
            },
          ],
        }
      : {}),
  };

  const inquiries = await prisma.inquiry.findMany({
    where: inquiryWhere,
    orderBy: {
      createdAt: "desc",
    },
    include: {
      cabin: {
        select: {
          name: true,
        },
      },
    },
  });

  const header = createCsvRow([
    "Imię i nazwisko",
    "Imię",
    "Nazwisko",
    "Telefon",
    "Email",
    "Domek",
    "Przyjazd",
    "Wyjazd",
    "Liczba osób",
    "Dorośli",
    "Dzieci",
    "Status",
    "Źródło",
    "Ulica i numer",
    "Kod pocztowy",
    "Miasto",
    "Kraj",
    "Wiadomość",
    "Wysłano",
  ]);

  const rows = inquiries.map((inquiry) => {
    const selectedCabinName =
      inquiry.cabin?.name || inquiry.cabinName || "Dowolny / do ustalenia";

    return createCsvRow([
      inquiry.fullName,
      inquiry.firstName,
      inquiry.lastName,
      inquiry.phone,
      inquiry.email,
      selectedCabinName,
      formatDate(inquiry.dateFrom),
      formatDate(inquiry.dateTo),
      inquiry.guests,
      inquiry.adults,
      inquiry.children,
      getStatusLabel(inquiry.status),
      getSourceLabel(inquiry.source),
      inquiry.street,
      inquiry.postalCode,
      inquiry.city,
      inquiry.country,
      inquiry.notes,
      formatDateTime(inquiry.createdAt),
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