import { updateSystemSettings } from "@/actions/settings";
import { prisma } from "@/lib/prisma";

type SearchParams = {
  saved?: string | string[];
};

type AdminSettingsPageProps = {
  searchParams?: Promise<SearchParams> | SearchParams;
};

async function getSystemSettings() {
  return prisma.systemSettings.upsert({
    where: {
      id: "main",
    },
    create: {
      id: "main",
      propertyName: "Domki Sztabinki",
      propertyCountry: "Polska",
      checkInTime: "15:00",
      checkOutTime: "11:00",
      minimumNights: 4,
      seasonStartMonth: 5,
      seasonEndMonth: 9,
    },
    update: {},
  });
}

export default async function AdminSettingsPage({
  searchParams,
}: AdminSettingsPageProps) {
  const resolvedSearchParams = await Promise.resolve(searchParams ?? {});
  const saved = resolvedSearchParams.saved === "1";
  const settings = await getSystemSettings();

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-zinc-900">
            Ustawienia
          </h1>

          <p className="mt-2 text-zinc-600">
            Podstawowe dane systemu Domki Sztabinki PMS.
          </p>
        </div>

        <div className="rounded-lg border bg-white px-4 py-3 text-sm text-zinc-600 shadow-sm">
          Rekord ustawień:{" "}
          <span className="font-semibold text-zinc-900">
            main
          </span>
        </div>
      </div>

      {saved ? (
        <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
          Ustawienia zostały zapisane.
        </div>
      ) : null}

      <form action={updateSystemSettings} className="space-y-8">
        <section className="rounded-xl border bg-white shadow-sm">
          <div className="border-b px-6 py-4">
            <h2 className="text-xl font-semibold text-zinc-900">
              Dane obiektu
            </h2>

            <p className="mt-1 text-sm text-zinc-500">
              Podstawowe informacje o obiekcie wykorzystywane w systemie.
            </p>
          </div>

          <div className="grid gap-6 p-6 lg:grid-cols-2">
            <div className="space-y-2 lg:col-span-2">
              <label
                htmlFor="propertyName"
                className="text-sm font-medium text-zinc-700"
              >
                Nazwa obiektu
              </label>

              <input
                id="propertyName"
                name="propertyName"
                type="text"
                defaultValue={settings.propertyName}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="propertyStreet"
                className="text-sm font-medium text-zinc-700"
              >
                Adres / ulica
              </label>

              <input
                id="propertyStreet"
                name="propertyStreet"
                type="text"
                defaultValue={settings.propertyStreet ?? ""}
                placeholder="np. Żegary"
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="propertyPostalCode"
                className="text-sm font-medium text-zinc-700"
              >
                Kod pocztowy
              </label>

              <input
                id="propertyPostalCode"
                name="propertyPostalCode"
                type="text"
                defaultValue={settings.propertyPostalCode ?? ""}
                placeholder="np. 16-500"
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="propertyCity"
                className="text-sm font-medium text-zinc-700"
              >
                Miejscowość
              </label>

              <input
                id="propertyCity"
                name="propertyCity"
                type="text"
                defaultValue={settings.propertyCity ?? ""}
                placeholder="np. Sejny"
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="propertyCountry"
                className="text-sm font-medium text-zinc-700"
              >
                Kraj
              </label>

              <input
                id="propertyCountry"
                name="propertyCountry"
                type="text"
                defaultValue={settings.propertyCountry}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2 lg:col-span-2">
              <label
                htmlFor="websiteUrl"
                className="text-sm font-medium text-zinc-700"
              >
                Strona internetowa
              </label>

              <input
                id="websiteUrl"
                name="websiteUrl"
                type="text"
                defaultValue={settings.websiteUrl ?? ""}
                placeholder="np. https://domkisztabinki.pl"
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white shadow-sm">
          <div className="border-b px-6 py-4">
            <h2 className="text-xl font-semibold text-zinc-900">
              Dane właściciela i kontakt
            </h2>

            <p className="mt-1 text-sm text-zinc-500">
              Dane kontaktowe używane wewnętrznie i w przyszłych dokumentach.
            </p>
          </div>

          <div className="grid gap-6 p-6 lg:grid-cols-2">
            <div className="space-y-2">
              <label
                htmlFor="ownerName"
                className="text-sm font-medium text-zinc-700"
              >
                Właściciel / osoba odpowiedzialna
              </label>

              <input
                id="ownerName"
                name="ownerName"
                type="text"
                defaultValue={settings.ownerName ?? ""}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="ownerEmail"
                className="text-sm font-medium text-zinc-700"
              >
                Email właściciela
              </label>

              <input
                id="ownerEmail"
                name="ownerEmail"
                type="email"
                defaultValue={settings.ownerEmail ?? ""}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="ownerPhone"
                className="text-sm font-medium text-zinc-700"
              >
                Telefon właściciela
              </label>

              <input
                id="ownerPhone"
                name="ownerPhone"
                type="text"
                defaultValue={settings.ownerPhone ?? ""}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="contactEmail"
                className="text-sm font-medium text-zinc-700"
              >
                Email kontaktowy dla gości
              </label>

              <input
                id="contactEmail"
                name="contactEmail"
                type="email"
                defaultValue={settings.contactEmail ?? ""}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="contactPhone"
                className="text-sm font-medium text-zinc-700"
              >
                Telefon kontaktowy dla gości
              </label>

              <input
                id="contactPhone"
                name="contactPhone"
                type="text"
                defaultValue={settings.contactPhone ?? ""}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-white shadow-sm">
          <div className="border-b px-6 py-4">
            <h2 className="text-xl font-semibold text-zinc-900">
              Zasady pobytu
            </h2>

            <p className="mt-1 text-sm text-zinc-500">
              Domyślne reguły używane przy rezerwacjach i w przyszłej stronie
              publicznej.
            </p>
          </div>

          <div className="grid gap-6 p-6 md:grid-cols-2 xl:grid-cols-4">
            <div className="space-y-2">
              <label
                htmlFor="checkInTime"
                className="text-sm font-medium text-zinc-700"
              >
                Zameldowanie od
              </label>

              <input
                id="checkInTime"
                name="checkInTime"
                type="time"
                defaultValue={settings.checkInTime}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="checkOutTime"
                className="text-sm font-medium text-zinc-700"
              >
                Wymeldowanie do
              </label>

              <input
                id="checkOutTime"
                name="checkOutTime"
                type="time"
                defaultValue={settings.checkOutTime}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="minimumNights"
                className="text-sm font-medium text-zinc-700"
              >
                Minimalna liczba nocy
              </label>

              <input
                id="minimumNights"
                name="minimumNights"
                type="number"
                min="1"
                max="365"
                defaultValue={settings.minimumNights}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="seasonStartMonth"
                className="text-sm font-medium text-zinc-700"
              >
                Początek sezonu — miesiąc
              </label>

              <input
                id="seasonStartMonth"
                name="seasonStartMonth"
                type="number"
                min="1"
                max="12"
                defaultValue={settings.seasonStartMonth}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>

            <div className="space-y-2">
              <label
                htmlFor="seasonEndMonth"
                className="text-sm font-medium text-zinc-700"
              >
                Koniec sezonu — miesiąc
              </label>

              <input
                id="seasonEndMonth"
                name="seasonEndMonth"
                type="number"
                min="1"
                max="12"
                defaultValue={settings.seasonEndMonth}
                className="w-full rounded-lg border px-4 py-3 text-zinc-900 shadow-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
              />
            </div>
          </div>
        </section>

        <div className="flex items-center justify-end gap-3 rounded-xl border bg-white p-4 shadow-sm">
          <p className="hidden text-sm text-zinc-500 md:block">
            Zmiany zostaną zapisane w ustawieniach systemu.
          </p>

          <button
            type="submit"
            className="rounded-lg bg-green-700 px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-green-800"
          >
            Zapisz ustawienia
          </button>
        </div>
      </form>
    </div>
  );
}