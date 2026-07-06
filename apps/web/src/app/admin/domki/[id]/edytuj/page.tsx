import Link from "next/link";
import { updateCabin } from "@/actions/cabins";
import { prisma } from "@/lib/prisma";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

export default async function EditCabinPage({ params }: Props) {
  const { id } = await params;

  const cabin = await prisma.cabin.findUnique({
    where: {
      id,
    },
  });

  if (!cabin) {
    return <div>Nie znaleziono domku.</div>;
  }

  return (
    <div className="max-w-4xl space-y-8">
      <div>
        <Link
          href="/admin/domki"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do domków
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Edycja domku</h1>

        <p className="mt-2 text-zinc-500">
          Edytuj dane domku, cenę regularną i cennik domyślny dla rezerwacji.
        </p>
      </div>

      <form
        action={updateCabin.bind(null, cabin.id)}
        className="space-y-8 rounded-xl border bg-white p-6 shadow-sm"
      >
        <section className="space-y-5">
          <h2 className="text-xl font-semibold">Podstawowe dane</h2>

          <div>
            <label className="block text-sm font-medium">Nazwa</label>
            <input
              name="name"
              required
              defaultValue={cabin.name}
              className="mt-2 w-full rounded-lg border p-3"
            />
          </div>

          <div>
            <label className="block text-sm font-medium">Krótka nazwa</label>
            <input
              name="shortName"
              defaultValue={cabin.shortName ?? ""}
              className="mt-2 w-full rounded-lg border p-3"
              placeholder="np. D1"
            />
          </div>

          <div>
            <label className="block text-sm font-medium">Opis</label>
            <textarea
              name="description"
              required
              defaultValue={cabin.description}
              rows={5}
              className="mt-2 w-full rounded-lg border p-3"
            />
          </div>

          <div className="grid gap-4 md:grid-cols-4">
            <div>
              <label className="block text-sm font-medium">Maks. osób</label>
              <input
                type="number"
                name="maxGuests"
                min={1}
                defaultValue={cabin.maxGuests}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">Sypialnie</label>
              <input
                type="number"
                name="bedrooms"
                min={0}
                defaultValue={cabin.bedrooms}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">Łazienki</label>
              <input
                type="number"
                name="bathrooms"
                min={0}
                defaultValue={cabin.bathrooms}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">Kolejność</label>
              <input
                type="number"
                name="sortOrder"
                min={0}
                defaultValue={cabin.sortOrder}
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>
          </div>
        </section>

        <section className="space-y-5 border-t pt-8">
          <div>
            <h2 className="text-xl font-semibold">Cena regularna</h2>
            <p className="mt-1 text-sm text-zinc-500">
              Cena regularna jest ceną bazową domku. Cennik poniżej służy do
              automatycznego liczenia rezerwacji według liczby nocy.
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium">
              Cena regularna za noc
            </label>
            <input
              type="number"
              name="pricePerNight"
              min={0}
              defaultValue={cabin.pricePerNight}
              required
              className="mt-2 w-full rounded-lg border p-3"
            />
          </div>
        </section>

        <section className="space-y-5 border-t pt-8">
          <div>
            <h2 className="text-xl font-semibold">
              Domyślny cennik według długości pobytu
            </h2>
            <p className="mt-1 text-sm text-zinc-500">
              Te ceny będą automatycznie podstawiane w rezerwacji, ale cenę
              rezerwacji nadal można ręcznie zmienić.
            </p>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
              <label className="block text-sm font-medium">1 noc</label>
              <input
                type="number"
                name="priceOneNight"
                min={0}
                defaultValue={cabin.priceOneNight}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">2 noce / za noc</label>
              <input
                type="number"
                name="priceTwoNights"
                min={0}
                defaultValue={cabin.priceTwoNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">3 noce / za noc</label>
              <input
                type="number"
                name="priceThreeNights"
                min={0}
                defaultValue={cabin.priceThreeNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">4 noce / za noc</label>
              <input
                type="number"
                name="priceFourNights"
                min={0}
                defaultValue={cabin.priceFourNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">5 nocy / za noc</label>
              <input
                type="number"
                name="priceFiveNights"
                min={0}
                defaultValue={cabin.priceFiveNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">6 nocy / za noc</label>
              <input
                type="number"
                name="priceSixNights"
                min={0}
                defaultValue={cabin.priceSixNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>

            <div>
              <label className="block text-sm font-medium">7+ nocy / za noc</label>
              <input
                type="number"
                name="priceSevenPlusNights"
                min={0}
                defaultValue={cabin.priceSevenPlusNights}
                required
                className="mt-2 w-full rounded-lg border p-3"
              />
            </div>
          </div>
        </section>

        <div className="flex gap-3 border-t pt-8">
          <button
            type="submit"
            className="rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800"
          >
            Zapisz zmiany
          </button>

          <Link
            href="/admin/domki"
            className="rounded-lg border px-6 py-3 hover:bg-zinc-50"
          >
            Anuluj
          </Link>
        </div>
      </form>
    </div>
  );
}