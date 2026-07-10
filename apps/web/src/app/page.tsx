import type { Metadata } from "next";

import { InquiryForm } from "@/components/public/InquiryForm";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Domki Sztabinki — domki nad jeziorem koło Sejn",
  description:
    "Komfortowe domki letniskowe nad jeziorem w Sztabinkach koło Sejn. Spokojny wypoczynek dla rodzin, natura, wędkowanie, sprzęt wodny i zapytanie o wolny termin online.",
  keywords: [
    "Domki Sztabinki",
    "domki nad jeziorem Sejny",
    "domki letniskowe Sejny",
    "noclegi Sejny",
    "Sztabinki",
    "Żegary",
    "wypoczynek nad jeziorem",
    "domki z widokiem na jezioro",
    "Suwałki noclegi",
    "wędkowanie Sejny",
  ],
  openGraph: {
    title: "Domki Sztabinki — wypoczynek nad jeziorem koło Sejn",
    description:
      "Spokojne domki letniskowe w Sztabinkach koło Sejn. Idealne miejsce na rodzinny pobyt nad wodą, odpoczynek w naturze i wędkowanie.",
    type: "website",
    locale: "pl_PL",
    siteName: "Domki Sztabinki",
  },
};

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

async function getPublicOccupiedDateRanges(cabinIds: string[]) {
  if (cabinIds.length === 0) {
    return [];
  }

  const reservations = await prisma.reservation.findMany({
    where: {
      cabinId: {
        in: cabinIds,
      },
      status: {
        not: "CANCELLED",
      },
    },
    orderBy: [
      {
        startDate: "asc",
      },
    ],
    select: {
      id: true,
      cabinId: true,
      startDate: true,
      endDate: true,
      checkInAt: true,
      checkOutAt: true,
      status: true,
    },
  });

  return reservations.map((reservation) => ({
    id: reservation.id,
    cabinId: reservation.cabinId,
    dateFrom: (reservation.checkInAt ?? reservation.startDate).toISOString(),
    dateTo: (reservation.checkOutAt ?? reservation.endDate).toISOString(),
    status: reservation.status,
  }));
}

async function getPublicSettings() {
  return prisma.systemSettings.findUnique({
    where: {
      id: "main",
    },
  });
}

function getMonthName(month: number) {
  const monthNames = [
    "styczeń",
    "luty",
    "marzec",
    "kwiecień",
    "maj",
    "czerwiec",
    "lipiec",
    "sierpień",
    "wrzesień",
    "październik",
    "listopad",
    "grudzień",
  ];

  if (month < 1 || month > 12) {
    return "maj";
  }

  return monthNames[month - 1];
}

function formatMinimumNights(nights: number) {
  if (nights === 1) {
    return "1 noc";
  }

  if (nights >= 2 && nights <= 4) {
    return `${nights} noce`;
  }

  return `${nights} nocy`;
}

function getPhoneHref(phone: string) {
  const normalizedPhone = phone.replace(/[^\d+]/g, "");

  if (normalizedPhone.startsWith("+")) {
    return `tel:${normalizedPhone}`;
  }

  if (normalizedPhone.length === 9) {
    return `tel:+48${normalizedPhone}`;
  }

  return `tel:${normalizedPhone}`;
}

export default async function HomePage() {
  const [cabins, settings] = await Promise.all([
    getPublicCabins(),
    getPublicSettings(),
  ]);

  const occupiedDateRanges = await getPublicOccupiedDateRanges(
    cabins.map((cabin) => cabin.id),
  );

  const minimumNights = settings?.minimumNights ?? 1;
  const checkInTime = settings?.checkInTime ?? "16:00";
  const checkOutTime = settings?.checkOutTime ?? "11:00";
  const seasonStartMonth = settings?.seasonStartMonth ?? 5;
  const seasonEndMonth = settings?.seasonEndMonth ?? 9;
  const seasonStartLabel = getMonthName(seasonStartMonth);
  const seasonEndLabel = getMonthName(seasonEndMonth);
  const seasonLabel = `${seasonStartLabel} — ${seasonEndLabel}`;
  const contactPhone = settings?.ownerPhone || "502 286 724";
  const contactPhoneHref = getPhoneHref(contactPhone);
  const contactEmail = settings?.ownerEmail || "";

  return (
    <main className="min-h-screen bg-white text-slate-950">
      <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
          <a
            href="/"
            className="text-center text-2xl font-black tracking-tight text-slate-950 lg:text-left"
          >
            Domki Sztabinki
          </a>

          <nav className="grid grid-cols-2 gap-2 text-sm font-bold sm:grid-cols-4 lg:flex lg:items-center lg:text-base">
            <a
              href="#domki"
              className="rounded-xl border border-slate-300 px-4 py-3 text-center text-slate-900 transition hover:bg-slate-100"
            >
              Domki
            </a>

            <a
              href="#cennik"
              className="rounded-xl border border-slate-300 px-4 py-3 text-center text-slate-900 transition hover:bg-slate-100"
            >
              Cennik
            </a>

            <a
              href="#kontakt"
              className="rounded-xl border border-slate-300 px-4 py-3 text-center text-slate-900 transition hover:bg-slate-100"
            >
              Kontakt
            </a>

            <a
              href={contactPhoneHref}
              className="rounded-xl bg-slate-950 px-4 py-3 text-center text-white shadow-sm transition hover:bg-slate-800"
            >
              Zadzwoń
            </a>
          </nav>
        </div>
      </header>

      <section className="bg-slate-950 px-4 py-16 text-white sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-12">
          <div>
            <p className="mb-5 text-sm font-semibold uppercase tracking-[0.25em] text-slate-300 sm:tracking-[0.3em]">
              Domki letniskowe nad jeziorem koło Sejn
            </p>

            <h1 className="max-w-4xl text-4xl font-black tracking-tight sm:text-5xl md:text-6xl">
              Domki Sztabinki — spokojny wypoczynek nad jeziorem
            </h1>

            <p className="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
              Komfortowe domki letniskowe w miejscowości Sztabinki koło Sejn.
              To miejsce dla osób, które szukają ciszy, natury, rodzinnego
              odpoczynku, bliskości jeziora i swobodnego pobytu z dala od
              zatłoczonych kurortów.
            </p>

            <div className="mt-8 grid gap-3 sm:mt-10 sm:flex sm:flex-row">
              <a
                href={contactPhoneHref}
                className="rounded-2xl bg-white px-6 py-4 text-center text-base font-black text-slate-950 shadow-sm transition hover:bg-slate-100 sm:px-8 sm:py-5 sm:text-lg"
              >
                Zadzwoń teraz: {contactPhone}
              </a>

              <a
                href="#kontakt"
                className="rounded-2xl border border-white px-6 py-4 text-center text-base font-black text-white transition hover:bg-white/10 sm:px-8 sm:py-5 sm:text-lg"
              >
                Zapytaj o wolny termin
              </a>
            </div>
          </div>

          <div className="rounded-[1.5rem] bg-white p-5 text-slate-950 shadow-2xl sm:rounded-[2rem] sm:p-6">
            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              Dlaczego warto
            </p>

            <div className="mt-5 grid gap-3 sm:mt-6 sm:gap-4">
              <div className="rounded-2xl bg-slate-50 p-4 sm:p-5">
                <p className="text-2xl font-black sm:text-3xl">nad jeziorem</p>
                <p className="mt-1 text-sm text-slate-600">
                  pomost, widok na wodę, sprzęt wodny i bliskość natury
                </p>
              </div>

              <div className="rounded-2xl bg-slate-50 p-4 sm:p-5">
                <p className="text-2xl font-black sm:text-3xl">dla rodzin</p>
                <p className="mt-1 text-sm text-slate-600">
                  wygodne domki, spokojna okolica i przestrzeń do odpoczynku
                </p>
              </div>

              <div className="rounded-2xl bg-slate-50 p-4 sm:p-5">
                <p className="text-2xl font-black sm:text-3xl">
                  Sejny i Suwalszczyzna
                </p>
                <p className="mt-1 text-sm text-slate-600">
                  dobra baza na wypoczynek i odkrywanie północno-wschodniej
                  Polski
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="domki" className="bg-white px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div className="mx-auto max-w-7xl">
          <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">
            Domki nad jeziorem
          </p>

          <h2 className="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl md:text-5xl">
            Wybierz domek na spokojny pobyt w Sztabinkach
          </h2>

          <p className="mt-5 max-w-3xl text-base leading-8 text-slate-600 sm:text-lg">
            Każdy domek jest przygotowany z myślą o wygodnym odpoczynku nad
            wodą. To dobre miejsce na rodzinny urlop, wyjazd z dziećmi,
            wędkowanie, grillowanie i spokojne wieczory z widokiem na jezioro.
          </p>

          {cabins.length === 0 ? (
            <div className="mt-10 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center sm:mt-12 sm:p-8">
              <h3 className="text-2xl font-black">Brak aktywnych domków</h3>
              <p className="mt-3 text-slate-600">
                Aktualnie nie ma domków dostępnych do prezentacji na stronie.
                W sprawie wolnych terminów prosimy o kontakt telefoniczny.
              </p>
            </div>
          ) : (
            <div className="mt-10 grid gap-8 sm:mt-12 lg:gap-10">
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
                    className="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm sm:rounded-[2rem]"
                  >
                    <div className="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                      <div className="bg-slate-100">
                        {mainImage ? (
                          <div className="relative aspect-[16/11] overflow-hidden bg-slate-100 lg:h-full lg:min-h-[31rem] lg:aspect-auto">
                            <img
                              src={mainImage}
                              alt={`${cabin.name} — domek letniskowy nad jeziorem w Sztabinkach koło Sejn`}
                              className="h-full w-full object-cover"
                            />

                            <div className="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-2 text-xs font-black uppercase tracking-[0.16em] text-slate-950 shadow-sm sm:left-4 sm:top-4 sm:px-4">
                              {imageCount === 1
                                ? "1 zdjęcie"
                                : `${imageCount} zdjęć`}
                            </div>
                          </div>
                        ) : (
                          <div className="flex aspect-[16/11] items-center justify-center bg-slate-100 px-6 text-center text-slate-500 lg:h-full lg:min-h-[31rem] lg:aspect-auto">
                            Zdjęcia domku będą widoczne po uzupełnieniu galerii.
                          </div>
                        )}
                      </div>

                      <div className="flex flex-col p-5 sm:p-6 lg:p-8">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                          <div>
                            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                              {cabin.shortName || "Domek letniskowy"}
                            </p>

                            <h3 className="mt-2 text-2xl font-black sm:text-3xl">
                              {cabin.name}
                            </h3>
                          </div>

                          <div className="rounded-2xl bg-slate-950 px-5 py-4 text-center text-white sm:min-w-32">
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

                            <div className="mt-3 grid grid-cols-4 gap-2 sm:gap-3">
                              {galleryImages.map((image) => (
                                <div
                                  key={image.id}
                                  className="aspect-square overflow-hidden rounded-xl bg-slate-100 sm:rounded-2xl"
                                >
                                  <img
                                    src={image.url}
                                    alt={
                                      image.alt ||
                                      `${cabin.name} — galeria domku nad jeziorem`
                                    }
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

                        <div className="mt-6 rounded-3xl bg-slate-50 p-4 sm:p-5">
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

                        <div className="mt-6 grid gap-3 sm:grid-cols-2">
                          <a
                            href="#kontakt"
                            className="rounded-2xl bg-slate-950 px-6 py-4 text-center text-sm font-black text-white transition hover:bg-slate-800"
                          >
                            Zapytaj o termin
                          </a>

                          <a
                            href={contactPhoneHref}
                            className="rounded-2xl border border-slate-300 px-6 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-slate-50"
                          >
                            Zadzwoń
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

      <section id="cennik" className="bg-slate-50 px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div className="mx-auto max-w-7xl">
          <div className="max-w-3xl">
            <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">
              Cennik i zasady pobytu
            </p>

            <h2 className="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl md:text-5xl">
              Najważniejsze informacje przed rezerwacją
            </h2>

            <p className="mt-5 text-base leading-8 text-slate-600 sm:text-lg">
              Ceny zależą od długości pobytu, terminu oraz wybranego domku.
              Najszybszym sposobem potwierdzenia dostępności i ceny jest
              telefon albo zapytanie przez formularz.
            </p>
          </div>

          <div className="mt-10 grid gap-4 sm:mt-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            <div className="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
              <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                Minimum pobytu
              </p>
              <p className="mt-4 text-3xl font-black">
                {formatMinimumNights(minimumNights)}
              </p>
              <p className="mt-3 leading-7 text-slate-600">
                Minimalny pobyt obowiązuje przy wysyłaniu zapytania o termin.
              </p>
            </div>

            <div className="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
              <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                Zameldowanie
              </p>
              <p className="mt-4 text-3xl font-black">{checkInTime}</p>
              <p className="mt-3 leading-7 text-slate-600">
                Godzina przyjazdu może być ustalana indywidualnie po
                wcześniejszym kontakcie.
              </p>
            </div>

            <div className="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
              <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                Wymeldowanie
              </p>
              <p className="mt-4 text-3xl font-black">{checkOutTime}</p>
              <p className="mt-3 leading-7 text-slate-600">
                Późniejsze wymeldowanie zależy od kolejnej rezerwacji i
                możliwości przygotowania domku.
              </p>
            </div>

            <div className="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
              <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
                Sezon
              </p>
              <p className="mt-4 text-3xl font-black">{seasonLabel}</p>
              <p className="mt-3 leading-7 text-slate-600">
                Zapytanie online można wysłać dla terminów mieszczących się w
                sezonie pobytowym.
              </p>
            </div>
          </div>

          <div className="mt-8 rounded-[1.5rem] bg-slate-950 p-5 text-white shadow-sm sm:rounded-[2rem] sm:p-6 md:p-8">
            <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
              <div>
                <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-300">
                  Ważne
                </p>

                <h3 className="mt-4 text-2xl font-black sm:text-3xl">
                  Miejsce dla osób szukających ciszy i odpoczynku
                </h3>

                <p className="mt-4 leading-8 text-slate-300">
                  Domki Sztabinki są przeznaczone dla gości, którzy chcą
                  odpocząć w spokojnej okolicy nad jeziorem. To nie jest miejsce
                  na głośne imprezy — zależy nam na komforcie wszystkich gości i
                  sąsiadów.
                </p>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-3xl bg-white/10 p-5">
                  <p className="font-black">Grill dostępny</p>
                  <p className="mt-2 text-sm leading-6 text-slate-300">
                    Goście mogą korzystać z grilla. Węgiel i rozpałka są po
                    stronie gości.
                  </p>
                </div>

                <div className="rounded-3xl bg-white/10 p-5">
                  <p className="font-black">Sprzęt wodny</p>
                  <p className="mt-2 text-sm leading-6 text-slate-300">
                    Na miejscu dostępne są między innymi rowerki wodne, kajak i
                    łódka.
                  </p>
                </div>

                <div className="rounded-3xl bg-white/10 p-5">
                  <p className="font-black">Wędkowanie</p>
                  <p className="mt-2 text-sm leading-6 text-slate-300">
                    Możliwość wędkowania w jeziorze lub w prywatnych stawach na
                    terenie obiektu.
                  </p>
                </div>

                <div className="rounded-3xl bg-white/10 p-5">
                  <p className="font-black">Kontakt telefoniczny</p>
                  <p className="mt-2 text-sm leading-6 text-slate-300">
                    Najszybszy sposób potwierdzenia terminu i ceny to kontakt
                    pod numerem {contactPhone}.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="bg-white px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div className="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:gap-10">
          <div>
            <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">
              Lokalizacja
            </p>

            <h2 className="mt-4 text-3xl font-black tracking-tight sm:text-4xl md:text-5xl">
              Sztabinki koło Sejn — jezioro, natura i spokój
            </h2>

            <p className="mt-5 text-base leading-8 text-slate-600 sm:text-lg">
              Domki znajdują się w spokojnej okolicy niedaleko Sejn, na
              Suwalszczyźnie. To dobre miejsce na rodzinny urlop, wypoczynek
              nad wodą, wędkowanie oraz wyjazd z dala od tłumu.
            </p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-3xl bg-slate-50 p-5 shadow-sm sm:p-6">
              <p className="text-sm font-semibold text-slate-500">
                Najbliższa okolica
              </p>
              <p className="mt-2 text-2xl font-black">jezioro i natura</p>
            </div>

            <div className="rounded-3xl bg-slate-50 p-5 shadow-sm sm:p-6">
              <p className="text-sm font-semibold text-slate-500">
                Charakter pobytu
              </p>
              <p className="mt-2 text-2xl font-black">spokojny wypoczynek</p>
            </div>

            <div className="rounded-3xl bg-slate-50 p-5 shadow-sm sm:p-6">
              <p className="text-sm font-semibold text-slate-500">
                Dla rodzin
              </p>
              <p className="mt-2 text-2xl font-black">woda i przestrzeń</p>
            </div>

            <div className="rounded-3xl bg-slate-50 p-5 shadow-sm sm:p-6">
              <p className="text-sm font-semibold text-slate-500">Kontakt</p>
              <p className="mt-2 text-2xl font-black">{contactPhone}</p>
            </div>
          </div>
        </div>
      </section>

      <section
        id="kontakt"
        className="bg-slate-950 px-4 py-16 text-white sm:px-6 sm:py-20 lg:px-8 lg:py-24"
      >
        <div className="mx-auto max-w-5xl text-center">
          <p className="text-sm font-bold uppercase tracking-[0.25em] text-slate-300">
            Zapytanie o pobyt
          </p>

          <h2 className="mt-4 text-3xl font-black tracking-tight sm:text-4xl md:text-5xl">
            Sprawdź wolny termin w Domkach Sztabinki
          </h2>

          <p className="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
            Wypełnij formularz, wybierz domek i sprawdź zajęte terminy w
            kalendarzu. Po wysłaniu zapytania potwierdzimy dostępność, cenę i
            szczegóły pobytu.
          </p>

          <InquiryForm
            recipientEmail={contactEmail}
            phoneNumber={contactPhone}
            cabins={cabins.map((cabin) => ({
              id: cabin.id,
              name: cabin.name,
            }))}
            occupiedDateRanges={occupiedDateRanges}
            minimumNights={minimumNights}
            minimumNightsLabel={formatMinimumNights(minimumNights)}
            seasonStartMonth={seasonStartMonth}
            seasonEndMonth={seasonEndMonth}
            seasonLabel={seasonLabel}
            checkInTime={checkInTime}
            checkOutTime={checkOutTime}
          />
        </div>
      </section>

      <footer className="border-t border-slate-200 bg-white px-4 py-8 sm:px-6 lg:px-8">
        <div className="mx-auto flex max-w-7xl flex-col gap-3 text-center text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:text-left">
          <p>© Domki Sztabinki</p>
          <p>Domki nad jeziorem koło Sejn — spokojny wypoczynek w naturze</p>
        </div>
      </footer>
    </main>
  );
}