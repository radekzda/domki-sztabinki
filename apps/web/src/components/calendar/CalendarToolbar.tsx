"use client";

import type {
  CalendarActiveFilters,
  CalendarCabin,
  CalendarReservationPaymentStatus,
  CalendarReservationSource,
  CalendarReservationStatus,
} from "@/modules/calendar/calendar.types";

type CalendarToolbarProps = {
  cabins: CalendarCabin[];
  filters: CalendarActiveFilters;
  onFiltersChange: (filters: CalendarActiveFilters) => void;
};

const statusOptions: {
  value: "ALL" | CalendarReservationStatus;
  label: string;
}[] = [
  { value: "ALL", label: "Wszystkie statusy" },
  { value: "PENDING", label: "Oczekuje na potwierdzenie" },
  { value: "CONFIRMED", label: "Potwierdzona" },
  { value: "CHECKED_IN", label: "Zameldowany" },
  { value: "CHECKED_OUT", label: "Wymeldowany" },
  { value: "CANCELLED", label: "Anulowany" },
];

const sourceOptions: {
  value: "ALL" | CalendarReservationSource;
  label: string;
}[] = [
  { value: "ALL", label: "Wszystkie źródła" },
  { value: "BOOKING", label: "Booking" },
  { value: "AIRBNB", label: "Airbnb" },
  { value: "WEBSITE", label: "WWW" },
  { value: "PHONE", label: "Telefon" },
  { value: "MANUAL", label: "Ręcznie" },
];

const paymentOptions: {
  value: "ALL" | CalendarReservationPaymentStatus;
  label: string;
}[] = [
  { value: "ALL", label: "Wszystkie płatności" },
  { value: "PENDING", label: "Oczekuje" },
  { value: "PAID", label: "Opłacona" },
  { value: "PARTIAL", label: "Częściowa" },
  { value: "REFUNDED", label: "Zwrócona" },
];

export default function CalendarToolbar({
  cabins,
  filters,
  onFiltersChange,
}: CalendarToolbarProps) {
  const hasActiveFilters =
    filters.cabinId !== "ALL" ||
    filters.status !== "ALL" ||
    filters.source !== "ALL" ||
    filters.payment !== "ALL";

  return (
    <div className="border-b bg-zinc-50 px-6 py-4">
      <div className="flex flex-wrap items-end gap-4">
        <div className="space-y-1">
          <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Domek
          </label>

          <select
            value={filters.cabinId}
            onChange={(event) => {
              onFiltersChange({
                ...filters,
                cabinId: event.target.value,
              });
            }}
            className="h-10 min-w-[180px] rounded-lg border bg-white px-3 text-sm font-medium"
          >
            <option value="ALL">Wszystkie domki</option>

            {cabins.map((cabin) => (
              <option key={cabin.id} value={cabin.id}>
                {cabin.shortName || cabin.name}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-1">
          <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Status
          </label>

          <select
            value={filters.status}
            onChange={(event) => {
              onFiltersChange({
                ...filters,
                status: event.target.value as CalendarActiveFilters["status"],
              });
            }}
            className="h-10 min-w-[240px] rounded-lg border bg-white px-3 text-sm font-medium"
          >
            {statusOptions.map((status) => (
              <option key={status.value} value={status.value}>
                {status.label}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-1">
          <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Źródło
          </label>

          <select
            value={filters.source}
            onChange={(event) => {
              onFiltersChange({
                ...filters,
                source: event.target.value as CalendarActiveFilters["source"],
              });
            }}
            className="h-10 min-w-[180px] rounded-lg border bg-white px-3 text-sm font-medium"
          >
            {sourceOptions.map((source) => (
              <option key={source.value} value={source.value}>
                {source.label}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-1">
          <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Płatność
          </label>

          <select
            value={filters.payment}
            onChange={(event) => {
              onFiltersChange({
                ...filters,
                payment:
                  event.target.value as CalendarActiveFilters["payment"],
              });
            }}
            className="h-10 min-w-[190px] rounded-lg border bg-white px-3 text-sm font-medium"
          >
            {paymentOptions.map((payment) => (
              <option key={payment.value} value={payment.value}>
                {payment.label}
              </option>
            ))}
          </select>
        </div>

        <button
          type="button"
          disabled={!hasActiveFilters}
          onClick={() => {
            onFiltersChange({
              cabinId: "ALL",
              status: "ALL",
              source: "ALL",
              payment: "ALL",
            });
          }}
          className="h-10 rounded-lg border bg-white px-4 text-sm font-semibold hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Wyczyść filtry
        </button>
      </div>
    </div>
  );
}