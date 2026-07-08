import Link from "next/link";
import { importGuestsFromCsv } from "@/actions/import-guests";

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
    return "Plik CSV ma nieprawidłowe nagłówki. Sprawdź pierwszy wiersz pliku.";
  }

  if (error) {
    return "Nie udało się zaimportować pliku CSV.";
  }

  return "";
}

export default async function ImportGuestsPage({ searchParams }: Props) {
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
          <h1 className="text-3xl font-bold">Import gości CSV</h1>

          <p className="mt-2 text-zinc-500">
            Zaimportuj bazę gości z Base44 albo innego programu.
          </p>
        </div>

        <Link
          href="/admin/goscie"
          className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-zinc-50"
        >
          Wróć do gości
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
          Plik powinien być zapisany jako CSV UTF-8, z separatorem średnik.
        </p>

        <form action={importGuestsFromCsv} className="mt-5 space-y-5">
          <div className="rounded-xl border border-dashed bg-zinc-50 p-5">
            <label className="block">
              <span className="text-sm font-semibold text-zinc-700">
                Plik CSV
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
            Importuj gości
          </button>
        </form>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Wymagany format CSV</h2>

        <p className="mt-2 text-sm text-zinc-500">
          Pierwszy wiersz pliku musi zawierać dokładnie te nagłówki:
        </p>

        <pre className="mt-4 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm text-white">
          firstName;lastName;fullName;email;phone;country;street;postalCode;city;fullAddress;notes;source
        </pre>

        <div className="mt-5 grid gap-4 md:grid-cols-2">
          <div className="rounded-xl bg-zinc-50 p-4">
            <h3 className="font-semibold">Jak wykrywamy duplikaty?</h3>

            <p className="mt-2 text-sm leading-6 text-zinc-600">
              Najpierw po e-mailu, potem po numerze telefonu. Jeśli gość już
              istnieje, system uzupełni tylko puste dane.
            </p>
          </div>

          <div className="rounded-xl bg-zinc-50 p-4">
            <h3 className="font-semibold">Co z adresem?</h3>

            <p className="mt-2 text-sm leading-6 text-zinc-600">
              Jeśli adres jest rozbity na ulicę, kod i miasto, zostanie zapisany
              w tych polach. Jeśli jest tylko pełny adres, zostanie zapisany w
              polu pełnego adresu.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}