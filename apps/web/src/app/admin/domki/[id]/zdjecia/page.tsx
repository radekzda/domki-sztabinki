import { prisma } from "@/lib/prisma";
import { addCabinImage } from "@/actions/cabins";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

export default async function Page({ params }: Props) {
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
    <div className="max-w-2xl">

      <h1 className="mb-6 text-3xl font-bold">
        Zdjęcia — {cabin.name}
      </h1>

      <form
        action={addCabinImage.bind(null, cabin.id)}
        className="space-y-4"
      >

        <input
          name="url"
          placeholder="https://..."
          className="w-full rounded-lg border p-3"
        />

        <button
          className="rounded-lg bg-green-700 px-6 py-3 text-white"
        >
          Dodaj zdjęcie
        </button>

      </form>

    </div>
  );
}