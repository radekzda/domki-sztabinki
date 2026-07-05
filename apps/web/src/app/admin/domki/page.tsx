import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { CabinsTable } from "@/components/tables/cabins-table";

export default async function DomkiPage() {
  const domki = await prisma.cabin.findMany({
    orderBy: {
      sortOrder: "asc",
    },
    include: {
      images: {
        orderBy: {
          sortOrder: "asc",
        },
      },
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Domki</h1>
          <p className="text-zinc-500">
            Zarządzanie domkami, cenami i dostępnością.
          </p>
        </div>

        <Link
          href="/admin/domki/nowy"
          className="rounded-lg bg-green-700 px-4 py-2 text-white hover:bg-green-800"
        >
          + Dodaj domek
        </Link>
      </div>

      {domki.length === 0 ? (
        <div className="rounded-xl border bg-white p-8 text-center text-zinc-500">
          Brak domków w bazie.
        </div>
      ) : (
        <CabinsTable cabins={domki} />
      )}
    </div>
  );
}