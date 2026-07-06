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
    <div className="max-w-3xl space-y-8">
      <div>
        <Link
          href={`/admin/goscie/${guest.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów gościa
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Edytuj gościa</h1>

        <p className="mt-2 text-zinc-500">
          Zmień dane kontaktowe gościa. Po zapisaniu dane zostaną też
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
              Te dane są używane w bazie gości i na listach rezerwacji.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm font-medium">Imię</label>
              <input
                name="firstName"
                required
                defaultValue={guest.firstName}
                className="w-full rounded-lg border p-3"
                placeholder="np. Jan"
              />
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Nazwisko</label>
              <input
                name="lastName"
                required
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
                required
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
                placeholder="np. 500 000 000"
              />
            </div>
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
        </section>

        <section className="rounded-xl border bg-zinc-50 p-4">
          <div className="font-semibold">Powiązane rezerwacje</div>

          <p className="mt-1 text-sm text-zinc-500">
            Ten gość ma przypisane rezerwacje: {guest.reservations.length}.
            Po zapisaniu imię, nazwisko, email, telefon i kraj zostaną
            zaktualizowane także w tych rezerwacjach.
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