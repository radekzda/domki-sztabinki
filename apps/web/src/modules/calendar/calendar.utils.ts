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

export function createCalendarDate(
  year: number,
  month: number,
  day: number
): Date {
  return new Date(Date.UTC(year, month, day, 12, 0, 0));
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
  month: number
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
  month: number
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

export function formatCalendarDate(date: Date): string {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
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
  reservation: CalendarReservation
): boolean {
  const dayStart = createCalendarDate(
    date.getUTCFullYear(),
    date.getUTCMonth(),
    date.getUTCDate()
  );

  const dayEnd = createCalendarDate(
    date.getUTCFullYear(),
    date.getUTCMonth(),
    date.getUTCDate() + 1
  );

  return reservation.startDate < dayEnd && reservation.endDate > dayStart;
}

export function calculateReservationBarPosition(
  reservation: CalendarReservation,
  visibleRangeStart: Date,
  visibleRangeEnd: Date
): ReservationBarPosition {
  const reservationStart =
    reservation.startDate < visibleRangeStart
      ? visibleRangeStart
      : reservation.startDate;

  const reservationEnd =
    reservation.endDate > visibleRangeEnd
      ? visibleRangeEnd
      : reservation.endDate;

  const millisecondsPerDay = 1000 * 60 * 60 * 24;

  const startColumn =
    Math.floor(
      (reservationStart.getTime() - visibleRangeStart.getTime()) /
        millisecondsPerDay
    ) + 1;

  const endColumn =
    Math.ceil(
      (reservationEnd.getTime() - visibleRangeStart.getTime()) /
        millisecondsPerDay
    ) + 1;

  return {
    startColumn,
    endColumn,
    columnSpan: Math.max(1, endColumn - startColumn),
    startsBeforeVisibleRange: reservation.startDate < visibleRangeStart,
    endsAfterVisibleRange: reservation.endDate > visibleRangeEnd,
  };
}
