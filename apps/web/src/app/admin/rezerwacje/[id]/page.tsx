import Link from "next/link";
import { notFound } from "next/navigation";
import {
  updateReservationPayment,
  updateReservationStatus,
} from "@/actions/reservations";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

type Props = {
  params: Promise<{
    id: string;
  }>;
  searchParams?: Promise<{
    error?: string;
  }>;
};

const reservationStatuses = [
  { value: "PENDING", label: "Oczekuje na potwierdzenie" },
  { value: "CONFIRMED", label: "Potwierdzona" },
  { value: "CHECKED_IN", label: "Zameldowany" },
  { value: "CHECKED_OUT", label: "Wymeldowany" },
  { value: "CANCELLED", label: "Anulowany" },
];

const paymentStatuses = [
  { value: "PENDING", label: "Oczekuje" },
  { value: "PAID", label: "Opłacona" },
  { value: "PARTIAL", label: "Częściowa" },
  { value: "REFUNDED", label: "Zwrócona" },
];

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

function formatMoneyFromNumber(value: number | null) {
  if (value === null) {
    return "—";
  }

  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
}

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return 0;
  }

  return Number(value.toString());
}

function decimalToNullableNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

function formatMoneyInputValue(value: { toString: () => string } | null) {
  if (!value) {
    return "";
  }

  return value.toString();
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

function getStatusClassName(status: string) {
  const normalizedStatus = normalizeReservationStatus(status);

  switch (normalizedStatus) {
    case "PENDING":
      return "bg-orange-100 text-orange-800";
    case "CONFIRMED":
      return "bg-blue-100 text-blue-800";
    case "CHECKED_IN":
      return "bg-green-100 text-green-800";
    case "CHECKED_OUT":
      return "bg-zinc-100 text-zinc-700";
    case "CANCELLED":
      return "bg-red-100 text-red-800";
    default:
      return "bg-zinc-100 text-zinc-700";
  }
}

function getStatusButtonClassName(status: string, activeStatus: string) {
  const normalizedActiveStatus = normalizeReservationStatus(activeStatus);
  const isActive = status === normalizedActiveStatus;

  if (isActive) {
    switch (status) {
      case "PENDING":
        return "border-orange-600 bg-orange-500 text-white";
      case "CONFIRMED":
        return "border-blue-600 bg-blue-600 text-white";
      case "CHECKED_IN":
        return "border-green-700 bg-green-700 text-white";
      case "CHECKED_OUT":
        return "border-zinc-600 bg-zinc-600 text-white";
      case "CANCELLED":
        return "border-red-600 bg-red-600 text-white";
      default:
        return "border-zinc-600 bg-zinc-600 text-white";
    }
  }

  return "border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50";
}

function getPaymentStatus({
  paymentStatus,
  totalPrice,
  paidAmount,
}: {
  paymentStatus: string | null;
  totalPrice: number;
  paidAmount: number;
}) {
  if (paymentStatus === "PAID") {
    return "PAID";
  }

  if (paymentStatus === "PARTIAL") {
    return "PARTIAL";
  }

  if (paymentStatus === "REFUNDED") {
    return "REFUNDED";
  }

  if (paymentStatus === "PENDING") {
    return "PENDING";
  }

  if (totalPrice <= 0) {
    return "PENDING";
  }

  if (paidAmount <= 0) {
    return "PENDING";
  }

  if (paidAmount >= totalPrice) {
    return "PAID";
  }

  return "PARTIAL";
}

function getPaymentStatusLabel(status: string) {
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

function getPaymentStatusClassName(status: string) {
  switch (status) {
    case "PENDING":
      return "bg-yellow-100 text-yellow-800";
    case "PAID":
      return "bg-green-100 text-green-800";
    case "PARTIAL":
      return "bg-blue-100 text-blue-800";
    case "REFUNDED":
      return "bg-zinc-100 text-zinc-700";
    default:
      return "bg-zinc-100 text-zinc-700";
  }
}

function getPaymentButtonClassName(status: string, activeStatus: string) {
  const isActive = status === activeStatus;

  if (isActive) {
    switch (status) {
      case "PENDING":
        return "border-yellow-600 bg-yellow-500 text-zinc-950";
      case "PAID":
        return "border-green-700 bg-green-700 text-white";
      case "PARTIAL":
        return "border-blue-600 bg-blue-600 text-white";
      case "REFUNDED":
        return "border-zinc-600 bg-zinc-600 text-white";
      default:
        return "border-zinc-600 bg-zinc-600 text-white";
    }
  }

  return "border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50";
}

function getSourceLabel(source: string) {
  switch (source) {
    case "BOOKING":
      return "Booking";
    case "AIRBNB":
      return "Airbnb";
    case "WEBSITE":
      return "WWW";
    case "PHONE":
      return "Telefon";
    case "MANUAL":
      return "Ręcznie";
    default:
      return source;
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

function getPaymentSummaryClassName(remainingAmount: number, isPaid: boolean) {
  if (isPaid) {
    return "text-green-700";
  }

  if (remainingAmount > 0) {
    return "text-yellow-800";
  }

  return "text-zinc-700";
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

function getCalendarHref(date: Date) {
  const year = date.getUTCFullYear();
  const month = date.getUTCMonth() + 1;

  return `/admin/kalendarz?year=${year}&month=${month}`;
}

export default async function ReservationDetailsPage({
  params,
  searchParams,
}: Props) {
  const resolvedParams = await params;
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const reservation = await prisma.reservation.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      cabin: true,
    },
  });

  if (!reservation) {
    notFound();
  }

  const totalPrice = decimalToNumber(reservation.totalPrice);
  const paidAmount = decimalToNumber(reservation.paidAmount);
  const pricePerNight = decimalToNullableNumber(reservation.pricePerNight);
  const remainingAmount = Math.max(0, totalPrice - paidAmount);
  const isPaid = reservation.totalPrice !== null && remainingAmount === 0;
  const normalizedStatus = normalizeReservationStatus(reservation.status);
  const paymentStatus = getPaymentStatus({
    paymentStatus: reservation.paymentStatus,
    totalPrice,
    paidAmount,
  });
  const calendarHref = getCalendarHref(reservation.startDate);

  return (
    <div className="max-w-6xl space-y-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <Link
            href="/admin/rezerwacje"
            className="text-sm text-zinc-500 hover:text-zinc-900"
          >
            ← Wróć do rezerwacji
          </Link>

          <h1 className="mt-3 text-3xl font-bold">{reservation.guestName}</h1>

          <p className="mt-2 text-zinc-500">
            Szczegóły rezerwacji w systemie PMS.
          </p>
        </div>

        <div className="flex flex-wrap items-center justify-end gap-2">
          <Link
            href={`/admin/rezerwacje/${reservation.id}/edytuj`}
            className="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"
          >
            Edytuj rezerwację
          </Link>

          <Link
            href={`/admin/rezerwacje/${reservation.id}/usun`}
            className="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800"
          >
            Usuń rezerwację
          </Link>

          <span
            className={`rounded-full px-4 py-2 text-sm font-semibold ${getStatusClassName(
              normalizedStatus,
            )}`}
          >
            {getStatusLabel(normalizedStatus)}
          </span>

          <span
            className={`rounded-full px-4 py-2 text-sm font-semibold ${getPaymentStatusClassName(
              paymentStatus,
            )}`}
          >
            {getPaymentStatusLabel(paymentStatus)}
          </span>

          <span
            className={`rounded-full px-4 py-2 text-sm font-semibold ${getSourceClassName(
              reservation.source,
            )}`}
          >
            {getSourceLabel(reservation.source)}
          </span>
        </div>
      </div>

      {resolvedSearchParams?.error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
          {resolvedSearchParams.error}
        </div>
      ) : null}

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-semibold">Status rezerwacji</h2>
            <p className="mt-1 text-sm text-zinc-500">
              Szybko zmień status bez wchodzenia w pełną edycję.
            </p>
          </div>

          <form action={updateReservationStatus} className="flex flex-wrap gap-2">
            <input type="hidden" name="reservationId" value={reservation.id} />

            {reservationStatuses.map((status) => (
              <button
                key={status.value}
                type="submit"
                name="status"
                value={status.value}
                className={`rounded-lg border px-4 py-2 text-sm font-semibold ${getStatusButtonClassName(
                  status.value,
                  normalizedStatus,
                )}`}
              >
                {status.label}
              </button>
            ))}
          </form>
        </div>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-start justify-between gap-6">
          <div>
            <h2 className="text-xl font-semibold">Szybka płatność</h2>
            <p className="mt-1 text-sm text-zinc-500">
              Zaktualizuj kwotę wpłaconą i status płatności bez pełnej edycji
              rezerwacji.
            </p>
          </div>

          <form
            action={updateReservationPayment}
            className="flex flex-wrap items-end gap-3"
          >
            <input type="hidden" name="reservationId" value={reservation.id} />

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Wpłacono
              </label>

              <input
                type="number"
                name="paidAmount"
                min={0}
                step="0.01"
                defaultValue={formatMoneyInputValue(reservation.paidAmount)}
                className="h-10 w-40 rounded-lg border px-3 text-sm font-medium"
                placeholder="np. 1000"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Status płatności
              </label>

              <select
                name="paymentStatus"
                defaultValue={paymentStatus}
                className="h-10 w-44 rounded-lg border bg-white px-3 text-sm font-medium"
              >
                {paymentStatuses.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </select>
            </div>

            <button
              type="submit"
              className="h-10 rounded-lg bg-green-700 px-4 text-sm font-semibold text-white hover:bg-green-800"
            >
              Zapisz płatność
            </button>

            <button
              type="submit"
              name="paymentAction"
              value="MARK_AS_PAID"
              className="h-10 rounded-lg border px-4 text-sm font-semibold hover:bg-zinc-50"
            >
              Oznacz jako opłacone
            </button>
          </form>
        </div>

        <div className="mt-5 flex flex-wrap gap-2">
          {paymentStatuses.map((status) => (
            <span
              key={status.value}
              className={`rounded-lg border px-3 py-2 text-xs font-semibold ${getPaymentButtonClassName(
                status.value,
                paymentStatus,
              )}`}
            >
              {status.label}
            </span>
          ))}
        </div>
      </section>

      <div className="grid gap-6 lg:grid-cols-4">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Domek</div>
          <div className="mt-1 text-2xl font-bold">
            {reservation.cabin.name}
          </div>
          <div className="mt-2 text-sm text-zinc-500">
            Maksymalnie {reservation.cabin.maxGuests} osób
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Noce</div>
          <div className="mt-1 text-2xl font-bold">{reservation.nights}</div>
          <div className="mt-2 text-sm text-zinc-500">
            Cena / noc: {formatMoneyFromNumber(pricePerNight)}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Goście</div>
          <div className="mt-1 text-2xl font-bold">{reservation.guests}</div>
          <div className="mt-2 text-sm text-zinc-500">
            Dorośli: {reservation.adults}, dzieci: {reservation.children}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm text-zinc-500">Płatność</div>
          <div
            className={`mt-1 text-2xl font-bold ${getPaymentSummaryClassName(
              remainingAmount,
              isPaid,
            )}`}
          >
            {isPaid ? "Opłacona" : `Do zapłaty ${formatMoneyFromNumber(remainingAmount)}`}
          </div>
          <div className="mt-2 text-sm text-zinc-500">
            Cena: {formatMoney(reservation.totalPrice)}
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold">Termin pobytu</h2>

          <div className="mt-5 space-y-4">
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
              <span className="text-zinc-500">Liczba nocy</span>
              <span className="text-right font-semibold">
                {reservation.nights}
              </span>
            </div>

            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Utworzono</span>
              <span className="text-right font-semibold">
                {formatDateTime(reservation.createdAt)}
              </span>
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold">Dane kontaktowe</h2>

          <div className="mt-5 space-y-4">
            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Imię</span>
              <span className="text-right font-semibold">
                {reservation.firstName || "—"}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Nazwisko</span>
              <span className="text-right font-semibold">
                {reservation.lastName || "—"}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Email</span>
              <span className="text-right font-semibold">
                {reservation.email || "—"}
              </span>
            </div>

            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Telefon</span>
              <span className="text-right font-semibold">
                {reservation.phone || "—"}
              </span>
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold">Płatności</h2>

          <div className="mt-5 space-y-4">
            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Status płatności</span>
              <span
                className={`rounded-full px-3 py-1 text-sm font-semibold ${getPaymentStatusClassName(
                  paymentStatus,
                )}`}
              >
                {getPaymentStatusLabel(paymentStatus)}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Cena za noc</span>
              <span className="text-right font-semibold">
                {formatMoneyFromNumber(pricePerNight)}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Liczba nocy</span>
              <span className="text-right font-semibold">
                {reservation.nights}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Cena pobytu</span>
              <span className="text-right font-semibold">
                {formatMoney(reservation.totalPrice)}
              </span>
            </div>

            <div className="flex justify-between gap-4 border-b pb-3">
              <span className="text-zinc-500">Wpłacono</span>
              <span className="text-right font-semibold">
                {formatMoney(reservation.paidAmount)}
              </span>
            </div>

            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Pozostało</span>
              <span
                className={`text-right font-bold ${getPaymentSummaryClassName(
                  remainingAmount,
                  isPaid,
                )}`}
              >
                {formatMoneyFromNumber(remainingAmount)}
              </span>
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold">Adres</h2>

          <div className="mt-5 text-lg font-semibold">
            {getAddress({
              street: reservation.street,
              postalCode: reservation.postalCode,
              city: reservation.city,
              country: reservation.country,
            })}
          </div>
        </section>
      </div>

      {reservation.notes ? (
        <section className="rounded-xl border bg-white p-6 shadow-sm">
          <h2 className="text-xl font-semibold">Uwagi</h2>

          <div className="mt-4 whitespace-pre-wrap text-zinc-700">
            {reservation.notes}
          </div>
        </section>
      ) : null}

      <div className="flex flex-wrap gap-3">
        <Link
          href={calendarHref}
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Wróć do kalendarza
        </Link>

        <Link
          href="/admin/rezerwacje"
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Lista rezerwacji
        </Link>
      </div>
    </div>
  );
}