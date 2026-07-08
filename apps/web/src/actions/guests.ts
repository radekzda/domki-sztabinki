"use server";

import type { Prisma } from "@prisma/client";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

function splitGuestName(guestName: string) {
  const parts = guestName.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) {
    return {
      firstName: "Gość",
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

function normalizeEmail(email: string) {
  return email.trim().toLowerCase();
}

function normalizePhone(phone: string | null) {
  if (!phone) {
    return null;
  }

  const normalizedPhone = phone.replace(/[^\d+]/g, "");

  return normalizedPhone || null;
}

function normalizePesel(value: string | null) {
  if (!value) {
    return null;
  }

  const normalizedPesel = value.replace(/\D/g, "");

  return normalizedPesel || null;
}

function getRequiredString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    return "";
  }

  return value.trim();
}

function getOptionalString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return null;
  }

  const trimmedValue = value.trim();

  return trimmedValue === "" ? null : trimmedValue;
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
      error: "Podaj datę urodzenia w poprawnym formacie.",
    };
  }

  const year = Number(isoMatch[1]);
  const month = Number(isoMatch[2]);
  const day = Number(isoMatch[3]);

  const date = createDateFromParts(year, month, day);

  if (!date) {
    return {
      date: null,
      error: "Podaj prawidłową datę urodzenia.",
    };
  }

  if (isDateInFuture(date)) {
    return {
      date: null,
      error: "Data urodzenia nie może być datą przyszłą.",
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

  const parts = [
    street,
    [postalCode, city].filter(Boolean).join(" "),
    country,
  ].filter(Boolean);

  if (parts.length === 0) {
    return null;
  }

  return parts.join(", ");
}

function redirectWithEditGuestError(guestId: string, message: string): never {
  redirect(
    `/admin/goscie/${guestId}/edytuj?error=${encodeURIComponent(message)}`
  );
}

export async function syncGuestsFromReservations() {
  const reservationsWithoutGuest = await prisma.reservation.findMany({
    where: {
      guestId: null,
    },
    orderBy: {
      createdAt: "asc",
    },
  });

  let updatedReservationsCount = 0;
  let createdGuestsCount = 0;
  let updatedGuestsCount = 0;

  for (const reservation of reservationsWithoutGuest) {
    const email = normalizeEmail(reservation.email);

    if (!email) {
      continue;
    }

    const splitName = splitGuestName(reservation.guestName);

    const firstName = reservation.firstName || splitName.firstName;
    const lastName = reservation.lastName || splitName.lastName;

    const fullAddress = buildFullAddress({
      street: reservation.street,
      postalCode: reservation.postalCode,
      city: reservation.city,
      country: reservation.country,
      fullAddress: null,
    });

    const guestData = {
      firstName,
      lastName,
      email,
      phone: reservation.phone,
      country: reservation.country,
      street: reservation.street,
      postalCode: reservation.postalCode,
      city: reservation.city,
      fullAddress,
      source: "RESERVATION_SYNC",
    };

    const existingGuest = await prisma.guest.findFirst({
      where: {
        email,
      },
    });

    const guest = existingGuest
      ? await prisma.guest.update({
          where: {
            id: existingGuest.id,
          },
          data: {
            firstName: existingGuest.firstName || guestData.firstName,
            lastName: existingGuest.lastName || guestData.lastName,
            email: existingGuest.email || guestData.email,
            phone: existingGuest.phone || guestData.phone,
            country: existingGuest.country || guestData.country,
            street: existingGuest.street || guestData.street,
            postalCode: existingGuest.postalCode || guestData.postalCode,
            city: existingGuest.city || guestData.city,
            fullAddress: existingGuest.fullAddress || guestData.fullAddress,
            source: existingGuest.source || guestData.source,
          },
        })
      : await prisma.guest.create({
          data: guestData,
        });

    if (existingGuest) {
      updatedGuestsCount += 1;
    } else {
      createdGuestsCount += 1;
    }

    await prisma.reservation.update({
      where: {
        id: reservation.id,
      },
      data: {
        guestId: guest.id,
        firstName,
        lastName,
        email,
      },
    });

    updatedReservationsCount += 1;
  }

  revalidatePath("/admin/goscie");
  revalidatePath("/admin/rezerwacje");
  revalidatePath("/admin/kalendarz");

  redirect(
    `/admin/goscie?sync=ok&reservations=${updatedReservationsCount}&created=${createdGuestsCount}&updated=${updatedGuestsCount}`
  );
}

export async function updateGuest(formData: FormData) {
  const guestId = getRequiredString(formData, "guestId");

  if (!guestId) {
    redirect("/admin/goscie");
  }

  const firstName = getRequiredString(formData, "firstName");
  const lastName = getRequiredString(formData, "lastName");
  const email = normalizeEmail(getRequiredString(formData, "email"));
  const phone = normalizePhone(getOptionalString(formData, "phone"));
  const country = getOptionalString(formData, "country");
  const street = getOptionalString(formData, "street");
  const postalCode = getOptionalString(formData, "postalCode");
  const city = getOptionalString(formData, "city");
  const fullAddress = getOptionalString(formData, "fullAddress");
  const notes = getOptionalString(formData, "notes");
  const source = getRequiredString(formData, "source") || "MANUAL";

  const pesel = normalizePesel(getOptionalString(formData, "pesel"));
  const documentNumber = getOptionalString(formData, "documentNumber");
  const nationality = getOptionalString(formData, "nationality");
  const externalGuestId = getOptionalString(formData, "externalGuestId");
  const isVip = formData.get("isVip") === "on";

  const birthDateResult = parseBirthDate(
    getOptionalString(formData, "birthDate")
  );

  if (birthDateResult.error) {
    redirectWithEditGuestError(guestId, birthDateResult.error);
  }

  if (!firstName && !lastName) {
    redirectWithEditGuestError(
      guestId,
      "Podaj przynajmniej imię albo nazwisko gościa."
    );
  }

  if (!email && !phone) {
    redirectWithEditGuestError(
      guestId,
      "Podaj przynajmniej email albo telefon gościa."
    );
  }

  const guest = await prisma.guest.findUnique({
    where: {
      id: guestId,
    },
  });

  if (!guest) {
    redirect("/admin/goscie");
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
        id: {
          not: guestId,
        },
        OR: duplicateConditions,
      },
      select: {
        id: true,
      },
    });

    if (existingGuest) {
      redirectWithEditGuestError(
        guestId,
        "Istnieje już inny gość z takim emailem, telefonem, PESEL-em albo zewnętrznym ID."
      );
    }
  }

  const guestName = `${firstName || "Gość"} ${lastName}`.trim();

  await prisma.$transaction([
    prisma.guest.update({
      where: {
        id: guestId,
      },
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
        source,
      },
    }),
    prisma.reservation.updateMany({
      where: {
        guestId,
      },
      data: {
        guestName,
        firstName: firstName || "Gość",
        lastName,
        email,
        phone,
        country,
        street,
        postalCode,
        city,
      },
    }),
  ]);

  revalidatePath("/admin/goscie");
  revalidatePath(`/admin/goscie/${guestId}`);
  revalidatePath(`/admin/goscie/${guestId}/edytuj`);
  revalidatePath("/admin/rezerwacje");
  revalidatePath("/admin/kalendarz");

  redirect(`/admin/goscie/${guestId}`);
}