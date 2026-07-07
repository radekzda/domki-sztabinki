import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

async function getPublicCabins() {
  return prisma.cabin.findMany({
    where: {
      isActive: true,
    },
    orderBy: [
      {
        sortOrder: "asc",
      },
      {
        createdAt: "desc",
      },
    ],
    include: {
      images: {
        orderBy: [
          {
            isMain: "desc",
          },
          {
            sortOrder: "asc",
          },
          {
            createdAt: "asc",
          },
        ],
      },
    },
  });
}

export default async function HomePage() {
  const cabins = await getPublicCabins();

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
            Wybierz domek dla siebie
          </h2>

          <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
            Poniżej znajdują się aktywne domki dostępne w systemie PMS. Dane są
            pobierane bezpośrednio z bazy, dlatego po zmianie domku w panelu
            admina strona publiczna korzysta z aktualnych informacji.
          </p>

          {cabins.length === 0 ? (
            <div className="mt-12 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
              <h3 className="text-2xl font-black">Brak aktywnych domków</h3>
              <p className="mt-3 text-slate-600">
                W panelu admina dodaj domek albo ustaw istniejący domek jako
                aktywny, żeby pojawił się na stronie publicznej.
              </p>
            </div>
          ) : (
            <div className="mt-12 grid gap-10">
              {cabins.map((cabin) => {
                const orderedImages = cabin.images;
                const mainImage =
                  cabin.mainImageUrl ||
                  orderedImages.find((image) => image.isMain)?.url ||
                  orderedImages[0]?.url ||
                  null;
                const galleryImages = orderedImages
                  .filter((image) => image.url !== mainImage)
                  .slice(0, 4);
                const imageCount = orderedImages.length;

                return (
                  <article
                    key={cabin.id}
                    className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm"
                  >
                    <div className="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                      <div className="bg-slate-100">
                        {mainImage ? (
                          <div className="relative aspect-[16/11] overflow-hidden bg-slate-100 lg:h-full lg:min-h-[31rem] lg:aspect-auto">
                            <img
                              src={mainImage}
                              alt={cabin.name}
                              className="h-full w-full object-cover"
                            />

                            <div className="absolute left-4 top-4 rounded-full bg-white/95 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-slate-950 shadow-sm">
                              {imageCount === 1
                                ? "1 zdjęcie"
                                : `${imageCount} zdjęć`}
                            </div>
                          </div>
                        ) : (
                          <div className="flex aspect-[16/11] items-center justify-center bg-slate-100 px-6 text-center text-slate-500 lg:h-full lg:min-h-[31rem] lg:aspect-auto">
                            Zdjęcie domku będzie widoczne po dodaniu go w panelu
                            admina.
                          </div>
                        )}
                      </div>

                      <div className="flex flex-col p-6 lg:p-8">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                          <div>
                            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                              {cabin.shortName || "Domek"}
                            </p>

                            <h3 className="mt-2 text-3xl font-black">
                              {cabin.name}
                            </h3>
                          </div>

                          <div className="rounded-2xl bg-slate-950 px-5 py-4 text-center text-white">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-300">
                              od
                            </p>
                            <p className="text-2xl font-black">
                              {cabin.priceSevenPlusNights} zł
                            </p>
                            <p className="text-xs text-slate-300">za noc</p>
                          </div>
                        </div>

                        <p className="mt-5 line-clamp-5 leading-7 text-slate-600">
                          {cabin.description}
                        </p>

                        {galleryImages.length > 0 ? (
                          <div className="mt-6">
                            <p className="text-sm font-black uppercase tracking-[0.2em] text-slate-500">
                              Galeria
                            </p>

                            <div className="mt-3 grid grid-cols-4 gap-3">
                              {galleryImages.map((image) => (
                                <div
                                  key={image.id}
                                  className="aspect-square overflow-hidden rounded-2xl bg-slate-100"
                                >
                                  <img
                                    src={image.url}
                                    alt={image.alt || cabin.name}
                                    className="h-full w-full object-cover"
                                  />
                                </div>
                              ))}
                            </div>
                          </div>
                        ) : null}

                        <div className="mt-6 grid gap-3 text-sm text-slate-700 sm:grid-cols-3">
                          <div className="rounded-2xl bg-slate-50 p-4">
                            <p className="font-black">{cabin.maxGuests} osób</p>
                            <p className="mt-1 text-slate-500">maksymalnie</p>
                          </div>

                          <div className="rounded-2xl bg-slate-50 p-4">
                            <p className="font-black">{cabin.bedrooms}</p>
                            <p className="mt-1 text-slate-500">sypialnie</p>
                          </div>

                          <div className="rounded-2xl bg-slate-50 p-4">
                            <p className="font-black">{cabin.bathrooms}</p>
                            <p className="mt-1 text-slate-500">łazienka</p>
                          </div>
                        </div>

                        <div className="mt-6 rounded-3xl bg-slate-50 p-5">
                          <p className="text-sm font-black uppercase tracking-[0.2em] text-slate-500">
                            Cennik orientacyjny
                          </p>

                          <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div className="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                              <span>1 noc</span>
                              <strong>{cabin.priceOneNight} zł / noc</strong>
                            </div>

                            <div className="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                              <span>2 noce</span>
                              <strong>{cabin.priceTwoNights} zł / noc</strong>
                            </div>

                            <div className="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                              <span>3 noce</span>
                              <strong>{cabin.priceThreeNights} zł / noc</strong>
                            </div>

                            <div className="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                              <span>7+ nocy</span>
                              <strong>
                                {cabin.priceSevenPlusNights} zł / noc
                              </strong>
                            </div>
                          </div>
                        </div>

                        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                          <a
                            href="tel:+48502286724"
                            className="rounded-2xl bg-slate-950 px-6 py-4 text-center text-sm font-black text-white transition hover:bg-slate-800"
                          >
                            Zapytaj o termin
                          </a>

                          <a
                            href="#kontakt"
                            className="rounded-2xl border border-slate-300 px-6 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
                          >
                            Kontakt
                          </a>
                        </div>
                      </div>
                    </div>
                  </article>
                );
              })}
            </div>
          )}
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