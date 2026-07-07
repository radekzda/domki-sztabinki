import Link from "next/link";

import { updateInquiryStatus } from "@/actions/inquiries";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

type InquiryStatusFilter = "NEW" | "CONTACTED" | "ARCHIVED";

type AdminInquiriesPageProps = {
  searchParams?: Promise<{
    status?: string | string[];
    q?: string | string[];
  }>;
};

const statusFilters: {
  label: string;
  status: InquiryStatusFilter | null;
}[] = [
  {
    label: "Wszystkie",
    status: null,
  },
  {
    label: "Nowe",
    status: "NEW",
  },
  {
    label: "Po kontakcie",
    status: "CONTACTED",
  },
  {
    label: "Archiwalne",
    status: "ARCHIVED",
  },
];

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

function getSingleSearchParam(value: string | string[] | undefined) {
  if (Array.isArray(value)) {
    return value[0] || "";
  }

  return value || "";
}

function getStatusFilter(value: string | string[] | undefined) {
  const status = getSingleSearchParam(value);

  if (status === "NEW") {
    return "NEW";
  }

  if (status === "CONTACTED") {
    return "CONTACTED";
  }

  if (status === "ARCHIVED") {
    return "ARCHIVED";
  }

  return null;
}

function normalizeSearchQuery(value: string | string[] | undefined) {
  return getSingleSearchParam(value).trim();
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

function getFilterLinkClassName(isActive: boolean) {
  if (isActive) {
    return "rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-slate-800";
  }

  return "rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 hover:text-slate-950";
}

function getFilterHref(
  status: InquiryStatusFilter | null,
  searchQuery: string
) {
  const params = new URLSearchParams();

  if (status) {
    params.set("status", status);
  }

  if (searchQuery) {
    params.set("q", searchQuery);
  }

  const queryString = params.toString();

  if (!queryString) {
    return "/admin/zapytania";
  }

  return `/admin/zapytania?${queryString}`;
}

function getClearSearchHref(activeStatus: InquiryStatusFilter | null) {
  if (!activeStatus) {
    return "/admin/zapytania";
  }

  return `/admin/zapytania?status=${activeStatus}`;
}

function getEmptyStateText(
  activeStatus: InquiryStatusFilter | null,
  searchQuery: string
) {
  if (searchQuery) {
    return `Brak zapytań pasujących do wyszukiwania: "${searchQuery}".`;
  }

  if (activeStatus === "NEW") {
    return "Nie ma obecnie nowych zapytań.";
  }

  if (activeStatus === "CONTACTED") {
    return "Nie ma obecnie zapytań oznaczonych jako po kontakcie.";
  }

  if (activeStatus === "ARCHIVED") {
    return "Nie ma obecnie zapytań archiwalnych.";
  }

  return "Gdy ktoś wyśle formularz ze strony publicznej, zapytanie pojawi się tutaj.";
}

export default async function AdminInquiriesPage({
  searchParams,
}: AdminInquiriesPageProps) {
  const resolvedSearchParams = searchParams ? await searchParams : {};
  const activeStatus = getStatusFilter(resolvedSearchParams.status);
  const searchQuery = normalizeSearchQuery(resolvedSearchParams.q);

  const inquiryWhere = {
    ...(activeStatus
      ? {
          status: activeStatus,
        }
      : {}),
    ...(searchQuery
      ? {
          OR: [
            {
              fullName: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              phone: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              email: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              cabinName: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              notes: {
                contains: searchQuery,
                mode: "insensitive" as const,
              },
            },
            {
              cabin: {
                is: {
                  name: {
                    contains: searchQuery,
                    mode: "insensitive" as const,
                  },
                },
              },
            },
          ],
        }
      : {}),
  };

  const [
    inquiries,
    totalInquiriesCount,
    newInquiriesCount,
    contactedInquiriesCount,
    archivedInquiriesCount,
  ] = await Promise.all([
    prisma.inquiry.findMany({
      where: inquiryWhere,
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
    }),
    prisma.inquiry.count(),
    prisma.inquiry.count({
      where: {
        status: "NEW",
      },
    }),
    prisma.inquiry.count({
      where: {
        status: "CONTACTED",
      },
    }),
    prisma.inquiry.count({
      where: {
        status: "ARCHIVED",
      },
    }),
  ]);

  const activeFilterLabel =
    statusFilters.find((filter) => filter.status === activeStatus)?.label ||
    "Wszystkie";

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

          <div className="grid gap-3 sm:grid-cols-4">
            <div className="rounded-2xl bg-slate-950 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-slate-300">
                Wszystkie
              </p>
              <p className="mt-1 text-3xl font-black">
                {totalInquiriesCount}
              </p>
            </div>

            <div className="rounded-2xl bg-emerald-600 px-5 py-4 text-white">
              <p className="text-sm font-semibold text-emerald-100">
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
          <div className="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
              <h2 className="text-xl font-black text-slate-950">
                Ostatnie zapytania
              </h2>
              <p className="mt-2 text-sm text-slate-600">
                Pokazujemy maksymalnie 100 najnowszych zapytań. Aktualny filtr:{" "}
                <strong>{activeFilterLabel}</strong>
                {searchQuery ? (
                  <>
                    {" "}
                    i wyszukiwanie: <strong>{searchQuery}</strong>
                  </>
                ) : null}
                .
              </p>
            </div>

            <div className="flex flex-wrap gap-3">
              {statusFilters.map((filter) => {
                const isActive = filter.status === activeStatus;

                return (
                  <Link
                    key={filter.label}
                    href={getFilterHref(filter.status, searchQuery)}
                    className={getFilterLinkClassName(isActive)}
                  >
                    {filter.label}
                  </Link>
                );
              })}
            </div>
          </div>

          <form
            action="/admin/zapytania"
            method="get"
            className="mt-6 flex flex-col gap-3 rounded-3xl bg-slate-50 p-4 lg:flex-row lg:items-center"
          >
            {activeStatus ? (
              <input type="hidden" name="status" value={activeStatus} />
            ) : null}

            <label className="flex-1">
              <span className="sr-only">Szukaj zapytań</span>
              <input
                type="search"
                name="q"
                defaultValue={searchQuery}
                placeholder="Szukaj po imieniu, telefonie, e-mailu, domku albo wiadomości"
                className="w-full rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-slate-950"
              />
            </label>

            <div className="flex flex-col gap-3 sm:flex-row">
              <button
                type="submit"
                className="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white transition hover:bg-slate-800"
              >
                Szukaj
              </button>

              {searchQuery ? (
                <Link
                  href={getClearSearchHref(activeStatus)}
                  className="rounded-2xl border border-slate-300 bg-white px-6 py-3 text-center text-sm font-black text-slate-700 transition hover:bg-slate-100 hover:text-slate-950"
                >
                  Wyczyść
                </Link>
              ) : null}
            </div>
          </form>
        </div>

        {inquiries.length === 0 ? (
          <div className="p-8 text-center">
            <h3 className="text-xl font-black text-slate-950">
              Brak zapytań
            </h3>
            <p className="mt-2 text-sm text-slate-600">
              {getEmptyStateText(activeStatus, searchQuery)}
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

                  <div className="mt-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div className="flex flex-wrap gap-3">
                      <Link
                        href={`/admin/zapytania/${inquiry.id}`}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-black text-slate-800 transition hover:bg-slate-100"
                      >
                        Szczegóły
                      </Link>
                    </div>

                    <form
                      action={updateInquiryStatus}
                      className="flex flex-wrap gap-3"
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
                  </div>
                </article>
              );
            })}
          </div>
        )}
      </section>
    </main>
  );
}