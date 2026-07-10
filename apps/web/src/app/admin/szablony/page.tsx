import {
  createResponseTemplate,
  deleteResponseTemplate,
  updateResponseTemplate,
} from "@/actions/responseTemplates";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

const defaultTemplates = [
  {
    name: "Potwierdzenie dostępności",
    subject: "Domki Sztabinki — odpowiedź na zapytanie [IMIE]",
    body: [
      "Dzień dobry,",
      "",
      "dziękujemy za zapytanie. Wybrany termin [TERMIN] w wybranym domku [DOMEK] jest dostępny.",
      "",
      "Cena pobytu wynosi [KWOTA] zł za [LICZBA_NOCY] noclegi. Cena obejmuje pobyt do [LICZBA_OSOB] osób oraz korzystanie z wyposażenia domku, grilla, łódki, kajaka i rowerków wodnych.",
      "",
      "W celu potwierdzenia rezerwacji prosimy o informację zwrotną. Następnie prześlemy dane do wpłaty zadatku.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 0,
  },
  {
    name: "Brak dostępności",
    subject: "Domki Sztabinki — odpowiedź na zapytanie [IMIE]",
    body: [
      "Dzień dobry,",
      "",
      "dziękujemy za zapytanie. Niestety wybrany termin [TERMIN] w domku [DOMEK] nie jest już dostępny.",
      "",
      "Możemy sprawdzić dla Państwa inny termin lub inny dostępny domek. Prosimy o informację, czy interesuje Państwa alternatywny termin pobytu.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 10,
  },
  {
    name: "Prośba o potwierdzenie rezerwacji",
    subject: "Domki Sztabinki — potwierdzenie rezerwacji [TERMIN]",
    body: [
      "Dzień dobry,",
      "",
      "wstępnie rezerwujemy dla Państwa termin [TERMIN] w domku [DOMEK].",
      "",
      "Cena pobytu wynosi [KWOTA] zł za [LICZBA_NOCY] noclegi dla maksymalnie [LICZBA_OSOB] osób.",
      "",
      "W celu potwierdzenia rezerwacji prosimy o wiadomość zwrotną. Po potwierdzeniu prześlemy dane do wpłaty zadatku.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 20,
  },
  {
    name: "Przypomnienie o zadatku",
    subject: "Domki Sztabinki — zadatek za rezerwację [TERMIN]",
    body: [
      "Dzień dobry,",
      "",
      "przypominamy o wpłacie zadatku za rezerwację terminu [TERMIN] w domku [DOMEK].",
      "",
      "Rezerwacja zostanie potwierdzona po zaksięgowaniu wpłaty zadatku.",
      "",
      "W razie pytań prosimy o kontakt.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 30,
  },
  {
    name: "Za krótki pobyt",
    subject: "Domki Sztabinki — odpowiedź na zapytanie [IMIE]",
    body: [
      "Dzień dobry,",
      "",
      "dziękujemy za zapytanie dotyczące terminu [TERMIN].",
      "",
      "Niestety w tym okresie minimalna długość pobytu jest dłuższa niż wskazana w zapytaniu. Prosimy o wybór dłuższego terminu pobytu albo kontakt telefoniczny w celu sprawdzenia dostępnych możliwości.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 40,
  },
  {
    name: "Pytanie o charakter pobytu",
    subject: "Domki Sztabinki — pytanie dotyczące pobytu [TERMIN]",
    body: [
      "Dzień dobry,",
      "",
      "dziękujemy za zapytanie dotyczące pobytu w terminie [TERMIN].",
      "",
      "Przed potwierdzeniem rezerwacji chcielibyśmy uprzejmie zapytać, czy celem pobytu jest spokojny wypoczynek, czy planowane jest spotkanie/impreza.",
      "",
      "Domki Sztabinki są miejscem przeznaczonym głównie do spokojnego wypoczynku rodzinnego nad jeziorem. Zależy nam na komforcie wszystkich gości oraz sąsiadów.",
      "",
      "Pozdrawiamy serdecznie",
      "Domki Sztabinki",
    ].join("\n"),
    sortOrder: 50,
  },
];

async function ensureDefaultTemplates() {
  const existingTemplates = await prisma.responseTemplate.findMany({
    where: {
      name: {
        in: defaultTemplates.map((template) => template.name),
      },
    },
    select: {
      name: true,
    },
  });

  const existingTemplateNames = new Set(
    existingTemplates.map((template) => template.name),
  );

  const missingTemplates = defaultTemplates.filter(
    (template) => !existingTemplateNames.has(template.name),
  );

  if (missingTemplates.length === 0) {
    return;
  }

  await prisma.responseTemplate.createMany({
    data: missingTemplates.map((template) => ({
      name: template.name,
      subject: template.subject,
      body: template.body,
      isActive: true,
      sortOrder: template.sortOrder,
    })),
  });
}

function formatDateTime(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZone: "Europe/Warsaw",
  }).format(date);
}

export default async function AdminResponseTemplatesPage() {
  await ensureDefaultTemplates();

  const templates = await prisma.responseTemplate.findMany({
    orderBy: [
      {
        sortOrder: "asc",
      },
      {
        createdAt: "asc",
      },
    ],
  });

  const activeTemplatesCount = templates.filter(
    (template) => template.isActive,
  ).length;

  return (
    <main className="space-y-8">
      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              Szablony
            </p>

            <h1 className="mt-2 text-3xl font-black tracking-tight text-slate-950">
              Szablony odpowiedzi
            </h1>

            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
              Tutaj przygotujesz gotowe odpowiedzi do klientów. W kolejnym kroku
              podłączymy je do zapytań, żeby zamiast pisać wiadomość ręcznie
              można było wybrać szablon odpowiedzi.
            </p>
          </div>

          <div className="grid gap-3 sm:grid-cols-2">
            <div className="rounded-2xl bg-slate-950 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-slate-300">
                Wszystkie
              </p>
              <p className="mt-1 text-3xl font-black">{templates.length}</p>
            </div>

            <div className="rounded-2xl bg-emerald-600 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-emerald-100">
                Aktywne
              </p>
              <p className="mt-1 text-3xl font-black">
                {activeTemplatesCount}
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">
          Dodaj nowy szablon
        </h2>

        <p className="mt-2 text-sm leading-6 text-slate-600">
          Możesz używać zmiennych:{" "}
          <strong>[IMIE]</strong>, <strong>[NAZWISKO]</strong>,{" "}
          <strong>[IMIE_NAZWISKO]</strong>, <strong>[TERMIN]</strong>,{" "}
          <strong>[DOMEK]</strong>, <strong>[LICZBA_NOCY]</strong>,{" "}
          <strong>[LICZBA_OSOB]</strong>, <strong>[KWOTA]</strong>.
        </p>

        <form action={createResponseTemplate} className="mt-6 grid gap-5">
          <div className="grid gap-5 lg:grid-cols-[1fr_10rem_10rem]">
            <label className="grid gap-2">
              <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                Nazwa
              </span>
              <input
                name="name"
                type="text"
                required
                placeholder="Np. Potwierdzenie dostępności"
                className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
              />
            </label>

            <label className="grid gap-2">
              <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                Kolejność
              </span>
              <input
                name="sortOrder"
                type="number"
                defaultValue={0}
                className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
              />
            </label>

            <label className="flex items-end gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <input
                name="isActive"
                type="checkbox"
                defaultChecked
                className="h-5 w-5"
              />
              <span className="text-sm font-black text-slate-700">
                Aktywny
              </span>
            </label>
          </div>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
              Temat e-maila
            </span>
            <input
              name="subject"
              type="text"
              required
              placeholder="Domki Sztabinki — odpowiedź na zapytanie [IMIE]"
              className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
            />
          </label>

          <label className="grid gap-2">
            <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
              Treść
            </span>
            <textarea
              name="body"
              required
              rows={10}
              placeholder={defaultTemplates[0].body}
              className="rounded-2xl border border-slate-300 px-4 py-3 text-sm leading-7 outline-none transition focus:border-slate-950"
            />
          </label>

          <div>
            <button
              type="submit"
              className="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-black text-white transition hover:bg-slate-800"
            >
              Dodaj szablon
            </button>
          </div>
        </form>
      </section>

      <section className="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div className="border-b border-slate-200 p-6">
          <h2 className="text-xl font-black text-slate-950">
            Lista szablonów
          </h2>

          <p className="mt-2 text-sm leading-6 text-slate-600">
            Edytuj gotowe odpowiedzi. Nieaktywne szablony nie będą później
            pokazywane przy wyborze odpowiedzi w zapytaniu.
          </p>
        </div>

        {templates.length === 0 ? (
          <div className="p-8 text-center">
            <h3 className="text-xl font-black text-slate-950">
              Brak szablonów
            </h3>
            <p className="mt-2 text-sm text-slate-600">
              Dodaj pierwszy szablon odpowiedzi.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-200">
            {templates.map((template) => (
              <article key={template.id} className="p-6">
                <form action={updateResponseTemplate} className="grid gap-5">
                  <input
                    type="hidden"
                    name="templateId"
                    value={template.id}
                  />

                  <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                      <div className="flex flex-wrap items-center gap-3">
                        <h3 className="text-xl font-black text-slate-950">
                          {template.name}
                        </h3>

                        {template.isActive ? (
                          <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800 ring-1 ring-emerald-200">
                            Aktywny
                          </span>
                        ) : (
                          <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">
                            Nieaktywny
                          </span>
                        )}
                      </div>

                      <p className="mt-2 text-sm text-slate-500">
                        Utworzono: {formatDateTime(template.createdAt)} ·
                        Zmieniono: {formatDateTime(template.updatedAt)}
                      </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                      <button
                        type="submit"
                        className="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
                      >
                        Zapisz zmiany
                      </button>
                    </div>
                  </div>

                  <div className="grid gap-5 lg:grid-cols-[1fr_10rem_10rem]">
                    <label className="grid gap-2">
                      <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                        Nazwa
                      </span>
                      <input
                        name="name"
                        type="text"
                        required
                        defaultValue={template.name}
                        className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
                      />
                    </label>

                    <label className="grid gap-2">
                      <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                        Kolejność
                      </span>
                      <input
                        name="sortOrder"
                        type="number"
                        defaultValue={template.sortOrder}
                        className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
                      />
                    </label>

                    <label className="flex items-end gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                      <input
                        name="isActive"
                        type="checkbox"
                        defaultChecked={template.isActive}
                        className="h-5 w-5"
                      />
                      <span className="text-sm font-black text-slate-700">
                        Aktywny
                      </span>
                    </label>
                  </div>

                  <label className="grid gap-2">
                    <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                      Temat e-maila
                    </span>
                    <input
                      name="subject"
                      type="text"
                      required
                      defaultValue={template.subject}
                      className="rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-slate-950"
                    />
                  </label>

                  <label className="grid gap-2">
                    <span className="text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                      Treść
                    </span>
                    <textarea
                      name="body"
                      required
                      rows={10}
                      defaultValue={template.body}
                      className="rounded-2xl border border-slate-300 px-4 py-3 text-sm leading-7 outline-none transition focus:border-slate-950"
                    />
                  </label>
                </form>

                <form action={deleteResponseTemplate} className="mt-4">
                  <input
                    type="hidden"
                    name="templateId"
                    value={template.id}
                  />

                  <button
                    type="submit"
                    className="rounded-xl bg-red-700 px-5 py-3 text-sm font-black text-white transition hover:bg-red-800"
                  >
                    Usuń szablon
                  </button>
                </form>
              </article>
            ))}
          </div>
        )}
      </section>
    </main>
  );
}