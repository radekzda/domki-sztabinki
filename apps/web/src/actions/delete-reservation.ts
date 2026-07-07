"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

import { prisma } from "@/lib/prisma";

function getRequiredString(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return "";
  }

  return value.trim();
}

export async function deleteReservation(formData: FormData) {
  const reservationId = getRequiredString(formData, "reservationId");

  if (!reservationId) {
    redirect("/admin/rezerwacje");
  }

  const reservation = await prisma.reservation.findUnique({
    where: {
      id: reservationId,
    },
    select: {
      id: true,
      guestId: true,
    },
  });

  if (!reservation) {
    redirect("/admin/rezerwacje");
  }

  await prisma.reservation.delete({
    where: {
      id: reservation.id,
    },
  });

  revalidatePath("/admin/rezerwacje");
  revalidatePath("/admin/kalendarz");
  revalidatePath("/admin/goscie");

  if (reservation.guestId) {
    revalidatePath(`/admin/goscie/${reservation.guestId}`);
  }

  redirect("/admin/rezerwacje");
}