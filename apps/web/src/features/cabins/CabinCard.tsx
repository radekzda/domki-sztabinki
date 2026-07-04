import { Cabin } from "./cabins.data";

export function CabinCard({ cabin }: { cabin: Cabin }) {
  return (
    <div className="border rounded-xl p-4 bg-white hover:shadow-md transition">
      <h3 className="text-lg font-medium">{cabin.name}</h3>

      <p className="text-sm text-zinc-500 mt-1">
        {cabin.description}
      </p>

      <div className="mt-3 text-sm space-y-1">
        <div>👥 max: {cabin.maxGuests}</div>
        <div>🛏 pokoje: {cabin.bedrooms}</div>
        <div>🚿 łazienki: {cabin.bathrooms}</div>
      </div>

      <div className="mt-4 font-semibold">
        {cabin.pricePerNight} zł / noc
      </div>

      <button className="mt-4 w-full bg-black text-white py-2 rounded-lg">
        Edytuj
      </button>
    </div>
  );
}