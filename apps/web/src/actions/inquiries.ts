"use server";

import { revalidatePath } from "next/cache";

import { prisma } from "@/lib/prisma";

export type CreatePublicInquiryInput = {
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
  cabinId: string;
  cabinName: string;
  dateFrom: string;
  dateTo: string;
  adults: string;
  children: string;
  street: string;
  postalCode: string;
  city: string;
  country: string;
  notes: string;
};

export type CreatePublicInquiryResult = {
  ok: boolean;
  message: string;
};

type AvailabilityDateRange = {
  id: string;
  dateFrom: string;
  dateTo: string;
  status: string;
};

const allowedInquiryStatuses = ["NEW", "APPROVED", "ARCHIVED"] as const;

const blockingReservationStatuses = [
  "PENDING",
  "CONFIRMED",
  "CHECKED_IN",
] as const;

const millisecondsInDay = 24 * 60 * 60 * 1000;

type InquiryStatus = (typeof allowedInquiryStatuses)[number];

function normalizeText(value: string) {
  return value.trim();
}

function normalizeInquiryStatus(value: string) {
  if (value === "CONTACTED") {
    return "APPROVED";
  }

  return value;
}

function normalizeReservationStatus(value: string) {
  if (value === "COMPLETED") {
    return "CHECKED_OUT";
  }

  if (
    value === "PENDING" ||
    value === "CONFIRMED" ||
    value === "CHECKED_IN" ||
    value === "CHECKED_OUT" ||
    value === "CANCELLED"
  ) {
    return value;
  }

  return "PENDING";
}

function isBlockingReservationStatus(value: string) {
  return blockingReservationStatuses.includes(
    normalizeReservationStatus(value) as (typeof blockingReservationStatuses)[number],
  );
}

function createUtcDateOnly(value: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const date = new Date(`${value}T00:00:00.000Z`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  if (date.toISOString().slice(0, 10) !== value) {
    return null;
  }

  return date;
}

function parseDateOnly(value: string) {
  return createUtcDateOnly(value);
}

function getDateInputValueFromDate(date: Date) {
  return date.toISOString().slice(0, 10);
}

function createUtcDateOnlyFromDate(date: Date) {
  const dateOnlyValue = getDateInputValueFromDate(date);

  return createUtcDateOnly(dateOnlyValue) ?? date;
}

function getTodayDateInputValueInWarsaw() {
  const formatter = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Europe/Warsaw",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  const parts = formatter.formatToParts(new Date());
  const year = parts.find((part) => part.type === "year")?.value ?? "";
  const month = parts.find((part) => part.type === "month")?.value ?? "";
  const day = parts.find((part) => part.type === "day")?.value ?? "";

  return `${year}-${month}-${day}`;
}

function getTodayDateOnlyInWarsaw() {
  const todayValue = getTodayDateInputValueInWarsaw();

  return createUtcDateOnly(todayValue);
}

function parsePeopleCount(value: string, fallback: number) {
  const parsedValue = Number.parseInt(value, 10);

  if (!Number.isInteger(parsedValue)) {
    return fallback;
  }

  return parsedValue;
}

function normalizePositiveInteger(value: number, fallback: number) {
  if (!Number.isInteger(value) || value < 1) {
    return fallback;
  }

  return value;
}

function normalizeSeasonMonth(value: number, fallback: number) {
  if (!Number.isInteger(value) || value < 1 || value > 12) {
    return fallback;
  }

  return value;
}

function getMonthName(month: number) {
  const monthNames = [
    "styczeń",
    "luty",
    "marzec",
    "kwiecień",
    "maj",
    "czerwiec",
    "lipiec",
    "sierpień",
    "wrzesień",
    "październik",
    "listopad",
    "grudzień",
  ];

  if (month < 1 || month > 12) {
    return "maj";
  }

  return monthNames[month - 1];
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

function getStayNights(dateFrom: Date, dateTo: Date) {
  return Math.round((dateTo.getTime() - dateFrom.getTime()) / millisecondsInDay);
}

function addUtcDays(date: Date, days: number) {
  const nextDate = new Date(date.getTime());
  nextDate.setUTCDate(nextDate.getUTCDate() + days);

  return nextDate;
}

function isMonthInSeason(month: number, seasonStartMonth: number, seasonEndMonth: number) {
  if (seasonStartMonth <= seasonEndMonth) {
    return month >= seasonStartMonth && month <= seasonEndMonth;
  }

  return month >= seasonStartMonth || month <= seasonEndMonth;
}

function isStayInsideSeason({
  dateFrom,
  dateTo,
  seasonStartMonth,
  seasonEndMonth,
}: {
  dateFrom: Date;
  dateTo: Date;
  seasonStartMonth: number;
  seasonEndMonth: number;
}) {
  for (
    let currentDate = new Date(dateFrom.getTime());
    currentDate < dateTo;
    currentDate = addUtcDays(currentDate, 1)
  ) {
    const currentMonth = currentDate.getUTCMonth() + 1;

    if (!isMonthInSeason(currentMonth, seasonStartMonth, seasonEndMonth)) {
      return false;
    }
  }

  return true;
}

function dateInputRangesOverlap({
  selectedDateFrom,
  selectedDateTo,
  occupiedDateFrom,
  occupiedDateTo,
}: {
  selectedDateFrom: string;
  selectedDateTo: string;
  occupiedDateFrom: string;
  occupiedDateTo: string;
}) {
  return selectedDateFrom < occupiedDateTo && selectedDateTo > occupiedDateFrom;
}

function isDateCheckInBoundaryOfRanges(
  dateInputValue: string,
  dateRanges: AvailabilityDateRange[],
) {
  return dateRanges.some((dateRange) => dateInputValue === dateRange.dateFrom);
}

function isDateCheckOutBoundaryOfRanges(
  dateInputValue: string,
  dateRanges: AvailabilityDateRange[],
) {
  return dateRanges.some((dateRange) => dateInputValue === dateRange.dateTo);
}

function isDateTurnoverBlockedDay({
  dateInputValue,
  allDateRanges,
  blockingDateRanges,
}: {
  dateInputValue: string;
  allDateRanges: AvailabilityDateRange[];
  blockingDateRanges: AvailabilityDateRange[];
}) {
  return (
    isDateCheckOutBoundaryOfRanges(dateInputValue, allDateRanges) &&
    isDateCheckInBoundaryOfRanges(dateInputValue, blockingDateRanges)
  );
}

function isInquiryStatus(value: string): value is InquiryStatus {
  return allowedInquiryStatuses.includes(value as InquiryStatus);
}

export async function createPublicInquiry(
  input: CreatePublicInquiryInput,
): Promise<CreatePublicInquiryResult> {
  const firstName = normalizeText(input.firstName);
  const lastName = normalizeText(input.lastName);
  const fullName = `${firstName} ${lastName}`.trim();
  const phone = normalizeText(input.phone);
  const email = normalizeText(input.email);
  const cabinId = normalizeText(input.cabinId);
  const fallbackCabinName = normalizeText(input.cabinName);
  const dateFrom = normalizeText(input.dateFrom);
  const dateTo = normalizeText(input.dateTo);
  const adultsValue = normalizeText(input.adults);
  const childrenValue = normalizeText(input.children);
  const street = normalizeText(input.street);
  const postalCode = normalizeText(input.postalCode);
  const city = normalizeText(input.city);
  const country = normalizeText(input.country);
  const notes = normalizeText(input.notes);

  if (!firstName || !lastName || !phone || !dateFrom || !dateTo) {
    return {
      ok: false,
      message: "Uzupełnij imię, nazwisko, telefon oraz termin pobytu.",
    };
  }

  const parsedDateFrom = parseDateOnly(dateFrom);
  const parsedDateTo = parseDateOnly(dateTo);
  const todayDate = getTodayDateOnlyInWarsaw();

  if (!parsedDateFrom || !parsedDateTo || !todayDate) {
    return {
      ok: false,
      message: "Podaj poprawny termin pobytu.",
    };
  }

  if (parsedDateFrom < todayDate) {
    return {
      ok: false,
      message: "Data przyjazdu nie może być wcześniejsza niż dzisiejsza data.",
    };
  }

  if (parsedDateTo <= parsedDateFrom) {
    return {
      ok: false,
      message: "Data wyjazdu musi być późniejsza niż data przyjazdu.",
    };
  }

  const settings = await prisma.systemSettings.findUnique({
    where: {
      id: "main",
    },
    select: {
      minimumNights: true,
      seasonStartMonth: true,
      seasonEndMonth: true,
    },
  });

  const minimumNights = normalizePositiveInteger(settings?.minimumNights ?? 1, 1);
  const seasonStartMonth = normalizeSeasonMonth(settings?.seasonStartMonth ?? 5, 5);
  const seasonEndMonth = normalizeSeasonMonth(settings?.seasonEndMonth ?? 9, 9);
  const seasonLabel = `${getMonthName(seasonStartMonth)} — ${getMonthName(
    seasonEndMonth,
  )}`;

  const stayNights = getStayNights(parsedDateFrom, parsedDateTo);

  if (stayNights < minimumNights) {
    return {
      ok: false,
      message: `Minimalny pobyt to ${formatNights(
        minimumNights,
      )}. Wybrany termin ma ${formatNights(stayNights)}.`,
    };
  }

  if (
    !isStayInsideSeason({
      dateFrom: parsedDateFrom,
      dateTo: parsedDateTo,
      seasonStartMonth,
      seasonEndMonth,
    })
  ) {
    return {
      ok: false,
      message: `Wybrany termin wykracza poza sezon (${seasonLabel}). Wybierz termin w sezonie.`,
    };
  }

  const adults = parsePeopleCount(adultsValue, 1);
  const children = parsePeopleCount(childrenValue, 0);

  if (adults < 1 || adults > 20) {
    return {
      ok: false,
      message: "Podaj poprawną liczbę dorosłych od 1 do 20.",
    };
  }

  if (children < 0 || children > 20) {
    return {
      ok: false,
      message: "Podaj poprawną liczbę dzieci od 0 do 20.",
    };
  }

  const guests = adults + children;

  if (guests < 1 || guests > 20) {
    return {
      ok: false,
      message: "Łączna liczba osób musi być od 1 do 20.",
    };
  }

  const cabin = cabinId
    ? await prisma.cabin.findFirst({
        where: {
          id: cabinId,
          isActive: true,
        },
        select: {
          id: true,
          name: true,
          maxGuests: true,
        },
      })
    : null;

  if (cabinId && !cabin) {
    return {
      ok: false,
      message:
        "Wybrany domek nie jest już dostępny na stronie. Odśwież stronę i spróbuj ponownie.",
    };
  }

  if (cabin && guests > cabin.maxGuests) {
    return {
      ok: false,
      message: `Wybrany domek mieści maksymalnie ${cabin.maxGuests} osób. Zmniejsz liczbę osób albo wybierz opcję dowolną / do ustalenia.`,
    };
  }

  if (cabin) {
    const reservationsForCabin = await prisma.reservation.findMany({
      where: {
        cabinId: cabin.id,
        status: {
          not: "CANCELLED",
        },
      },
      select: {
        id: true,
        startDate: true,
        endDate: true,
        checkInAt: true,
        checkOutAt: true,
        status: true,
      },
    });

    const allDateRanges = reservationsForCabin.map((reservation) => ({
      id: reservation.id,
      dateFrom: getDateInputValueFromDate(
        createUtcDateOnlyFromDate(reservation.checkInAt ?? reservation.startDate),
      ),
      dateTo: getDateInputValueFromDate(
        createUtcDateOnlyFromDate(reservation.checkOutAt ?? reservation.endDate),
      ),
      status: reservation.status,
    }));

    const blockingDateRanges = allDateRanges.filter((dateRange) =>
      isBlockingReservationStatus(dateRange.status),
    );

    const isStartOnCheckInBoundary = isDateCheckInBoundaryOfRanges(
      dateFrom,
      blockingDateRanges,
    );

    const isStartOnTurnoverBlockedDay = isDateTurnoverBlockedDay({
      dateInputValue: dateFrom,
      allDateRanges,
      blockingDateRanges,
    });

    const isEndOnTurnoverBlockedDay = isDateTurnoverBlockedDay({
      dateInputValue: dateTo,
      allDateRanges,
      blockingDateRanges,
    });

    if (isStartOnTurnoverBlockedDay || isEndOnTurnoverBlockedDay) {
      return {
        ok: false,
        message:
          "Wybrany dzień jest już w pełni zajęty, ponieważ tego samego dnia jest wymeldowanie i zameldowanie innej rezerwacji. Wybierz inny termin.",
      };
    }

    if (isStartOnCheckInBoundary) {
      return {
        ok: false,
        message:
          "Wybrany dzień jest dniem zameldowania innej rezerwacji. Może być dniem wyjazdu, ale nie może być początkiem nowego pobytu.",
      };
    }

    const conflictingReservation = blockingDateRanges.find((dateRange) =>
      dateInputRangesOverlap({
        selectedDateFrom: dateFrom,
        selectedDateTo: dateTo,
        occupiedDateFrom: dateRange.dateFrom,
        occupiedDateTo: dateRange.dateTo,
      }),
    );

    if (conflictingReservation) {
      return {
        ok: false,
        message:
          "Wybrany termin jest już zajęty dla tego domku. Wybierz inny termin, inny domek albo opcję dowolną / do ustalenia.",
      };
    }
  }

  await prisma.inquiry.create({
    data: {
      fullName,
      firstName,
      lastName,
      phone,
      email: email || null,
      cabinId: cabin?.id ?? null,
      cabinName: cabin?.name ?? (fallbackCabinName || null),
      dateFrom: parsedDateFrom,
      dateTo: parsedDateTo,
      guests,
      adults,
      children,
      street: street || null,
      postalCode: postalCode || null,
      city: city || null,
      country: country || null,
      notes: notes || null,
      status: "NEW",
      source: "WWW",
    },
  });

  revalidatePath("/");
  revalidatePath("/admin/zapytania");

  return {
    ok: true,
    message:
      "Dziękujemy. Zapytanie zostało zapisane. Skontaktujemy się w sprawie dostępności i ceny.",
  };
}

export async function updateInquiryStatus(formData: FormData) {
  const inquiryIdValue = formData.get("inquiryId");
  const statusValue = formData.get("status");

  const inquiryId = typeof inquiryIdValue === "string" ? inquiryIdValue : "";
  const rawStatus = typeof statusValue === "string" ? statusValue : "";
  const status = normalizeInquiryStatus(rawStatus);

  if (!inquiryId || !isInquiryStatus(status)) {
    return;
  }

  await prisma.inquiry.updateMany({
    where: {
      id: inquiryId,
    },
    data: {
      status,
    },
  });

  revalidatePath("/admin/zapytania");
  revalidatePath(`/admin/zapytania/${inquiryId}`);
}