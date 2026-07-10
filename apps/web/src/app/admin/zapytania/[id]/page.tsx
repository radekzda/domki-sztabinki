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

type InquiryCabin = {
  id: string;
  name: string;
  priceOneNight: number;
  priceTwoNights: number;
  priceThreeNights: number;
  priceFourNights: number;
  priceFiveNights: number;
  priceSixNights: number;
  priceSevenPlusNights: number;
};

type TemplateVariables = {
  firstName: string;
  lastName: string;
  fullName: string;
  term: string;
  cabinName: string;
  nights: number;
  guests: number;
  phone: string;
  email: string;
  address: string;
  pricePerNight: string;
  totalPrice: string;
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

function formatNights(nights: number) {
  if (nights === 1) {
    return "1 noc";
  }

  if (nights >= 2 && nights <= 4) {
    return `${nights} noce`;
  }

  return `${nights} nocy`;
}

function formatPeople(count: number) {
  if (count === 1) {
    return "1 osoba";
  }

  if (count >= 2 && count <= 4) {
    return `${count} osoby`;
  }

  return `${count} osób`;
}

function formatMoney(amount: number | null) {
  if (amount === null) {
    return "[KWOTA]";
  }

  return new Intl.NumberFormat("pl-PL", {
    maximumFractionDigits: 0,
  }).format(amount);
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
    return "Obsłużone";
  }

  if (status === "ARCHIVED") {
    return "Archiwalne";
  }

  return status;
}

function getStatusDescription(status: string) {
  if (status === "NEW") {
    return "Zapytanie wymaga sprawdzenia i kontaktu z gościem.";
  }

  if (status === "APPROVED" || status === "CONTACTED") {
    return "Zapytanie zostało obsłużone. Możesz utworzyć rezerwację, zostawić w historii albo archiwizować, jeśli nie zakończyło się rezerwacją.";
  }

  if (status === "ARCHIVED") {
    return "Zapytanie jest odłożone do archiwum.";
  }

  return "Status zapytania wymaga sprawdzenia.";
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

function getStatusPanelClassName(status: string) {
  if (status === "NEW") {
    return "rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-950";
  }

  if (status === "APPROVED" || status === "CONTACTED") {
    return "rounded-3xl border border-sky-200 bg-sky-50 p-5 text-sky-950";
  }

  if (status === "ARCHIVED") {
    return "rounded-3xl border border-slate-200 bg-slate-50 p-5 text-slate-950";
  }

  return "rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-950";
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

function getSourceLabel(source: string | null) {
  if (source === "WWW" || source === "WEBSITE") {
    return "Strona WWW";
  }

  if (source === "PHONE") {
    return "Telefon";
  }

  if (source === "BOOKING") {
    return "Booking";
  }

  if (source === "AIRBNB") {
    return "Airbnb";
  }

  if (!source) {
    return "Nie podano";
  }

  return source;
}

function getAddressText({
  street,
  postalCode,
  city,
  country,
}: {
  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;
}) {
  const firstLine = street || "";
  const secondLine = [postalCode, city].filter(Boolean).join(" ");
  const lines = [firstLine, secondLine, country || ""].filter(Boolean);

  if (lines.length === 0) {
    return "Nie podano adresu.";
  }

  return lines.join(", ");
}

function getPricePerNightForNights(cabin: InquiryCabin | null, nights: number) {
  if (!cabin) {
    return null;
  }

  if (nights === 1) {
    return cabin.priceOneNight;
  }

  if (nights === 2) {
    return cabin.priceTwoNights;
  }

  if (nights === 3) {
    return cabin.priceThreeNights;
  }

  if (nights === 4) {
    return cabin.priceFourNights;
  }

  if (nights === 5) {
    return cabin.priceFiveNights;
  }

  if (nights === 6) {
    return cabin.priceSixNights;
  }

  return cabin.priceSevenPlusNights;
}

function replaceTemplateVariables(content: string, variables: TemplateVariables) {
  const replacements: Record<string, string> = {
    "[IMIE]": variables.firstName || variables.fullName,
    "[NAZWISKO]": variables.lastName,
    "[IMIE_NAZWISKO]": variables.fullName,
    "[TERMIN]": variables.term,
    "[DOMEK]": variables.cabinName,
    "[LICZBA_NOCY]": String(variables.nights),
    "[LICZBA_OSOB]": String(variables.guests),
    "[KWOTA]": variables.totalPrice,
    "[CENA_ZA_NOC]": variables.pricePerNight,
    "[TELEFON]": variables.phone,
    "[EMAIL]": variables.email,
    "[ADRES]": variables.address,
  };

  return Object.entries(replacements).reduce(
    (updatedContent, [placeholder, value]) =>
      updatedContent.split(placeholder).join(value),
    content,
  );
}

function getMailtoHref({
  email,
  subject,
  body,
}: {
  email: string;
  subject: string;
  body: string;
}) {
  return `mailto:${email}?subject=${encodeURIComponent(
    subject,
  )}&body=${encodeURIComponent(body)}`;
}

function getTemplatePreview(body: string) {
  const cleanedBody = body.replace(/\s+/g, " ").trim();

  if (cleanedBody.length <= 190) {
    return cleanedBody;
  }

  return `${cleanedBody.slice(0, 190)}...`;
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
  params.set("source", "WWW");

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

  const [inquiry, responseTemplates] = await Promise.all([
    prisma.inquiry.findUnique({
      where: {
        id: resolvedParams.id,
      },
      include: {
        cabin: {
          select: {
            id: true,
            name: true,
            priceOneNight: true,
            priceTwoNights: true,
            priceThreeNights: true,
            priceFourNights: true,
            priceFiveNights: true,
            priceSixNights: true,
            priceSevenPlusNights: true,
          },
        },
      },
    }),
    prisma.responseTemplate.findMany({
      where: {
        isActive: true,
      },
      orderBy: [
        {
          sortOrder: "asc",
        },
        {
          createdAt: "asc",
        },
      ],
    }),
  ]);

  if (!inquiry) {
    notFound();
  }

  const fallbackNameParts = splitFullName(inquiry.fullName);
  const firstName = inquiry.firstName || fallbackNameParts.firstName;
  const lastName = inquiry.lastName || fallbackNameParts.lastName;
  const selectedCabinName =
    inquiry.cabin?.name || inquiry.cabinName || "Dowolny / do ustalenia";
  const nights = getNightsCount(inquiry.dateFrom, inquiry.dateTo);
  const sourceLabel = getSourceLabel(inquiry.source);
  const addressText = getAddressText({
    street: inquiry.street,
    postalCode: inquiry.postalCode,
    city: inquiry.city,
    country: inquiry.country,
  });
  const pricePerNight = getPricePerNightForNights(inquiry.cabin, nights);
  const totalPrice = pricePerNight === null ? null : pricePerNight * nights;

  const templateVariables: TemplateVariables = {
    firstName,
    lastName,
    fullName: inquiry.fullName,
    term: `${formatDate(inquiry.dateFrom)} – ${formatDate(inquiry.dateTo)}`,
    cabinName: selectedCabinName,
    nights,
    guests: inquiry.guests,
    phone: inquiry.phone,
    email: inquiry.email || "",
    address: addressText,
    pricePerNight: formatMoney(pricePerNight),
    totalPrice: formatMoney(totalPrice),
  };

  const templatesWithMailto = responseTemplates.map((template) => {
    const subject = replaceTemplateVariables(
      template.subject,
      templateVariables,
    );
    const body = replaceTemplateVariables(template.body, templateVariables);

    return {
      id: template.id,
      name: template.name,
      subject,
      body,
      preview: getTemplatePreview(body),
      mailtoHref: inquiry.email
        ? getMailtoHref({
            email: inquiry.email,
            subject,
            body,
          })
        : null,
    };
  });

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
      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
          <div className="min-w-0">
            <Link
              href="/admin/zapytania"
              className="inline-flex rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
            >
              Wróć do listy zapytań
            </Link>

            <p className="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              Szczegóły zapytania
            </p>

            <div className="mt-3 flex flex-col gap-3 lg:flex-row lg:items-center">
              <h1 className="min-w-0 text-3xl font-black tracking-tight text-slate-950">
                {inquiry.fullName}
              </h1>

              <div className="flex flex-wrap gap-2">
                <span
                  className={`inline-flex w-fit rounded-full px-4 py-2 text-sm font-black ring-1 ${getStatusClassName(
                    inquiry.status,
                  )}`}
                >
                  {getStatusLabel(inquiry.status)}
                </span>

                <span className="inline-flex w-fit rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-700 ring-1 ring-slate-200">
                  Źródło: {sourceLabel}
                </span>
              </div>
            </div>

            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
              Zapytanie wysłane {formatDateTime(inquiry.createdAt)}. Sprawdź
              termin, domek i dane kontaktowe, a następnie wybierz gotowy
              szablon odpowiedzi albo utwórz rezerwację z tego zapytania.
            </p>
          </div>

          <div className="grid gap-3 sm:grid-cols-2 xl:min-w-[32rem]">
            <a
              href={getPhoneHref(inquiry.phone)}
              className="rounded-2xl bg-slate-950 px-5 py-4 text-center text-sm font-black text-white transition hover:bg-slate-800"
            >
              Zadzwoń
            </a>

            {inquiry.email ? (
              <a
                href="#wybierz-odpowiedz"
                className="rounded-2xl border border-slate-300 bg-white px-5 py-4 text-center text-sm font-black text-slate-800 transition hover:bg-slate-100"
              >
                Wybierz odpowiedź
              </a>
            ) : (
              <div className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-center text-sm font-black text-slate-400">
                Brak e-maila
              </div>
            )}

            <Link
              href={createReservationHref}
              className="rounded-2xl bg-emerald-700 px-5 py-4 text-center text-sm font-black text-white transition hover:bg-emerald-800"
            >
              Utwórz rezerwację
            </Link>

            <Link
              href={`/admin/zapytania/${inquiry.id}/usun`}
              className="rounded-2xl bg-red-700 px-5 py-4 text-center text-sm font-black text-white transition hover:bg-red-800"
            >
              Usuń zapytanie
            </Link>

            <form
              action={updateInquiryStatus}
              className="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2"
            >
              <input type="hidden" name="inquiryId" value={inquiry.id} />

              {inquiry.status !== "APPROVED" &&
              inquiry.status !== "CONTACTED" ? (
                <button
                  type="submit"
                  name="status"
                  value="APPROVED"
                  className={getActionButtonClassName("APPROVED")}
                >
                  Oznacz jako obsłużone
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
        </div>

        <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-3xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Termin
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {formatDate(inquiry.dateFrom)} – {formatDate(inquiry.dateTo)}
            </p>
            <p className="mt-1 text-sm text-slate-600">
              {formatNights(nights)}
            </p>
          </div>

          <div className="rounded-3xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Domek
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {selectedCabinName}
            </p>
            <p className="mt-1 text-sm text-slate-600">
              {inquiry.cabin ? "wybrany konkretny domek" : "do dopasowania"}
            </p>
          </div>

          <div className="rounded-3xl bg-slate-50 p-5">
            <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
              Osoby
            </p>
            <p className="mt-2 text-lg font-black text-slate-950">
              {formatPeople(inquiry.guests)}
            </p>
            <p className="mt-1 text-sm text-slate-600">
              {inquiry.adults} dorosłych, {inquiry.children} dzieci
            </p>
          </div>

          <div className={getStatusPanelClassName(inquiry.status)}>
            <p className="text-xs font-black uppercase tracking-[0.16em] opacity-80">
              Status
            </p>
            <p className="mt-2 text-lg font-black">
              {getStatusLabel(inquiry.status)}
            </p>
            <p className="mt-1 text-sm leading-6">
              {getStatusDescription(inquiry.status)}
            </p>
          </div>
        </div>
      </section>

      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
          Obsługa zapytania
        </p>

        <h2 className="mt-2 text-2xl font-black tracking-tight text-slate-950">
          Co dalej?
        </h2>

        <div className="mt-6 grid gap-4 lg:grid-cols-3">
          <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p className="text-sm font-black text-slate-950">
              1. Odpowiedz klientowi
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Wybierz gotowy szablon odpowiedzi. System podstawi termin, domek,
              liczbę osób oraz wyliczoną kwotę pobytu.
            </p>
          </div>

          <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p className="text-sm font-black text-slate-950">
              2. Oznacz jako obsłużone
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Po wysłaniu wiadomości możesz oznaczyć zapytanie jako obsłużone,
              nawet jeśli klient jeszcze nie potwierdził rezerwacji.
            </p>
          </div>

          <div className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
            <p className="text-sm font-black text-slate-950">
              3. Utwórz rezerwację albo archiwizuj
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Jeśli klient potwierdzi pobyt, utwórz rezerwację. Jeśli nie,
              możesz zostawić zapytanie jako obsłużone albo przenieść je do
              archiwum.
            </p>
          </div>
        </div>
      </section>

      <section
        id="wybierz-odpowiedz"
        className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
      >
        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <p className="text-sm font-bold uppercase tracking-[0.2em] text-slate-500">
              Odpowiedź do gościa
            </p>

            <h2 className="mt-2 text-2xl font-black tracking-tight text-slate-950">
              Wybierz odpowiedź
            </h2>

            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
              Wybierz jeden z aktywnych szablonów. System automatycznie podstawi
              dane z zapytania: termin, domek, liczbę nocy, liczbę osób oraz
              kwotę pobytu.
            </p>

            {pricePerNight !== null && totalPrice !== null ? (
              <p className="mt-3 max-w-3xl rounded-2xl bg-emerald-50 p-4 text-sm font-bold leading-6 text-emerald-900 ring-1 ring-emerald-200">
                Wyliczona kwota: {formatMoney(totalPrice)} zł ={" "}
                {formatNights(nights)} × {formatMoney(pricePerNight)} zł za
                noc.
              </p>
            ) : (
              <p className="mt-3 max-w-3xl rounded-2xl bg-amber-50 p-4 text-sm font-bold leading-6 text-amber-900 ring-1 ring-amber-200">
                Nie można automatycznie wyliczyć kwoty, ponieważ zapytanie nie
                ma wybranego konkretnego domku. W szablonie pozostanie [KWOTA].
              </p>
            )}
          </div>

          <Link
            href="/admin/szablony"
            className="rounded-2xl border border-slate-300 bg-white px-5 py-3 text-center text-sm font-black text-slate-800 transition hover:bg-slate-100"
          >
            Edytuj szablony
          </Link>
        </div>

        {!inquiry.email ? (
          <div className="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
            To zapytanie nie ma adresu e-mail. Możesz skontaktować się z
            gościem telefonicznie albo najpierw uzupełnić e-mail w rezerwacji.
          </div>
        ) : templatesWithMailto.length === 0 ? (
          <div className="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
            Brak aktywnych szablonów odpowiedzi. Wejdź w zakładkę „Szablony” i
            dodaj albo aktywuj przynajmniej jeden szablon.
          </div>
        ) : (
          <div className="mt-6 grid gap-4 lg:grid-cols-2">
            {templatesWithMailto.map((template) => (
              <article
                key={template.id}
                className="rounded-3xl border border-slate-200 bg-slate-50 p-5"
              >
                <h3 className="text-lg font-black text-slate-950">
                  {template.name}
                </h3>

                <p className="mt-3 text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                  Temat
                </p>
                <p className="mt-1 text-sm font-bold leading-6 text-slate-800">
                  {template.subject}
                </p>

                <p className="mt-4 text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                  Podgląd treści
                </p>
                <p className="mt-1 text-sm leading-6 text-slate-600">
                  {template.preview}
                </p>

                {template.mailtoHref ? (
                  <a
                    href={template.mailtoHref}
                    className="mt-5 inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
                  >
                    Użyj tego szablonu
                  </a>
                ) : (
                  <div className="mt-5 inline-flex rounded-2xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-500">
                    Brak e-maila
                  </div>
                )}
              </article>
            ))}
          </div>
        )}
      </section>

      <section className="grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
        <div className="space-y-8">
          <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 className="text-xl font-black text-slate-950">
              Dane kontaktowe
            </h2>
            <p className="mt-2 text-sm text-slate-600">
              Dane podane przez gościa w formularzu publicznym.
            </p>

            <div className="mt-6 grid gap-4 md:grid-cols-2">
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

              <div className="rounded-2xl bg-slate-50 p-5">
                <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                  E-mail
                </p>
                {inquiry.email ? (
                  <a
                    href="#wybierz-odpowiedz"
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
        </div>

        <div className="space-y-8">
          <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 className="text-xl font-black text-slate-950">Adres</h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              {addressText}
            </p>

            <div className="mt-6 grid gap-4 md:grid-cols-2">
              <div className="rounded-2xl bg-slate-50 p-5 md:col-span-2">
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

              <div className="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                  Kraj
                </p>
                <p className="mt-2 text-lg font-black text-slate-950">
                  {inquiry.country || "Nie podano"}
                </p>
              </div>
            </div>
          </section>
        </div>
      </section>
    </main>
  );
}