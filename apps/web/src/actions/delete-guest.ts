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

export async function deleteGuest(formData: FormData) {
  const guestId = getRequiredString(formData, "guestId");

  if (!guestId) {
    redirect("/admin/goscie");
  }

  const guest = await prisma.guest.findUnique({
    where: {
      id: guestId,
    },
    select: {
      id: true,
      _count: {
        select: {
          reservations: true,
        },
      },
    },
  });

  if (!guest) {
    redirect("/admin/goscie");
  }

  if (guest._count.reservations > 0) {
    redirect(
      `/admin/goscie/${guest.id}/usun?error=${encodeURIComponent(
        "Nie można usunąć gościa, który ma przypisane rezerwacje. Najpierw usuń jego rezerwacje albo zostaw gościa w bazie.",
      )}`,
    );
  }

  await prisma.guest.delete({
    where: {
      id: guest.id,
    },
  });

  revalidatePath("/admin/goscie");
  revalidatePath("/admin/rezerwacje");
  revalidatePath("/admin/kalendarz");

  redirect("/admin/goscie");
}