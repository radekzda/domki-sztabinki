"use client";

import { createPortal } from "react-dom";
import type { CalendarReservation } from "@/modules/calendar/calendar.types";
import { formatCalendarDateTime } from "@/modules/calendar/calendar.utils";

type TooltipPosition = {
  left: number;
  top: number;
};

type ReservationTooltipProps = {
  reservation: CalendarReservation;
  position: TooltipPosition | null;
};

function formatMoney(value: number | null) {
  if (value === null) {
    return "—";
  }

  return new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency: "PLN",
  }).format(value);
}

function getReservationStatusLabel(status: CalendarReservation["status"]) {
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

function getReservationSourceLabel(source: CalendarReservation["source"]) {
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

function getPaymentInfo(reservation: CalendarReservation) {
  if (reservation.totalPrice === null) {
    return {
      label: "Płatność",
      value: "Brak danych",
      className: "text-zinc-700",
    };
  }

  const paidAmount = reservation.paidAmount ?? 0;
  const remainingAmount = Math.max(0, reservation.totalPrice - paidAmount);

  if (remainingAmount === 0) {
    return {
      label: "Opłacono",
      value: formatMoney(paidAmount),
      className: "text-green-700",
    };
  }

  return {
    label: "Pozostało do zapłaty",
    value: formatMoney(remainingAmount),
    className: "text-red-700",
  };
}

function getAddress(reservation: CalendarReservation) {
  const parts = [
    reservation.street,
    [reservation.postalCode, reservation.city].filter(Boolean).join(" "),
    reservation.country,
  ].filter(Boolean);

  if (parts.length === 0) {
    return "—";
  }

  return parts.join(", ");
}

export default function ReservationTooltip({
  reservation,
  position,
}: ReservationTooltipProps) {
  if (!position) {
    return null;
  }

  const paymentInfo = getPaymentInfo(reservation);

  const tooltip = (
    <div
      className="pointer-events-none fixed z-[9999] max-h-[calc(100vh-32px)] w-[620px] max-w-[calc(100vw-32px)] overflow-y-auto rounded-2xl border bg-white p-5 text-[18px] leading-snug text-zinc-900 shadow-2xl"
      style={{
        left: position.left,
        top: position.top,
      }}
    >
      <div className="space-y-4">
        <div>
          <div className="text-[24px] font-bold leading-tight">
            {reservation.guestName}
          </div>

          <div className="mt-2 flex flex-wrap gap-2 text-[14px]">
            <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
              {getReservationSourceLabel(reservation.source)}
            </span>

            <span className="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700">
              {getReservationStatusLabel(reservation.status)}
            </span>
          </div>
        </div>

        <div className="grid gap-2 rounded-xl bg-zinc-50 p-4">
          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Zameldowanie</span>
            <span className="text-right font-semibold">
              {formatCalendarDateTime(
                reservation.checkInAt ?? reservation.startDate
              )}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Wymeldowanie</span>
            <span className="text-right font-semibold">
              {formatCalendarDateTime(
                reservation.checkOutAt ?? reservation.endDate
              )}
            </span>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Noce</div>
            <div className="mt-1 text-[28px] font-bold">
              {reservation.nights}
            </div>
          </div>

          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Cena / noc</div>
            <div className="mt-1 text-[24px] font-bold">
              {formatMoney(reservation.pricePerNight)}
            </div>
          </div>

          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Razem</div>
            <div className="mt-1 text-[24px] font-bold">
              {formatMoney(reservation.totalPrice)}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Dorośli</div>
            <div className="mt-1 text-[28px] font-bold">
              {reservation.adults}
            </div>
          </div>

          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Dzieci</div>
            <div className="mt-1 text-[28px] font-bold">
              {reservation.children}
            </div>
          </div>

          <div className="rounded-xl border p-3 text-center">
            <div className="text-[14px] text-zinc-500">Goście</div>
            <div className="mt-1 text-[28px] font-bold">
              {reservation.guests}
            </div>
          </div>
        </div>

        <div className="rounded-xl border p-4">
          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Cena pobytu</span>
            <span className="font-semibold">
              {formatMoney(reservation.totalPrice)}
            </span>
          </div>

          <div className="mt-2 flex justify-between gap-4">
            <span className="text-zinc-500">Wpłacono</span>
            <span className="font-semibold">
              {formatMoney(reservation.paidAmount)}
            </span>
          </div>

          <div className="mt-3 border-t pt-3">
            <div className="flex justify-between gap-4">
              <span className="font-semibold">{paymentInfo.label}</span>
              <span className={`font-bold ${paymentInfo.className}`}>
                {paymentInfo.value}
              </span>
            </div>
          </div>
        </div>

        <div className="grid gap-2 rounded-xl bg-zinc-50 p-4">
          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Email</span>
            <span className="max-w-[360px] text-right font-semibold">
              {reservation.email || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Telefon</span>
            <span className="text-right font-semibold">
              {reservation.phone || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Adres</span>
            <span className="max-w-[360px] text-right font-semibold">
              {getAddress(reservation)}
            </span>
          </div>
        </div>

        {reservation.notes ? (
          <div className="rounded-xl border p-4">
            <div className="text-[13px] font-semibold uppercase tracking-wide text-zinc-500">
              Uwagi
            </div>

            <div className="mt-2 whitespace-pre-wrap text-[17px] text-zinc-700">
              {reservation.notes}
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );

  return createPortal(tooltip, document.body);
}