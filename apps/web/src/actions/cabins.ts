"use server";

import { prisma } from "@/lib/prisma";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export async function createCabin(formData: FormData) {
  await prisma.cabin.create({
    data: {
      name: formData.get("name") as string,
      description: formData.get("description") as string,
      maxGuests: Number(formData.get("maxGuests")),
      bedrooms: Number(formData.get("bedrooms")),
      bathrooms: Number(formData.get("bathrooms")),
      pricePerNight: Number(formData.get("pricePerNight")),
    },
  });

  revalidatePath("/admin/domki");
  redirect("/admin/domki");
}

export async function updateCabin(id: string, formData: FormData) {
  "use server";

  await prisma.cabin.update({
    where: {
      id,
    },
    data: {
      name: formData.get("name") as string,
      description: formData.get("description") as string,
      maxGuests: Number(formData.get("maxGuests")),
      pricePerNight: Number(formData.get("pricePerNight")),
    },
  });

  revalidatePath("/admin/domki");
  redirect("/admin/domki");
}
export async function deleteCabin(id: string) {
  "use server";

  await prisma.cabin.delete({
    where: { id },
  });

  revalidatePath("/admin/domki");
}
export async function addCabinImage(
  cabinId: string,
  formData: FormData
) {
  "use server";

  const url = formData.get("url") as string;

  if (!url) return;

  await prisma.cabinImage.create({
    data: {
      cabinId,
      url,
      isMain: true,
    },
  });

  revalidatePath("/admin/domki");
}