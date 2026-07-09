"use client";

import { useEffect, useMemo, useRef, useState } from "react";
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

type SelectedCalendarDate = {
  cabinId: string;
  cabinName: string;
  date: Date;
};

type ReservationDateRange = {
  startDate: Date;
  endDate: Date;
};

function filterReservations(
  reservations: CalendarReservation[],
  filters: CalendarActiveFilters,
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
  filters: CalendarActiveFilters,
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

function createUtcDayStart(date: Date) {
  return new Date(
    Date.UTC(
      date.getUTCFullYear(),
      date.getUTCMonth(),
      date.getUTCDate(),
      0,
      0,
      0,
      0,
    ),
  );
}

function addUtcDays(date: Date, days: number) {
  const nextDate = createUtcDayStart(date);

  nextDate.setUTCDate(nextDate.getUTCDate() + days);

  return nextDate;
}

function formatDateQueryValue(date: Date) {
  const normalizedDate = createUtcDayStart(date);
  const year = normalizedDate.getUTCFullYear();
  const month = String(normalizedDate.getUTCMonth() + 1).padStart(2, "0");
  const day = String(normalizedDate.getUTCDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function formatDisplayDate(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function createReservationDateRange(
  firstDate: Date,
  secondDate: Date,
): ReservationDateRange {
  const normalizedFirstDate = createUtcDayStart(firstDate);
  const normalizedSecondDate = createUtcDayStart(secondDate);

  const firstTime = normalizedFirstDate.getTime();
  const secondTime = normalizedSecondDate.getTime();

  if (firstTime === secondTime) {
    return {
      startDate: normalizedFirstDate,
      endDate: addUtcDays(normalizedFirstDate, 1),
    };
  }

  if (firstTime < secondTime) {
    return {
      startDate: normalizedFirstDate,
      endDate: normalizedSecondDate,
    };
  }

  return {
    startDate: normalizedSecondDate,
    endDate: normalizedFirstDate,
  };
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

function isSameCalendarDay(firstDate: Date, secondDate: Date) {
  const normalizedFirstDate = createUtcDayStart(firstDate);
  const normalizedSecondDate = createUtcDayStart(secondDate);

  return normalizedFirstDate.getTime() === normalizedSecondDate.getTime();
}

function isBlockingReservationStatus(status: CalendarReservation["status"]) {
  return (
    status === "PENDING" ||
    status === "CONFIRMED" ||
    status === "CHECKED_IN"
  );
}

function hasReservationConflict(
  cabin: CalendarCabin,
  range: ReservationDateRange,
) {
  return cabin.reservations.some((reservation) => {
    if (!isBlockingReservationStatus(reservation.status)) {
      return false;
    }

    const reservationStart = createUtcDayStart(reservation.startDate);
    const reservationEnd = createUtcDayStart(reservation.endDate);

    return reservationStart < range.endDate && reservationEnd > range.startDate;
  });
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

function getDayCellClassName(day: CalendarDay, isSelected: boolean) {
  const baseClassName =
    "border-r text-left transition-colors focus:outline-none";

  if (isSelected) {
    return `${baseClassName} bg-green-200 ring-2 ring-inset ring-green-700 hover:bg-green-200 focus:bg-green-200`;
  }

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

  const clickTimerRef = useRef<number | null>(null);

  const [filters, setFilters] = useState<CalendarActiveFilters>({
    cabinId: "ALL",
    status: "ALL",
    source: "ALL",
  });

  const [selectedDate, setSelectedDate] =
    useState<SelectedCalendarDate | null>(null);

  const dayWidth = 72;
  const rowHeight = 116;
  const cabinColumnWidth = 130;

  const gridTemplateColumns = `${cabinColumnWidth}px repeat(${data.month.days.length}, ${dayWidth}px)`;

  const filteredCabins = useMemo(
    () => filterCabins(data.cabins, filters),
    [data.cabins, filters],
  );

  const reservationsCount = filteredCabins.reduce(
    (total, cabin) => total + cabin.reservations.length,
    0,
  );

  const calendarReturnUrl = `/admin/kalendarz?year=${data.month.year}&month=${
    data.month.month + 1
  }`;

  useEffect(() => {
    return () => {
      if (clickTimerRef.current !== null) {
        window.clearTimeout(clickTimerRef.current);
      }
    };
  }, []);

  function openNewReservation(
    cabinId: string,
    startDateValue: Date,
    endDateValue: Date,
  ) {
    const startDate = formatDateQueryValue(startDateValue);
    const endDate = formatDateQueryValue(endDateValue);

    router.push(
      `/admin/rezerwacje/nowa?cabinId=${encodeURIComponent(
        cabinId,
      )}&startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(
        endDate,
      )}&returnTo=${encodeURIComponent(calendarReturnUrl)}`,
    );
  }

  function findOriginalCabin(cabinId: string) {
    return data.cabins.find((cabin) => cabin.id === cabinId) ?? null;
  }

  function handleDateSelection(cabin: CalendarCabin, date: Date) {
    const cabinName = cabin.shortName || cabin.name;

    if (!selectedDate) {
      setSelectedDate({
        cabinId: cabin.id,
        cabinName,
        date,
      });

      return;
    }

    if (selectedDate.cabinId !== cabin.id) {
      setSelectedDate({
        cabinId: cabin.id,
        cabinName,
        date,
      });

      return;
    }

    const range = createReservationDateRange(selectedDate.date, date);
    const originalCabin = findOriginalCabin(cabin.id);

    if (!originalCabin) {
      setSelectedDate(null);
      return;
    }

    if (hasReservationConflict(originalCabin, range)) {
      window.alert(
        `Ten termin jest już zajęty.\n\nDomek: ${cabinName}\nOd: ${formatDisplayDate(
          range.startDate,
        )}\nDo: ${formatDisplayDate(range.endDate)}`,
      );

      setSelectedDate(null);
      return;
    }

    const shouldCreateReservation = window.confirm(
      `Utworzyć nową rezerwację?\n\nDomek: ${cabinName}\nOd: ${formatDisplayDate(
        range.startDate,
      )}\nDo: ${formatDisplayDate(range.endDate)}`,
    );

    setSelectedDate(null);

    if (!shouldCreateReservation) {
      return;
    }

    openNewReservation(cabin.id, range.startDate, range.endDate);
  }

  function handleDayClick(cabin: CalendarCabin, date: Date) {
    if (clickTimerRef.current !== null) {
      window.clearTimeout(clickTimerRef.current);
    }

    clickTimerRef.current = window.setTimeout(() => {
      handleDateSelection(cabin, date);
      clickTimerRef.current = null;
    }, 220);
  }

  function handleDayDoubleClick(cabin: CalendarCabin, date: Date) {
    if (clickTimerRef.current !== null) {
      window.clearTimeout(clickTimerRef.current);
      clickTimerRef.current = null;
    }

    setSelectedDate(null);
    openNewReservation(cabin.id, date, addUtcDays(date, 1));
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
              Dwuklik w pusty dzień dodaje rezerwację na 1 noc. Możesz też
              kliknąć pierwszy dzień, a potem drugi dzień w tym samym domku, aby
              utworzyć rezerwację z zakresem dat.
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
                    {data.month.days.map((day) => {
                      const isSelected =
                        selectedDate?.cabinId === cabin.id &&
                        isSameCalendarDay(selectedDate.date, day.date);

                      return (
                        <button
                          key={`${cabin.id}-${day.date.toISOString()}`}
                          type="button"
                          onClick={() => {
                            handleDayClick(cabin, day.date);
                          }}
                          onDoubleClick={() => {
                            handleDayDoubleClick(cabin, day.date);
                          }}
                          className={getDayCellClassName(day, isSelected)}
                          aria-label={`Wybierz termin: ${
                            cabin.shortName || cabin.name
                          }, dzień ${day.day}`}
                        />
                      );
                    })}
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