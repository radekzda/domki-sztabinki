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
    <div className="max-w-3xl">

      <h1 className="mb-6 text-3xl font-bold">
        Edycja domku
      </h1>

      <form
         action={updateCabin.bind(null, cabin.id)}
         className="space-y-6"
      >

        <div>
          <label>Nazwa</label>

          <input
            defaultValue={cabin.name}
            name="name"
            className="mt-2 w-full rounded-lg border p-3"
          />
        </div>

        <div>
          <label>Opis</label>

          <textarea
            defaultValue={cabin.description}
            rows={6}
            name="description"
            className="mt-2 w-full rounded-lg border p-3"
          />
        </div>

        <div className="grid grid-cols-2 gap-4">

          <div>
            <label>Cena</label>

            <input
              type="number"
              defaultValue={cabin.pricePerNight}
              name="pricePerNight"
              className="mt-2 w-full rounded-lg border p-3"
            />
          </div>

          <div>
            <label>Goście</label>

            <input
              type="number"
              defaultValue={cabin.maxGuests}
              name="maxGuests"
              className="mt-2 w-full rounded-lg border p-3"
            />
          </div>

        </div>
        <div className="flex gap-3">

        <button
          type="submit"
          className="rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800"
         >
             Zapisz zmiany
        </button>

        <a
          href="/admin/domki"
          className="rounded-lg border px-6 py-3"
         >
        Anuluj
         </a>

        </div>
      </form>

    </div>
  );
}