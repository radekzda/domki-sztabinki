import { updateInquiryStatus } from "@/actions/inquiries";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

function formatDate(date: Date) {
  return new Intl.DateTimeFormat("pl-PL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: "Europe/Warsaw",
  }).format(date);
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

function getStatusLabel(status: string) {
  if (status === "NEW") {
    return "Nowe";
  }

  if (status === "CONTACTED") {
    return "Po kontakcie";
  }

  if (status === "ARCHIVED") {
    return "Archiwalne";
  }

  return status;
}

function getStatusClassName(status: string) {
  if (status === "NEW") {
    return "bg-emerald-50 text-emerald-800 ring-emerald-200";
  }

  if (status === "CONTACTED") {
    return "bg-sky-50 text-sky-800 ring-sky-200";
  }

  if (status === "ARCHIVED") {
    return "bg-slate-100 text-slate-700 ring-slate-200";
  }

  return "bg-amber-50 text-amber-800 ring-amber-200";
}

function getActionButtonClassName(status: string) {
  if (status === "NEW") {
    return "rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white transition hover:bg-emerald-700";
  }

  if (status === "CONTACTED") {
    return "rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white transition hover:bg-sky-700";
  }

  if (status === "ARCHIVED") {
    return "rounded-xl bg-slate-700 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800";
  }

  return "rounded-xl bg-slate-950 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800";
}

export default async function AdminInquiriesPage() {
  const inquiries = await prisma.inquiry.findMany({
    orderBy: {
      createdAt: "desc",
    },
    take: 100,
    include: {
      cabin: {
        select: {
          name: true,
        },
      },
    },
  });

  const newInquiriesCount = inquiries.filter(
    (inquiry) => inquiry.status === "NEW"
  ).length;

  const contactedInquiriesCount = inquiries.filter(
    (inquiry) => inquiry.status === "CONTACTED"
  ).length;

  const archivedInquiriesCount = inquiries.filter(
    (inquiry) => inquiry.status === "ARCHIVED"
  ).length;

  return (
    <main className="space-y-8">
      <div className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              Zapytania
            </p>

            <h1 className="mt-2 text-3xl font-black tracking-tight text-slate-950">
              Zapytania ze strony publicznej
            </h1>

            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
              Tutaj trafiają wiadomości wysłane przez formularz kontaktowy na
              stronie głównej. Na tym etapie zapytanie nie tworzy jeszcze
              rezerwacji — służy tylko do kontaktu z gościem.
            </p>
          </div>

          <div className="grid gap-3 sm:grid-cols-3">
            <div className="rounded-2xl bg-slate-950 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-slate-300">
                Nowe
              </p>
              <p className="mt-1 text-3xl font-black">{newInquiriesCount}</p>
            </div>

            <div className="rounded-2xl bg-sky-600 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-sky-100">
                Po kontakcie
              </p>
              <p className="mt-1 text-3xl font-black">
                {contactedInquiriesCount}
              </p>
            </div>

            <div className="rounded-2xl bg-slate-200 px-5 py-4 text-slate-950">
              <p className="text-sm font-semibold text-slate-600">
                Archiwalne
              </p>
              <p className="mt-1 text-3xl font-black">
                {archivedInquiriesCount}
              </p>
            </div>
          </div>
        </div>
      </div>

      <section className="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div className="border-b border-slate-200 p-6">
          <h2 className="text-xl font-black text-slate-950">
            Ostatnie zapytania
          </h2>
          <p className="mt-2 text-sm text-slate-600">
            Pokazujemy maksymalnie 100 najnowszych zapytań. Status możesz
            zmienić bezpośrednio na karcie zapytania.
          </p>
        </div>

        {inquiries.length === 0 ? (
          <div className="p-8 text-center">
            <h3 className="text-xl font-black text-slate-950">
              Brak zapytań
            </h3>
            <p className="mt-2 text-sm text-slate-600">
              Gdy ktoś wyśle formularz ze strony publicznej, zapytanie pojawi
              się tutaj.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-200">
            {inquiries.map((inquiry) => {
              const selectedCabinName =
                inquiry.cabin?.name ||
                inquiry.cabinName ||
                "Dowolny / do ustalenia";

              return (
                <article key={inquiry.id} className="p-6">
                  <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                      <div className="flex flex-wrap items-center gap-3">
                        <h3 className="text-xl font-black text-slate-950">
                          {inquiry.fullName}
                        </h3>

                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 ${getStatusClassName(
                            inquiry.status
                          )}`}
                        >
                          {getStatusLabel(inquiry.status)}
                        </span>
                      </div>

                      <p className="mt-2 text-sm text-slate-500">
                        Wysłano: {formatDateTime(inquiry.createdAt)}
                      </p>
                    </div>

                    <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                      <span className="font-bold">Termin:</span>{" "}
                      {formatDate(inquiry.dateFrom)} –{" "}
                      {formatDate(inquiry.dateTo)}
                    </div>
                  </div>

                  <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-2xl bg-slate-50 p-4">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        Telefon
                      </p>
                      <a
                        href={`tel:${inquiry.phone.replace(/[^\d+]/g, "")}`}
                        className="mt-2 block text-base font-bold text-slate-950 hover:underline"
                      >
                        {inquiry.phone}
                      </a>
                    </div>

                    <div className="rounded-2xl bg-slate-50 p-4">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        E-mail
                      </p>
                      {inquiry.email ? (
                        <a
                          href={`mailto:${inquiry.email}`}
                          className="mt-2 block break-all text-base font-bold text-slate-950 hover:underline"
                        >
                          {inquiry.email}
                        </a>
                      ) : (
                        <p className="mt-2 text-base font-bold text-slate-500">
                          Nie podano
                        </p>
                      )}
                    </div>

                    <div className="rounded-2xl bg-slate-50 p-4">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        Domek
                      </p>
                      <p className="mt-2 text-base font-bold text-slate-950">
                        {selectedCabinName}
                      </p>
                    </div>

                    <div className="rounded-2xl bg-slate-50 p-4">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        Liczba osób
                      </p>
                      <p className="mt-2 text-base font-bold text-slate-950">
                        {inquiry.guests}
                      </p>
                    </div>
                  </div>

                  {inquiry.notes ? (
                    <div className="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        Wiadomość
                      </p>
                      <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                        {inquiry.notes}
                      </p>
                    </div>
                  ) : null}

                  <form
                    action={updateInquiryStatus}
                    className="mt-5 flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                  >
                    <input
                      type="hidden"
                      name="inquiryId"
                      value={inquiry.id}
                    />

                    {inquiry.status !== "CONTACTED" ? (
                      <button
                        type="submit"
                        name="status"
                        value="CONTACTED"
                        className={getActionButtonClassName("CONTACTED")}
                      >
                        Oznacz jako po kontakcie
                      </button>
                    ) : null}

                    {inquiry.status !== "NEW" ? (
                      <button
                        type="submit"
                        name="status"
                        value="NEW"
                        className={getActionButtonClassName("NEW")}
                      >
                        Przywróć jako nowe
                      </button>
                    ) : null}

                    {inquiry.status !== "ARCHIVED" ? (
                      <button
                        type="submit"
                        name="status"
                        value="ARCHIVED"
                        className={getActionButtonClassName("ARCHIVED")}
                      >
                        Archiwizuj
                      </button>
                    ) : null}
                  </form>
                </article>
              );
            })}
          </div>
        )}
      </section>
    </main>
  );
}