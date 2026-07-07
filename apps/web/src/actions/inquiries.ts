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

const allowedInquiryStatuses = ["NEW", "CONTACTED", "ARCHIVED"] as const;

type InquiryStatus = (typeof allowedInquiryStatuses)[number];

function normalizeText(value: string) {
  return value.trim();
}

function parseDateOnly(value: string) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const date = new Date(`${value}T12:00:00.000Z`);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

function parsePeopleCount(value: string, fallback: number) {
  const parsedValue = Number.parseInt(value, 10);

  if (!Number.isInteger(parsedValue)) {
    return fallback;
  }

  return parsedValue;
}

function dateRangesOverlap({
  selectedDateFrom,
  selectedDateTo,
  occupiedDateFrom,
  occupiedDateTo,
}: {
  selectedDateFrom: Date;
  selectedDateTo: Date;
  occupiedDateFrom: Date;
  occupiedDateTo: Date;
}) {
  return selectedDateFrom < occupiedDateTo && selectedDateTo > occupiedDateFrom;
}

function isInquiryStatus(value: string): value is InquiryStatus {
  return allowedInquiryStatuses.includes(value as InquiryStatus);
}

export async function createPublicInquiry(
  input: CreatePublicInquiryInput
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
      message:
        "Uzupełnij imię, nazwisko, telefon oraz termin pobytu.",
    };
  }

  const parsedDateFrom = parseDateOnly(dateFrom);
  const parsedDateTo = parseDateOnly(dateTo);

  if (!parsedDateFrom || !parsedDateTo) {
    return {
      ok: false,
      message: "Podaj poprawny termin pobytu.",
    };
  }

  if (parsedDateTo <= parsedDateFrom) {
    return {
      ok: false,
      message: "Data wyjazdu musi być późniejsza niż data przyjazdu.",
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
      },
    });

    const conflictingReservation = reservationsForCabin.find((reservation) => {
      const occupiedDateFrom = reservation.checkInAt ?? reservation.startDate;
      const occupiedDateTo = reservation.checkOutAt ?? reservation.endDate;

      return dateRangesOverlap({
        selectedDateFrom: parsedDateFrom,
        selectedDateTo: parsedDateTo,
        occupiedDateFrom,
        occupiedDateTo,
      });
    });

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
  const status = typeof statusValue === "string" ? statusValue : "";

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