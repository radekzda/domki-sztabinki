"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";

function getTextValue(
  formData: FormData,
  key: string,
  fallback: string,
) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return fallback;
  }

  const trimmedValue = value.trim();

  if (!trimmedValue) {
    return fallback;
  }

  return trimmedValue;
}

function getOptionalTextValue(formData: FormData, key: string) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return null;
  }

  const trimmedValue = value.trim();

  if (!trimmedValue) {
    return null;
  }

  return trimmedValue;
}

function getIntegerValue(
  formData: FormData,
  key: string,
  fallback: number,
  min: number,
  max: number,
) {
  const value = formData.get(key);

  if (typeof value !== "string") {
    return fallback;
  }

  const parsedValue = Number.parseInt(value, 10);

  if (Number.isNaN(parsedValue)) {
    return fallback;
  }

  if (parsedValue < min) {
    return min;
  }

  if (parsedValue > max) {
    return max;
  }

  return parsedValue;
}

export async function updateSystemSettings(formData: FormData) {
  const propertyName = getTextValue(
    formData,
    "propertyName",
    "Domki Sztabinki",
  );

  const ownerName = getOptionalTextValue(formData, "ownerName");
  const ownerEmail = getOptionalTextValue(formData, "ownerEmail");
  const ownerPhone = getOptionalTextValue(formData, "ownerPhone");

  const contactEmail = getOptionalTextValue(formData, "contactEmail");
  const contactPhone = getOptionalTextValue(formData, "contactPhone");

  const propertyStreet = getOptionalTextValue(formData, "propertyStreet");
  const propertyPostalCode = getOptionalTextValue(
    formData,
    "propertyPostalCode",
  );
  const propertyCity = getOptionalTextValue(formData, "propertyCity");
  const propertyCountry = getTextValue(
    formData,
    "propertyCountry",
    "Polska",
  );

  const checkInTime = getTextValue(formData, "checkInTime", "15:00");
  const checkOutTime = getTextValue(formData, "checkOutTime", "11:00");

  const minimumNights = getIntegerValue(
    formData,
    "minimumNights",
    4,
    1,
    365,
  );

  const seasonStartMonth = getIntegerValue(
    formData,
    "seasonStartMonth",
    5,
    1,
    12,
  );

  const seasonEndMonth = getIntegerValue(
    formData,
    "seasonEndMonth",
    9,
    1,
    12,
  );

  const websiteUrl = getOptionalTextValue(formData, "websiteUrl");

  await prisma.systemSettings.upsert({
    where: {
      id: "main",
    },
    create: {
      id: "main",
      propertyName,
      ownerName,
      ownerEmail,
      ownerPhone,
      contactEmail,
      contactPhone,
      propertyStreet,
      propertyPostalCode,
      propertyCity,
      propertyCountry,
      checkInTime,
      checkOutTime,
      minimumNights,
      seasonStartMonth,
      seasonEndMonth,
      websiteUrl,
    },
    update: {
      propertyName,
      ownerName,
      ownerEmail,
      ownerPhone,
      contactEmail,
      contactPhone,
      propertyStreet,
      propertyPostalCode,
      propertyCity,
      propertyCountry,
      checkInTime,
      checkOutTime,
      minimumNights,
      seasonStartMonth,
      seasonEndMonth,
      websiteUrl,
    },
  });

  revalidatePath("/admin");
  revalidatePath("/admin/ustawienia");

  redirect("/admin/ustawienia?saved=1");
}