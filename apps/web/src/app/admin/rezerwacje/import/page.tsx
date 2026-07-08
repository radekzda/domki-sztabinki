import Link from "next/link";
import { importReservationsFromCsv } from "@/actions/import-reservations";

type Props = {
  searchParams?: Promise<{
    result?: string;
    rows?: string;
    created?: string;
    updated?: string;
    skipped?: string;
    error?: string;
  }>;
};

function getNumberParam(value: string | undefined) {
  if (!value) {
    return 0;
  }

  const parsedValue = Number(value);

  if (!Number.isFinite(parsedValue)) {
    return 0;
  }

  return parsedValue;
}

function getErrorMessage(error: string | undefined) {
  if (error === "no-file") {
    return "Nie wybrano pliku CSV.";
  }

  if (error === "wrong-file-type") {
    return "Wybrany plik musi mieć rozszerzenie .csv.";
  }

  if (error === "wrong-headers") {
    return "Plik CSV ma nieprawidłowe nagłówki. Użyj pliku Reservation_export.csv.";
  }

  if (error) {
    return "Nie udało się zaimportować pliku CSV.";
  }

  return "";
}

export default async function ImportReservationsPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const result = resolvedSearchParams?.result;
  const error = resolvedSearchParams?.error;

  const rows = getNumberParam(resolvedSearchParams?.rows);
  const created = getNumberParam(resolvedSearchParams?.created);
  const updated = getNumberParam(resolvedSearchParams?.updated);
  const skipped = getNumberParam(resolvedSearchParams?.skipped);

  const errorMessage = getErrorMessage(error);

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Import rezerwacji CSV</h1>

          <p className="mt-2 text-zinc-500">
            Zaimportuj rezerwacje z pliku Reservation_export.csv.
          </p>
        </div>

        <Link
          href="/admin/rezerwacje"
          className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
        >
          Wróć do rezerwacji
        </Link>
      </div>

      {result === "ok" ? (
        <section className="rounded-xl border border-green-200 bg-green-50 p-5 text-green-800">
          <div className="text-lg font-semibold">
            Import zakończony poprawnie.
          </div>

          <div className="mt-2 grid gap-3 text-sm md:grid-cols-4">
            <div className="rounded-lg bg-white p-3">
              <div className="text-green-700">Wiersze w CSV</div>
              <div className="text-2xl font-bold">{rows}</div>
            </div>

            <div className="rounded-lg bg-white p-3">
              <div className="text-green-700">Utworzono</div>
              <div className="text-2xl font-bold">{created}</div>
            </div>

            <div className="rounded-lg bg-white p-3">
              <div className="text-green-700">Zaktualizowano</div>
              <div className="text-2xl font-bold">{updated}</div>
            </div>

            <div className="rounded-lg bg-white p-3">
              <div className="text-green-700">Pominięto</div>
              <div className="text-2xl font-bold">{skipped}</div>
            </div>
          </div>
        </section>
      ) : null}

      {errorMessage ? (
        <section className="rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
          <div className="text-lg font-semibold">Błąd importu</div>

          <p className="mt-2 text-sm">{errorMessage}</p>
        </section>
      ) : null}

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Wybierz plik CSV</h2>

        <p className="mt-2 text-sm text-zinc-500">
          Najpierw zaimportuj gości z Guest_export.csv. Rezerwacje są łączone z
          gośćmi po polu guest_id z pliku rezerwacji i externalGuestId w bazie
          gości.
        </p>

        <form action={importReservationsFromCsv} className="mt-5 space-y-5">
          <div className="rounded-xl border border-dashed bg-zinc-50 p-5">
            <label className="block">
              <span className="text-sm font-semibold text-zinc-700">
                Plik Reservation_export.csv
              </span>

              <input
                type="file"
                name="file"
                accept=".csv,text/csv"
                required
                className="mt-2 block w-full rounded-lg border bg-white px-3 py-2 text-sm"
              />
            </label>
          </div>

          <button className="rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800">
            Importuj rezerwacje
          </button>
        </form>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Obsługiwany format</h2>

        <p className="mt-2 text-sm text-zinc-500">
          Importer obsługuje plik z bazy programu o takim układzie nagłówków:
        </p>

        <pre className="mt-4 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm text-white">
          room_id,adults_count,total_price,check_in_time,children_count,check_in,payment_status,check_out_time,ordered_by,source,special_requests,check_out,guest_id,paid_amount,status,id,created_date,updated_date,created_by_id,created_by,is_sample
        </pre>

        <div className="mt-5 grid gap-4 md:grid-cols-2">
          <div className="rounded-xl bg-zinc-50 p-4">
            <h3 className="font-semibold">Jak łączymy z gośćmi?</h3>

            <p className="mt-2 text-sm leading-6 text-zinc-600">
              Plik rezerwacji ma guest_id. System szuka gościa, którego
              externalGuestId jest takie samo. Dlatego import gości musi być
              wykonany przed importem rezerwacji.
            </p>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <h3 className="font-semibold">Jak łączymy z domkami?</h3>

            <p className="mt-2 text-sm leading-6 text-zinc-600">
              Importer mapuje room_id z programu na Domek 1, Domek 2, Domek 3
              i Domek 4. Jeśli domek nie istnieje w PMS, rezerwacja zostanie
              pominięta.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}