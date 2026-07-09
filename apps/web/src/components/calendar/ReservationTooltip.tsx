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
      return status;
  }
}

function getReservationStatusClassName(status: CalendarReservation["status"]) {
  switch (status) {
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

function getPaymentStatusLabel(status: CalendarReservation["paymentStatus"]) {
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

function getPaymentStatusClassName(status: CalendarReservation["paymentStatus"]) {
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

function getRemainingAmount(reservation: CalendarReservation) {
  const totalPrice = reservation.totalPrice ?? 0;
  const paidAmount = reservation.paidAmount ?? 0;

  return Math.max(0, totalPrice - paidAmount);
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

  const remainingAmount = getRemainingAmount(reservation);

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
            <span
              className={`rounded-full px-3 py-1 font-semibold ${getReservationStatusClassName(
                reservation.status,
              )}`}
            >
              {getReservationStatusLabel(reservation.status)}
            </span>

            <span
              className={`rounded-full px-3 py-1 font-semibold ${getPaymentStatusClassName(
                reservation.paymentStatus,
              )}`}
            >
              {getPaymentStatusLabel(reservation.paymentStatus)}
            </span>

            <span className="rounded-full bg-zinc-100 px-3 py-1 font-semibold text-zinc-700">
              {getReservationSourceLabel(reservation.source)}
            </span>
          </div>
        </div>

        <div className="grid gap-2 rounded-xl bg-zinc-50 p-4">
          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Zameldowanie</span>
            <span className="text-right font-semibold">
              {formatCalendarDateTime(
                reservation.checkInAt ?? reservation.startDate,
              )}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Wymeldowanie</span>
            <span className="text-right font-semibold">
              {formatCalendarDateTime(
                reservation.checkOutAt ?? reservation.endDate,
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
            <div className="text-[14px] text-zinc-500">Dorośli</div>
            <div className="mt-1 text-[28px] font-bold">
              {reservation.adults}
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
          <div className="grid gap-3">
            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Cena / noc</span>
              <span className="font-semibold">
                {formatMoney(reservation.pricePerNight)}
              </span>
            </div>

            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Cena pobytu</span>
              <span className="font-semibold">
                {formatMoney(reservation.totalPrice)}
              </span>
            </div>

            <div className="flex justify-between gap-4">
              <span className="text-zinc-500">Wpłacono</span>
              <span className="font-semibold">
                {formatMoney(reservation.paidAmount)}
              </span>
            </div>

            <div className="border-t pt-3">
              <div className="flex justify-between gap-4">
                <span className="font-semibold">Pozostało</span>
                <span
                  className={`font-bold ${
                    remainingAmount > 0 ? "text-yellow-800" : "text-green-700"
                  }`}
                >
                  {formatMoney(remainingAmount)}
                </span>
              </div>
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