import Link from "next/link";
import { notFound } from "next/navigation";
import { updateGuest } from "@/actions/guests";
import { prisma } from "@/lib/prisma";

type Props = {
  params: Promise<{
    id: string;
  }>;
  searchParams?: Promise<{
    error?: string;
  }>;
};

function formatDateInput(date: Date | null) {
  if (!date) {
    return "";
  }

  return date.toISOString().slice(0, 10);
}

export default async function EditGuestPage({ params, searchParams }: Props) {
  const resolvedParams = await params;
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const guest = await prisma.guest.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      reservations: true,
    },
  });

  if (!guest) {
    notFound();
  }

  return (
    <div className="max-w-4xl space-y-8">
      <div>
        <Link
          href={`/admin/goscie/${guest.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów gościa
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Edytuj gościa</h1>

        <p className="mt-2 text-zinc-500">
          Zmień dane gościa. Po zapisaniu dane kontaktowe i adresowe zostaną też
          zaktualizowane w powiązanych rezerwacjach.
        </p>
      </div>

      {resolvedSearchParams?.error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
          {resolvedSearchParams.error}
        </div>
      ) : null}

      <form
        action={updateGuest}
        className="space-y-6 rounded-xl border bg-white p-6 shadow-sm"
      >
        <input type="hidden" name="guestId" value={guest.id} />

        <section className="space-y-4">
          <div>
            <h2 className="text-xl font-semibold">Dane podstawowe</h2>

            <p className="mt-1 text-sm text-zinc-500">
              Wymagane jest przynajmniej imię albo nazwisko oraz przynajmniej
              email albo telefon.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm font-medium">Imię</label>

              <input
                name="firstName"
                defaultValue={guest.firstName}
                className="w-full rounded-lg border p-3"
                placeholder="np. Jan"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Nazwisko</label>

              <input
                name="lastName"
                defaultValue={guest.lastName}
                className="w-full rounded-lg border p-3"
                placeholder="np. Kowalski"
              />
            </div>
          </div>
        </section>

        <section className="space-y-4 border-t pt-6">
          <div>
            <h2 className="text-xl font-semibold">Kontakt</h2>
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm font-medium">Email</label>

              <input
                type="email"
                name="email"
                defaultValue={guest.email}
                className="w-full rounded-lg border p-3"
                placeholder="np. jan@example.com"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Telefon</label>

              <input
                name="phone"
                defaultValue={guest.phone ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. +48 500 000 000"
              />
            </div>
          </div>
        </section>

        <section className="space-y-4 border-t pt-6">
          <div>
            <h2 className="text-xl font-semibold">Dane dokumentu i programu</h2>

            <p className="mt-1 text-sm text-zinc-500">
              Te pola pochodzą głównie z importu programu, ale można je też
              uzupełnić ręcznie.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm font-medium">PESEL</label>

              <input
                name="pesel"
                defaultValue={guest.pesel ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. 80010112345"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Numer dokumentu</label>

              <input
                name="documentNumber"
                defaultValue={guest.documentNumber ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. ABC123456"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Narodowość</label>

              <input
                name="nationality"
                defaultValue={guest.nationality ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. Polska"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Data urodzenia</label>

              <input
                type="date"
                name="birthDate"
                defaultValue={formatDateInput(guest.birthDate)}
                className="w-full rounded-lg border p-3"
              />
            </div>

            <div className="space-y-2 md:col-span-2">
              <label className="text-sm font-medium">Zewnętrzne ID gościa</label>

              <input
                name="externalGuestId"
                defaultValue={guest.externalGuestId ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="ID z innego programu"
              />
            </div>

            <label className="flex items-center gap-3 rounded-xl border bg-zinc-50 p-4 text-sm font-semibold">
              <input
                type="checkbox"
                name="isVip"
                defaultChecked={guest.isVip}
                className="h-4 w-4 rounded border-zinc-300"
              />
              Gość VIP
            </label>
          </div>
        </section>

        <section className="space-y-4 border-t pt-6">
          <div>
            <h2 className="text-xl font-semibold">Adres</h2>

            <p className="mt-1 text-sm text-zinc-500">
              Te pola są używane przy danych gościa. Przy zapisie zostaną też
              uzupełnione w powiązanych rezerwacjach.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2 md:col-span-2">
              <label className="text-sm font-medium">Ulica i numer</label>

              <input
                name="street"
                defaultValue={guest.street ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. Leśna 5"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Kod pocztowy</label>

              <input
                name="postalCode"
                defaultValue={guest.postalCode ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. 16-500"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Miasto</label>

              <input
                name="city"
                defaultValue={guest.city ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. Sejny"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Kraj</label>

              <input
                name="country"
                defaultValue={guest.country ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. Polska"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Źródło techniczne</label>

              <select
                name="source"
                defaultValue={guest.source || "MANUAL"}
                className="w-full rounded-lg border bg-white p-3"
              >
                <option value="MANUAL">Ręcznie</option>
                <option value="BASE44">Base44</option>
                <option value="CSV_IMPORT">Import CSV</option>
                <option value="RESERVATION_SYNC">Synchronizacja rezerwacji</option>
              </select>
            </div>

            <div className="space-y-2 md:col-span-2">
              <label className="text-sm font-medium">Pełny adres</label>

              <input
                name="fullAddress"
                defaultValue={guest.fullAddress ?? ""}
                className="w-full rounded-lg border p-3"
                placeholder="np. Leśna 5, 16-500 Sejny, Polska"
              />
            </div>
          </div>
        </section>

        <section className="space-y-4 border-t pt-6">
          <div>
            <h2 className="text-xl font-semibold">Notatki</h2>

            <p className="mt-1 text-sm text-zinc-500">
              Notatki są zapisane tylko przy gościu. Nie nadpisują notatek w
              rezerwacjach.
            </p>
          </div>

          <textarea
            name="notes"
            rows={6}
            defaultValue={guest.notes ?? ""}
            className="w-full rounded-lg border p-3"
            placeholder="Np. preferencje gościa, informacje z importu, uwagi organizacyjne..."
          />
        </section>

        <section className="rounded-xl border bg-zinc-50 p-4">
          <div className="font-semibold">Powiązane rezerwacje</div>

          <p className="mt-1 text-sm text-zinc-500">
            Ten gość ma przypisane rezerwacje: {guest.reservations.length}. Po
            zapisaniu imię, nazwisko, email, telefon, kraj, ulica, kod pocztowy i
            miasto zostaną zaktualizowane także w tych rezerwacjach.
          </p>
        </section>

        <div className="flex gap-3 border-t pt-6">
          <button className="rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800">
            Zapisz zmiany
          </button>

          <Link
            href={`/admin/goscie/${guest.id}`}
            className="rounded-lg border px-6 py-3 hover:bg-zinc-50"
          >
            Anuluj
          </Link>
        </div>
      </form>
    </div>
  );
}