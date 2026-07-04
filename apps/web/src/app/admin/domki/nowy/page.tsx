import { createCabin } from "@/actions/cabins";
export default function NowyDomekPage() {
  return (
    <div className="max-w-2xl">
      <h1 className="text-3xl font-bold mb-6">
        Dodaj domek
      </h1>

      <form action={createCabin} className="space-y-5">

        <div>
          <label className="block mb-2 font-medium">
            Nazwa
          </label>

          <input
            className="border rounded-lg w-full p-3"
            name="name"
          />
        </div>

        <div>
          <label className="block mb-2 font-medium">
            Opis
          </label>

          <textarea
            className="border rounded-lg w-full p-3"
            rows={5}
            name="description"
          />
        </div>

        <div className="grid grid-cols-2 gap-4">

          <div>
            <label className="block mb-2">
              Cena za noc
            </label>

            <input
              type="number"
              className="border rounded-lg w-full p-3"
              name="pricePerNight"
            />
          </div>

          <div>
            <label className="block mb-2">
              Maks. osób
            </label>

            <input
              type="number"
              className="border rounded-lg w-full p-3"
              name="maxGuests"
            />
          </div>

          <div>
            <label className="block mb-2">
              Sypialnie
            </label>

            <input
              type="number"
              className="border rounded-lg w-full p-3"
              name="bedrooms"
            />
          </div>

          <div>
            <label className="block mb-2">
              Łazienki
            </label>

            <input
              type="number"
              className="border rounded-lg w-full p-3"
              name="bathrooms"
            />
          </div>

        </div>

        <button
          className="bg-green-700 text-white px-6 py-3 rounded-lg"
        >
          Zapisz domek
        </button>

      </form>

    </div>
  );
}