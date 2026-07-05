import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { createReservation } from "@/actions/reservations";

const statuses = [
  { value: "PENDING", label: "Oczekująca" },
  { value: "CONFIRMED", label: "Potwierdzona" },
  { value: "CANCELLED", label: "Anulowana" },
  { value: "COMPLETED", label: "Zakończona" },
];

export default async function NowaRezerwacjaPage() {
  const cabins = await prisma.cabin.findMany({
    where: {
      isActive: true,
    },
    orderBy: {
      sortOrder: "asc",
    },
  });

  return (
    <div className="max-w-3xl space-y-8">
      <div>
        <Link
          href="/admin/rezerwacje"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do rezerwacji
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Dodaj rezerwację</h1>

        <p className="mt-2 text-zinc-500">
          Dodaj ręczną rezerwację z telefonu, wiadomości lub własnej strony.
        </p>
      </div>

      <form action={createReservation} className="space-y-6 rounded-xl border bg-white p-6 shadow-sm">
        <div className="space-y-2">
          <label className="text-sm font-medium">Domek</label>

          <select name="cabinId" required className="w-full rounded-lg border p-3">
            <option value="">Wybierz domek</option>

            {cabins.map((cabin) => (
              <option key={cabin.id} value={cabin.id}>
                {cabin.name} — maks. {cabin.maxGuests} osób
              </option>
            ))}
          </select>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Imię i nazwisko gościa</label>
            <input
              name="guestName"
              required
              className="w-full rounded-lg border p-3"
              placeholder="np. Jan Kowalski"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Email</label>
            <input
              type="email"
              name="email"
              required
              className="w-full rounded-lg border p-3"
              placeholder="np. jan@example.com"
            />
          </div>
        </div>

        <div className="space-y-2">
          <label className="text-sm font-medium">Telefon</label>
          <input
            name="phone"
            className="w-full rounded-lg border p-3"
            placeholder="np. 500 000 000"
          />
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Przyjazd</label>
            <input
              type="date"
              name="startDate"
              required
              className="w-full rounded-lg border p-3"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Wyjazd</label>
            <input
              type="date"
              name="endDate"
              required
              className="w-full rounded-lg border p-3"
            />
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-sm font-medium">Liczba osób</label>
            <input
              type="number"
              name="guests"
              required
              min={1}
              className="w-full rounded-lg border p-3"
              placeholder="np. 4"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Status</label>
            <select name="status" required defaultValue="PENDING" className="w-full rounded-lg border p-3">
              {statuses.map((status) => (
                <option key={status.value} value={status.value}>
                  {status.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="flex gap-3">
          <button className="rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800">
            Zapisz rezerwację
          </button>

          <Link
            href="/admin/rezerwacje"
            className="rounded-lg border px-6 py-3 hover:bg-zinc-50"
          >
            Anuluj
          </Link>
        </div>
      </form>
    </div>
  );
}