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
    case "CONFIRMED":
      return "bg-blue-600 text-white";
    case "PENDING":
      return "bg-yellow-400 text-zinc-950";
    case "CANCELLED":
      return "bg-red-500 text-white";
    case "COMPLETED":
      return "bg-zinc-400 text-white";
    default:
      return "bg-zinc-500 text-white";
  }
}

function getReservationSourceBadge(source: CalendarReservation["source"]) {
  switch (source) {
    case "BOOKING":
      return "B";
    case "AIRBNB":
      return "A";
    case "WEBSITE":
      return "W";
    case "PHONE":
      return "T";
    case "MANUAL":
      return "M";
    default:
      return "?";
  }
}

function getReservationSourceBadgeClassName(
  source: CalendarReservation["source"]
) {
  switch (source) {
    case "BOOKING":
      return "bg-green-700 text-white";
    case "AIRBNB":
      return "bg-red-500 text-white";
    case "WEBSITE":
      return "bg-blue-600 text-white";
    case "PHONE":
      return "bg-yellow-500 text-zinc-950";
    case "MANUAL":
      return "bg-zinc-600 text-white";
    default:
      return "bg-zinc-400 text-white";
  }
}

function getRemainingAmount(reservation: CalendarReservation) {
  const totalPrice = reservation.totalPrice ?? 0;
  const paidAmount = reservation.paidAmount ?? 0;

  return Math.max(0, totalPrice - paidAmount);
}

function formatPhone(phone: string | null) {
  return phone || "brak telefonu";
}

function formatMoneyShort(value: number | null) {
  if (value === null) {
    return "brak ceny";
  }

  return `${value} zł`;
}

function formatNights(nights: number) {
  if (nights === 1) {
    return "1 noc";
  }

  return `${nights} noce`;
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
    Math.max(TOOLTIP_MARGIN, maxLeft)
  );

  const availableTooltipHeight = Math.max(
    240,
    viewportHeight - TOOLTIP_MARGIN * 2
  );

  const tooltipHeight = Math.min(
    ESTIMATED_TOOLTIP_HEIGHT,
    availableTooltipHeight
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
    Math.max(TOOLTIP_MARGIN, maxTop)
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

  function openReservationDetails() {
    setTooltipPosition(null);
    router.push(`/admin/rezerwacje/${reservation.id}`);
  }

  return (
    <>
      <div
        className={`pointer-events-auto absolute bottom-[5%] top-[5%] flex cursor-pointer overflow-hidden rounded-2xl px-4 py-3 text-[20px] leading-none shadow-sm transition-all hover:z-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-green-300 ${getReservationBarColor(
          reservation.status
        )}`}
        style={{
          left: `calc(${position.startColumn - 1} * ${DAY_WIDTH}px)`,
          width: `calc(${position.columnSpan} * ${DAY_WIDTH}px - 4px)`,
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
        <div className="flex min-w-0 flex-1 flex-col justify-evenly overflow-hidden">
          <div className="flex min-w-0 items-center gap-2 font-bold">
            <span
              className={`flex h-7 w-7 shrink-0 items-center justify-center rounded text-[14px] font-bold shadow-sm ${getReservationSourceBadgeClassName(
                reservation.source
              )}`}
            >
              {getReservationSourceBadge(reservation.source)}
            </span>

            <span className="truncate">{reservation.guestName}</span>
          </div>

          <div className="truncate font-semibold opacity-95">
            {formatPhone(reservation.phone)}
          </div>

          <div className="truncate font-semibold opacity-95">
            {formatNights(reservation.nights)} •{" "}
            {formatMoneyShort(reservation.totalPrice)}
          </div>

          <div className="truncate text-[17px] font-semibold opacity-95">
            {isPaid ? "opłacono" : `do zapłaty ${remainingAmount} zł`}
          </div>
        </div>

        <div
          className={`ml-3 flex h-8 w-8 shrink-0 items-center justify-center self-center rounded-full text-base font-bold ${
            isPaid ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"
          }`}
        >
          {isPaid ? "✓" : "!"}
        </div>
      </div>

      <ReservationTooltip reservation={reservation} position={tooltipPosition} />
    </>
  );
}