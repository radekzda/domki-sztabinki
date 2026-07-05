import { prisma } from "@/lib/prisma";
import {
  uploadCabinImage,
  setMainCabinImage,
  deleteCabinImage,
} from "@/actions/cabins";
import Link from "next/link";

type Props = {
  params: Promise<{
    id: string;
  }>;
};

export default async function Page({ params }: Props) {
  const { id } = await params;

  const cabin = await prisma.cabin.findUnique({
    where: { id },
    include: {
      images: {
        orderBy: {
          sortOrder: "asc",
        },
      },
    },
  });

  if (!cabin) {
    return <div>Nie znaleziono domku.</div>;
  }

  return (
    <div className="space-y-8">
      <div>
        <Link
          href="/admin/domki"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do domków
        </Link>

        <h1 className="mt-3 text-3xl font-bold">
          Zdjęcia — {cabin.name}
        </h1>
      </div>

      <form
        action={uploadCabinImage.bind(null, cabin.id)}
        className="rounded-xl border bg-white p-6 space-y-4"
      >
        <h2 className="text-xl font-semibold">
          Dodaj nowe zdjęcie
        </h2>

        <input
          type="file"
          name="image"
          accept="image/jpeg,image/png,image/webp"
          className="w-full rounded-lg border p-3"
        />

        <button className="rounded-lg bg-green-700 px-6 py-3 text-white hover:bg-green-800">
          Prześlij zdjęcie
        </button>
      </form>

      <div>
        <h2 className="mb-4 text-xl font-semibold">
          Galeria zdjęć
        </h2>

        {cabin.images.length === 0 ? (
          <div className="rounded-xl border bg-white p-8 text-center text-zinc-500">
            Brak zdjęć dla tego domku.
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {cabin.images.map((image) => (
              <div
                key={image.id}
                className="overflow-hidden rounded-xl border bg-white shadow-sm"
              >
                <img
                  src={image.url}
                  alt={image.alt ?? cabin.name}
                  className="h-48 w-full object-cover"
                />

                <div className="space-y-3 p-4">
                  {image.isMain ? (
                    <span className="inline-block rounded-full bg-green-100 px-3 py-1 text-sm text-green-700">
                      Zdjęcie główne
                    </span>
                  ) : (
                    <form action={setMainCabinImage.bind(null, image.id)}>
                      <button className="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Ustaw jako główne
                      </button>
                    </form>
                  )}

                  <form action={deleteCabinImage.bind(null, image.id)}>
                    <button className="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                      Usuń zdjęcie
                    </button>
                  </form>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}