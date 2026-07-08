"use server";

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

  if (!firstName && !lastName) {
    redirect(buildNewGuestUrl("missing-name"));
  }

  if (!email && !phone) {
    redirect(buildNewGuestUrl("missing-contact"));
  }

  const duplicateConditions = [];

  if (email) {
    duplicateConditions.push({
      email: {
        equals: email,
        mode: "insensitive" as const,
      },
    });
  }

  if (phone) {
    duplicateConditions.push({
      phone,
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
      notes,
      source: "MANUAL",
    },
    select: {
      id: true,
    },
  });

  redirect(`/admin/goscie/${guest.id}`);
}