import Link from "next/link";
import { notFound } from "next/navigation";

import { deleteInquiry } from "@/actions/delete-inquiry";
import { prisma } from "@/lib/prisma";

type DeleteInquiryPageProps = {
  params: Promise<{
    id: string;
  }>;
};

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

  if (status === "APPROVED" || status === "CONTACTED") {
    return "Zatwierdzone";
  }

  if (status === "ARCHIVED") {
    return "Archiwalne";
  }

  return status;
}

export default async function DeleteInquiryPage({
  params,
}: DeleteInquiryPageProps) {
  const resolvedParams = await params;

  const inquiry = await prisma.inquiry.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      cabin: {
        select: {
          name: true,
        },
      },
    },
  });

  if (!inquiry) {
    notFound();
  }

  const selectedCabinName =
    inquiry.cabin?.name || inquiry.cabinName || "Dowolny / do ustalenia";

  return (
    <div className="max-w-3xl space-y-8">
      <div>
        <Link
          href={`/admin/zapytania/${inquiry.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów zapytania
        </Link>

        <h1 className="mt-3 text-3xl font-bold text-red-700">
          Usuń zapytanie
        </h1>

        <p className="mt-2 text-zinc-500">
          Potwierdź usunięcie zapytania ze strony WWW. Tej operacji nie można
          cofnąć.
        </p>
      </div>

      <section className="rounded-xl border border-red-200 bg-red-50 p-5">
        <h2 className="text-xl font-semibold text-red-900">
          Czy na pewno chcesz usunąć to zapytanie?
        </h2>

        <p className="mt-2 text-sm leading-6 text-red-800">
          Zapytanie zostanie trwale usunięte z listy zapytań. Ta operacja nie
          usuwa rezerwacji ani gości. Jeżeli z tego zapytania została już
          utworzona rezerwacja, rezerwacja zostanie w systemie.
        </p>
      </section>

      <section className="rounded-xl border bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Dane zapytania</h2>

        <div className="mt-5 space-y-4">
          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Gość</span>
            <span className="text-right font-semibold">
              {inquiry.fullName}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Telefon</span>
            <span className="text-right font-semibold">
              {inquiry.phone}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">E-mail</span>
            <span className="text-right font-semibold">
              {inquiry.email || "—"}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Domek</span>
            <span className="text-right font-semibold">
              {selectedCabinName}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Termin</span>
            <span className="text-right font-semibold">
              {formatDate(inquiry.dateFrom)} – {formatDate(inquiry.dateTo)}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Liczba osób</span>
            <span className="text-right font-semibold">
              {inquiry.guests}
            </span>
          </div>

          <div className="flex justify-between gap-4 border-b pb-3">
            <span className="text-zinc-500">Status</span>
            <span className="text-right font-semibold">
              {getStatusLabel(inquiry.status)}
            </span>
          </div>

          <div className="flex justify-between gap-4">
            <span className="text-zinc-500">Wysłano</span>
            <span className="text-right font-semibold">
              {formatDateTime(inquiry.createdAt)}
            </span>
          </div>
        </div>
      </section>

      {inquiry.notes ? (
        <section className="rounded-xl border bg-white p-5 shadow-sm">
          <h2 className="text-xl font-semibold">Wiadomość</h2>

          <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-zinc-700">
            {inquiry.notes}
          </p>
        </section>
      ) : null}

      <div className="flex flex-wrap gap-3">
        <form action={deleteInquiry}>
          <input type="hidden" name="inquiryId" value={inquiry.id} />

          <button
            type="submit"
            className="rounded-lg bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800"
          >
            Tak, usuń zapytanie
          </button>
        </form>

        <Link
          href={`/admin/zapytania/${inquiry.id}`}
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Anuluj
        </Link>

        <Link
          href="/admin/zapytania"
          className="rounded-lg border px-5 py-3 text-sm font-semibold hover:bg-zinc-50"
        >
          Lista zapytań
        </Link>
      </div>
    </div>
  );
}