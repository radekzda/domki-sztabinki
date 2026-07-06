import Link from "next/link";
import { prisma } from "@/lib/prisma";
import ReservationForm from "@/components/reservations/ReservationForm";

type Props = {
  searchParams?: Promise<{
    error?: string;
    cabinId?: string;
    startDate?: string;
    endDate?: string;
    guestId?: string;
  }>;
};

function isValidDateInputValue(value: string | undefined) {
  if (!value) {
    return false;
  }

  return /^\d{4}-\d{2}-\d{2}$/.test(value);
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

      <ReservationForm
        cabins={cabins}
        initialGuestId={initialGuest?.id ?? ""}
        initialCabinId={
          initialCabinExists ? resolvedSearchParams?.cabinId ?? "" : ""
        }
        initialStartDate={initialStartDate}
        initialEndDate={initialEndDate}
        initialFirstName={initialGuest?.firstName ?? ""}
        initialLastName={initialGuest?.lastName ?? ""}
        initialEmail={initialGuest?.email ?? ""}
        initialPhone={initialGuest?.phone ?? ""}
        initialCountry={initialGuest?.country ?? settings.propertyCountry}
        initialCheckInTime={settings.checkInTime}
        initialCheckOutTime={settings.checkOutTime}
        minimumNights={settings.minimumNights}
      />
    </div>
  );
}