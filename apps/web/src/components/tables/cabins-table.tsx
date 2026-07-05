import Link from "next/link";
import { DeleteCabinButton } from "@/components/tables/delete-cabin-button";
import { ToggleCabinStatus } from "@/components/tables/toggle-cabin-status";

type Cabin = {
  id: string;
  name: string;
  description: string;
  maxGuests: number;
  bedrooms: number;
  bathrooms: number;
  pricePerNight: number;
  sortOrder: number;
  isActive: boolean;
  mainImageUrl: string | null;
  images: {
    id: string;
    url: string;
    alt: string | null;
    isMain: boolean;
  }[];
};

export function CabinsTable({ cabins }: { cabins: Cabin[] }) {
  return (
    <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
      <table className="w-full">
        <thead className="bg-zinc-100 text-left text-sm">
          <tr>
            <th className="p-4">Zdjęcie</th>
            <th className="p-4">Domek</th>
            <th className="p-4">Goście</th>
            <th className="p-4">Układ</th>
            <th className="p-4">Lp.</th>
            <th className="p-4">Cena</th>
            <th className="p-4">Status</th>
            <th className="p-4">Akcje</th>
          </tr>
        </thead>

        <tbody>
          {cabins.map((cabin) => (
            <tr key={cabin.id} className="border-t hover:bg-zinc-50">
              <td className="p-4">
                {cabin.images[0] ? (
                  <img
                    src={cabin.images[0].url}
                    alt={cabin.images[0].alt ?? cabin.name}
                    className="h-16 w-24 rounded-lg object-cover"
                  />
                ) : (
                  <div className="flex h-16 w-24 items-center justify-center rounded-lg bg-zinc-100 text-xs text-zinc-400">
                    Brak zdjęcia
                  </div>
                )}
              </td>

              <td className="p-4">
                <div className="font-semibold">{cabin.name}</div>
                <div className="text-sm text-zinc-500">
                  {cabin.description}
                </div>
              </td>

              <td className="p-4">{cabin.maxGuests} osób</td>

              <td className="p-4">
                {cabin.bedrooms} syp. / {cabin.bathrooms} łaz.
              </td>

              <td className="p-4">{cabin.sortOrder}</td>

              <td className="p-4 font-semibold">
                {cabin.pricePerNight} zł
              </td>

              <td className="p-4">
                <ToggleCabinStatus
                  id={cabin.id}
                  active={cabin.isActive}
                />
              </td>

              <td className="p-4">
                <div className="flex gap-2">
                  <Link
                    href={`/admin/domki/${cabin.id}/edytuj`}
                    className="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white"
                  >
                    Edytuj
                  </Link>

                  <Link
                    href={`/admin/domki/${cabin.id}/zdjecia`}
                    className="rounded-lg bg-green-600 px-3 py-2 text-sm text-white"
                  >
                    Zdjęcia
                  </Link>

                  <DeleteCabinButton id={cabin.id} />
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}