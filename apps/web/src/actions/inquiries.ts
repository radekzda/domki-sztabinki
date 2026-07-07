"use server";

import { revalidatePath } from "next/cache";

import { prisma } from "@/lib/prisma";

export type CreatePublicInquiryInput = {
  fullName: string;
  phone: string;
  email: string;
  cabinId: string;
  cabinName: string;
  dateFrom: string;
  dateTo: string;
  guests: string;
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

function isInquiryStatus(value: string): value is InquiryStatus {
  return allowedInquiryStatuses.includes(value as InquiryStatus);
}

export async function createPublicInquiry(
  input: CreatePublicInquiryInput
): Promise<CreatePublicInquiryResult> {
  const fullName = normalizeText(input.fullName);
  const phone = normalizeText(input.phone);
  const email = normalizeText(input.email);
  const cabinId = normalizeText(input.cabinId);
  const fallbackCabinName = normalizeText(input.cabinName);
  const dateFrom = normalizeText(input.dateFrom);
  const dateTo = normalizeText(input.dateTo);
  const guestsValue = normalizeText(input.guests);
  const notes = normalizeText(input.notes);

  if (!fullName || !phone || !dateFrom || !dateTo || !guestsValue) {
    return {
      ok: false,
      message:
        "Uzupełnij imię i nazwisko, telefon, termin pobytu oraz liczbę osób.",
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

  const guests = Number.parseInt(guestsValue, 10);

  if (!Number.isInteger(guests) || guests < 1 || guests > 20) {
    return {
      ok: false,
      message: "Podaj poprawną liczbę osób od 1 do 20.",
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

  await prisma.inquiry.create({
    data: {
      fullName,
      phone,
      email: email || null,
      cabinId: cabin?.id ?? null,
      cabinName: cabin?.name ?? (fallbackCabinName || null),
      dateFrom: parsedDateFrom,
      dateTo: parsedDateTo,
      guests,
      notes: notes || null,
      status: "NEW",
      source: "WEBSITE",
    },
  });

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