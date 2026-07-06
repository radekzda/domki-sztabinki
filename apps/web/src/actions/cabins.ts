"use server";

import crypto from "crypto";
import path from "path";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

function getString(formData: FormData, key: string, defaultValue = "") {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return defaultValue;
  }

  return value.trim();
}

function getNumber(formData: FormData, key: string, defaultValue: number) {
  const value = formData.get(key);

  if (typeof value !== "string" || value.trim() === "") {
    return defaultValue;
  }

  const parsedValue = Number(value);

  if (!Number.isFinite(parsedValue)) {
    return defaultValue;
  }

  return Math.max(0, Math.floor(parsedValue));
}

function getCabinFormData(formData: FormData) {
  return {
    name: getString(formData, "name"),
    description: getString(formData, "description"),

    maxGuests: getNumber(formData, "maxGuests", 6),
    bedrooms: getNumber(formData, "bedrooms", 2),
    bathrooms: getNumber(formData, "bathrooms", 1),

    pricePerNight: getNumber(formData, "pricePerNight", 450),
    priceOneNight: getNumber(formData, "priceOneNight", 800),
    priceTwoNights: getNumber(formData, "priceTwoNights", 450),
    priceThreeNights: getNumber(formData, "priceThreeNights", 440),
    priceFourNights: getNumber(formData, "priceFourNights", 430),
    priceFiveNights: getNumber(formData, "priceFiveNights", 420),
    priceSixNights: getNumber(formData, "priceSixNights", 410),
    priceSevenPlusNights: getNumber(formData, "priceSevenPlusNights", 350),

    shortName: getString(formData, "shortName") || null,
    sortOrder: getNumber(formData, "sortOrder", 0),
  };
}

export async function createCabin(formData: FormData) {
  await prisma.cabin.create({
    data: getCabinFormData(formData),
  });

  revalidatePath("/admin/domki");
  revalidatePath("/admin/kalendarz");

  redirect("/admin/domki");
}

export async function updateCabin(id: string, formData: FormData) {
  await prisma.cabin.update({
    where: {
      id,
    },
    data: getCabinFormData(formData),
  });

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${id}/edytuj`);
  revalidatePath("/admin/kalendarz");

  redirect("/admin/domki");
}

export async function deleteCabin(id: string) {
  await prisma.cabin.delete({
    where: {
      id,
    },
  });

  revalidatePath("/admin/domki");
  revalidatePath("/admin/kalendarz");
}

export async function toggleCabinStatus(id: string) {
  const cabin = await prisma.cabin.findUnique({
    where: {
      id,
    },
  });

  if (!cabin) {
    return;
  }

  await prisma.cabin.update({
    where: {
      id,
    },
    data: {
      isActive: !cabin.isActive,
    },
  });

  revalidatePath("/admin/domki");
  revalidatePath("/admin/kalendarz");
}

export async function normalizeCabinImageOrder(cabinId: string) {
  const images = await prisma.cabinImage.findMany({
    where: {
      cabinId,
    },
    orderBy: [
      {
        sortOrder: "asc",
      },
      {
        createdAt: "asc",
      },
    ],
  });

  await Promise.all(
    images.map((image, index) =>
      prisma.cabinImage.update({
        where: {
          id: image.id,
        },
        data: {
          sortOrder: index,
        },
      })
    )
  );

  revalidatePath(`/admin/domki/${cabinId}/zdjecia`);
}

export async function uploadCabinImage(cabinId: string, formData: FormData) {
  const file = formData.get("image") as File | null;

  if (!file || file.size === 0) {
    return;
  }

  const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

  if (!allowedTypes.includes(file.type)) {
    throw new Error("Dozwolone są tylko pliki JPG, PNG lub WEBP.");
  }

  const maxSize = 8 * 1024 * 1024;

  if (file.size > maxSize) {
    throw new Error("Plik jest za duży. Maksymalny rozmiar to 8 MB.");
  }

  await normalizeCabinImageOrder(cabinId);

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

  await fs.mkdir(uploadDir, {
    recursive: true,
  });

  await fs.writeFile(filePath, buffer);

  const imageUrl = `/uploads/cabins/${cabinId}/${fileName}`;

  const imageCount = await prisma.cabinImage.count({
    where: {
      cabinId,
    },
  });

  await prisma.cabinImage.create({
    data: {
      cabinId,
      url: imageUrl,
      isMain: imageCount === 0,
      sortOrder: imageCount,
    },
  });

  if (imageCount === 0) {
    await prisma.cabin.update({
      where: {
        id: cabinId,
      },
      data: {
        mainImageUrl: imageUrl,
      },
    });
  }

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${cabinId}/zdjecia`);
  revalidatePath("/admin/kalendarz");
}

export async function setMainCabinImage(imageId: string) {
  const image = await prisma.cabinImage.findUnique({
    where: {
      id: imageId,
    },
  });

  if (!image) {
    return;
  }

  await prisma.$transaction([
    prisma.cabinImage.updateMany({
      where: {
        cabinId: image.cabinId,
      },
      data: {
        isMain: false,
      },
    }),
    prisma.cabinImage.update({
      where: {
        id: imageId,
      },
      data: {
        isMain: true,
      },
    }),
    prisma.cabin.update({
      where: {
        id: image.cabinId,
      },
      data: {
        mainImageUrl: image.url,
      },
    }),
  ]);

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${image.cabinId}/zdjecia`);
  revalidatePath("/admin/kalendarz");
}

async function moveCabinImage(imageId: string, direction: "up" | "down") {
  const image = await prisma.cabinImage.findUnique({
    where: {
      id: imageId,
    },
  });

  if (!image) {
    return;
  }

  await normalizeCabinImageOrder(image.cabinId);

  const images = await prisma.cabinImage.findMany({
    where: {
      cabinId: image.cabinId,
    },
    orderBy: [
      {
        sortOrder: "asc",
      },
      {
        createdAt: "asc",
      },
    ],
  });

  const currentIndex = images.findIndex((item) => item.id === imageId);

  if (currentIndex === -1) {
    return;
  }

  const targetIndex = direction === "up" ? currentIndex - 1 : currentIndex + 1;

  if (targetIndex < 0 || targetIndex >= images.length) {
    return;
  }

  const currentImage = images[currentIndex];
  const targetImage = images[targetIndex];

  await prisma.$transaction([
    prisma.cabinImage.update({
      where: {
        id: currentImage.id,
      },
      data: {
        sortOrder: targetImage.sortOrder,
      },
    }),
    prisma.cabinImage.update({
      where: {
        id: targetImage.id,
      },
      data: {
        sortOrder: currentImage.sortOrder,
      },
    }),
  ]);

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${image.cabinId}/zdjecia`);
  revalidatePath("/admin/kalendarz");
}

export async function moveCabinImageUp(imageId: string) {
  await moveCabinImage(imageId, "up");
}

export async function moveCabinImageDown(imageId: string) {
  await moveCabinImage(imageId, "down");
}

export async function deleteCabinImage(imageId: string) {
  const image = await prisma.cabinImage.findUnique({
    where: {
      id: imageId,
    },
  });

  if (!image) {
    return;
  }

  await prisma.cabinImage.delete({
    where: {
      id: imageId,
    },
  });

  if (image.url.startsWith("/uploads/")) {
    const fs = await import("fs/promises");
    const filePath = path.join(process.cwd(), "public", image.url);

    await fs.unlink(filePath).catch(() => {});
  }

  const remainingImages = await prisma.cabinImage.findMany({
    where: {
      cabinId: image.cabinId,
    },
    orderBy: [
      {
        sortOrder: "asc",
      },
      {
        createdAt: "asc",
      },
    ],
  });

  if (image.isMain) {
    const nextMainImage = remainingImages[0];

    if (nextMainImage) {
      await prisma.$transaction([
        prisma.cabinImage.update({
          where: {
            id: nextMainImage.id,
          },
          data: {
            isMain: true,
          },
        }),
        prisma.cabin.update({
          where: {
            id: image.cabinId,
          },
          data: {
            mainImageUrl: nextMainImage.url,
          },
        }),
      ]);
    } else {
      await prisma.cabin.update({
        where: {
          id: image.cabinId,
        },
        data: {
          mainImageUrl: null,
        },
      });
    }
  }

  await normalizeCabinImageOrder(image.cabinId);

  revalidatePath("/admin/domki");
  revalidatePath(`/admin/domki/${image.cabinId}/zdjecia`);
  revalidatePath("/admin/kalendarz");
}