import type {
  CalendarDay,
  CalendarMonth,
  CalendarReservation,
  ReservationBarPosition,
} from "./calendar.types";

const MONTH_NAMES = [
  "Styczeń",
  "Luty",
  "Marzec",
  "Kwiecień",
  "Maj",
  "Czerwiec",
  "Lipiec",
  "Sierpień",
  "Wrzesień",
  "Październik",
  "Listopad",
  "Grudzień",
];

const MILLISECONDS_PER_DAY = 1000 * 60 * 60 * 24;

export function createCalendarDate(
  year: number,
  month: number,
  day: number,
): Date {
  return new Date(Date.UTC(year, month, day, 12, 0, 0));
}

function createLocalDayStartFromCalendarDate(date: Date): Date {
  return new Date(
    date.getUTCFullYear(),
    date.getUTCMonth(),
    date.getUTCDate(),
    0,
    0,
    0,
    0,
  );
}

function getCalendarReservationStart(reservation: CalendarReservation): Date {
  return reservation.checkInAt ?? reservation.startDate;
}

function getCalendarReservationEnd(reservation: CalendarReservation): Date {
  return reservation.checkOutAt ?? reservation.endDate;
}

export function isCalendarToday(date: Date): boolean {
  const today = new Date();

  return (
    today.getUTCFullYear() === date.getUTCFullYear() &&
    today.getUTCMonth() === date.getUTCMonth() &&
    today.getUTCDate() === date.getUTCDate()
  );
}

export function generateCalendarDays(
  year: number,
  month: number,
): CalendarDay[] {
  const numberOfDays = new Date(year, month + 1, 0).getDate();

  return Array.from({ length: numberOfDays }, (_, index) => {
    const date = createCalendarDate(year, month, index + 1);

    return {
      date,
      day: date.getUTCDate(),
      month: date.getUTCMonth(),
      year: date.getUTCFullYear(),
      weekDay: date.getUTCDay(),
      isToday: isCalendarToday(date),
      isCurrentMonth: true,
    };
  });
}

export function createCalendarMonth(
  year: number,
  month: number,
): CalendarMonth {
  return {
    year,
    month,
    name: MONTH_NAMES[month],
    days: generateCalendarDays(year, month),
  };
}

export function getCalendarMonthStart(year: number, month: number): Date {
  return createCalendarDate(year, month, 1);
}

export function getCalendarMonthEnd(year: number, month: number): Date {
  return createCalendarDate(year, month + 1, 1);
}

export function getCalendarMonthQueryStart(year: number, month: number): Date {
  return new Date(Date.UTC(year, month, 1, 0, 0, 0, 0));
}

export function getCalendarMonthQueryEnd(year: number, month: number): Date {
  return new Date(Date.UTC(year, month + 1, 1, 0, 0, 0, 0));
}

export function formatCalendarDate(date: Date): string {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

export function formatCalendarDateTime(date: Date | null): string {
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

export function formatCalendarShortDate(date: Date): string {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
  }).format(date);
}

export function isDateInsideReservation(
  date: Date,
  reservation: CalendarReservation,
): boolean {
  const dayStart = createLocalDayStartFromCalendarDate(date);

  const dayEnd = new Date(dayStart);
  dayEnd.setDate(dayEnd.getDate() + 1);

  return (
    getCalendarReservationStart(reservation) < dayEnd &&
    getCalendarReservationEnd(reservation) >= dayStart
  );
}

export function calculateReservationBarPosition(
  reservation: CalendarReservation,
  visibleRangeStart: Date,
  visibleRangeEnd: Date,
): ReservationBarPosition {
  const rangeStart = createLocalDayStartFromCalendarDate(visibleRangeStart);
  const rangeEnd = createLocalDayStartFromCalendarDate(visibleRangeEnd);

  const reservationStart = getCalendarReservationStart(reservation);
  const reservationEnd = getCalendarReservationEnd(reservation);

  const effectiveStart =
    reservationStart < rangeStart ? rangeStart : reservationStart;

  const effectiveEnd = reservationEnd > rangeEnd ? rangeEnd : reservationEnd;

  const rawStartOffset =
    (effectiveStart.getTime() - rangeStart.getTime()) / MILLISECONDS_PER_DAY;

  const rawEndOffset =
    (effectiveEnd.getTime() - rangeStart.getTime()) / MILLISECONDS_PER_DAY;

  const startOffset = Math.max(0, rawStartOffset);
  const endOffset = Math.max(startOffset, rawEndOffset);

  const durationInDays = endOffset - startOffset;

  return {
    startColumn: startOffset + 1,
    endColumn: endOffset + 1,
    columnSpan: Math.max(0.8, durationInDays),
    startsBeforeVisibleRange: reservationStart < rangeStart,
    endsAfterVisibleRange: reservationEnd >= rangeEnd,
  };
}