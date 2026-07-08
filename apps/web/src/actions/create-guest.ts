"use server";

import type { Prisma } from "@prisma/client";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

function cleanText(value: FormDataEntryValue | null) {
  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

function cleanNullableText(value: FormDataEntryValue | null) {
  const cleanedValue = cleanText(value);

  return cleanedValue ? cleanedValue : null;
}

function normalizeEmail(value: string) {
  return value.trim().toLowerCase();
}

function normalizePhone(value: string | null) {
  if (!value) {
    return null;
  }

  const normalizedPhone = value.replace(/[^\d+]/g, "");

  return normalizedPhone || null;
}

function normalizePesel(value: string | null) {
  if (!value) {
    return null;
  }

  const normalizedPesel = value.replace(/\D/g, "");

  return normalizedPesel || null;
}

function createDateFromParts(year: number, month: number, day: number) {
  const date = new Date(Date.UTC(year, month - 1, day));

  if (
    date.getUTCFullYear() !== year ||
    date.getUTCMonth() !== month - 1 ||
    date.getUTCDate() !== day
  ) {
    return null;
  }

  return date;
}

function isDateInFuture(date: Date) {
  const now = new Date();
  const today = new Date(
    Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())
  );

  return date.getTime() > today.getTime();
}

function parseBirthDate(value: string | null) {
  if (!value) {
    return {
      date: null as Date | null,
      error: null as string | null,
    };
  }

  const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!isoMatch) {
    return {
      date: null,
      error: "invalid-birth-date",
    };
  }

  const year = Number(isoMatch[1]);
  const month = Number(isoMatch[2]);
  const day = Number(isoMatch[3]);

  const date = createDateFromParts(year, month, day);

  if (!date) {
    return {
      date: null,
      error: "invalid-birth-date",
    };
  }

  if (isDateInFuture(date)) {
    return {
      date: null,
      error: "future-birth-date",
    };
  }

  return {
    date,
    error: null,
  };
}

function buildFullAddress({
  street,
  postalCode,
  city,
  country,
  fullAddress,
}: {
  street: string | null;
  postalCode: string | null;
  city: string | null;
  country: string | null;
  fullAddress: string | null;
}) {
  if (fullAddress) {
    return fullAddress;
  }

  const addressParts = [
    street,
    [postalCode, city].filter(Boolean).join(" "),
    country,
  ].filter(Boolean);

  if (addressParts.length === 0) {
    return null;
  }

  return addressParts.join(", ");
}

function buildNewGuestUrl(error: string) {
  const params = new URLSearchParams();

  params.set("error", error);

  return `/admin/goscie/nowy?${params.toString()}`;
}

export async function createGuest(formData: FormData) {
  const firstName = cleanText(formData.get("firstName"));
  const lastName = cleanText(formData.get("lastName"));
  const email = normalizeEmail(cleanText(formData.get("email")));
  const phone = normalizePhone(cleanNullableText(formData.get("phone")));
  const country = cleanNullableText(formData.get("country"));
  const street = cleanNullableText(formData.get("street"));
  const postalCode = cleanNullableText(formData.get("postalCode"));
  const city = cleanNullableText(formData.get("city"));
  const fullAddress = cleanNullableText(formData.get("fullAddress"));
  const notes = cleanNullableText(formData.get("notes"));

  const pesel = normalizePesel(cleanNullableText(formData.get("pesel")));
  const documentNumber = cleanNullableText(formData.get("documentNumber"));
  const nationality = cleanNullableText(formData.get("nationality"));
  const externalGuestId = cleanNullableText(formData.get("externalGuestId"));
  const isVip = formData.get("isVip") === "on";

  const birthDateResult = parseBirthDate(
    cleanNullableText(formData.get("birthDate"))
  );

  if (birthDateResult.error) {
    redirect(buildNewGuestUrl(birthDateResult.error));
  }

  if (!firstName && !lastName) {
    redirect(buildNewGuestUrl("missing-name"));
  }

  if (!email && !phone) {
    redirect(buildNewGuestUrl("missing-contact"));
  }

  const duplicateConditions: Prisma.GuestWhereInput[] = [];

  if (email) {
    duplicateConditions.push({
      email: {
        equals: email,
        mode: "insensitive",
      },
    });
  }

  if (phone) {
    duplicateConditions.push({
      phone,
    });
  }

  if (pesel) {
    duplicateConditions.push({
      pesel,
    });
  }

  if (externalGuestId) {
    duplicateConditions.push({
      externalGuestId,
    });
  }

  if (duplicateConditions.length > 0) {
    const existingGuest = await prisma.guest.findFirst({
      where: {
        OR: duplicateConditions,
      },
      select: {
        id: true,
      },
    });

    if (existingGuest) {
      redirect(`/admin/goscie/${existingGuest.id}`);
    }
  }

  const guest = await prisma.guest.create({
    data: {
      firstName: firstName || "Gość",
      lastName,
      email,
      phone,
      country,
      street,
      postalCode,
      city,
      fullAddress: buildFullAddress({
        street,
        postalCode,
        city,
        country,
        fullAddress,
      }),
      pesel,
      documentNumber,
      nationality,
      birthDate: birthDateResult.date,
      isVip,
      externalGuestId,
      notes,
      source: "MANUAL",
    },
    select: {
      id: true,
    },
  });

  redirect(`/admin/goscie/${guest.id}`);
}