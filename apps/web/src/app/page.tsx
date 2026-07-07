export default function HomePage() {
  return (
    <main className="min-h-screen bg-white text-slate-950">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-6 sm:flex-row sm:items-center sm:justify-between lg:px-8">
          <a href="/" className="text-2xl font-black tracking-tight text-slate-950">
            Domki Sztabinki
          </a>

          <nav className="flex flex-col gap-3 text-base font-bold sm:flex-row sm:items-center">
            <a
              href="#domki"
              className="rounded-xl border border-slate-300 px-5 py-3 text-center text-slate-900 transition hover:bg-slate-100"
            >
              Zobacz domki
            </a>

            <a
              href="#kontakt"
              className="rounded-xl border border-slate-300 px-5 py-3 text-center text-slate-900 transition hover:bg-slate-100"
            >
              Kontakt
            </a>

            <a
              href="tel:+48502286724"
              className="rounded-xl bg-slate-950 px-6 py-3 text-center text-white shadow-sm transition hover:bg-slate-800"
            >
              Zadzwoń: 502 286 724
            </a>
          </nav>
        </div>
      </header>

      <section className="bg-slate-950 px-6 py-24 text-white lg:px-8">
        <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
          <div>
            <p className="mb-5 text-sm font-semibold uppercase tracking-[0.3em] text-slate-300">
              Domki Sztabinki
            </p>

            <h1 className="max-w-4xl text-4xl font-black tracking-tight md:text-6xl">
              Wypoczynek nad jeziorem w ciszy i naturze
            </h1>

            <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
              Komfortowe domki letniskowe w miejscowości Sztabinki koło Sejn.
              Idealne miejsce na spokojny rodzinny odpoczynek, pobyt nad wodą,
              wędkowanie i oderwanie się od codziennego pośpiechu.
            </p>

            <div className="mt-10 flex flex-col gap-4 sm:flex-row">
              <a
                href="tel:+48502286724"
                className="rounded-2xl bg-white px-8 py-5 text-center text-lg font-black text-slate-950 shadow-sm transition hover:bg-slate-100"
              >
                Zadzwoń teraz: 502 286 724
              </a>

              <a
                href="#domki"
                className="rounded-2xl border border-white px-8 py-5 text-center text-lg font-black text-white transition hover:bg-white/10"
              >
                Zobacz domki
              </a>
            </div>
          </div>

          <div className="rounded-[2rem] bg-white p-6 text-slate-950 shadow-2xl">
            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              W skrócie
            </p>

            <div className="mt-6 grid gap-4">
              <div className="rounded-2xl bg-slate-50 p-5">
                <p className="text-3xl font-black">48 m²</p>
                <p className="mt-1 text-sm text-slate-600">
                  wygodny domek z tarasem i widokiem na jezioro
                </p>
              </div>

              <div className="rounded-2xl bg-slate-50 p-5">
                <p className="text-3xl font-black">do 6 osób</p>
                <p className="mt-1 text-sm text-slate-600">
                  salon z aneksem, dwie sypialnie i łazienka
                </p>
              </div>

              <div className="rounded-2xl bg-slate-50 p-5">
                <p className="text-3xl font-black">nad jeziorem</p>
                <p className="mt-1 text-sm text-slate-600">
                  pomost, sprzęt wodny, grill i miejsce na ognisko
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="domki" className="bg-white px-6 py-24 lg:px-8">
        <div className="mx-auto max-w-7xl">
          <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">
            Domki
          </p>

          <h2 className="mt-4 text-4xl font-black tracking-tight text-slate-950 md:text-5xl">
            Komfortowy pobyt blisko natury
          </h2>

          <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
            Każdy domek ma funkcjonalny układ, taras oraz dostęp do atrakcji na
            terenie obiektu. To miejsce dla osób, które szukają spokojnego
            wypoczynku, a nie głośnych imprez.
          </p>

          <div className="mt-12 grid gap-6 md:grid-cols-3">
            <article className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-xl font-black">Wygodne wnętrze</h3>
              <p className="mt-3 leading-7 text-slate-600">
                Salon z aneksem kuchennym, sypialnia z łóżkiem podwójnym,
                druga sypialnia z dwoma łóżkami pojedynczymi oraz łazienka z
                prysznicem.
              </p>
            </article>

            <article className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-xl font-black">Pełne wyposażenie</h3>
              <p className="mt-3 leading-7 text-slate-600">
                Smart TV, Wi-Fi, lodówka, zmywarka, mikrofalówka, płyta
                indukcyjna, czajnik, kostkarka, moskitiery i klimatyzacja.
              </p>
            </article>

            <article className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
              <h3 className="text-xl font-black">Atrakcje na miejscu</h3>
              <p className="mt-3 leading-7 text-slate-600">
                Pomost, rowerki wodne, kajak, łódka, grill, miejsce na ognisko,
                plac zabaw dla dzieci oraz możliwość wędkowania.
              </p>
            </article>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 px-6 py-24 lg:px-8">
        <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.85fr_1.15fr]">
          <div>
            <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">
              Lokalizacja
            </p>

            <h2 className="mt-4 text-4xl font-black tracking-tight md:text-5xl">
              Sztabinki koło Sejn
            </h2>

            <p className="mt-5 text-lg leading-8 text-slate-600">
              Domki znajdują się w spokojnej okolicy nad jeziorem, niedaleko
              Sejn. To dobre miejsce na rodzinny urlop, odpoczynek nad wodą i
              wyjazd z dala od tłumu.
            </p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-3xl bg-white p-6 shadow-sm">
              <p className="text-sm font-semibold text-slate-500">
                Najbliższa okolica
              </p>
              <p className="mt-2 text-2xl font-black">jezioro i natura</p>
            </div>

            <div className="rounded-3xl bg-white p-6 shadow-sm">
              <p className="text-sm font-semibold text-slate-500">
                Charakter pobytu
              </p>
              <p className="mt-2 text-2xl font-black">spokojny wypoczynek</p>
            </div>

            <div className="rounded-3xl bg-white p-6 shadow-sm">
              <p className="text-sm font-semibold text-slate-500">
                Dla rodzin
              </p>
              <p className="mt-2 text-2xl font-black">plac zabaw i woda</p>
            </div>

            <div className="rounded-3xl bg-white p-6 shadow-sm">
              <p className="text-sm font-semibold text-slate-500">
                Kontakt
              </p>
              <p className="mt-2 text-2xl font-black">502 286 724</p>
            </div>
          </div>
        </div>
      </section>

      <section id="kontakt" className="bg-slate-950 px-6 py-24 text-white lg:px-8">
        <div className="mx-auto max-w-4xl text-center">
          <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-300">
            Rezerwacja
          </p>

          <h2 className="mt-4 text-4xl font-black tracking-tight md:text-5xl">
            Zapytaj o wolny termin
          </h2>

          <p className="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-300">
            Strona rezerwacji online będzie rozwijana w kolejnym etapie. Na ten
            moment najlepiej skontaktować się telefonicznie.
          </p>

          <div className="mt-8">
            <a
              href="tel:+48502286724"
              className="inline-flex justify-center rounded-2xl bg-white px-8 py-5 text-lg font-black text-slate-950 shadow-sm transition hover:bg-slate-100"
            >
              Zadzwoń: 502 286 724
            </a>
          </div>
        </div>
      </section>

      <footer className="border-t border-slate-200 bg-white px-6 py-8 lg:px-8">
        <div className="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <p>© Domki Sztabinki</p>
          <p>Spokojny wypoczynek nad jeziorem koło Sejn</p>
        </div>
      </footer>
    </main>
  );
}