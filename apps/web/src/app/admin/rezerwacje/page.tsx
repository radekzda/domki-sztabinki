import Link from "next/link";
import { prisma } from "@/lib/prisma";

type Props = {
  searchParams?: Promise<{
    status?: string;
    source?: string;
    payment?: string;
    q?: string;
    dateFrom?: string;
    dateTo?: string;
  }>;
};

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

const quickStatusFilters = [
  {
    label: "Wszystkie",
    status: "ALL",
  },
  {
    label: "Oczekuje na potwierdzenie",
    status: "PENDING",
  },
  {
    label: "Potwierdzona",
    status: "CONFIRMED",
  },
  {
    label: "Zameldowany",
    status: "CHECKED_IN",
  },
  {
    label: "Wymeldowany",
    status: "CHECKED_OUT",
  },
  {
    label: "Anulowany",
    status: "CANCELLED",
  },
];

const quickSourceFilters = [
  {
    label: "Wszystkie źródła",
    source: "ALL",
  },
  {
    label: "Ręcznie",
    source: "MANUAL",
  },
  {
    label: "Telefon",
    source: "PHONE",
  },
  {
    label: "WWW",
    source: "WEBSITE",
  },
  {
    label: "Booking",
    source: "BOOKING",
  },
  {
    label: "Airbnb",
    source: "AIRBNB",
  },
];

const quickPaymentFilters = [
  {
    label: "Wszystkie płatności",
    payment: "ALL",
  },
  {
    label: "Oczekuje",
    payment: "PENDING",
  },
  {
    label: "Opłacona",
    payment: "PAID",
  },
  {
    label: "Częściowa",
    payment: "PARTIAL",
  },
  {
    label: "Zwrócona",
    payment: "REFUNDED",
  },
];

function getStatusFilter(value: string | undefined) {
  if (!value) {
    return "ALL";
  }

  if (!allowedStatuses.includes(value)) {
    return "ALL";
  }

  return value;
}

function getSourceFilter(value: string | undefined) {
  if (!value) {
    return "ALL";
  }

  if (!allowedSources.includes(value)) {
    return "ALL";
  }

  return value;
}

function getPaymentFilter(value: string | undefined) {
  if (!value) {
    return "ALL";
  }

  if (!allowedPaymentFilters.includes(value)) {
    return "ALL";
  }

  return value;
}

function getSearchQuery(value: string | undefined) {
  if (!value) {
    return "";
  }

  return value.trim();
}

function getDateInputValue(value: string | undefined) {
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

function formatDate(date: Date) {
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

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

function formatMoney(value: number | null) {
  if (value === null) {
    return "—";
  }

  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
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

function getStatusLabel(status: string) {
  switch (status) {
    case "PENDING":
      return "Oczekuje na potwierdzenie";
    case "CONFIRMED":
      return "Potwierdzona";
    case "CHECKED_IN":
      return "Zameldowany";
    case "CHECKED_OUT":
      return "Wymeldowany";
    case "COMPLETED":
      return "Wymeldowany";
    case "CANCELLED":
      return "Anulowany";
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

function getPaymentFilterLabel(paymentFilter: string) {
  switch (paymentFilter) {
    case "PENDING":
      return "Oczekuje";
    case "PAID":
      return "Opłacona";
    case "PARTIAL":
      return "Częściowa";
    case "REFUNDED":
      return "Zwrócona";
    default:
      return paymentFilter;
  }
}

function getStatusClassName(status: string) {
  switch (status) {
    case "PENDING":
      return "bg-orange-100 text-orange-800";
    case "CONFIRMED":
      return "bg-blue-100 text-blue-700";
    case "CHECKED_IN":
      return "bg-green-100 text-green-700";
    case "CHECKED_OUT":
      return "bg-zinc-100 text-zinc-700";
    case "COMPLETED":
      return "bg-zinc-100 text-zinc-700";
    case "CANCELLED":
      return "bg-red-100 text-red-700";
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

function getPaymentClassName(remainingAmount: number | null) {
  if (remainingAmount === null) {
    return "text-zinc-500";
  }

  if (remainingAmount === 0) {
    return "text-green-700";
  }

  return "text-yellow-700";
}

function getPaymentLabel(remainingAmount: number | null) {
  if (remainingAmount === null) {
    return "Brak ceny";
  }

  if (remainingAmount === 0) {
    return "Opłacono";
  }

  return `Do zapłaty ${formatMoney(remainingAmount)}`;
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

function getReservationPaymentStatusLabel(status: ReservationPaymentStatus) {
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

function getReservationPaymentStatusClassName(status: ReservationPaymentStatus) {
  switch (status) {
    case "PENDING":
      return "bg-orange-100 text-orange-800";
    case "PAID":
      return "bg-green-100 text-green-700";
    case "PARTIAL":
      return "bg-yellow-100 text-yellow-800";
    case "REFUNDED":
      return "bg-zinc-100 text-zinc-700";
    default:
      return "bg-zinc-100 text-zinc-700";
  }
}

function getQuickStatusFilterClassName(isActive: boolean) {
  if (isActive) {
    return "rounded-full bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800";
  }

  return "rounded-full border bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900";
}

function getQuickSourceFilterClassName(isActive: boolean) {
  if (isActive) {
    return "rounded-full bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-800";
  }

  return "rounded-full border bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900";
}

function getQuickPaymentFilterClassName(isActive: boolean) {
  if (isActive) {
    return "rounded-full bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800";
  }

  return "rounded-full border bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900";
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

function isCurrentlyCheckedIn({
  status,
  startDate,
  endDate,
  checkInAt,
  checkOutAt,
}: {
  status: string;
  startDate: Date;
  endDate: Date;
  checkInAt: Date | null;
  checkOutAt: Date | null;
}) {
  if (status === "CHECKED_IN") {
    return true;
  }

  if (status !== "CONFIRMED") {
    return false;
  }

  const now = new Date();
  const checkInDate = checkInAt ?? startDate;
  const checkOutDate = checkOutAt ?? endDate;

  return (
    now.getTime() >= checkInDate.getTime() &&
    now.getTime() < checkOutDate.getTime()
  );
}

function getReservationStatusLabel(reservation: {
  status: string;
  startDate: Date;
  endDate: Date;
  checkInAt: Date | null;
  checkOutAt: Date | null;
}) {
  if (isCurrentlyCheckedIn(reservation)) {
    return "Zameldowany";
  }

  return getStatusLabel(reservation.status);
}

function getReservationStatusClassName(reservation: {
  status: string;
  startDate: Date;
  endDate: Date;
  checkInAt: Date | null;
  checkOutAt: Date | null;
}) {
  if (isCurrentlyCheckedIn(reservation)) {
    return "bg-green-100 text-green-700";
  }

  return getStatusClassName(reservation.status);
}

function hasActiveFilters({
  searchQuery,
  statusFilter,
  sourceFilter,
  paymentFilter,
  dateFrom,
  dateTo,
}: {
  searchQuery: string;
  statusFilter: string;
  sourceFilter: string;
  paymentFilter: string;
  dateFrom: string;
  dateTo: string;
}) {
  return (
    searchQuery !== "" ||
    statusFilter !== "ALL" ||
    sourceFilter !== "ALL" ||
    paymentFilter !== "ALL" ||
    dateFrom !== "" ||
    dateTo !== ""
  );
}

function buildReservationsUrl({
  searchQuery,
  statusFilter,
  sourceFilter,
  paymentFilter,
  dateFrom,
  dateTo,
}: {
  searchQuery: string;
  statusFilter: string;
  sourceFilter: string;
  paymentFilter: string;
  dateFrom: string;
  dateTo: string;
}) {
  const params = new URLSearchParams();

  if (searchQuery) {
    params.set("q", searchQuery);
  }

  if (dateFrom) {
    params.set("dateFrom", dateFrom);
  }

  if (dateTo) {
    params.set("dateTo", dateTo);
  }

  if (statusFilter !== "ALL") {
    params.set("status", statusFilter);
  }

  if (sourceFilter !== "ALL") {
    params.set("source", sourceFilter);
  }

  if (paymentFilter !== "ALL") {
    params.set("payment", paymentFilter);
  }

  const queryString = params.toString();

  return queryString ? `/admin/rezerwacje?${queryString}` : "/admin/rezerwacje";
}

function buildExportUrl({
  searchQuery,
  statusFilter,
  sourceFilter,
  paymentFilter,
  dateFrom,
  dateTo,
}: {
  searchQuery: string;
  statusFilter: string;
  sourceFilter: string;
  paymentFilter: string;
  dateFrom: string;
  dateTo: string;
}) {
  const params = new URLSearchParams();

  if (searchQuery) {
    params.set("q", searchQuery);
  }

  if (dateFrom) {
    params.set("dateFrom", dateFrom);
  }

  if (dateTo) {
    params.set("dateTo", dateTo);
  }

  if (statusFilter !== "ALL") {
    params.set("status", statusFilter);
  }

  if (sourceFilter !== "ALL") {
    params.set("source", sourceFilter);
  }

  if (paymentFilter !== "ALL") {
    params.set("payment", paymentFilter);
  }

  const queryString = params.toString();

  return queryString
    ? `/admin/rezerwacje/export?${queryString}`
    : "/admin/rezerwacje/export";
}

export default async function ReservationsPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const statusFilter = getStatusFilter(resolvedSearchParams?.status);
  const sourceFilter = getSourceFilter(resolvedSearchParams?.source);
  const paymentFilter = getPaymentFilter(resolvedSearchParams?.payment);
  const searchQuery = getSearchQuery(resolvedSearchParams?.q);
  const dateFrom = getDateInputValue(resolvedSearchParams?.dateFrom);
  const dateTo = getDateInputValue(resolvedSearchParams?.dateTo);

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

  const totalReservations = reservations.length;

  const pendingReservations = reservations.filter(
    (reservation) => reservation.status === "PENDING"
  ).length;

  const confirmedReservations = reservations.filter(
    (reservation) => reservation.status === "CONFIRMED"
  ).length;

  const checkedInReservations = reservations.filter((reservation) =>
    isCurrentlyCheckedIn(reservation)
  ).length;

  const unpaidReservations = reservations.filter((reservation) => {
    const totalPrice = decimalToNumber(reservation.totalPrice);
    const paidAmount = decimalToNumber(reservation.paidAmount);
    const remainingAmount = getRemainingAmount(totalPrice, paidAmount);

    return remainingAmount !== null && remainingAmount > 0;
  }).length;

  const financialSummary = reservations.reduce(
    (summary, reservation) => {
      const totalPrice = decimalToNumber(reservation.totalPrice) ?? 0;
      const paidAmount = decimalToNumber(reservation.paidAmount) ?? 0;
      const remainingAmount = Math.max(0, totalPrice - paidAmount);

      return {
        totalPrice: summary.totalPrice + totalPrice,
        paidAmount: summary.paidAmount + paidAmount,
        remainingAmount: summary.remainingAmount + remainingAmount,
        nights: summary.nights + reservation.nights,
      };
    },
    {
      totalPrice: 0,
      paidAmount: 0,
      remainingAmount: 0,
      nights: 0,
    }
  );

  const averageReservationValue =
    totalReservations > 0
      ? Math.round(financialSummary.totalPrice / totalReservations)
      : 0;

  const activeFilters = hasActiveFilters({
    searchQuery,
    statusFilter,
    sourceFilter,
    paymentFilter,
    dateFrom,
    dateTo,
  });

  const exportUrl = buildExportUrl({
    searchQuery,
    statusFilter,
    sourceFilter,
    paymentFilter,
    dateFrom,
    dateTo,
  });

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Rezerwacje</h1>

          <p className="mt-2 text-zinc-500">
            Lista rezerwacji z terminem, domkiem, liczbą nocy i płatnościami.
          </p>
        </div>

        <div className="flex flex-wrap gap-3">
          <Link
            href={exportUrl}
            className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
          >
            Eksport CSV
          </Link>

          <Link
            href="/admin/rezerwacje/import"
            className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
          >
            Import CSV
          </Link>

          <Link
            href="/admin/rezerwacje/nowa"
            className="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
          >
            + Dodaj rezerwację
          </Link>
        </div>
      </div>

      <section className="grid gap-4 md:grid-cols-5">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Rezerwacje</div>
          <div className="mt-1 text-3xl font-bold">{totalReservations}</div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Oczekuje</div>
          <div className="mt-1 text-3xl font-bold text-orange-700">
            {pendingReservations}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Potwierdzone</div>
          <div className="mt-1 text-3xl font-bold text-blue-700">
            {confirmedReservations}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Zameldowani</div>
          <div className="mt-1 text-3xl font-bold text-green-700">
            {checkedInReservations}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Do zapłaty</div>
          <div className="mt-1 text-3xl font-bold text-yellow-700">
            {unpaidReservations}
          </div>
        </div>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Podsumowanie finansowe</h2>

        <p className="mt-1 text-sm text-zinc-500">
          Wartości liczone są dla aktualnie widocznych rezerwacji po filtrach.
        </p>

        <div className="mt-5 grid gap-4 md:grid-cols-5">
          <div className="rounded-xl bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Cena pobytów razem</div>
            <div className="mt-1 text-2xl font-bold text-green-700">
              {formatMoney(financialSummary.totalPrice)}
            </div>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Wpłacono razem</div>
            <div className="mt-1 text-2xl font-bold text-green-700">
              {formatMoney(financialSummary.paidAmount)}
            </div>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Pozostało razem</div>
            <div className="mt-1 text-2xl font-bold text-yellow-700">
              {formatMoney(financialSummary.remainingAmount)}
            </div>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Liczba nocy</div>
            <div className="mt-1 text-2xl font-bold">
              {financialSummary.nights}
            </div>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <div className="text-sm text-zinc-500">Średnia rezerwacja</div>
            <div className="mt-1 text-2xl font-bold">
              {formatMoney(averageReservationValue)}
            </div>
          </div>
        </div>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="mb-5 space-y-5">
          <div>
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szybki filtr statusu rezerwacji
            </div>

            <div className="flex flex-wrap gap-2">
              {quickStatusFilters.map((filter) => {
                const isActive = filter.status === statusFilter;

                return (
                  <Link
                    key={filter.status}
                    href={buildReservationsUrl({
                      searchQuery,
                      statusFilter: filter.status,
                      sourceFilter,
                      paymentFilter,
                      dateFrom,
                      dateTo,
                    })}
                    className={getQuickStatusFilterClassName(isActive)}
                  >
                    {filter.label}
                  </Link>
                );
              })}
            </div>
          </div>

          <div>
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szybki filtr statusu płatności
            </div>

            <div className="flex flex-wrap gap-2">
              {quickPaymentFilters.map((filter) => {
                const isActive = filter.payment === paymentFilter;

                return (
                  <Link
                    key={filter.payment}
                    href={buildReservationsUrl({
                      searchQuery,
                      statusFilter,
                      sourceFilter,
                      paymentFilter: filter.payment,
                      dateFrom,
                      dateTo,
                    })}
                    className={getQuickPaymentFilterClassName(isActive)}
                  >
                    {filter.label}
                  </Link>
                );
              })}
            </div>
          </div>

          <div>
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szybki filtr źródła
            </div>

            <div className="flex flex-wrap gap-2">
              {quickSourceFilters.map((filter) => {
                const isActive = filter.source === sourceFilter;

                return (
                  <Link
                    key={filter.source}
                    href={buildReservationsUrl({
                      searchQuery,
                      statusFilter,
                      sourceFilter: filter.source,
                      paymentFilter,
                      dateFrom,
                      dateTo,
                    })}
                    className={getQuickSourceFilterClassName(isActive)}
                  >
                    {filter.label}
                  </Link>
                );
              })}
            </div>
          </div>
        </div>

        <form className="grid gap-4 md:grid-cols-6">
          <div className="space-y-1 md:col-span-2">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Szukaj
            </label>

            <input
              name="q"
              defaultValue={searchQuery}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
              placeholder="Gość, email, telefon albo domek"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Pobyt od
            </label>

            <input
              type="date"
              name="dateFrom"
              defaultValue={dateFrom}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Pobyt do
            </label>

            <input
              type="date"
              name="dateTo"
              defaultValue={dateTo}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
            />
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Status rezerwacji
            </label>

            <select
              name="status"
              defaultValue={statusFilter}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
            >
              <option value="ALL">Wszystkie statusy</option>
              <option value="PENDING">Oczekuje na potwierdzenie</option>
              <option value="CONFIRMED">Potwierdzona</option>
              <option value="CHECKED_IN">Zameldowany</option>
              <option value="CHECKED_OUT">Wymeldowany</option>
              <option value="CANCELLED">Anulowany</option>
            </select>
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Źródło
            </label>

            <select
              name="source"
              defaultValue={sourceFilter}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
            >
              <option value="ALL">Wszystkie źródła</option>
              <option value="MANUAL">Ręcznie</option>
              <option value="PHONE">Telefon</option>
              <option value="WEBSITE">WWW</option>
              <option value="BOOKING">Booking</option>
              <option value="AIRBNB">Airbnb</option>
            </select>
          </div>

          <div className="space-y-1">
            <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
              Status płatności
            </label>

            <select
              name="payment"
              defaultValue={paymentFilter}
              className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
            >
              <option value="ALL">Wszystkie płatności</option>
              <option value="PENDING">Oczekuje</option>
              <option value="PAID">Opłacona</option>
              <option value="PARTIAL">Częściowa</option>
              <option value="REFUNDED">Zwrócona</option>
            </select>
          </div>

          <div className="flex items-end gap-3 md:col-span-5">
            <button className="h-10 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white hover:bg-zinc-800">
              Filtruj
            </button>

            <Link
              href="/admin/rezerwacje"
              className="flex h-10 items-center rounded-lg border px-4 text-sm font-semibold hover:bg-zinc-50"
            >
              Wyczyść
            </Link>
          </div>
        </form>
      </section>

      <section className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
          <h2 className="text-xl font-semibold">Lista rezerwacji</h2>

          {activeFilters ? (
            <div className="flex flex-wrap gap-2 text-sm">
              {searchQuery ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Szukaj: {searchQuery}
                </span>
              ) : null}

              {dateFrom ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Od: {dateFrom}
                </span>
              ) : null}

              {dateTo ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Do: {dateTo}
                </span>
              ) : null}

              {statusFilter !== "ALL" ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Status: {getStatusLabel(statusFilter)}
                </span>
              ) : null}

              {paymentFilter !== "ALL" ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Płatność: {getPaymentFilterLabel(paymentFilter)}
                </span>
              ) : null}

              {sourceFilter !== "ALL" ? (
                <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
                  Źródło: {getSourceLabel(sourceFilter)}
                </span>
              ) : null}
            </div>
          ) : null}
        </div>

        {reservations.length === 0 ? (
          <div className="p-8 text-center text-zinc-500">
            Brak rezerwacji dla wybranych filtrów lub wyszukiwanej frazy.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[1450px] border-collapse text-sm">
              <thead className="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                <tr>
                  <th className="border-b p-4">Gość</th>
                  <th className="border-b p-4">Domek</th>
                  <th className="border-b p-4">Termin</th>
                  <th className="border-b p-4 text-center">Noce</th>
                  <th className="border-b p-4 text-center">Goście</th>
                  <th className="border-b p-4 text-right">Cena</th>
                  <th className="border-b p-4 text-right">Wpłacono</th>
                  <th className="border-b p-4 text-right">Pozostało</th>
                  <th className="border-b p-4">Status</th>
                  <th className="border-b p-4">Status płatności</th>
                  <th className="border-b p-4">Źródło</th>
                  <th className="border-b p-4 text-right">Akcje</th>
                </tr>
              </thead>

              <tbody>
                {reservations.map((reservation) => {
                  const totalPrice = decimalToNumber(reservation.totalPrice);
                  const paidAmount = decimalToNumber(reservation.paidAmount);
                  const remainingAmount = getRemainingAmount(
                    totalPrice,
                    paidAmount
                  );

                  const reservationPaymentStatus = getReservationPaymentStatus({
                    paymentStatus: reservation.paymentStatus,
                    totalPrice,
                    paidAmount,
                  });

                  return (
                    <tr
                      key={reservation.id}
                      className="align-top hover:bg-zinc-50"
                    >
                      <td className="border-b p-4">
                        <div className="font-semibold">
                          {reservation.guestName}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          {reservation.phone || "brak telefonu"}
                        </div>

                        <div className="mt-1 text-zinc-500">
                          {reservation.email || "brak emaila"}
                        </div>
                      </td>

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
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getReservationStatusClassName(
                            reservation
                          )}`}
                        >
                          {getReservationStatusLabel(reservation)}
                        </span>
                      </td>

                      <td className="border-b p-4">
                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getReservationPaymentStatusClassName(
                            reservationPaymentStatus
                          )}`}
                        >
                          {getReservationPaymentStatusLabel(
                            reservationPaymentStatus
                          )}
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

                          <Link
                            href={`/admin/rezerwacje/${reservation.id}/usun`}
                            className="rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800"
                          >
                            Usuń
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