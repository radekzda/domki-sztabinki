export type CalendarViewMode = "month" | "week";

export type CalendarReservationStatus =
  | "PENDING"
  | "CONFIRMED"
  | "CANCELLED"
  | "COMPLETED";

export type CalendarReservationSource =
  | "MANUAL"
  | "PHONE"
  | "WEBSITE"
  | "BOOKING"
  | "AIRBNB";

export type CalendarStatusFilter = "ALL" | CalendarReservationStatus;

export type CalendarSourceFilter = "ALL" | CalendarReservationSource;

export interface CalendarReservation {
  id: string;
  cabinId: string;

  guestName: string;
  firstName: string | null;
  lastName: string | null;

  email: string;
  phone: string | null;

  startDate: Date;
  endDate: Date;

  checkInAt: Date | null;
  checkOutAt: Date | null;

  nights: number;
  pricePerNight: number | null;

  guests: number;
  adults: number;
  children: number;

  status: CalendarReservationStatus;
  source: CalendarReservationSource;

  totalPrice: number | null;
  paidAmount: number | null;

  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;

  notes: string | null;
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

export interface CalendarActiveFilters {
  cabinId: string;
  status: CalendarStatusFilter;
  source: CalendarSourceFilter;
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