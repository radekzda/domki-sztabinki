import Link from "next/link";
import { notFound } from "next/navigation";

import { updateInquiryStatus } from "@/actions/inquiries";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

type AdminInquiryDetailsPageProps = {
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

function formatDateForInput(date: Date) {
  return date.toISOString().slice(0, 10);
}

function getNightsCount(dateFrom: Date, dateTo: Date) {
  const millisecondsPerDay = 1000 * 60 * 60 * 24;
  const difference = dateTo.getTime() - dateFrom.getTime();

  return Math.max(1, Math.round(difference / millisecondsPerDay));
}

function splitFullName(fullName: string) {
  const cleanedFullName = fullName.trim().replace(/\s+/g, " ");

  if (!cleanedFullName) {
    return {
      firstName: "",
      lastName: "",
    };
  }

  const parts = cleanedFullName.split(" ");

  if (parts.length === 1) {
    return {
      firstName: parts[0],
      lastName: "",
    };
  }

  return {
    firstName: parts[0],
    lastName: parts.slice(1).join(" "),
  };
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

function getStatusClassName(status: string) {
  if (status === "NEW") {
    return "bg-emerald-50 text-emerald-800 ring-emerald-200";
  }

  if (status === "APPROVED" || status === "CONTACTED") {
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

  if (status === "APPROVED" || status === "CONTACTED") {
    return "rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white transition hover:bg-sky-700";
  }

  if (status === "ARCHIVED") {
    return "rounded-xl bg-slate-700 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800";
  }

  return "rounded-xl bg-slate-950 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-800";
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

function getCreateReservationHref({
  inquiryId,
  cabinId,
  firstName,
  lastName,
  email,
  phone,
  dateFrom,
  dateTo,
  adults,
  children,
  street,
  postalCode,
  city,
  country,
  notes,
}: {
  inquiryId: string;
  cabinId: string | null;
  firstName: string;
  lastName: string;
  email: string | null;
  phone: string;
  dateFrom: Date;
  dateTo: Date;
  adults: number;
  children: number;
  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;
  notes: string | null;
}) {
  const params = new URLSearchParams();

  params.set("inquiryId", inquiryId);
  params.set("firstName", firstName);
  params.set("lastName", lastName);
  params.set("phone", phone);
  params.set("startDate", formatDateForInput(dateFrom));
  params.set("endDate", formatDateForInput(dateTo));
  params.set("adults", String(adults));
  params.set("children", String(children));
  params.set("guests", String(adults + children));
  params.set("source", "WEBSITE");

  if (cabinId) {
    params.set("cabinId", cabinId);
  }

  if (email) {
    params.set("email", email);
  }

  if (street) {
    params.set("street", street);
  }

  if (postalCode) {
    params.set("postalCode", postalCode);
  }

  if (city) {
    params.set("city", city);
  }

  if (country) {
    params.set("country", country);
  }

  if (notes) {
    params.set("notes", notes);
  }

  return `/admin/rezerwacje/nowa?${params.toString()}`;
}

export default async function AdminInquiryDetailsPage({
  params,
}: AdminInquiryDetailsPageProps) {
  const resolvedParams = await params;

  const inquiry = await prisma.inquiry.findUnique({
    where: {
      id: resolvedParams.id,
    },
    include: {
      cabin: {
        select: {
          id: true,
          name: true,
          maxGuests: true,
          bedrooms: true,
          bathrooms: true,
          pricePerNight: true,
        },
      },
    },
  });

  if (!inquiry) {
    notFound();
  }

  const fallbackNameParts = splitFullName(inquiry.fullName);
  const firstName = inquiry.firstName || fallbackNameParts.firstName;
  const lastName = inquiry.lastName || fallbackNameParts.lastName;
  const selectedCabinName =
    inquiry.cabin?.name || inquiry.cabinName || "Dowolny / do ustalenia";
  const nights = getNightsCount(inquiry.dateFrom, inquiry.dateTo);
  const createReservationHref = getCreateReservationHref({
    inquiryId: inquiry.id,
    cabinId: inquiry.cabinId,
    firstName,
    lastName,
    email: inquiry.email,
    phone: inquiry.phone,
    dateFrom: inquiry.dateFrom,
    dateTo: inquiry.dateTo,
    adults: inquiry.adults,
    children: inquiry.children,
    street: inquiry.street,
    postalCode: inquiry.postalCode,
    city: inquiry.city,
    country: inquiry.country,
    notes: inquiry.notes,
  });

  return (
    <main className="space-y-8">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <Link
            href="/admin/zapytania"
            className="inline-flex rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
          >
            Wróć do listy zapytań
          </Link>

          <p className="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
            Szczegóły zapytania
          </p>

          <h1 className="mt-2 text-3xl font-black tracking-tight text-slate-950">
            {inquiry.fullName}
          </h1>
        </div>

        <span
          className={`inline-flex w-fit rounded-full px-4 py-2 text-sm font-black ring-1 ${getStatusClassName(
            inquiry.status,
          )}`}
        >
          {getStatusLabel(inquiry.status)}
        </span>
      </div>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <h2 className="text-xl font-black text-slate-950">
              Dane kontaktowe
            </h2>
            <p className="mt-2 text-sm text-slate-600">
              Dane podane przez gościa w formularzu publicznym.
            </p>
          </div>

          <p className="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Wysłano: <strong>{formatDateTime(inquiry.createdAt)}</strong>
          </p>
        </div>

        <div className="mt-6 grid gap-4 md:grid-cols-3">
          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Imię
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {firstName || "Nie podano"}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Nazwisko
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {lastName || "Nie podano"}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Telefon
            </p>
            <a
              href={getPhoneHref(inquiry.phone)}
              className="mt-2 block text-lg font-black text-slate-950 hover:underline"
            >
              {inquiry.phone}
            </a>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5 md:col-span-3">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              E-mail
            </p>
            {inquiry.email ? (
              <a
                href={`mailto:${inquiry.email}`}
                className="mt-2 block break-all text-lg font-black text-slate-950 hover:underline"
              >
                {inquiry.email}
              </a>
            ) : (
              <p className="mt-2 text-lg font-black text-slate-500">
                Nie podano
              </p>
            )}
          </div>
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">Pobyt</h2>
        <p className="mt-2 text-sm text-slate-600">
          Termin i podstawowe informacje z zapytania.
        </p>

        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Przyjazd
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {formatDate(inquiry.dateFrom)}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Wyjazd
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {formatDate(inquiry.dateTo)}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Liczba nocy
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">{nights}</p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Razem osób
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.guests}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Dorośli
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.adults}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Dzieci
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.children}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5 md:col-span-2">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Źródło zapytania
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.source}
            </p>
          </div>
        </div>

        <div className="mt-4 rounded-2xl bg-slate-50 p-5">
          <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
            Wybrany domek
          </p>
          <p className="mt-2 text-lg font-black text-slate-950">
            {selectedCabinName}
          </p>

          {inquiry.cabin ? (
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Domek w bazie: do {inquiry.cabin.maxGuests} osób,{" "}
              {inquiry.cabin.bedrooms} sypialnie, {inquiry.cabin.bathrooms}{" "}
              łazienka, cena bazowa {String(inquiry.cabin.pricePerNight)} PLN
              za dobę.
            </p>
          ) : (
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Gość nie wybrał konkretnego aktywnego domku albo domek został
              później usunięty z aktywnej listy.
            </p>
          )}
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">Adres</h2>

        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-2xl bg-slate-50 p-5 xl:col-span-2">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Ulica i numer
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.street || "Nie podano"}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Kod pocztowy
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.postalCode || "Nie podano"}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Miasto
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.city || "Nie podano"}
            </p>
          </div>

          <div className="rounded-2xl bg-slate-50 p-5 xl:col-span-4">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Kraj
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {inquiry.country || "Nie podano"}
            </p>
          </div>
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">
          Wiadomość od gościa
        </h2>

        {inquiry.notes ? (
          <p className="mt-4 whitespace-pre-wrap rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-7 text-slate-700">
            {inquiry.notes}
          </p>
        ) : (
          <p className="mt-4 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">
            Gość nie dodał dodatkowej wiadomości.
          </p>
        )}
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">Dalsza obsługa</h2>
        <p className="mt-2 text-sm text-slate-600">
          Możesz przejść do formularza nowej rezerwacji z danymi tego zapytania.
          Rezerwacja nie zostanie utworzona automatycznie — najpierw sprawdzisz
          i zatwierdzisz formularz. Po zapisaniu rezerwacji zapytanie zostanie
          oznaczone jako zatwierdzone.
        </p>

        <div className="mt-5 flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <Link
            href={createReservationHref}
            className="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
          >
            Utwórz rezerwację
          </Link>

          <Link
            href="/admin/rezerwacje"
            className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 transition hover:bg-slate-100"
          >
            Przejdź do rezerwacji
          </Link>
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 className="text-xl font-black text-slate-950">
          Status zapytania
        </h2>
        <p className="mt-2 text-sm text-slate-600">
          Zmień status zapytania. To nadal nie tworzy rezerwacji.
        </p>

        <form
          action={updateInquiryStatus}
          className="mt-5 flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4"
        >
          <input type="hidden" name="inquiryId" value={inquiry.id} />

          {inquiry.status !== "APPROVED" && inquiry.status !== "CONTACTED" ? (
            <button
              type="submit"
              name="status"
              value="APPROVED"
              className={getActionButtonClassName("APPROVED")}
            >
              Oznacz jako zatwierdzone
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
      </section>
    </main>
  );
}