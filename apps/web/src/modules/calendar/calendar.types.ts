export type CalendarViewMode = "month" | "week";

export type CalendarReservationStatus =
  | "PENDING"
  | "CONFIRMED"
  | "CANCELLED"
  | "COMPLETED";

export interface CalendarReservation {
  id: string;
  cabinId: string;

  guestName: string;
  email: string;
  phone: string | null;

  startDate: Date;
  endDate: Date;

  guests: number;

  status: CalendarReservationStatus;
}

export interface CalendarCabin {
  id: string;

  name: string;

  shortName: string | null;

  maxGuests: number;

  isActive: boolean;

  reservations: CalendarReservation[];
}

export interface CalendarDay {
  date: Date;

  day: number;

  month: number;

  year: number;

  weekDay: number;

  isToday: boolean;

  isCurrentMonth: boolean;
}

export interface CalendarMonth {
  year: number;

  month: number;

  name: string;

  days: CalendarDay[];
}

export interface CalendarFilters {
  cabinId?: string;

  status?: CalendarReservationStatus;

  showCancelled: boolean;

  showCompleted: boolean;
}

export interface ReservationBarPosition {
  startColumn: number;

  endColumn: number;

  columnSpan: number;

  startsBeforeVisibleRange: boolean;

  endsAfterVisibleRange: boolean;
}

export interface CalendarTimelineRange {
  startDate: Date;

  endDate: Date;
}

export interface CalendarEngineData {
  cabins: CalendarCabin[];

  month: CalendarMonth;

  range: CalendarTimelineRange;

  filters: CalendarFilters;
}