export default function AdminSettingsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-zinc-900">
          Ustawienia
        </h1>

        <p className="mt-2 text-zinc-600">
          Podstawowe ustawienia systemu Domki Sztabinki PMS.
        </p>
      </div>

      <div className="rounded-xl border bg-white p-6 shadow-sm">
        <h2 className="text-xl font-semibold text-zinc-900">
          Moduł ustawień
        </h2>

        <p className="mt-2 text-zinc-600">
          Ten moduł zostanie rozwinięty w kolejnym etapie projektu. Będzie
          zawierał dane obiektu, godziny zameldowania i wymeldowania,
          domyślne zasady pobytu oraz dane kontaktowe właściciela.
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-zinc-900">
            Dane obiektu
          </h3>

          <p className="mt-2 text-sm text-zinc-600">
            Nazwa obiektu, adres, dane kontaktowe i podstawowe informacje
            widoczne w systemie.
          </p>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-zinc-900">
            Zasady pobytu
          </h3>

          <p className="mt-2 text-sm text-zinc-600">
            Godzina zameldowania, godzina wymeldowania, minimalna liczba nocy
            oraz zasady sezonowe.
          </p>
        </div>

        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-zinc-900">
            Rezerwacje
          </h3>

          <p className="mt-2 text-sm text-zinc-600">
            Domyślne ustawienia rezerwacji, statusy, źródła oraz przyszłe
            integracje z systemami zewnętrznymi.
          </p>
        </div>
      </div>
    </div>
  );
}