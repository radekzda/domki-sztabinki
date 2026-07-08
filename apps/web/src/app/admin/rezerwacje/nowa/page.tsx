import Link from "next/link";

import ReservationForm from "@/components/reservations/ReservationForm";
import { prisma } from "@/lib/prisma";

type Props = {
  searchParams?: Promise<{
    error?: string;
    cabinId?: string;
    startDate?: string;
    endDate?: string;
    guestId?: string;
    inquiryId?: string;
    guestName?: string;
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    guests?: string;
    adults?: string;
    children?: string;
    source?: string;
    street?: string;
    postalCode?: string;
    city?: string;
    country?: string;
    notes?: string;
    checkInTime?: string;
    checkOutTime?: string;
    totalPrice?: string;
    paidAmount?: string;
  }>;
};

function isValidDateInputValue(value: string | undefined) {
  if (!value) {
    return false;
  }

  return /^\d{4}-\d{2}-\d{2}$/.test(value);
}

function isValidTimeInputValue(value: string | undefined) {
  if (!value) {
    return false;
  }

  return /^\d{2}:\d{2}$/.test(value);
}

function getNextDateInputValue(dateValue: string) {
  const date = new Date(`${dateValue}T00:00:00`);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  date.setDate(date.getDate() + 1);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function getSearchParamValue(value: string | undefined) {
  return typeof value === "string" ? value.trim() : "";
}

function splitGuestName(guestName: string) {
  const cleanedGuestName = guestName.trim().replace(/\s+/g, " ");

  if (!cleanedGuestName) {
    return {
      firstName: "",
      lastName: "",
    };
  }

  const parts = cleanedGuestName.split(" ");

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

function getNumberInputValue(value: string, fallback: string) {
  const parsedValue = Number.parseInt(value, 10);

  if (!Number.isInteger(parsedValue) || parsedValue < 0 || parsedValue > 20) {
    return fallback;
  }

  return String(parsedValue);
}

function getMoneyInputValue(value: string) {
  const normalizedValue = value.replace(",", ".");

  if (!normalizedValue) {
    return "";
  }

  const parsedValue = Number(normalizedValue);

  if (!Number.isFinite(parsedValue) || parsedValue < 0) {
    return "";
  }

  return normalizedValue;
}

function getReservationSource(value: string) {
  if (value === "WWW" || value === "WEBSITE") {
    return "WEBSITE";
  }

  if (value === "PHONE") {
    return "PHONE";
  }

  if (value === "BOOKING") {
    return "BOOKING";
  }

  if (value === "AIRBNB") {
    return "AIRBNB";
  }

  return "MANUAL";
}

function getReservationSourceLabel(source: string) {
  if (source === "WEBSITE") {
    return "WWW";
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

  return "Ręcznie";
}

async function getSystemSettings() {
  return prisma.systemSettings.upsert({
    where: {
      id: "main",
    },
    create: {
      id: "main",
      propertyName: "Domki Sztabinki",
      propertyCountry: "Polska",
      checkInTime: "15:00",
      checkOutTime: "11:00",
      minimumNights: 4,
      seasonStartMonth: 5,
      seasonEndMonth: 9,
    },
    update: {},
  });
}

export default async function NowaRezerwacjaPage({ searchParams }: Props) {
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const error = resolvedSearchParams?.error;
  const inquiryId = getSearchParamValue(resolvedSearchParams?.inquiryId);
  const urlGuestName = getSearchParamValue(resolvedSearchParams?.guestName);
  const urlFirstName = getSearchParamValue(resolvedSearchParams?.firstName);
  const urlLastName = getSearchParamValue(resolvedSearchParams?.lastName);
  const urlEmail = getSearchParamValue(resolvedSearchParams?.email);
  const urlPhone = getSearchParamValue(resolvedSearchParams?.phone);
  const urlAdults = getNumberInputValue(
    getSearchParamValue(resolvedSearchParams?.adults),
    "2",
  );
  const urlChildren = getNumberInputValue(
    getSearchParamValue(resolvedSearchParams?.children),
    "0",
  );
  const urlSource = getReservationSource(
    getSearchParamValue(resolvedSearchParams?.source),
  );
  const urlStreet = getSearchParamValue(resolvedSearchParams?.street);
  const urlPostalCode = getSearchParamValue(resolvedSearchParams?.postalCode);
  const urlCity = getSearchParamValue(resolvedSearchParams?.city);
  const urlCountry = getSearchParamValue(resolvedSearchParams?.country);
  const urlNotes = getSearchParamValue(resolvedSearchParams?.notes);
  const urlCheckInTime = getSearchParamValue(resolvedSearchParams?.checkInTime);
  const urlCheckOutTime = getSearchParamValue(
    resolvedSearchParams?.checkOutTime,
  );
  const urlTotalPrice = getMoneyInputValue(
    getSearchParamValue(resolvedSearchParams?.totalPrice),
  );
  const urlPaidAmount = getMoneyInputValue(
    getSearchParamValue(resolvedSearchParams?.paidAmount),
  );
  const splitUrlGuestName = splitGuestName(urlGuestName);

  const initialStartDate = isValidDateInputValue(
    resolvedSearchParams?.startDate,
  )
    ? resolvedSearchParams?.startDate ?? ""
    : "";

  const initialEndDate = isValidDateInputValue(resolvedSearchParams?.endDate)
    ? resolvedSearchParams?.endDate ?? ""
    : initialStartDate
      ? getNextDateInputValue(initialStartDate)
      : "";

  const [cabins, initialGuest, settings] = await Promise.all([
    prisma.cabin.findMany({
      where: {
        isActive: true,
      },
      orderBy: {
        sortOrder: "asc",
      },
      select: {
        id: true,
        name: true,
        maxGuests: true,
        pricePerNight: true,
        priceOneNight: true,
        priceTwoNights: true,
        priceThreeNights: true,
        priceFourNights: true,
        priceFiveNights: true,
        priceSixNights: true,
        priceSevenPlusNights: true,
      },
    }),
    resolvedSearchParams?.guestId
      ? prisma.guest.findUnique({
          where: {
            id: resolvedSearchParams.guestId,
          },
        })
      : Promise.resolve(null),
    getSystemSettings(),
  ]);

  const initialCabinExists = cabins.some(
    (cabin) => cabin.id === resolvedSearchParams?.cabinId,
  );

  const initialFirstName =
    initialGuest?.firstName ?? (urlFirstName || splitUrlGuestName.firstName);
  const initialLastName =
    initialGuest?.lastName ?? (urlLastName || splitUrlGuestName.lastName);
  const initialEmail = initialGuest?.email ?? urlEmail;
  const initialPhone = initialGuest?.phone ?? urlPhone;
  const initialCountry =
    initialGuest?.country ?? (urlCountry || settings.propertyCountry);
  const initialCheckInTime = isValidTimeInputValue(urlCheckInTime)
    ? urlCheckInTime
    : settings.checkInTime;
  const initialCheckOutTime = isValidTimeInputValue(urlCheckOutTime)
    ? urlCheckOutTime
    : settings.checkOutTime;
  const isFromInquiry = Boolean(inquiryId);

  return (
    <div className="max-w-5xl space-y-8">
      <div>
        <Link
          href="/admin/rezerwacje"
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do rezerwacji
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Dodaj rezerwację</h1>

        <p className="mt-2 text-zinc-500">
          Dodaj pełną rezerwację PMS z automatycznie wyliczaną ceną domyślną.
        </p>
      </div>

      {error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
          {error}
        </div>
      ) : null}

      {initialGuest ? (
        <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
          <div className="font-semibold">
            Tworzysz rezerwację dla istniejącego gościa.
          </div>

          <div className="mt-1">
            {initialGuest.firstName} {initialGuest.lastName} ·{" "}
            {initialGuest.email}
          </div>
        </div>
      ) : null}

      {isFromInquiry ? (
        <div className="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
          <div className="font-semibold">
            Tworzysz rezerwację na podstawie zapytania z WWW.
          </div>

          <div className="mt-1">
            Po zapisaniu rezerwacji zapytanie zostanie oznaczone jako
            zatwierdzone.
          </div>

          <div className="mt-2 grid gap-1 md:grid-cols-2">
            <div>
              Gość: {initialFirstName} {initialLastName}
            </div>
            {urlPhone ? <div>Telefon: {urlPhone}</div> : null}
            {urlEmail ? <div>E-mail: {urlEmail}</div> : null}
            <div>Dorośli: {urlAdults}</div>
            <div>Dzieci: {urlChildren}</div>
            <div>Źródło rezerwacji: {getReservationSourceLabel(urlSource)}</div>
            {urlStreet ? <div>Ulica i numer: {urlStreet}</div> : null}
            {urlPostalCode ? <div>Kod pocztowy: {urlPostalCode}</div> : null}
            {urlCity ? <div>Miasto: {urlCity}</div> : null}
            {initialCountry ? <div>Kraj: {initialCountry}</div> : null}
          </div>

          {urlNotes ? (
            <div className="mt-3 rounded-lg border border-sky-200 bg-white p-3">
              <div className="font-semibold">Wiadomość z zapytania:</div>
              <div className="mt-1 whitespace-pre-wrap">{urlNotes}</div>
            </div>
          ) : null}
        </div>
      ) : null}

      <ReservationForm
        cabins={cabins}
        initialGuestId={initialGuest?.id ?? ""}
        initialCabinId={
          initialCabinExists ? resolvedSearchParams?.cabinId ?? "" : ""
        }
        initialStartDate={initialStartDate}
        initialEndDate={initialEndDate}
        initialFirstName={initialFirstName}
        initialLastName={initialLastName}
        initialEmail={initialEmail}
        initialPhone={initialPhone}
        initialAdults={urlAdults}
        initialChildren={urlChildren}
        initialSource={urlSource}
        initialStreet={urlStreet}
        initialPostalCode={urlPostalCode}
        initialCity={urlCity}
        initialCountry={initialCountry}
        initialNotes={urlNotes}
        initialCheckInTime={initialCheckInTime}
        initialCheckOutTime={initialCheckOutTime}
        initialTotalPrice={urlTotalPrice}
        initialPaidAmount={urlPaidAmount}
        initialInquiryId={inquiryId}
        minimumNights={settings.minimumNights}
      />
    </div>
  );
}