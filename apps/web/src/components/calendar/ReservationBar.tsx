"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import ReservationTooltip from "@/components/calendar/ReservationTooltip";
import type {
  CalendarReservation,
  ReservationBarPosition,
} from "@/modules/calendar/calendar.types";

type TooltipPosition = {
  left: number;
  top: number;
};

type ReservationBarProps = {
  reservation: CalendarReservation;
  position: ReservationBarPosition;
};

const DAY_WIDTH = 72;
const TOOLTIP_WIDTH = 620;
const TOOLTIP_MARGIN = 16;
const TOOLTIP_GAP = 12;
const ESTIMATED_TOOLTIP_HEIGHT = 620;

function getReservationBarColor(status: CalendarReservation["status"]) {
  switch (status) {
    case "PENDING":
      return "bg-orange-500 text-white";
    case "CONFIRMED":
      return "bg-blue-600 text-white";
    case "CHECKED_IN":
      return "bg-green-700 text-white";
    case "CHECKED_OUT":
      return "bg-zinc-400 text-white";
    case "CANCELLED":
      return "bg-red-500 text-white";
    default:
      return "bg-zinc-500 text-white";
  }
}

function getReservationContinuationClassName(position: ReservationBarPosition) {
  const classes = [];

  if (position.startsBeforeVisibleRange) {
    classes.push("rounded-l-none");
  }

  if (position.endsAfterVisibleRange) {
    classes.push("rounded-r-none");
  }

  return classes.join(" ");
}

function getRemainingAmount(reservation: CalendarReservation) {
  const totalPrice = reservation.totalPrice ?? 0;
  const paidAmount = reservation.paidAmount ?? 0;

  return Math.max(0, totalPrice - paidAmount);
}

function formatPhone(phone: string | null) {
  return phone || "brak telefonu";
}

function formatGuests(reservation: CalendarReservation) {
  return `Gości: ${reservation.guests}`;
}

function clamp(value: number, min: number, max: number) {
  return Math.max(min, Math.min(value, max));
}

function getTooltipPosition(element: HTMLDivElement): TooltipPosition {
  const rect = element.getBoundingClientRect();

  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;

  const maxLeft = viewportWidth - TOOLTIP_WIDTH - TOOLTIP_MARGIN;

  const left = clamp(
    rect.left,
    TOOLTIP_MARGIN,
    Math.max(TOOLTIP_MARGIN, maxLeft),
  );

  const availableTooltipHeight = Math.max(
    240,
    viewportHeight - TOOLTIP_MARGIN * 2,
  );

  const tooltipHeight = Math.min(
    ESTIMATED_TOOLTIP_HEIGHT,
    availableTooltipHeight,
  );

  const spaceBelow = viewportHeight - rect.bottom - TOOLTIP_MARGIN;
  const spaceAbove = rect.top - TOOLTIP_MARGIN;

  let preferredTop: number;

  if (spaceBelow >= tooltipHeight + TOOLTIP_GAP) {
    preferredTop = rect.bottom + TOOLTIP_GAP;
  } else if (spaceAbove >= tooltipHeight + TOOLTIP_GAP) {
    preferredTop = rect.top - tooltipHeight - TOOLTIP_GAP;
  } else if (spaceBelow >= spaceAbove) {
    preferredTop = rect.bottom + TOOLTIP_GAP;
  } else {
    preferredTop = rect.top - tooltipHeight - TOOLTIP_GAP;
  }

  const maxTop = viewportHeight - tooltipHeight - TOOLTIP_MARGIN;

  const top = clamp(
    preferredTop,
    TOOLTIP_MARGIN,
    Math.max(TOOLTIP_MARGIN, maxTop),
  );

  return {
    left,
    top,
  };
}

export default function ReservationBar({
  reservation,
  position,
}: ReservationBarProps) {
  const router = useRouter();

  const [tooltipPosition, setTooltipPosition] =
    useState<TooltipPosition | null>(null);

  const remainingAmount = getRemainingAmount(reservation);
  const isPaid = reservation.totalPrice !== null && remainingAmount === 0;

  const isOneDayContinuation =
    position.startsBeforeVisibleRange && position.columnSpan <= 1.15;

  const isVeryCompact = position.columnSpan < 1.4;
  const isCompact = position.columnSpan < 2.4;

  const barWidth = isOneDayContinuation
    ? "16px"
    : `calc(${position.columnSpan} * ${DAY_WIDTH}px - 4px)`;

  function openReservationDetails() {
    setTooltipPosition(null);
    router.push(`/admin/rezerwacje/${reservation.id}`);
  }

  return (
    <>
      <div
        className={`pointer-events-auto absolute bottom-[5%] top-[5%] flex cursor-pointer overflow-hidden rounded-2xl text-white shadow-sm transition-all hover:z-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-green-300 ${getReservationBarColor(
          reservation.status,
        )} ${getReservationContinuationClassName(position)}`}
        style={{
          left: `calc(${position.startColumn - 1} * ${DAY_WIDTH}px)`,
          width: barWidth,
        }}
        onClick={openReservationDetails}
        onMouseEnter={(event) => {
          setTooltipPosition(getTooltipPosition(event.currentTarget));
        }}
        onMouseMove={(event) => {
          setTooltipPosition(getTooltipPosition(event.currentTarget));
        }}
        onMouseLeave={() => {
          setTooltipPosition(null);
        }}
        onFocus={(event) => {
          setTooltipPosition(getTooltipPosition(event.currentTarget));
        }}
        onBlur={() => {
          setTooltipPosition(null);
        }}
        onKeyDown={(event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openReservationDetails();
          }
        }}
        role="button"
        tabIndex={0}
      >
        {isOneDayContinuation ? (
          <div className="h-full w-full rounded-r bg-white/20" />
        ) : (
          <>
            {position.startsBeforeVisibleRange ? (
              <div className="absolute bottom-2 left-0 top-2 w-1.5 rounded-r bg-white/60" />
            ) : null}

            {position.endsAfterVisibleRange ? (
              <div className="absolute bottom-2 right-0 top-2 w-1.5 rounded-l bg-white/60" />
            ) : null}

            {isVeryCompact ? (
              <div className="flex min-w-0 flex-1 items-center px-2">
                <span className="truncate text-[12px] font-bold leading-none">
                  {reservation.guestName}
                </span>
              </div>
            ) : isCompact ? (
              <div className="flex min-w-0 flex-1 flex-col justify-center gap-1 px-2 py-2">
                <div className="truncate text-[13px] font-bold leading-tight">
                  {reservation.guestName}
                </div>

                <div className="truncate text-[12px] font-semibold leading-tight opacity-95">
                  {formatGuests(reservation)}
                </div>

                {!isPaid ? (
                  <div className="truncate text-[11px] font-semibold leading-tight opacity-95">
                    do zapłaty {remainingAmount} zł
                  </div>
                ) : null}
              </div>
            ) : (
              <>
                <div className="flex min-w-0 flex-1 flex-col justify-evenly overflow-hidden px-3 py-2">
                  <div className="truncate text-[17px] font-bold leading-tight">
                    {reservation.guestName}
                  </div>

                  <div className="truncate text-[16px] font-semibold leading-tight opacity-95">
                    {formatPhone(reservation.phone)}
                  </div>

                  <div className="truncate text-[16px] font-semibold leading-tight opacity-95">
                    {formatGuests(reservation)}
                  </div>

                  {!isPaid ? (
                    <div className="truncate text-[14px] font-semibold leading-tight opacity-95">
                      do zapłaty {remainingAmount} zł
                    </div>
                  ) : null}
                </div>

                {!isPaid ? (
                  <div className="mr-2 flex h-7 w-7 shrink-0 items-center justify-center self-center rounded-full bg-yellow-100 text-sm font-bold text-yellow-800">
                    !
                  </div>
                ) : null}
              </>
            )}
          </>
        )}
      </div>

      <ReservationTooltip reservation={reservation} position={tooltipPosition} />
    </>
  );
}