"use client";

import ReservationLayer from "@/components/calendar/ReservationLayer";
import type { CalendarEngineData } from "@/modules/calendar/calendar.types";

type CalendarProps = {
  data: CalendarEngineData;
};

export default function Calendar({ data }: CalendarProps) {
  const dayWidth = 48;
  const rowHeight = 64;
  const cabinColumnWidth = 260;
  const gridTemplateColumns = `${cabinColumnWidth}px repeat(${data.month.days.length}, ${dayWidth}px)`;

  return (
    <div className="space-y-6">
      <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div className="border-b px-6 py-5">
          <h2 className="text-2xl font-bold">
            {data.month.name} {data.month.year}
          </h2>

          <p className="mt-2 text-sm text-zinc-500">Calendar Engine v1</p>
        </div>

        <div className="overflow-x-auto p-4">
          <div
            className="grid min-w-max"
            style={{
              gridTemplateColumns,
            }}
          >
            <div className="sticky left-0 z-30 border-b border-r bg-zinc-100 p-3 font-semibold">
              Domek
            </div>

            {data.month.days.map((day) => (
              <div
                key={day.date.toISOString()}
                className={`border-b border-r p-2 text-center text-sm ${
                  day.isToday
                    ? "bg-green-700 font-bold text-white"
                    : "bg-zinc-100"
                }`}
              >
                {day.day}
              </div>
            ))}

            {data.cabins.map((cabin) => (
              <div key={cabin.id} className="contents">
                <div
                  className="sticky left-0 z-20 border-b border-r bg-white p-3"
                  style={{
                    height: rowHeight,
                  }}
                >
                  <div className="font-semibold">{cabin.name}</div>

                  <div className="mt-1 text-xs text-zinc-500">
                    max {cabin.maxGuests} osób
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
                      <div
                        key={`${cabin.id}-${day.date.toISOString()}`}
                        className="border-r bg-white"
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

        <div className="mt-4 grid gap-3 md:grid-cols-3">
          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Domki</div>
            <div className="mt-1 text-2xl font-bold">
              {data.cabins.length}
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Dni</div>
            <div className="mt-1 text-2xl font-bold">
              {data.month.days.length}
            </div>
          </div>

          <div className="rounded-lg border p-4">
            <div className="text-sm text-zinc-500">Widok</div>
            <div className="mt-1 text-2xl font-bold">Month</div>
          </div>
        </div>
      </div>
    </div>
  );
}