"use client";

export default function CalendarLegend() {
  return (
    <div className="rounded-xl border bg-white p-5 shadow-sm">
      <div className="grid gap-6 xl:grid-cols-3">
        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">
            Źródła rezerwacji
          </h3>

          <div className="mt-4 flex flex-wrap gap-4 text-sm">
            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded bg-green-700 text-xs font-bold text-white">
                B
              </span>
              <span>Booking</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded bg-red-500 text-xs font-bold text-white">
                A
              </span>
              <span>Airbnb</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded bg-blue-600 text-xs font-bold text-white">
                W
              </span>
              <span>WWW</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded bg-yellow-500 text-xs font-bold text-zinc-950">
                T
              </span>
              <span>Telefon</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded bg-zinc-600 text-xs font-bold text-white">
                M
              </span>
              <span>Ręcznie</span>
            </div>
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">
            Status rezerwacji
          </h3>

          <div className="mt-4 flex flex-wrap gap-4 text-sm">
            <div className="flex items-center gap-2">
              <span className="h-4 w-4 rounded-full bg-orange-500" />
              <span>Oczekuje na potwierdzenie</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="h-4 w-4 rounded-full bg-blue-600" />
              <span>Potwierdzona</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="h-4 w-4 rounded-full bg-green-700" />
              <span>Zameldowany</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="h-4 w-4 rounded-full bg-zinc-400" />
              <span>Wymeldowany</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="h-4 w-4 rounded-full bg-red-500" />
              <span>Anulowany</span>
            </div>
          </div>

          <div className="mt-4 rounded-lg bg-zinc-50 p-3 text-sm leading-6 text-zinc-600">
            <strong>Blokują termin:</strong> oczekujące, potwierdzone i
            zameldowane. <strong>Nie blokują:</strong> wymeldowane i anulowane.
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">
            Płatność
          </h3>

          <div className="mt-4 flex flex-wrap gap-4 text-sm">
            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-700">
                ✓
              </span>
              <span>Opłacona</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded-full bg-yellow-100 text-sm font-bold text-yellow-800">
                !
              </span>
              <span>Do zapłaty</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-800">
                %
              </span>
              <span>Częściowa</span>
            </div>

            <div className="flex items-center gap-2">
              <span className="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-700">
                ↩
              </span>
              <span>Zwrócona</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}