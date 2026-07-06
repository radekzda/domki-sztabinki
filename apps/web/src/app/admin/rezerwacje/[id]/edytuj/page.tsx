import Link from "next/link";
import { notFound } from "next/navigation";
import ReservationEditForm from "@/components/reservations/ReservationEditForm";
import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

type Props = {
  params: Promise<{
    id: string;
  }>;
  searchParams?: Promise<{
    error?: string;
  }>;
};

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

function padNumber(value: number) {
  return String(value).padStart(2, "0");
}

function formatDateInputValue(date: Date) {
  return [
    date.getFullYear(),
    padNumber(date.getMonth() + 1),
    padNumber(date.getDate()),
  ].join("-");
}

function formatTimeInputValue(date: Date | null, fallback: string) {
  if (!date) {
    return fallback;
  }

  return [padNumber(date.getHours()), padNumber(date.getMinutes())].join(":");
}

function splitGuestName(guestName: string) {
  const parts = guestName.trim().split(/\s+/);

  if (parts.length === 0) {
    return {
      firstName: "",
      lastName: "",
    };
  }

  if (parts.length === 1) {
    return {
      firstName: parts[0],
      lastName: "",
    };
  }

  return {
    firstName: parts.slice(0, -1).join(" "),
    lastName: parts[parts.length - 1],
  };
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

export default async function EditReservationPage({
  params,
  searchParams,
}: Props) {
  const resolvedParams = await params;
  const resolvedSearchParams = searchParams ? await searchParams : undefined;

  const [reservation, cabins, settings] = await Promise.all([
    prisma.reservation.findUnique({
      where: {
        id: resolvedParams.id,
      },
    }),
    prisma.cabin.findMany({
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
    getSystemSettings(),
  ]);

  if (!reservation) {
    notFound();
  }

  const checkInDate = reservation.checkInAt ?? reservation.startDate;
  const checkOutDate = reservation.checkOutAt ?? reservation.endDate;

  const splitName = splitGuestName(reservation.guestName);

  const firstName = reservation.firstName ?? splitName.firstName;
  const lastName = reservation.lastName ?? splitName.lastName;

  return (
    <div className="max-w-5xl space-y-8">
      <div>
        <Link
          href={`/admin/rezerwacje/${reservation.id}`}
          className="text-sm text-zinc-500 hover:text-zinc-900"
        >
          ← Wróć do szczegółów rezerwacji
        </Link>

        <h1 className="mt-3 text-3xl font-bold">Edytuj rezerwację</h1>

        <p className="mt-2 text-zinc-500">
          Zmień dane gościa, termin pobytu, płatność, status lub źródło
          rezerwacji.
        </p>
      </div>

      {resolvedSearchParams?.error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
          {resolvedSearchParams.error}
        </div>
      ) : null}

      <ReservationEditForm
        key={reservation.id}
        cabins={cabins}
        minimumNights={settings.minimumNights}
        reservation={{
          id: reservation.id,
          cabinId: reservation.cabinId,

          guestName: reservation.guestName,
          firstName,
          lastName,

          email: reservation.email,
          phone: reservation.phone ?? "",

          startDateValue: formatDateInputValue(checkInDate),
          endDateValue: formatDateInputValue(checkOutDate),

          checkInTimeValue: formatTimeInputValue(
            reservation.checkInAt,
            settings.checkInTime,
          ),
          checkOutTimeValue: formatTimeInputValue(
            reservation.checkOutAt,
            settings.checkOutTime,
          ),

          adults: reservation.adults,
          children: reservation.children,

          status: reservation.status,
          source: reservation.source,

          totalPrice: decimalToNumber(reservation.totalPrice),
          paidAmount: decimalToNumber(reservation.paidAmount),

          street: reservation.street ?? "",
          postalCode: reservation.postalCode ?? "",
          city: reservation.city ?? "",
          country: reservation.country ?? settings.propertyCountry,

          notes: reservation.notes ?? "",
        }}
      />
    </div>
  );
}