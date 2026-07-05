"use server";

import crypto from "crypto";
import { prisma } from "@/lib/prisma";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import path from "path";

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
  await prisma.cabin.update({
    where: { id },
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
  await prisma.cabin.delete({
    where: { id },
  });

  revalidatePath("/admin/domki");
}

export async function toggleCabinStatus(id: string) {
  const cabin = await prisma.cabin.findUnique({
    where: { id },
  });

  if (!cabin) return;

  await prisma.cabin.update({
    where: { id },
    data: {
      isActive: !cabin.isActive,
    },
  });

  revalidatePath("/admin/domki");
}

export async function uploadCabinImage(
  cabinId: string,
  formData: FormData
) {
  const file = formData.get("image") as File | null;

  if (!file || file.size === 0) return;

  const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

  if (!allowedTypes.includes(file.type)) {
    throw new Error("Dozwolone są tylko pliki JPG, PNG lub WEBP.");
  }

  const maxSize = 8 * 1024 * 1024;

  if (file.size > maxSize) {
    throw new Error("Plik jest za duży. Maksymalny rozmiar to 8 MB.");
  }

  const extension = file.name.split(".").pop()?.toLowerCase() || "jpg";

  const safeName = file.name
    .replace(/\.[^/.]+$/, "")
    .toLowerCase()
    .replace(/ą/g, "a")
    .replace(/ć/g, "c")
    .replace(/ę/g, "e")
    .replace(/ł/g, "l")
    .replace(/ń/g, "n")
    .replace(/ó/g, "o")
    .replace(/ś/g, "s")
    .replace(/ź/g, "z")
    .replace(/ż/g, "z")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");

  const fileName = `${safeName || "zdjecie"}-${crypto
    .randomUUID()
    .slice(0, 8)}.${extension}`;

  const bytes = await file.arrayBuffer();
  const buffer = Buffer.from(bytes);

  const fs = await import("fs/promises");

  const uploadDir = path.join(
    process.cwd(),
    "public",
    "uploads",
    "cabins",
    cabinId
  );

  const filePath = path.join(uploadDir, fileName);

  await fs.mkdir(uploadDir, { recursive: true });
  await fs.writeFile(filePath, buffer);

  const imageUrl = `/uploads/cabins/${cabinId}/${fileName}`;

  const imageCount = await prisma.cabinImage.count({
    where: { cabinId },
  });

  await prisma.cabinImage.create({
    data: {
      cabinId,
      url: imageUrl,
      isMain: imageCount === 0,
    },
  });

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${cabinId}/zdjecia`);
}

export async function setMainCabinImage(imageId: string) {
  const image = await prisma.cabinImage.findUnique({
    where: { id: imageId },
  });

  if (!image) return;

  await prisma.$transaction([
    prisma.cabinImage.updateMany({
      where: { cabinId: image.cabinId },
      data: { isMain: false },
    }),
    prisma.cabinImage.update({
      where: { id: imageId },
      data: { isMain: true },
    }),
  ]);

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${image.cabinId}/zdjecia`);
}

export async function deleteCabinImage(imageId: string) {
  const image = await prisma.cabinImage.findUnique({
    where: { id: imageId },
  });

  if (!image) return;

  await prisma.cabinImage.delete({
    where: { id: imageId },
  });

  if (image.url.startsWith("/uploads/")) {
    const fs = await import("fs/promises");
    const filePath = path.join(process.cwd(), "public", image.url);

    await fs.unlink(filePath).catch(() => {});
  }

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${image.cabinId}/zdjecia`);
}