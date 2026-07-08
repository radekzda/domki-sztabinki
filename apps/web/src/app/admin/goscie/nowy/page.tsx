import Link from "next/link";
import { createGuest } from "@/actions/create-guest";

type Props = {
  searchParams?: Promise<{
    error?: string;
  }>;
};

function getErrorMessage(error: string | undefined) {
  if (error === "missing-name") {
    return "Podaj przynajmniej imię albo nazwisko gościa.";
  }

  if (error === "missing-contact") {
    return "Podaj przynajmniej telefon albo adres e-mail.";
  }

  if (error === "invalid-birth-date") {
    return "Podaj prawidłową datę urodzenia.";
  }

  if (error === "future-birth-date") {
    return "Data urodzenia nie może być datą przyszłą.";
  }

  if (error) {
    return "Nie udało się dodać gościa. Sprawdź dane i spróbuj ponownie.";
  }

  return "";
}

export default async function NewGuestPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;
  const errorMessage = getErrorMessage(resolvedSearchParams?.error);

  return (
    <div className="space-y-8">
      <div>
        <Link
          href="/admin/goscie"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do gości
        </Link>

        <div className="mt-3">
          <h1 className="text-3xl font-bold">Dodaj gościa</h1>

          <p className="mt-2 text-zinc-500">
            Dodaj gościa ręcznie, bez tworzenia rezerwacji.
          </p>
        </div>
      </div>

      {errorMessage ? (
        <section className="rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
          <div className="font-semibold">Nie można dodać gościa</div>
          <p className="mt-2 text-sm">{errorMessage}</p>
        </section>
      ) : null}

      <form action={createGuest} className="space-y-8">
        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Dane podstawowe</h2>

          <div className="mt-5 grid gap-4 md:grid-cols-2">
            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Imię
              </label>

              <input
                name="firstName"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Jan"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Nazwisko
              </label>

              <input
                name="lastName"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Kowalski"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Telefon
              </label>

              <input
                name="phone"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. +48 500 000 000"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Email
              </label>

              <input
                type="email"
                name="email"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. jan@example.com"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Kraj
              </label>

              <input
                name="country"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Polska"
              />
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Dane dokumentu i programu</h2>

          <p className="mt-1 text-sm text-zinc-500">
            Te pola są opcjonalne. Przydają się przy imporcie z innych
            programów.
          </p>

          <div className="mt-5 grid gap-4 md:grid-cols-2">
            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                PESEL
              </label>

              <input
                name="pesel"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. 80010112345"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Numer dokumentu
              </label>

              <input
                name="documentNumber"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. ABC123456"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Narodowość
              </label>

              <input
                name="nationality"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Polska"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Data urodzenia
              </label>

              <input
                type="date"
                name="birthDate"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
              />
            </div>

            <div className="space-y-1 md:col-span-2">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Zewnętrzne ID gościa
              </label>

              <input
                name="externalGuestId"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="ID z innego programu"
              />
            </div>

            <label className="flex items-center gap-3 rounded-xl border bg-zinc-50 p-4 text-sm font-semibold">
              <input
                type="checkbox"
                name="isVip"
                className="h-4 w-4 rounded border-zinc-300"
              />
              Gość VIP
            </label>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Adres</h2>

          <p className="mt-1 text-sm text-zinc-500">
            Adres jest opcjonalny. Możesz wpisać go w częściach albo jako pełny
            adres.
          </p>

          <div className="mt-5 grid gap-4 md:grid-cols-2">
            <div className="space-y-1 md:col-span-2">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Ulica i numer
              </label>

              <input
                name="street"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Leśna 5"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Kod pocztowy
              </label>

              <input
                name="postalCode"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. 16-500"
              />
            </div>

            <div className="space-y-1">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Miasto
              </label>

              <input
                name="city"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Sejny"
              />
            </div>

            <div className="space-y-1 md:col-span-2">
              <label className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Pełny adres
              </label>

              <input
                name="fullAddress"
                className="h-10 w-full rounded-lg border bg-white px-3 text-sm font-medium"
                placeholder="np. Leśna 5, 16-500 Sejny, Polska"
              />
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Notatki</h2>

          <div className="mt-5">
            <label className="sr-only">Notatki</label>

            <textarea
              name="notes"
              rows={5}
              className="w-full rounded-lg border bg-white px-3 py-2 text-sm font-medium"
              placeholder="Np. preferencje gościa, wcześniejszy kontakt, uwagi organizacyjne..."
            />
          </div>
        </section>

        <div className="flex flex-wrap gap-3">
          <button className="rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800">
            Zapisz gościa
          </button>

          <Link
            href="/admin/goscie"
            className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
          >
            Anuluj
          </Link>
        </div>
      </form>
    </div>
  );
}