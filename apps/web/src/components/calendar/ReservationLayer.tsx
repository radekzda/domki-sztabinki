"use client";

import ReservationBar from "@/components/calendar/ReservationBar";
import type {
  CalendarReservation,
  CalendarTimelineRange,
} from "@/modules/calendar/calendar.types";
import { calculateReservationBarPosition } from "@/modules/calendar/calendar.utils";

type ReservationLayerProps = {
  reservations: CalendarReservation[];
  range: CalendarTimelineRange;
};

export default function ReservationLayer({
  reservations,
  range,
}: ReservationLayerProps) {
  return (
    <div className="pointer-events-none absolute inset-0 z-10">
      {reservations.map((reservation) => {
        const position = calculateReservationBarPosition(
          reservation,
          range.startDate,
          range.endDate
        );

        return (
          <div
            key={reservation.id}
            className="pointer-events-auto absolute left-0 right-0 top-3"
          >
            <ReservationBar reservation={reservation} position={position} />
          </div>
        );
      })}
    </div>
  );
}