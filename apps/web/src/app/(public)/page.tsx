export default function PublicHomePage() {
  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <section className="mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center px-6 py-16 text-center">
        <p className="mb-4 text-sm font-semibold uppercase tracking-[0.25em] text-slate-500">
          Domki Sztabinki
        </p>

        <h1 className="max-w-3xl text-4xl font-bold tracking-tight text-slate-950 md:text-6xl">
          Strona publiczna jest w przygotowaniu
        </h1>

        <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
          System PMS działa już w panelu administracyjnym. Publiczna strona z
          prezentacją domków, galerią, cennikiem i formularzem zapytania będzie
          rozwijana w kolejnym etapie projektu.
        </p>

        <div className="mt-10 flex flex-col gap-3 sm:flex-row">
          <a
            href="/admin"
            className="rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
          >
            Przejdź do panelu admina
          </a>
        </div>
      </section>
    </main>
  );
}