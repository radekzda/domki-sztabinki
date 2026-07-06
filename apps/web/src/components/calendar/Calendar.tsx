"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import CalendarLegend from "@/components/calendar/CalendarLegend";
import MonthNavigation from "@/components/calendar/MonthNavigation";
import ReservationLayer from "@/components/calendar/ReservationLayer";
import CalendarToolbar from "@/components/calendar/CalendarToolbar";
import type {
  CalendarActiveFilters,
  CalendarCabin,
  CalendarDay,
  CalendarEngineData,
  CalendarReservation,
} from "@/modules/calendar/calendar.types";

type CalendarProps = {
  data: CalendarEngineData;
};

function filterReservations(
  reservations: CalendarReservation[],
  filters: CalendarActiveFilters
) {
  return reservations.filter((reservation) => {
    if (filters.status !== "ALL" && reservation.status !== filters.status) {
      return false;
    }

    if (filters.source !== "ALL" && reservation.source !== filters.source) {
      return false;
    }

    return true;
  });
}

function filterCabins(
  cabins: CalendarCabin[],
  filters: CalendarActiveFilters
): CalendarCabin[] {
  return cabins
    .filter((cabin) => {
      if (filters.cabinId === "ALL") {
        return true;
      }

      return cabin.id === filters.cabinId;
    })
    .map((cabin) => ({
      ...cabin,
      reservations: filterReservations(cabin.reservations, filters),
    }));
}

function formatDateQueryValue(date: Date) {
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, "0");
  const day = String(date.getUTCDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function formatNextDateQueryValue(date: Date) {
  const nextDate = new Date(date);

  nextDate.setUTCDate(nextDate.getUTCDate() + 1);

  return formatDateQueryValue(nextDate);
}

function getWeekDayLabel(day: CalendarDay) {
  switch (day.weekDay) {
    case 0:
      return "ND";
    case 1:
      return "PN";
    case 2:
      return "WT";
    case 3:
      return "ŚR";
    case 4:
      return "CZ";
    case 5:
      return "PT";
    case 6:
      return "SB";
    default:
      return "";
  }
}

function isWeekend(day: CalendarDay) {
  return day.weekDay === 0 || day.weekDay === 6;
}

function getDayHeaderClassName(day: CalendarDay) {
  const baseClassName = "border-b border-r p-2 text-center text-sm";

  if (day.isToday) {
    return `${baseClassName} bg-green-700 font-bold text-white`;
  }

  if (isWeekend(day)) {
    return `${baseClassName} bg-amber-50 font-semibold text-amber-900`;
  }

  return `${baseClassName} bg-zinc-100 text-zinc-700`;
}

function getDayCellClassName(day: CalendarDay) {
  const baseClassName =
    "border-r text-left transition-colors focus:outline-none";

  if (day.isToday) {
    return `${baseClassName} bg-green-50 hover:bg-green-100 focus:bg-green-100`;
  }

  if (isWeekend(day)) {
    return `${baseClassName} bg-amber-50/60 hover:bg-amber-100 focus:bg-amber-100`;
  }

  return `${baseClassName} bg-white hover:bg-green-50 focus:bg-green-50`;
}

export default function Calendar({ data }: CalendarProps) {
  const router = useRouter();

  const [filters, setFilters] = useState<CalendarActiveFilters>({
    cabinId: "ALL",
    status: "ALL",
    source: "ALL",
  });

  const dayWidth = 72;
  const rowHeight = 116;
  const cabinColumnWidth = 130;

  const gridTemplateColumns = `${cabinColumnWidth}px repeat(${data.month.days.length}, ${dayWidth}px)`;

  const filteredCabins = useMemo(
    () => filterCabins(data.cabins, filters),
    [data.cabins, filters]
  );

  const reservationsCount = filteredCabins.reduce(
    (total, cabin) => total + cabin.reservations.length,
    0
  );

  function openNewReservation(cabinId: string, date: Date) {
    const startDate = formatDateQueryValue(date);
    const endDate = formatNextDateQueryValue(date);

    router.push(
      `/admin/rezerwacje/nowa?cabinId=${encodeURIComponent(
        cabinId
      )}&startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(
        endDate
      )}`
    );
  }

  return (
    <div className="space-y-6">
      <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-4 border-b px-6 py-5">
          <div>
            <h2 className="text-2xl font-bold">
              {data.month.name} {data.month.year}
            </h2>

            <p className="mt-2 text-sm text-zinc-500">
              Dwuklik w pusty dzień dodaje rezerwację. Weekendy są oznaczone
              jasnym tłem.
            </p>
          </div>

          <MonthNavigation year={data.month.year} month={data.month.month} />
        </div>

        <CalendarToolbar
          cabins={data.cabins}
          filters={filters}
          onFiltersChange={setFilters}
        />

        <div className="overflow-x-auto p-4">
          <div className="grid min-w-max" style={{ gridTemplateColumns }}>
            <div className="sticky left-0 z-30 border-b border-r bg-zinc-100 p-3 text-sm font-semibold">
              Domek
            </div>

            {data.month.days.map((day) => (
              <div
                key={day.date.toISOString()}
                className={getDayHeaderClassName(day)}
              >
                <div className="leading-none">{day.day}</div>
                <div className="mt-1 text-[10px] leading-none opacity-75">
                  {getWeekDayLabel(day)}
                </div>
              </div>
            ))}

            {filteredCabins.map((cabin) => (
              <div key={cabin.id} className="contents">
                <div
                  className="sticky left-0 z-20 border-b border-r bg-white p-3"
                  style={{ height: rowHeight }}
                >
                  <div className="truncate text-sm font-semibold">
                    {cabin.shortName || cabin.name}
                  </div>

                  <div className="mt-1 text-xs text-zinc-500">
                    max {cabin.maxGuests}
                  </div>
                </div>

                <div
                  className="relative border-b"
                  style={{
                    gridColumn: `span ${data.month.days.length}`,
                    height: rowHeight,
                  }}
                >
                  <div
                    className="grid h-full"
                    style={{
                      gridTemplateColumns: `repeat(${data.month.days.length}, ${dayWidth}px)`,
                    }}
                  >
                    {data.month.days.map((day) => (
                      <button
                        key={`${cabin.id}-${day.date.toISOString()}`}
                        type="button"
                        onDoubleClick={() => {
                          openNewReservation(cabin.id, day.date);
                        }}
                        className={getDayCellClassName(day)}
                        aria-label={`Dodaj rezerwację: ${
                          cabin.shortName || cabin.name
                        }, dzień ${day.day}`}
                      />
                    ))}
                  </div>

                  <ReservationLayer
                    reservations={cabin.reservations}
                    range={data.range}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="rounded-xl border bg-white p-5 shadow-sm">
        <h3 className="font-semibold">Informacje</h3>

        <div className="mt-4 grid gap-3 md:grid-cols-4">
          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Domki</div>
            <div className="mt-1 text-2xl font-bold">
              {filteredCabins.length}
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Dni</div>
            <div className="mt-1 text-2xl font-bold">
              {data.month.days.length}
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Rezerwacje</div>
            <div className="mt-1 text-2xl font-bold">
              {reservationsCount}
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Widok</div>
            <div className="mt-1 text-2xl font-bold">Month</div>
          </div>
        </div>
      </div>

      <CalendarLegend />
    </div>
  );
}