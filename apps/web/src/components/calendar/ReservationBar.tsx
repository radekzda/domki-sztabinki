"use client";

import type {
  CalendarReservation,
  ReservationBarPosition,
} from "@/modules/calendar/calendar.types";

type ReservationBarProps = {
  reservation: CalendarReservation;
  position: ReservationBarPosition;
};

function getReservationBarColor(status: CalendarReservation["status"]) {
  switch (status) {
    case "CONFIRMED":
      return "bg-emerald-600 text-white";

    case "PENDING":
      return "bg-amber-500 text-black";

    case "CANCELLED":
      return "bg-rose-500 text-white";

    case "COMPLETED":
      return "bg-sky-500 text-white";

    default:
      return "bg-zinc-500 text-white";
  }
}

export default function ReservationBar({
  reservation,
  position,
}: ReservationBarProps) {
  return (
    <div
      className={`absolute flex h-10 items-center rounded-lg px-3 text-sm font-medium shadow transition-all ${getReservationBarColor(
        reservation.status
      )}`}
      style={{
        left: `calc(${position.startColumn - 1} * 48px)`,
        width: `calc(${position.columnSpan} * 48px - 4px)`,
      }}
    >
      <div className="truncate">
        {reservation.guestName}
      </div>
    </div>
  );
}