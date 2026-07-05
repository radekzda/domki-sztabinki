import Link from "next/link";
import Calendar from "@/components/calendar/Calendar";
import { prisma } from "@/lib/prisma";
import type {
  CalendarCabin,
  CalendarEngineData,
  CalendarReservationStatus,
} from "@/modules/calendar/calendar.types";
import {
  createCalendarMonth,
  getCalendarMonthEnd,
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
  if (
    status === "PENDING" ||
    status === "CONFIRMED" ||
    status === "CANCELLED" ||
    status === "COMPLETED"
  ) {
    return status;
  }

  return "PENDING";
}

export default async function KalendarzPage({ searchParams }: Props) {
  const resolvedSearchParams = await searchParams;

  const { year, month } = getSelectedCalendarDate(resolvedSearchParams);

  const calendarMonth = createCalendarMonth(year, month);
  const rangeStart = getCalendarMonthStart(year, month);
  const rangeEnd = getCalendarMonthEnd(year, month);

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
            lt: rangeEnd,
          },
          endDate: {
            gt: rangeStart,
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
      email: reservation.email,
      phone: reservation.phone,
      startDate: reservation.startDate,
      endDate: reservation.endDate,
      guests: reservation.guests,
      status: mapReservationStatus(reservation.status),
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