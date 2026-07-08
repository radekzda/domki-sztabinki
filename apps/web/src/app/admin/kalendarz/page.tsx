import Link from "next/link";
import Calendar from "@/components/calendar/Calendar";
import { prisma } from "@/lib/prisma";
import type {
  CalendarCabin,
  CalendarEngineData,
  CalendarReservationSource,
  CalendarReservationStatus,
} from "@/modules/calendar/calendar.types";
import {
  createCalendarMonth,
  getCalendarMonthEnd,
  getCalendarMonthQueryEnd,
  getCalendarMonthQueryStart,
  getCalendarMonthStart,
} from "@/modules/calendar/calendar.utils";

type Props = {
  searchParams?: Promise<{
    year?: string;
    month?: string;
  }>;
};

function getSelectedCalendarDate(searchParams?: {
  year?: string;
  month?: string;
}) {
  const today = new Date();

  const selectedYear = Number(searchParams?.year) || today.getFullYear();

  const selectedMonth =
    Number(searchParams?.month) >= 1 && Number(searchParams?.month) <= 12
      ? Number(searchParams?.month) - 1
      : today.getMonth();

  return {
    year: selectedYear,
    month: selectedMonth,
  };
}

function mapReservationStatus(status: string): CalendarReservationStatus {
  if (status === "PENDING") {
    return "PENDING";
  }

  if (status === "CONFIRMED") {
    return "CONFIRMED";
  }

  if (status === "CHECKED_IN") {
    return "CHECKED_IN";
  }

  if (status === "CHECKED_OUT" || status === "COMPLETED") {
    return "CHECKED_OUT";
  }

  if (status === "CANCELLED") {
    return "CANCELLED";
  }

  return "PENDING";
}

function mapReservationSource(source: string): CalendarReservationSource {
  if (
    source === "MANUAL" ||
    source === "PHONE" ||
    source === "WEBSITE" ||
    source === "BOOKING" ||
    source === "AIRBNB"
  ) {
    return source;
  }

  return "MANUAL";
}

function decimalToNumber(value: { toString: () => string } | null) {
  if (!value) {
    return null;
  }

  return Number(value.toString());
}

export default async function KalendarzPage({ searchParams }: Props) {
  const resolvedSearchParams = await searchParams;

  const { year, month } = getSelectedCalendarDate(resolvedSearchParams);

  const calendarMonth = createCalendarMonth(year, month);
  const rangeStart = getCalendarMonthStart(year, month);
  const rangeEnd = getCalendarMonthEnd(year, month);
  const queryRangeStart = getCalendarMonthQueryStart(year, month);
  const queryRangeEnd = getCalendarMonthQueryEnd(year, month);

  const cabins = await prisma.cabin.findMany({
    where: {
      isActive: true,
    },
    orderBy: {
      sortOrder: "asc",
    },
    include: {
      reservations: {
        where: {
          startDate: {
            lt: queryRangeEnd,
          },
          endDate: {
            gte: queryRangeStart,
          },
        },
        orderBy: {
          startDate: "asc",
        },
      },
    },
  });

  const calendarCabins: CalendarCabin[] = cabins.map((cabin) => ({
    id: cabin.id,
    name: cabin.name,
    shortName: cabin.shortName,
    maxGuests: cabin.maxGuests,
    isActive: cabin.isActive,
    reservations: cabin.reservations.map((reservation) => ({
      id: reservation.id,
      cabinId: reservation.cabinId,

      guestName: reservation.guestName,
      firstName: reservation.firstName,
      lastName: reservation.lastName,

      email: reservation.email,
      phone: reservation.phone,

      startDate: reservation.startDate,
      endDate: reservation.endDate,

      checkInAt: reservation.checkInAt,
      checkOutAt: reservation.checkOutAt,

      nights: reservation.nights,
      pricePerNight: decimalToNumber(reservation.pricePerNight),

      guests: reservation.guests,
      adults: reservation.adults,
      children: reservation.children,

      status: mapReservationStatus(reservation.status),
      source: mapReservationSource(reservation.source),

      totalPrice: decimalToNumber(reservation.totalPrice),
      paidAmount: decimalToNumber(reservation.paidAmount),

      street: reservation.street,
      postalCode: reservation.postalCode,
      city: reservation.city,
      country: reservation.country,

      notes: reservation.notes,
    })),
  }));

  const calendarData: CalendarEngineData = {
    cabins: calendarCabins,
    month: calendarMonth,
    range: {
      startDate: rangeStart,
      endDate: rangeEnd,
    },
    filters: {
      showCancelled: true,
      showCompleted: true,
    },
  };

  return (
    <div className="space-y-8">
      <div className="flex items-start justify-between gap-6">
        <div>
          <h1 className="text-3xl font-bold">Kalendarz rezerwacji</h1>

          <p className="mt-2 text-zinc-500">
            Widok dostępności domków i rezerwacji oparty o Calendar Engine v1.
          </p>
        </div>

        <Link
          href="/admin/rezerwacje/nowa"
          className="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
        >
          + Dodaj rezerwację
        </Link>
      </div>

      <Calendar data={calendarData} />
    </div>
  );
}