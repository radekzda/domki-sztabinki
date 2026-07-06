"use server";

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

function redirectWithEditGuestError(guestId: string, message: string): never {
  redirect(`/admin/goscie/${guestId}/edytuj?error=${encodeURIComponent(message)}`);
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

    const guestData = {
      firstName,
      lastName,
      email,
      phone: reservation.phone,
      country: reservation.country,
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
          data: guestData,
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
  const phone = getOptionalString(formData, "phone");
  const country = getOptionalString(formData, "country");

  if (!firstName) {
    redirectWithEditGuestError(guestId, "Podaj imię gościa.");
  }

  if (!lastName) {
    redirectWithEditGuestError(guestId, "Podaj nazwisko gościa.");
  }

  if (!email) {
    redirectWithEditGuestError(guestId, "Podaj email gościa.");
  }

  const guest = await prisma.guest.findUnique({
    where: {
      id: guestId,
    },
  });

  if (!guest) {
    redirect("/admin/goscie");
  }

  const guestName = `${firstName} ${lastName}`.trim();

  await prisma.$transaction([
    prisma.guest.update({
      where: {
        id: guestId,
      },
      data: {
        firstName,
        lastName,
        email,
        phone,
        country,
      },
    }),
    prisma.reservation.updateMany({
      where: {
        guestId,
      },
      data: {
        guestName,
        firstName,
        lastName,
        email,
        phone,
        country,
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